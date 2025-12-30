<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ TRAINING_SAVE.PHP — Processamento de Criação de Treinamento              ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Processar o formulário de criação de treinamento          ║
 * ║                e salvar no banco de dados                                ║
 * ║                                                                           ║
 * ║ @acesso        Admin Geral | Gestor (canAccessAdmin)                     ║
 * ║ @método        POST (formulário)                                         ║
 * ║ @escopo        Por empresa (company_id)                                  ║
 * ║                                                                           ║
 * ║ @parâmetros    POST:                                                     ║
 * ║                - company_id: ID da empresa                               ║
 * ║                - title: título (obrigatório)                             ║
 * ║                - objective: objetivo (obrigatório)                       ║
 * ║                - description: descrição                                  ║
 * ║                - cover_image: URL da capa                                ║
 * ║                - reward_image: URL da recompensa                         ║
 * ║                - difficulty: nível de dificuldade                        ║
 * ║                - estimated_minutes: duração estimada                     ║
 * ║                - tags: tags separadas por vírgula                        ║
 * ║                - is_active: 1 ou 0                                       ║
 * ║                - roles[]: array de IDs de cargos                         ║
 * ║                - videos[]: array de vídeo-aulas                          ║
 * ║                                                                           ║
 * ║ @transação     Usa beginTransaction/commit/rollBack                      ║
 * ║                                                                           ║
 * ║ @redireciona   treinamento.php?id=X (sucesso) ou training_new.php        ║
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
$company_id  = (int)($_POST['company_id'] ?? 0);
$title       = trim($_POST['title'] ?? '');
$objective   = trim($_POST['objective'] ?? '');
$description = trim($_POST['description'] ?? '');
$cover       = trim($_POST['cover_image'] ?? '');
$reward      = trim($_POST['reward_image'] ?? '');
$difficulty  = $_POST['difficulty'] ?? 'Iniciante';
$est_min     = (int)($_POST['estimated_minutes'] ?? 0);
$tags        = trim($_POST['tags'] ?? '');
$is_active   = (int)($_POST['is_active'] ?? 1);
$roles       = $_POST['roles'] ?? [];
$videos      = $_POST['videos'] ?? [];

/**
 * Validação básica: empresa, título e objetivo são obrigatórios
 */
if ($company_id <= 0 || $title === '' || $objective === '') {
    header('Location: ' . url_for('/pages/training_new.php')); 
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: PERSISTÊNCIA NO BANCO DE DADOS
// ═══════════════════════════════════════════════════════════════════════════

try {
    /**
     * Inicia transação para garantir atomicidade
     */
    $pdo->beginTransaction();

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 1: Insere o treinamento principal
    // ─────────────────────────────────────────────────────────────────────
    
    $insT = $pdo->prepare("
        INSERT INTO trainings
        (company_id, title, objective, description, cover_image, reward_image, difficulty, estimated_minutes, tags, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insT->execute([
        $company_id, 
        $title, 
        $objective, 
        $description, 
        $cover, 
        $reward, 
        $difficulty, 
        $est_min, 
        $tags, 
        $is_active
    ]);
    
    /**
     * Obtém o ID do treinamento recém-criado
     */
    $training_id = (int)$pdo->lastInsertId();

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 2: Vincula cargos ao treinamento
    // ─────────────────────────────────────────────────────────────────────
    
    if (!empty($roles)) {
        $insRT = $pdo->prepare("INSERT IGNORE INTO role_training (role_id, training_id) VALUES (?, ?)");
        foreach ($roles as $rid) {
            $rid = (int)$rid; 
            if ($rid > 0) {
                $insRT->execute([$rid, $training_id]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 3: Insere as vídeo-aulas
    // ─────────────────────────────────────────────────────────────────────
    
    if (!empty($videos)) {
        $insV = $pdo->prepare("
            INSERT INTO training_videos
            (training_id, title, summary, video_provider, video_ref, thumb_image, duration_seconds, order_index, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        foreach ($videos as $v) {
            $titleV  = trim($v['title'] ?? '');
            $summary = trim($v['summary'] ?? '');
            $prov    = trim($v['video_provider'] ?? 'youtube');
            $ref     = trim($v['video_ref'] ?? '');
            $thumb   = trim($v['thumb_image'] ?? '');
            $dur     = (int)($v['duration_seconds'] ?? 0);
            $ord     = max(1, (int)($v['order_index'] ?? 1));
            
            /**
             * Só insere se título e referência estiverem preenchidos
             */
            if ($titleV !== '' && $ref !== '') {
                $insV->execute([
                    $training_id, 
                    $titleV, 
                    $summary, 
                    $prov, 
                    $ref, 
                    $thumb, 
                    $dur, 
                    $ord
                ]);
            }
        }
    }

    /**
     * Confirma a transação
     */
    $pdo->commit();
    
    /**
     * Redireciona para a página do treinamento recém-criado
     */
    header('Location: ' . url_for('/pages/treinamento.php') . '?id=' . $training_id); 
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
