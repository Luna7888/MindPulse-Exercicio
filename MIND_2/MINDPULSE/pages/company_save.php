<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ COMPANY_SAVE.PHP — API para Criar Nova Empresa                           ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Criar nova empresa e vincular ao usuário logado           ║
 * ║                                                                           ║
 * ║ @acesso        Admin Geral | Gestor (canAccessAdmin)                     ║
 * ║ @método        POST (JSON)                                               ║
 * ║                                                                           ║
 * ║ @parâmetros    JSON body:                                                ║
 * ║                - name: nome jurídico/razão social (obrigatório)          ║
 * ║                - trade_name: nome fantasia (obrigatório)                 ║
 * ║                - document: CNPJ (obrigatório)                            ║
 * ║                - logo_url: URL do logo                                   ║
 * ║                - is_active: boolean (ativa/inativa)                      ║
 * ║                                                                           ║
 * ║ @retorno       JSON: {status: 'ok', company_id: N} ou erro               ║
 * ║                                                                           ║
 * ║ @efeitos       - Cria registro em companies                              ║
 * ║                - Vincula usuário à empresa (user_company)                ║
 * ║                - Atualiza sessão com nova lista de empresas              ║
 * ║                - Define nova empresa como current_company                ║
 * ║                                                                           ║
 * ║ @dependências  db.php, auth.php                                          ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INICIALIZAÇÃO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Inicia sessão se necessário
 */
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Inicia buffer de saída e define Content-Type
 */
ob_start(); 
header('Content-Type: application/json; charset=utf-8');

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: DEPENDÊNCIAS E VERIFICAÇÕES
// ═══════════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

/**
 * Exige autenticação e permissão administrativa
 */
requireLogin(); 
if (!canAccessAdmin()) { 
    http_response_code(403); 
    echo json_encode(['status' => 'error', 'message' => 'Acesso negado']); 
    exit; 
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: PROCESSAMENTO DA REQUISIÇÃO
// ═══════════════════════════════════════════════════════════════════════════

try {
    /**
     * Lê e decodifica o corpo da requisição JSON
     */
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    
    /**
     * Extrai e sanitiza parâmetros
     */
    $name       = trim($in['name'] ?? '');
    $trade_name = trim($in['trade_name'] ?? '');
    $document   = trim($in['document'] ?? '');
    $logo_url   = trim($in['logo_url'] ?? '');
    $is_active  = !empty($in['is_active']) ? 1 : 0;

    /**
     * Validações obrigatórias
     */
    if ($name === '') {
        throw new Exception('Nome da empresa é obrigatório.');
    }
    if ($trade_name === '') {
        throw new Exception('Nome fantasia é obrigatório.');
    }
    if ($document === '') {
        throw new Exception('CNPJ é obrigatório.');
    }
    
    /**
     * Logo URL: string vazia se não informado (NOT NULL no schema)
     */
    if ($logo_url === '') {
        $logo_url = '';
    }

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 1: Insere a empresa
    // ─────────────────────────────────────────────────────────────────────
    
    /**
     * INSERT com colunas do schema:
     * id (AI), name, trade_name, document, logo_url, is_active, created_at
     */
    $sql = "INSERT INTO companies
            (name, trade_name, document, logo_url, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())";
    $st = $pdo->prepare($sql);
    $st->execute([$name, $trade_name, $document, $logo_url, (int)$is_active]);
    
    /**
     * Obtém o ID da empresa recém-criada
     */
    $companyId = (int)$pdo->lastInsertId();

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 2: Vincula o usuário à nova empresa
    // ─────────────────────────────────────────────────────────────────────
    
    $userId = (int)$_SESSION['user']['id'];
    $pdo->prepare("INSERT IGNORE INTO user_company (user_id, company_id) VALUES (?, ?)")
        ->execute([$userId, $companyId]);

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 3: Atualiza a sessão
    // ─────────────────────────────────────────────────────────────────────
    
    /**
     * Recarrega lista de empresas do usuário para o select do header
     */
    $st2 = $pdo->prepare("
        SELECT c.id, c.name
        FROM companies c
        JOIN user_company uc ON uc.company_id = c.id
        WHERE uc.user_id = ?
        ORDER BY c.name
    ");
    $st2->execute([$userId]);
    $_SESSION['companies'] = $st2->fetchAll(PDO::FETCH_ASSOC);
    
    /**
     * Define a nova empresa como empresa atual
     */
    $_SESSION['current_company'] = ['id' => $companyId, 'name' => $name];

    /**
     * Limpa buffers e retorna sucesso
     */
    while (ob_get_level()) ob_end_clean();
    echo json_encode([
        'status' => 'ok', 
        'company_id' => $companyId
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    /**
     * Em caso de erro, limpa buffers e retorna erro
     */
    while (ob_get_level()) ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}
