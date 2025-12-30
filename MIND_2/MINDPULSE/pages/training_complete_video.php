<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ TRAINING_COMPLETE_VIDEO.PHP — API para Marcar Vídeo como Concluído       ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Registrar que o usuário concluiu uma aula (vídeo)         ║
 * ║                de um treinamento                                         ║
 * ║                                                                           ║
 * ║ @acesso        Usuários autenticados com acesso ao treinamento           ║
 * ║ @método        POST (JSON)                                               ║
 * ║ @escopo        Por empresa + por cargo (role_training)                   ║
 * ║                                                                           ║
 * ║ @parâmetros    JSON body:                                                ║
 * ║                - training_id: ID do treinamento                          ║
 * ║                - video_id: ID do vídeo concluído                         ║
 * ║                                                                           ║
 * ║ @retorno       JSON: {status: 'ok', training_completed: bool}            ║
 * ║                ou {status: 'error', message: '...'}                      ║
 * ║                                                                           ║
 * ║ @idempotência  Sim - pode ser chamado múltiplas vezes sem efeito         ║
 * ║                colateral (ON DUPLICATE KEY UPDATE)                       ║
 * ║                                                                           ║
 * ║ @efeitos       1. Insere registro em user_video_progress                 ║
 * ║                2. Se completou todos os vídeos, concede recompensa       ║
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
 * Inicia buffer de saída
 * 
 * Isso captura qualquer output acidental (warnings, includes com echo)
 * e permite limpar tudo antes de enviar o JSON puro
 */
ob_start();

/**
 * Define Content-Type como JSON
 */
header('Content-Type: application/json; charset=utf-8');

/**
 * Inclui dependências
 */
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
    // ─────────────────────────────────────────────────────────────────────
    // PASSO 1: Capturar e validar parâmetros
    // ─────────────────────────────────────────────────────────────────────
    
    /**
     * Lê o corpo da requisição como JSON
     */
    $input = json_decode(file_get_contents('php://input'), true);
    
    /**
     * Extrai os parâmetros necessários
     */
    $trainingId = (int)($input['training_id'] ?? 0);
    $videoId    = (int)($input['video_id'] ?? 0);
    $userId     = (int)($_SESSION['user']['id'] ?? 0);
    $companyId  = currentCompanyId();

    /**
     * Valida que os IDs são válidos
     */
    if ($trainingId <= 0 || $videoId <= 0) {
        while (ob_get_level()) ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Parâmetros inválidos']); 
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 2: Validar treinamento
    // ─────────────────────────────────────────────────────────────────────
    
    /**
     * Verifica se o treinamento existe e pertence à empresa
     */
    $training = trainingById($pdo, $trainingId, $companyId);
    if (!$training) {
        while (ob_get_level()) ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Treinamento inválido.']); 
        exit;
    }
    
    /**
     * Verifica se o usuário tem acesso ao treinamento
     * (baseado nos cargos do usuário)
     */
    if (!userHasAccessToTraining($pdo, $userId, $trainingId)) {
        while (ob_get_level()) ob_end_clean();
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']); 
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 3: Validar vídeo
    // ─────────────────────────────────────────────────────────────────────
    
    /**
     * Verifica se o vídeo existe, pertence ao treinamento e está ativo
     */
    $st = $pdo->prepare("
        SELECT id FROM training_videos 
        WHERE id = ? AND training_id = ? AND is_active = 1
    ");
    $st->execute([$videoId, $trainingId]);
    
    if (!$st->fetch()) {
        while (ob_get_level()) ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Vídeo inválido.']); 
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 4: Registrar progresso
    // ─────────────────────────────────────────────────────────────────────
    
    /**
     * Insere ou atualiza o registro de progresso
     * 
     * ON DUPLICATE KEY UPDATE torna a operação idempotente:
     * - Se não existe: insere novo registro
     * - Se já existe: atualiza o timestamp
     */
    $ins = $pdo->prepare("
        INSERT INTO user_video_progress (user_id, video_id) 
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE completed_at = VALUES(completed_at)
    ");
    $ins->execute([$userId, $videoId]);

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 5: Verificar e conceder recompensa
    // ─────────────────────────────────────────────────────────────────────
    
    /**
     * Verifica se o usuário completou todos os vídeos do treinamento
     * Se sim, concede a recompensa
     * 
     * Retorna true se a recompensa foi concedida (ou já existia)
     */
    $completed = awardTrainingIfComplete($pdo, $userId, $trainingId);

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 6: Responder com sucesso
    // ─────────────────────────────────────────────────────────────────────
    
    /**
     * Limpa qualquer saída anterior e envia JSON puro
     */
    while (ob_get_level()) ob_end_clean();
    echo json_encode([
        'status' => 'ok',
        'training_completed' => $completed  // Indica se o treinamento foi finalizado
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    // ─────────────────────────────────────────────────────────────────────
    // TRATAMENTO DE ERROS
    // ─────────────────────────────────────────────────────────────────────
    
    /**
     * Em caso de erro, limpa buffers e retorna erro genérico
     * (não expõe detalhes técnicos por segurança)
     */
    while (ob_get_level()) ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Falha no servidor.'
    ], JSON_UNESCAPED_UNICODE);
}
