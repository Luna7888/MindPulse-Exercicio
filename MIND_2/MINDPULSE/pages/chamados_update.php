<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ CHAMADOS_UPDATE.PHP — API para Atualizar Status de Chamado               ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Atualizar o status de um chamado/feedback                 ║
 * ║                                                                           ║
 * ║ @acesso        Admin Geral | Gestor (canAccessAdmin)                     ║
 * ║ @método        POST (JSON)                                               ║
 * ║ @escopo        Por empresa (company_id)                                  ║
 * ║                                                                           ║
 * ║ @parâmetros    JSON body:                                                ║
 * ║                - id: ID do chamado                                       ║
 * ║                - status: novo status (aberto|em_andamento|concluido)     ║
 * ║                                                                           ║
 * ║ @retorno       JSON: {status: 'ok'} ou {status: 'error', message: '...'}║
 * ║                                                                           ║
 * ║ @segurança     - Valida permissão administrativa                         ║
 * ║                - Valida que chamado pertence à empresa                   ║
 * ║                - Valida que status é um dos permitidos                   ║
 * ║                                                                           ║
 * ║ @dependências  db.php, auth.php, feedback.php                            ║
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
 * Inicia buffer de saída para capturar outputs acidentais
 */
ob_start(); 

/**
 * Define Content-Type como JSON
 */
header('Content-Type: application/json; charset=utf-8');

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: DEPENDÊNCIAS E VERIFICAÇÕES
// ═══════════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/feedback.php';

/**
 * Exige autenticação
 */
requireLogin(); 

/**
 * Verifica permissão administrativa
 * Apenas Admin Geral e Gestores podem atualizar chamados
 */
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
     * Extrai parâmetros
     */
    $id     = (int)($in['id'] ?? 0);
    $status = $in['status'] ?? 'aberto';
    
    /**
     * Valida ID
     */
    if (!$id) {
        throw new Exception('ID inválido');
    }
    
    /**
     * Atualiza o status do chamado
     * 
     * fb_update_status() valida:
     * - Status é um dos permitidos (aberto, em_andamento, concluido)
     * - Chamado pertence à empresa atual
     */
    fb_update_status($pdo, $id, currentCompanyId(), $status);

    /**
     * Limpa buffers e retorna sucesso
     */
    while (ob_get_level()) ob_end_clean();
    echo json_encode(['status' => 'ok']);
    
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
