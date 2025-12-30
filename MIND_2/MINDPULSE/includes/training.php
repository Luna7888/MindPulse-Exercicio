<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ TRAINING.PHP — Funções de Gerenciamento de Treinamentos                  ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Fornecer funções para manipulação de treinamentos,        ║
 * ║                vídeos, progresso do usuário e recompensas                ║
 * ║                                                                           ║
 * ║ @acesso        Interno (incluído por páginas de treinamento)             ║
 * ║ @escopo        Por empresa e por cargo (role-based access)               ║
 * ║                                                                           ║
 * ║ @funções       userRoles() - Cargos do usuário                           ║
 * ║                trainingsForUser() - Treinamentos acessíveis              ║
 * ║                trainingById() - Busca treinamento por ID                 ║
 * ║                trainingVideos() - Vídeos de um treinamento               ║
 * ║                userCompletedVideos() - Vídeos que o usuário concluiu     ║
 * ║                userTrainingProgress() - Progresso percentual             ║
 * ║                awardTrainingIfComplete() - Concede recompensa            ║
 * ║                userHasAccessToTraining() - Verifica permissão            ║
 * ║                youtube_id_from() - Extrai ID do YouTube de URL           ║
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
// SEÇÃO: FUNÇÕES DE USUÁRIO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * userRoles() — Retorna os cargos de um usuário
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $userId ID do usuário
 * @return array Lista de cargos [{id, name}, ...]
 * 
 * @uso
 * Usado para determinar quais treinamentos o usuário pode acessar
 * Treinamentos são vinculados a cargos via tabela role_training
 */
function userRoles(PDO $pdo, int $userId): array {
    $st = $pdo->prepare("
        SELECT r.id, r.name
        FROM user_role ur 
        JOIN roles r ON r.id = ur.role_id 
        WHERE ur.user_id = ?
    ");
    $st->execute([$userId]); 
    return $st->fetchAll();
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE LISTAGEM DE TREINAMENTOS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * trainingsForUser() — Lista treinamentos acessíveis ao usuário
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $userId ID do usuário
 * @param int|null $companyId ID da empresa atual
 * @return array Lista de treinamentos
 * 
 * @lógica
 * Retorna treinamentos que:
 * 1. Pertencem à empresa atual (company_id)
 * 2. Estão ativos (is_active = 1)
 * 3. Estão vinculados a pelo menos um cargo do usuário (via role_training)
 * 
 * @ordenação
 * Mais recentes primeiro (ORDER BY created_at DESC)
 * 
 * @exemplo
 * $trainings = trainingsForUser($pdo, 123, 1);
 * foreach ($trainings as $t) {
 *     echo $t['title'];
 * }
 */
function trainingsForUser(PDO $pdo, int $userId, ?int $companyId): array {
    // Se não há empresa selecionada, retorna vazio
    if (!$companyId) return [];
    
    /**
     * Query com múltiplos JOINs:
     * - role_training: vincula treinamentos a cargos
     * - user_role: vincula usuários a cargos
     * 
     * DISTINCT evita duplicatas (se usuário tem múltiplos cargos
     * que dão acesso ao mesmo treinamento)
     */
    $sql = "SELECT DISTINCT t.*
            FROM trainings t
            JOIN role_training rt ON rt.training_id = t.id
            JOIN user_role ur ON ur.role_id = rt.role_id
            WHERE ur.user_id = ? 
              AND t.company_id = ? 
              AND t.is_active = 1
            ORDER BY t.created_at DESC";
    
    $st = $pdo->prepare($sql);
    $st->execute([$userId, $companyId]);
    return $st->fetchAll();
}

/**
 * trainingById() — Busca um treinamento específico por ID
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $trainingId ID do treinamento
 * @param int|null $companyId ID da empresa (para validação de escopo)
 * @return array|null Dados do treinamento ou null se não encontrado
 * 
 * @segurança
 * Valida que o treinamento pertence à empresa atual
 * Isso previne acesso a treinamentos de outras empresas
 */
function trainingById(PDO $pdo, int $trainingId, ?int $companyId): ?array {
    $st = $pdo->prepare("
        SELECT * FROM trainings 
        WHERE id = ? AND company_id = ? AND is_active = 1 
        LIMIT 1
    ");
    $st->execute([$trainingId, $companyId]);
    $row = $st->fetch();
    return $row ?: null;
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE VÍDEOS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * trainingVideos() — Lista os vídeos de um treinamento
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $trainingId ID do treinamento
 * @return array Lista de vídeos ordenados por order_index
 * 
 * @campos_retornados
 * - id: ID do vídeo
 * - title: título da aula
 * - video_url: URL do vídeo (YouTube, Vimeo, etc.)
 * - order_index: posição na sequência
 * - duration_minutes: duração estimada
 * - is_active: se está ativo
 */
function trainingVideos(PDO $pdo, int $trainingId): array {
    $st = $pdo->prepare("
        SELECT * FROM training_videos 
        WHERE training_id = ? AND is_active = 1 
        ORDER BY order_index ASC
    ");
    $st->execute([$trainingId]);
    return $st->fetchAll();
}

/**
 * userCompletedVideos() — Lista IDs dos vídeos que o usuário concluiu
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $userId ID do usuário
 * @param int $trainingId ID do treinamento
 * @return array Lista de IDs de vídeos concluídos [1, 3, 5, ...]
 * 
 * @uso
 * Usado para calcular progresso e determinar próximo vídeo
 */
function userCompletedVideos(PDO $pdo, int $userId, int $trainingId): array {
    $st = $pdo->prepare("
        SELECT uvp.video_id
        FROM user_video_progress uvp
        JOIN training_videos tv ON tv.id = uvp.video_id
        WHERE uvp.user_id = ? AND tv.training_id = ?
    ");
    $st->execute([$userId, $trainingId]);
    return array_column($st->fetchAll(), 'video_id');
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE PROGRESSO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * userTrainingProgress() — Calcula o progresso do usuário em um treinamento
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $userId ID do usuário
 * @param int $trainingId ID do treinamento
 * @return array ['total' => int, 'done' => int, 'percent' => int, 'nextVideoId' => int|null]
 * 
 * @lógica
 * 1. Conta total de vídeos do treinamento
 * 2. Conta quantos o usuário já concluiu
 * 3. Calcula porcentagem (arredondada para baixo)
 * 4. Identifica o próximo vídeo não concluído
 * 
 * @exemplo
 * $progress = userTrainingProgress($pdo, 123, 1);
 * // ['total' => 10, 'done' => 7, 'percent' => 70, 'nextVideoId' => 8]
 */
function userTrainingProgress(PDO $pdo, int $userId, int $trainingId): array {
    // Busca todos os vídeos do treinamento
    $videos = trainingVideos($pdo, $trainingId);
    $total = count($videos);
    
    // Se não há vídeos, retorna zerado
    if ($total === 0) {
        return ['total' => 0, 'done' => 0, 'percent' => 0, 'nextVideoId' => null];
    }

    // Busca IDs dos vídeos que o usuário concluiu
    $doneIds = userCompletedVideos($pdo, $userId, $trainingId);
    $done = count($doneIds);
    
    // Calcula porcentagem (floor para não mostrar 100% antes de concluir tudo)
    $percent = (int) floor(($done / $total) * 100);

    // Encontra o próximo vídeo não concluído (na ordem)
    $next = null;
    foreach ($videos as $v) { 
        if (!in_array($v['id'], $doneIds)) { 
            $next = $v['id']; 
            break; 
        } 
    }

    return [
        'total' => $total,
        'done' => $done,
        'percent' => $percent,
        'nextVideoId' => $next
    ];
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE RECOMPENSA
// ═══════════════════════════════════════════════════════════════════════════

/**
 * awardTrainingIfComplete() — Concede recompensa se treinamento foi concluído
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $userId ID do usuário
 * @param int $trainingId ID do treinamento
 * @return bool true se recompensa foi concedida (ou já existia), false se não completou
 * 
 * @idempotência
 * Pode ser chamada múltiplas vezes sem efeito colateral
 * Se o usuário já tem a recompensa, retorna true sem fazer nada
 * 
 * @lógica
 * 1. Verifica se usuário já tem a recompensa
 * 2. Verifica se completou todos os vídeos
 * 3. Se sim, insere registro na tabela user_training_reward
 * 
 * @gamificação
 * As recompensas aparecem no dashboard do usuário como badges/troféus
 */
function awardTrainingIfComplete(PDO $pdo, int $userId, int $trainingId): bool {
    // Verifica se usuário já tem esta recompensa
    $chk = $pdo->prepare("
        SELECT 1 FROM user_training_reward 
        WHERE user_id = ? AND training_id = ? 
        LIMIT 1
    ");
    $chk->execute([$userId, $trainingId]);
    if ($chk->fetch()) return true;  // Já tem, retorna sucesso

    // Verifica se completou todos os vídeos
    $videos = trainingVideos($pdo, $trainingId);
    $total = count($videos);
    if ($total === 0) return false;  // Sem vídeos, não há o que completar

    $doneIds = userCompletedVideos($pdo, $userId, $trainingId);
    if (count($doneIds) < $total) return false;  // Ainda não completou tudo

    // Busca a imagem de recompensa do treinamento
    $st = $pdo->prepare("SELECT reward_image FROM trainings WHERE id = ? LIMIT 1");
    $st->execute([$trainingId]);
    $reward = $st->fetchColumn() ?: '/assets/img/reward_default.png';

    // Insere a recompensa
    $ins = $pdo->prepare("
        INSERT INTO user_training_reward (user_id, training_id, reward_image) 
        VALUES (?, ?, ?)
    ");
    $ins->execute([$userId, $trainingId, $reward]);
    
    return true;
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE AUTORIZAÇÃO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * userHasAccessToTraining() — Verifica se usuário pode acessar um treinamento
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $userId ID do usuário
 * @param int $trainingId ID do treinamento
 * @return bool true se tem acesso, false se não
 * 
 * @lógica
 * Verifica se existe interseção entre:
 * - Cargos do usuário (user_role)
 * - Cargos vinculados ao treinamento (role_training)
 * 
 * @uso
 * Chamada antes de exibir página de treinamento ou marcar vídeo como visto
 */
function userHasAccessToTraining(PDO $pdo, int $userId, int $trainingId): bool {
    $sql = "SELECT 1
            FROM role_training rt
            JOIN user_role ur ON ur.role_id = rt.role_id
            JOIN trainings t ON t.id = rt.training_id
            WHERE ur.user_id = ? AND rt.training_id = ? AND t.is_active = 1
            LIMIT 1";
    
    $st = $pdo->prepare($sql);
    $st->execute([$userId, $trainingId]);
    return (bool)$st->fetch();
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES UTILITÁRIAS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * youtube_id_from() — Extrai o ID de um vídeo do YouTube de uma URL
 * 
 * @param string $ref URL ou ID do vídeo
 * @return string|null ID do vídeo ou null se não for reconhecido
 * 
 * @formatos_aceitos
 * - https://www.youtube.com/watch?v=VIDEOID
 * - https://youtu.be/VIDEOID
 * - https://www.youtube.com/embed/VIDEOID
 * - VIDEOID (ID puro)
 * 
 * @exemplo
 * youtube_id_from('https://youtu.be/dQw4w9WgXcQ');  // 'dQw4w9WgXcQ'
 * youtube_id_from('dQw4w9WgXcQ');                    // 'dQw4w9WgXcQ'
 * youtube_id_from('https://vimeo.com/123');          // null
 */
function youtube_id_from(string $ref): ?string {
    $ref = trim($ref);
    
    // Tenta extrair de URLs do YouTube
    if (preg_match('~^(?:https?:)?//(?:www\.)?youtu(?:\.be/|be\.com/(?:watch\?v=|embed/))([A-Za-z0-9_-]{6,})~', $ref, $m)) {
        return $m[1];
    }
    
    // Verifica se é um ID puro (6+ caracteres alfanuméricos)
    if (preg_match('~^[A-Za-z0-9_-]{6,}$~', $ref)) {
        return $ref;
    }
    
    return null;
}
