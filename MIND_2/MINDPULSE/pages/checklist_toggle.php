<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ CHECKLIST_TOGGLE.PHP — API para Marcar/Desmarcar Tarefa                  ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Endpoint AJAX para toggle de status de tarefa             ║
 * ║                (marcar como feita ou desmarcar)                          ║
 * ║                                                                           ║
 * ║ @acesso        Colaboradores autenticados                                ║
 * ║ @método        POST (JSON)                                               ║
 * ║ @escopo        Por empresa e período                                     ║
 * ║                                                                           ║
 * ║ @parâmetros    JSON body:                                                ║
 * ║                - action: 'check' ou 'uncheck'                            ║
 * ║                - task_id: ID da tarefa                                   ║
 * ║                - checklist_id: ID do checklist                           ║
 * ║                - frequency: frequência do checklist                      ║
 * ║                - period: 'current' ou 'prev'                             ║
 * ║                                                                           ║
 * ║ @retorno       JSON: {status: 'ok', period_key: '...'} ou erro           ║
 * ║                                                                           ║
 * ║ @funções       markTask(): marca tarefa como feita                       ║
 * ║                unmarkTask(): desmarca tarefa                             ║
 * ║                                                                           ║
 * ║ @dependências  db.php, auth.php, checklist.php                           ║
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
 * Isso evita que warnings ou espaços em branco corrompam o JSON
 */
ob_start();

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: DEPENDÊNCIAS E VERIFICAÇÕES
// ═══════════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/checklist.php';

/**
 * Exige autenticação
 */
requireLogin();

/**
 * Define Content-Type como JSON
 */
header('Content-Type: application/json; charset=utf-8');

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: PROCESSAMENTO DA REQUISIÇÃO
// ═══════════════════════════════════════════════════════════════════════════

try {
    /**
     * Lê e decodifica o corpo da requisição JSON
     */
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    
    /**
     * Extrai parâmetros da requisição
     */
    $action = $in['action'] ?? '';           // 'check' ou 'uncheck'
    $taskId = (int)($in['task_id'] ?? 0);    // ID da tarefa
    $clId   = (int)($in['checklist_id'] ?? 0); // ID do checklist
    $freq   = $in['frequency'] ?? 'daily';   // Frequência do checklist
    $mode   = $in['period'] ?? 'current';    // 'current' ou 'prev'

    /**
     * Dados do contexto
     */
    $userId    = (int)$_SESSION['user']['id'];
    $companyId = currentCompanyId();
    
    /**
     * Validação de parâmetros obrigatórios
     */
    if (!$taskId || !$clId || !$companyId) {
        throw new Exception('Parâmetros inválidos');
    }

    /**
     * Calcula a period_key baseado no modo e frequência
     * 
     * period_key_for(): retorna chave do período atual
     * period_key_prev(): retorna chave do período anterior
     */
    $pkey = ($mode === 'prev') ? period_key_prev($freq) : period_key_for($freq);
    
    /**
     * Executa a ação apropriada
     */
    if ($action === 'check') {
        /**
         * Marca tarefa como feita
         * 
         * dueWindowLate(): verifica se está no período "late" (atrasado)
         * - Se período atual: verifica janela de tempo
         * - Se período anterior: sempre considera como late (regularização)
         */
        $late = ($mode === 'current') ? dueWindowLate($freq) : true;
        markTask($pdo, $clId, $taskId, $userId, $companyId, $pkey, $late);
        
    } elseif ($action === 'uncheck') {
        /**
         * Desmarca tarefa
         */
        unmarkTask($pdo, $taskId, $companyId, $pkey);
        
    } else {
        throw new Exception('Ação inválida');
    }

    /**
     * Limpa buffers e retorna sucesso
     */
    while (ob_get_level()) ob_end_clean();
    echo json_encode([
        'status' => 'ok', 
        'period_key' => $pkey
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
