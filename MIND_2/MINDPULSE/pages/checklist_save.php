<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ CHECKLIST_SAVE.PHP — Processamento de Criação de Checklist               ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Processar o formulário de criação de checklist            ║
 * ║                e salvar no banco de dados                                ║
 * ║                                                                           ║
 * ║ @acesso        Admin Geral | Gestor (canAccessAdmin)                     ║
 * ║ @método        POST (formulário)                                         ║
 * ║ @escopo        Por empresa (company_id)                                  ║
 * ║                                                                           ║
 * ║ @parâmetros    POST:                                                     ║
 * ║                - title: título do checklist (obrigatório)                ║
 * ║                - description: descrição                                  ║
 * ║                - frequency: daily|weekly|biweekly|monthly                ║
 * ║                - default_role_id: cargo padrão                           ║
 * ║                - roles[]: array de IDs de cargos com acesso              ║
 * ║                - tasks[]: array de tarefas com name, period, priority    ║
 * ║                                                                           ║
 * ║ @transação     Usa beginTransaction/commit/rollBack para garantir        ║
 * ║                integridade dos dados                                     ║
 * ║                                                                           ║
 * ║ @redireciona   checklists.php (sucesso) ou checklist_new.php (erro)      ║
 * ║                                                                           ║
 * ║ @dependências  db.php, auth.php                                          ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INICIALIZAÇÃO E VERIFICAÇÕES
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Inicia sessão se necessário
 */
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Inclui dependências
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

/**
 * Exige autenticação e permissão administrativa
 */
requireLogin(); 
if (!canAccessManager()) { 
    http_response_code(403); 
    exit('Acesso negado'); 
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: EXTRAÇÃO E VALIDAÇÃO DE DADOS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Extrai dados do formulário
 */
$title   = trim($_POST['title'] ?? '');
$desc    = trim($_POST['description'] ?? '');
$freq    = $_POST['frequency'] ?? 'daily';
$defRole = (int)($_POST['default_role_id'] ?? 0);
$roles   = $_POST['roles'] ?? [];
$tasks   = $_POST['tasks'] ?? [];

/**
 * Dados de contexto
 */
$companyId = currentCompanyId();
$userId    = (int)$_SESSION['user']['id'];

/**
 * Validação básica: título é obrigatório
 */
if ($title === '' || !$companyId) { 
    header('Location: ' . url_for('/pages/checklist_new.php')); 
    exit; 
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: PERSISTÊNCIA NO BANCO DE DADOS
// ═══════════════════════════════════════════════════════════════════════════

try {
    /**
     * Inicia transação para garantir atomicidade
     * Se qualquer operação falhar, todas são revertidas
     */
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
    }

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 1: Insere o checklist principal
    // ─────────────────────────────────────────────────────────────────────
    $st = $pdo->prepare("
        INSERT INTO checklists (company_id, title, description, frequency, default_role_id, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $st->execute([
        $companyId, 
        $title, 
        $desc, 
        $freq, 
        $defRole ?: null,  // null se não selecionou cargo padrão
        $userId
    ]);
    
    /**
     * Obtém o ID do checklist recém-criado
     */
    $clId = (int)$pdo->lastInsertId();

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 2: Insere relações checklist-cargo (permissões)
    // ─────────────────────────────────────────────────────────────────────
    if (!empty($roles)) {
        $ins = $pdo->prepare("INSERT INTO checklist_role (checklist_id, role_id) VALUES (?, ?)");
        foreach ($roles as $rid) { 
            $rid = (int)$rid; 
            if ($rid > 0) {
                $ins->execute([$clId, $rid]); 
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 3: Insere as tarefas do checklist
    // ─────────────────────────────────────────────────────────────────────
    if (!empty($tasks)) {
        $insT = $pdo->prepare("
            INSERT INTO checklist_tasks (checklist_id, priority, name, period, notes) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($tasks as $t) {
            $name = trim($t['name'] ?? ''); 
            
            // Ignora tarefas sem nome
            if ($name === '') continue;
            
            // Prioridade: mínimo 1, máximo 5, padrão 3
            $pri = max(1, min(5, (int)($t['priority'] ?? 3)));
            
            // Período padrão: final do dia
            $per = $t['period'] ?? 'final_dia';
            
            // Observações: null se vazio
            $notes = trim($t['notes'] ?? '') ?: null;
            
            $insT->execute([$clId, $pri, $name, $per, $notes]);
        }
    }

    /**
     * Confirma a transação
     */
    $pdo->commit();
    
    /**
     * Redireciona para a lista de checklists
     */
    header('Location: ' . url_for('/pages/checklists.php')); 
    exit;

} catch (Throwable $e) {
    /**
     * Em caso de erro, reverte a transação
     */
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    /**
     * Retorna erro 500 com mensagem
     */
    http_response_code(500);
    echo "Erro ao salvar: " . $e->getMessage();
}
