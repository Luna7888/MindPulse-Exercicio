<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ CHECKLIST.PHP — Funções de Gerenciamento de Checklists                   ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Fornecer funções para manipulação de checklists,          ║
 * ║                tarefas, marcações e cálculo de pendências/atrasos        ║
 * ║                                                                           ║
 * ║ @acesso        Interno (incluído por páginas de checklist)               ║
 * ║ @escopo        Por empresa e por cargo (role-based access)               ║
 * ║                                                                           ║
 * ║ @conceito_períodos                                                       ║
 * ║   Checklists têm frequências: daily, weekly, biweekly, monthly           ║
 * ║   Cada período gera uma "chave" única (period_key):                      ║
 * ║   - daily: 2025-10-15                                                    ║
 * ║   - weekly: 2025-W42                                                     ║
 * ║   - biweekly: 2025-B21                                                   ║
 * ║   - monthly: 2025-10                                                     ║
 * ║                                                                           ║
 * ║ @funções       period_key_for() - Chave do período atual                 ║
 * ║                period_key_prev() - Chave do período anterior             ║
 * ║                dueWindowLate() - Se passou o prazo                       ║
 * ║                checklistsForUser() - Checklists acessíveis               ║
 * ║                checklistTasks() - Tarefas de um checklist                ║
 * ║                isTaskDone() - Se tarefa foi concluída                    ║
 * ║                isTaskPendingPrev() - Se ficou pendente no período ant.   ║
 * ║                markTask() - Marcar tarefa como concluída                 ║
 * ║                unmarkTask() - Desmarcar tarefa                           ║
 * ║                totalsForUser() - Totais para dashboard                   ║
 * ║                                                                           ║
 * ║ @dependências  db.php (conexão PDO), auth.php (sessão)                   ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: DEPENDÊNCIAS
// ═══════════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE PERÍODO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * period_key_for() — Gera a chave do período atual para uma frequência
 * 
 * @param string $freq Frequência: 'daily', 'weekly', 'biweekly', 'monthly'
 * @param DateTime|null $when Data de referência (padrão: agora)
 * @return string Chave do período
 * 
 * @formatos_de_chave
 * - daily: '2025-10-15' (ano-mês-dia)
 * - weekly: '2025-W42' (ano-semana ISO)
 * - biweekly: '2025-B21' (ano-quinzena)
 * - monthly: '2025-10' (ano-mês)
 * 
 * @uso
 * A chave é usada para identificar marcações de tarefas em cada período
 * Permite saber se a tarefa foi feita "hoje", "esta semana", etc.
 */
function period_key_for(string $freq, ?DateTime $when = null): string {
    $when = $when ?: new DateTime('now');
    
    switch ($freq) {
        case 'weekly':   
            // Formato ISO: ano-W + número da semana
            return $when->format('o-\WW');  // Ex: 2025-W42
            
        case 'biweekly': 
            // Quinzena: divide número da semana por 2
            $w = (int)$when->format('W'); 
            return sprintf('%s-B%02d', $when->format('o'), (int)ceil($w/2));  // Ex: 2025-B21
            
        case 'monthly':  
            return $when->format('Y-m');  // Ex: 2025-10
            
        default:  // daily
            return $when->format('Y-m-d');  // Ex: 2025-10-15
    }
}

/**
 * period_key_prev() — Gera a chave do período ANTERIOR
 * 
 * @param string $freq Frequência
 * @param DateTime|null $when Data de referência
 * @return string Chave do período anterior
 * 
 * @uso
 * Usado para verificar se há tarefas pendentes do período passado
 * Tarefas não feitas no período anterior contam como "atraso"
 */
function period_key_prev(string $freq, ?DateTime $when = null): string {
    $when = $when ?: new DateTime('now');
    
    switch ($freq) {
        case 'weekly':   
            $when->modify('-1 week'); 
            break;
        case 'biweekly': 
            $when->modify('-2 weeks'); 
            break;
        case 'monthly':  
            $when->modify('first day of previous month'); 
            break;
        default:  // daily
            $when->modify('-1 day');
    }
    
    return period_key_for($freq, $when);
}

/**
 * dueWindowLate() — Verifica se já passou o prazo do período atual
 * 
 * @param string $freq Frequência
 * @param DateTime|null $now Momento atual
 * @return bool true se passou do prazo
 * 
 * @lógica
 * - daily: passa às 23:59:59 do dia
 * - weekly: passa no domingo às 23:59:59
 * - biweekly: passa no domingo às 23:59:59 (da quinzena)
 * - monthly: passa no último dia do mês às 23:59:59
 */
function dueWindowLate(string $freq, ?DateTime $now = null): bool {
    $now = $now ?: new DateTime('now');
    
    switch ($freq) {
        case 'weekly':   
            $end = (clone $now)->modify('sunday this week 23:59:59'); 
            break;
        case 'biweekly': 
            $end = (clone $now)->modify('sunday this week 23:59:59'); 
            break;
        case 'monthly':  
            $end = (clone $now)->modify('last day of this month 23:59:59'); 
            break;
        default:  // daily
            $end = (clone $now)->setTime(23, 59, 59);
    }
    
    return $now > $end;
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE LISTAGEM
// ═══════════════════════════════════════════════════════════════════════════

/**
 * checklistsForUser() — Lista checklists acessíveis ao usuário
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $userId ID do usuário
 * @param int $companyId ID da empresa
 * @return array Lista de checklists
 * 
 * @lógica
 * Retorna checklists que:
 * 1. Pertencem à empresa atual
 * 2. Estão ativos
 * 3. Estão vinculados a pelo menos um cargo do usuário
 * 
 * @ordenação
 * Por frequência (daily, weekly, biweekly, monthly) e depois por título
 */
function checklistsForUser(PDO $pdo, int $userId, int $companyId): array {
    $sql = "SELECT DISTINCT c.*
            FROM checklists c
            JOIN checklist_role cr ON cr.checklist_id = c.id
            JOIN user_role ur ON ur.role_id = cr.role_id AND ur.user_id = ?
            WHERE c.company_id = ? AND c.is_active = 1
            ORDER BY FIELD(c.frequency, 'daily', 'weekly', 'biweekly', 'monthly'), c.title";
    
    $st = $pdo->prepare($sql);
    $st->execute([$userId, $companyId]);
    return $st->fetchAll() ?: [];
}

/**
 * checklistTasks() — Lista as tarefas ativas de um checklist
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $checklistId ID do checklist
 * @return array Lista de tarefas ordenadas por prioridade
 */
function checklistTasks(PDO $pdo, int $checklistId): array {
    $st = $pdo->prepare("
        SELECT * FROM checklist_tasks 
        WHERE checklist_id = ? AND is_active = 1 
        ORDER BY priority ASC, id ASC
    ");
    $st->execute([$checklistId]);
    return $st->fetchAll() ?: [];
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE VERIFICAÇÃO DE STATUS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * isTaskDone() — Verifica se uma tarefa foi concluída em um período
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $taskId ID da tarefa
 * @param int $companyId ID da empresa
 * @param string $periodKey Chave do período
 * @return array|null Dados da conclusão ou null se não concluída
 * 
 * @retorno
 * - id: ID do registro de conclusão
 * - completed_at: timestamp da conclusão
 * - was_late: se foi marcada com atraso
 */
function isTaskDone(PDO $pdo, int $taskId, int $companyId, string $periodKey): ?array {
    $st = $pdo->prepare("
        SELECT id, completed_at, was_late 
        FROM checklist_task_done 
        WHERE task_id = ? AND company_id = ? AND period_key = ? 
        LIMIT 1
    ");
    $st->execute([$taskId, $companyId, $periodKey]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * isTaskPendingPrev() — Verifica se tarefa ficou pendente no período anterior
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $taskId ID da tarefa
 * @param int $companyId ID da empresa
 * @param string $freq Frequência do checklist
 * @return bool true se não foi feita no período anterior
 * 
 * @uso
 * Tarefas pendentes do período anterior são consideradas "em atraso"
 * e aparecem destacadas no dashboard
 */
function isTaskPendingPrev(PDO $pdo, int $taskId, int $companyId, string $freq): bool {
    $prev = period_key_prev($freq);
    $st = $pdo->prepare("
        SELECT 1 FROM checklist_task_done 
        WHERE task_id = ? AND company_id = ? AND period_key = ? 
        LIMIT 1
    ");
    $st->execute([$taskId, $companyId, $prev]);
    return !$st->fetchColumn();
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE MARCAÇÃO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * markTask() — Marca uma tarefa como concluída
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $checklistId ID do checklist
 * @param int $taskId ID da tarefa
 * @param int $userId ID do usuário que está marcando
 * @param int $companyId ID da empresa
 * @param string $periodKey Chave do período
 * @param bool $late Se está sendo marcada com atraso
 * 
 * @idempotência
 * Usa ON DUPLICATE KEY UPDATE para ser seguro chamar múltiplas vezes
 * Se já existe registro, atualiza o timestamp e quem marcou
 */
function markTask(PDO $pdo, int $checklistId, int $taskId, int $userId, int $companyId, string $periodKey, bool $late = false): void {
    $sql = "INSERT INTO checklist_task_done 
            (checklist_id, task_id, user_id, company_id, period_key, was_late)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                completed_at = NOW(), 
                user_id = VALUES(user_id), 
                was_late = VALUES(was_late)";
    
    $st = $pdo->prepare($sql);
    $st->execute([$checklistId, $taskId, $userId, $companyId, $periodKey, $late ? 1 : 0]);
}

/**
 * unmarkTask() — Remove a marcação de uma tarefa
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $taskId ID da tarefa
 * @param int $companyId ID da empresa
 * @param string $periodKey Chave do período
 * 
 * @uso
 * Permite que o usuário "desfaça" uma marcação feita por engano
 */
function unmarkTask(PDO $pdo, int $taskId, int $companyId, string $periodKey): void {
    $st = $pdo->prepare("
        DELETE FROM checklist_task_done 
        WHERE task_id = ? AND company_id = ? AND period_key = ?
    ");
    $st->execute([$taskId, $companyId, $periodKey]);
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE TOTALIZAÇÃO (DASHBOARD)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * totalsForUser() — Calcula totais de tarefas para o dashboard
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $userId ID do usuário
 * @param int $companyId ID da empresa
 * @return array ['overdue' => int, 'today' => int, 'week' => int, 'month' => int]
 * 
 * @categorias
 * - overdue: tarefas em atraso (período anterior + marcadas como late)
 * - today: tarefas diárias pendentes
 * - week: tarefas semanais/quinzenais pendentes
 * - month: tarefas mensais pendentes
 */
function totalsForUser(PDO $pdo, int $userId, int $companyId): array {
    $lists = checklistsForUser($pdo, $userId, $companyId);
    $tot = ['overdue' => 0, 'today' => 0, 'week' => 0, 'month' => 0];

    foreach ($lists as $cl) {
        $freq  = $cl['frequency'];
        $pkey  = period_key_for($freq);
        $tasks = checklistTasks($pdo, (int)$cl['id']);

        foreach ($tasks as $t) {
            $done = isTaskDone($pdo, (int)$t['id'], $companyId, $pkey);
            
            if (!$done) {
                // Tarefa não feita no período atual
                if ($freq === 'daily') $tot['today']++;
                if ($freq === 'weekly' || $freq === 'biweekly') $tot['week']++;
                if ($freq === 'monthly') $tot['month']++;
            } else if (!empty($done['was_late'])) {
                // Tarefa foi feita, mas com atraso
                $tot['overdue']++;
            }
            
            // Verifica se ficou pendente no período anterior
            if (isTaskPendingPrev($pdo, (int)$t['id'], $companyId, $freq)) {
                $tot['overdue']++;
            }
        }
    }
    
    return $tot;
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES AUXILIARES DE UI
// ═══════════════════════════════════════════════════════════════════════════

/**
 * checklistPendingNow() — Conta tarefas pendentes de um checklist no período atual
 * 
 * @param PDO $pdo Conexão
 * @param array $cl Dados do checklist
 * @param int $companyId ID da empresa
 * @return int Número de tarefas pendentes
 */
function checklistPendingNow(PDO $pdo, array $cl, int $companyId): int {
    $tasks = checklistTasks($pdo, (int)$cl['id']); 
    $pkey = period_key_for($cl['frequency']);
    $pend = 0; 
    
    foreach ($tasks as $t) { 
        if (!isTaskDone($pdo, (int)$t['id'], $companyId, $pkey)) {
            $pend++; 
        }
    }
    
    return $pend;
}

/**
 * checklistHasPrevOverdue() — Verifica se checklist tem tarefas atrasadas
 * 
 * @param PDO $pdo Conexão
 * @param array $cl Dados do checklist
 * @param int $companyId ID da empresa
 * @return bool true se há tarefas do período anterior não feitas
 */
function checklistHasPrevOverdue(PDO $pdo, array $cl, int $companyId): bool {
    foreach (checklistTasks($pdo, (int)$cl['id']) as $t) {
        if (isTaskPendingPrev($pdo, (int)$t['id'], $companyId, $cl['frequency'])) {
            return true;
        }
    }
    return false;
}

/**
 * period_label() — Converte código de período em label legível
 * 
 * @param string $p Código do período
 * @return string Label em português
 */
function period_label($p) { 
    return [
        'inicio_dia' => 'Início do dia',
        'final_dia' => 'Até o final do dia',
        'inicio_semana' => 'Início da semana',
        'final_semana' => 'Até o final da semana'
    ][$p] ?? $p; 
}
