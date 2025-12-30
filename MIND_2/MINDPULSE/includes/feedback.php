<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ FEEDBACK.PHP — Funções de Gerenciamento de Feedback/Chamados             ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Fornecer funções para o canal de comunicação              ║
 * ║                colaborador → gestor (feedbacks, sugestões, ocorrências)  ║
 * ║                                                                           ║
 * ║ @acesso        Interno (incluído por páginas de feedback)                ║
 * ║ @escopo        Por empresa (company_id)                                  ║
 * ║                                                                           ║
 * ║ @conceito_sentimentos                                                    ║
 * ║   Cada feedback tem um "sentimento" associado (gamificação):             ║
 * ║   - 🚀 Excelente (score 5) - "No topo!"                                  ║
 * ║   - 🙂 Bem (score 4) - "Mandando bem"                                    ║
 * ║   - 😐 Ok (score 3) - "Tudo ok"                                          ║
 * ║   - 😓 Sobrecarregado (score 2) - "Correria"                             ║
 * ║   - 😣 Estressado (score 1) - "Precisando de apoio"                      ║
 * ║                                                                           ║
 * ║ @funções       fb_sentiments() - Lista de sentimentos                    ║
 * ║                fb_categories() - Lista de categorias                     ║
 * ║                fb_status_badge() - Badge HTML para status                ║
 * ║                fb_create() - Criar novo ticket                           ║
 * ║                fb_my_tickets() - Tickets do usuário                      ║
 * ║                fb_list_admin() - Todos tickets (admin)                   ║
 * ║                fb_update_status() - Atualizar status                     ║
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
// SEÇÃO: DEFINIÇÕES DE DADOS (SENTIMENTOS E CATEGORIAS)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * fb_sentiments() — Retorna lista de sentimentos disponíveis
 * 
 * @return array Lista de sentimentos com score, key, emoji e title
 * 
 * @gamificação
 * Os sentimentos permitem capturar o estado emocional do colaborador
 * Isso ajuda gestores a identificar problemas antes que escalonem
 * 
 * @ordenação
 * Do mais positivo (5) ao mais negativo (1)
 */
function fb_sentiments(): array {
    return [
        ['score' => 5, 'key' => 'excelente',      'emoji' => '🚀', 'title' => 'No topo!'],
        ['score' => 4, 'key' => 'bem',            'emoji' => '🙂', 'title' => 'Mandando bem'],
        ['score' => 3, 'key' => 'ok',             'emoji' => '😐', 'title' => 'Tudo ok'],
        ['score' => 2, 'key' => 'sobrecarregado', 'emoji' => '😓', 'title' => 'Correria'],
        ['score' => 1, 'key' => 'estressado',     'emoji' => '😣', 'title' => 'Precisando de apoio'],
    ];
}

/**
 * fb_categories() — Retorna lista de categorias de feedback
 * 
 * @return array Mapa key => label
 * 
 * @categorias
 * - melhoria_processo: sugestões de como melhorar processos
 * - suporte_operacional: pedidos de ajuda com tarefas
 * - ocorrencia: relato de incidentes ou problemas
 * - feedback_geral: feedback construtivo geral
 * - reconhecimento: elogios a colegas ou equipes
 * - infra_recursos: problemas com equipamentos, sistemas, etc.
 */
function fb_categories(): array {
    return [
        'melhoria_processo'   => 'Sugestão de melhoria',
        'suporte_operacional' => 'Preciso de ajuda',
        'ocorrencia'          => 'Ocorrência / incidente',
        'feedback_geral'      => 'Feedback construtivo',
        'reconhecimento'      => 'Reconhecimento / elogio',
        'infra_recursos'      => 'Infraestrutura / recursos',
    ];
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE UI
// ═══════════════════════════════════════════════════════════════════════════

/**
 * fb_status_badge() — Gera HTML de badge para status do ticket
 * 
 * @param string $st Status: 'aberto', 'em_andamento', 'concluido'
 * @return string HTML do badge com cor apropriada
 * 
 * @cores
 * - aberto: vermelho (#ff4d4f) - requer atenção
 * - em_andamento: amarelo (#ffd666) - em processo
 * - concluido: verde (#36cfc9) - resolvido
 */
function fb_status_badge(string $st): string {
    $map = [
        'aberto'       => 'background:#ff4d4f;color:#0f1117',
        'em_andamento' => 'background:#ffd666;color:#0f1117',
        'concluido'    => 'background:#36cfc9;color:#0f1117',
    ];
    
    $style = $map[$st] ?? 'background:#9aa4b2;color:#0f1117';
    $label = ucwords(str_replace('_', ' ', $st));  // 'em_andamento' → 'Em Andamento'
    
    return "<span class=\"badge\" style=\"$style\">$label</span>";
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE CRUD
// ═══════════════════════════════════════════════════════════════════════════

/**
 * fb_create() — Cria um novo ticket de feedback
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $companyId ID da empresa
 * @param int $userId ID do usuário que está criando
 * @param array $data Dados do ticket (sentiment_key, sentiment_score, category, message)
 * @return int ID do ticket criado
 * 
 * @status_inicial
 * Todos os tickets começam com status 'aberto'
 */
function fb_create(PDO $pdo, int $companyId, int $userId, array $data): int {
    $sql = "INSERT INTO feedback_tickets
            (company_id, user_id, sentiment_key, sentiment_score, category, message, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 'aberto', NOW(), NOW())";
    
    $st = $pdo->prepare($sql);
    $st->execute([
        $companyId,
        $userId,
        $data['sentiment_key'],
        $data['sentiment_score'],
        $data['category'], 
        $data['message']
    ]);
    
    return (int)$pdo->lastInsertId();
}

/**
 * fb_my_tickets() — Lista tickets do usuário logado
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $companyId ID da empresa
 * @param int $userId ID do usuário
 * @return array Lista de tickets ordenados por data (mais recentes primeiro)
 */
function fb_my_tickets(PDO $pdo, int $companyId, int $userId): array {
    $st = $pdo->prepare("
        SELECT * FROM feedback_tickets 
        WHERE company_id = ? AND user_id = ? 
        ORDER BY created_at DESC
    ");
    $st->execute([$companyId, $userId]);
    return $st->fetchAll() ?: [];
}

/**
 * fb_list_admin() — Lista todos os tickets da empresa (visão admin)
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $companyId ID da empresa
 * @return array Lista de tickets com dados do usuário que criou
 * 
 * @ordenação
 * 1. Por status (aberto primeiro, depois em_andamento, depois concluido)
 * 2. Por data de criação (mais recentes primeiro dentro de cada status)
 * 
 * @campos_extras
 * Inclui user_name e avatar_url do criador do ticket
 */
function fb_list_admin(PDO $pdo, int $companyId): array {
    $st = $pdo->prepare("
        SELECT t.*, u.name AS user_name, u.avatar_url
        FROM feedback_tickets t
        JOIN users u ON u.id = t.user_id
        WHERE t.company_id = ?
        ORDER BY FIELD(t.status, 'aberto', 'em_andamento', 'concluido'), t.created_at DESC
    ");
    $st->execute([$companyId]);
    return $st->fetchAll() ?: [];
}

/**
 * fb_update_status() — Atualiza o status de um ticket
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $id ID do ticket
 * @param int $companyId ID da empresa (para validação de escopo)
 * @param string $status Novo status: 'aberto', 'em_andamento', 'concluido'
 * 
 * @throws Exception Se status não for válido
 * 
 * @segurança
 * Valida que o ticket pertence à empresa (previne acesso cross-company)
 * Valida que o status é um dos valores permitidos
 */
function fb_update_status(PDO $pdo, int $id, int $companyId, string $status): void {
    // Lista de status permitidos
    $allowed = ['aberto', 'em_andamento', 'concluido'];
    
    // Valida o status
    if (!in_array($status, $allowed, true)) {
        throw new Exception('Status inválido');
    }
    
    // Atualiza o status e o timestamp de atualização
    $st = $pdo->prepare("
        UPDATE feedback_tickets 
        SET status = ?, updated_at = NOW() 
        WHERE id = ? AND company_id = ?
    ");
    $st->execute([$status, $id, $companyId]);
}
