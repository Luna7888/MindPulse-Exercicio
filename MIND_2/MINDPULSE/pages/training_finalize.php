<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ TRAINING_FINALIZE.PHP — API para Finalizar Treinamento                   ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Verificar se o treinamento foi completado e conceder      ║
 * ║                a recompensa ao usuário                                   ║
 * ║                                                                           ║
 * ║ @acesso        Colaboradores autenticados                                ║
 * ║ @método        POST (JSON)                                               ║
 * ║ @escopo        Por empresa (company_id)                                  ║
 * ║                                                                           ║
 * ║ @parâmetros    JSON body:                                                ║
 * ║                - training_id: ID do treinamento                          ║
 * ║                                                                           ║
 * ║ @retorno       JSON: {status: 'ok', awarded: bool} ou erro               ║
 * ║                awarded = true se a recompensa foi concedida              ║
 * ║                                                                           ║
 * ║ @validações    - Treinamento existe e pertence à empresa                 ║
 * ║                - Usuário tem acesso ao treinamento                       ║
 * ║                - Todos os vídeos foram assistidos                        ║
 * ║                                                                           ║
 * ║ @gamificação   Concede recompensa se 100% dos vídeos foram assistidos    ║
 * ║                                                                           ║
 * ║ @dependências  db.php, auth.php, training.php                            ║
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
require_once __DIR__ . '/../includes/training.php';

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
    $input = json_decode(file_get_contents('php://input'), true);
    
    /**
     * Extrai parâmetros
     */
    $trainingId = (int)($input['training_id'] ?? 0);
    $userId     = (int)($_SESSION['user']['id'] ?? 0);
    $companyId  = currentCompanyId();

    /**
     * Validação básica
     */
    if ($trainingId <= 0) { 
        while (ob_get_level()) ob_end_clean(); 
        echo json_encode(['status' => 'error', 'message' => 'Parâmetros inválidos']); 
        exit; 
    }

    /**
     * Valida que o treinamento existe e pertence à empresa
     */
    $training = trainingById($pdo, $trainingId, $companyId);
    if (!$training) { 
        while (ob_get_level()) ob_end_clean(); 
        echo json_encode(['status' => 'error', 'message' => 'Treinamento inválido.']); 
        exit; 
    }
    
    /**
     * Valida que o usuário tem acesso ao treinamento
     */
    if (!userHasAccessToTraining($pdo, $userId, $trainingId)) {
        while (ob_get_level()) ob_end_clean(); 
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']); 
        exit;
    }

    /**
     * Verifica se o treinamento foi completado e concede recompensa
     * 
     * awardTrainingIfComplete() verifica:
     * 1. Se todos os vídeos foram assistidos
     * 2. Se a recompensa já foi concedida
     * 3. Se não, concede a recompensa
     * 
     * Retorna true se a recompensa foi concedida agora
     */
    $ok = awardTrainingIfComplete($pdo, $userId, $trainingId);

    /**
     * Limpa buffers e retorna sucesso
     */
    while (ob_get_level()) ob_end_clean();
    echo json_encode([
        'status' => 'ok', 
        'awarded' => $ok
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    /**
     * Em caso de erro, limpa buffers e retorna erro
     */
    while (ob_get_level()) ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Falha no servidor.'
    ], JSON_UNESCAPED_UNICODE);
}
