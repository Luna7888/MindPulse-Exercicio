<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ FEEDBACK_SUBMIT.PHP — API para Enviar Feedback                           ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Criar novo feedback/chamado do colaborador                ║
 * ║                                                                           ║
 * ║ @acesso        Colaboradores autenticados                                ║
 * ║ @método        POST (JSON)                                               ║
 * ║ @escopo        Por empresa (company_id)                                  ║
 * ║                                                                           ║
 * ║ @parâmetros    JSON body:                                                ║
 * ║                - sentiment_key: chave do sentimento (obrigatório)        ║
 * ║                - sentiment_score: score 1-5 (obrigatório)                ║
 * ║                - category: categoria do feedback                         ║
 * ║                - subject: assunto (opcional)                             ║
 * ║                - message: mensagem (obrigatório)                         ║
 * ║                                                                           ║
 * ║ @retorno       JSON: {status: 'ok'} ou {status: 'error', message: '...'}║
 * ║                                                                           ║
 * ║ @nota          Se subject for informado, é concatenado no início da      ║
 * ║                mensagem para manter compatibilidade com schema simples   ║
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
 * Inicia buffer de saída e define Content-Type
 */
ob_start(); 
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

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: PROCESSAMENTO DA REQUISIÇÃO
// ═══════════════════════════════════════════════════════════════════════════

try {
    /**
     * Lê e decodifica o corpo da requisição JSON
     */
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    
    /**
     * Dados do contexto
     */
    $userId = (int)$_SESSION['user']['id'];
    $companyId = currentCompanyId();

    /**
     * Extrai e sanitiza parâmetros
     */
    $data = [
        'sentiment_key'   => $in['sentiment_key'] ?? '',
        'sentiment_score' => (int)($in['sentiment_score'] ?? 0),
        'category'        => $in['category'] ?? 'feedback_geral',
        'subject'         => trim($in['subject'] ?? ''),
        'message'         => trim($in['message'] ?? ''),
    ];
    
    /**
     * Validações
     */
    if ($data['sentiment_key'] === '' || $data['sentiment_score'] < 1) {
        throw new Exception('Selecione o sentimento.');
    }
    if ($data['message'] === '') {
        throw new Exception('Escreva sua mensagem.');
    }

    /**
     * Se subject foi informado, concatena no início da mensagem
     * 
     * Isso mantém compatibilidade com schemas que não têm coluna subject
     */
    if ($data['subject'] !== '') {
        $data['message'] = 'Assunto: ' . $data['subject'] . "\n\n" . $data['message'];
    }

    /**
     * Cria o feedback no banco
     */
    fb_create($pdo, $companyId, $userId, $data);

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
