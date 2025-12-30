<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ TREINAMENTO.PHP — Página de Execução de Treinamento Individual           ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Exibir e permitir a execução de um treinamento específico ║
 * ║                com player de vídeo, lista de aulas e controle de         ║
 * ║                progresso                                                 ║
 * ║                                                                           ║
 * ║ @acesso        Usuários com cargo vinculado ao treinamento               ║
 * ║ @escopo        Por empresa + por cargo (role_training)                   ║
 * ║                                                                           ║
 * ║ @parâmetros    GET id: ID do treinamento                                 ║
 * ║                GET v: ID do vídeo atual (opcional)                       ║
 * ║                                                                           ║
 * ║ @funcionalidades                                                         ║
 * ║   - Player de vídeo (YouTube, Vimeo, Cloudflare, Mux, MP4)              ║
 * ║   - Marcar aula como concluída                                          ║
 * ║   - Navegação entre aulas                                               ║
 * ║   - Finalizar treinamento e ganhar recompensa                           ║
 * ║   - Lista lateral de aulas com status                                   ║
 * ║                                                                           ║
 * ║ @providers_suportados                                                    ║
 * ║   - youtube: YouTube e YouTube nocookie                                 ║
 * ║   - vimeo: Vimeo embeds                                                 ║
 * ║   - cloudflare: Cloudflare Stream                                       ║
 * ║   - mux: Mux Video (HLS)                                                ║
 * ║   - mp4: Vídeos MP4 diretos                                             ║
 * ║                                                                           ║
 * ║ @dependências  layout_start.php, training.php                            ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INCLUSÃO DE DEPENDÊNCIAS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Inclui o layout base e funções de treinamento
 */
require_once __DIR__ . '/../includes/layout_start.php';
require_once __DIR__ . '/../includes/training.php';

/**
 * Fallback: função youtube_id_from se não estiver definida
 * Extrai o ID de vídeo do YouTube de URLs em vários formatos
 */
if (!function_exists('youtube_id_from')) {
    function youtube_id_from(string $ref): ?string {
        $ref = trim($ref);
        // Tenta extrair de URLs do YouTube
        if (preg_match('~^(?:https?:)?//(?:www\.)?youtu(?:\.be/|be\.com/(?:watch\?v=|embed/))([A-Za-z0-9_-]{6,})~', $ref, $m)) {
            return $m[1];
        }
        // Verifica se é um ID puro
        if (preg_match('~^[A-Za-z0-9_-]{6,}$~', $ref)) {
            return $ref;
        }
        return null;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CAPTURA E VALIDAÇÃO DE PARÂMETROS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * IDs necessários para a página
 */
$userId     = (int)($_SESSION['user']['id'] ?? 0);
$companyId  = currentCompanyId();
$trainingId = (int)($_GET['id'] ?? 0);

/**
 * Validação: ID do treinamento deve ser válido
 */
if ($trainingId <= 0) { 
    echo '<div class="card" style="padding:20px">Treinamento inválido.</div>'; 
    require_once __DIR__ . '/../includes/layout_end.php'; 
    exit; 
}

/**
 * Busca o treinamento no banco
 * Valida que pertence à empresa atual e está ativo
 */
$training = trainingById($pdo, $trainingId, $companyId);
if (!$training) { 
    echo '<div class="card" style="padding:20px">Treinamento não encontrado.</div>'; 
    require_once __DIR__ . '/../includes/layout_end.php'; 
    exit; 
}

/**
 * Verifica se o usuário tem acesso ao treinamento
 * Baseado nos cargos do usuário vs cargos vinculados ao treinamento
 */
if (!userHasAccessToTraining($pdo, $userId, $trainingId)) {
    http_response_code(403);
    echo '<div class="card" style="padding:20px">Você não tem acesso a este treinamento.</div>';
    require_once __DIR__ . '/../includes/layout_end.php'; 
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CARREGAMENTO DE DADOS DO TREINAMENTO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Lista de vídeos do treinamento (ordenados por order_index)
 */
$videos = trainingVideos($pdo, $trainingId);

/**
 * Progresso do usuário neste treinamento
 */
$progress = userTrainingProgress($pdo, $userId, $trainingId);

/**
 * IDs dos vídeos que o usuário já concluiu
 * array_flip transforma em mapa para busca O(1): [id => index]
 */
$doneIdsArr = userCompletedVideos($pdo, $userId, $trainingId);
$doneIds    = array_flip($doneIdsArr);

/**
 * Determina qual vídeo exibir
 * Prioridade:
 * 1. Parâmetro ?v= na URL
 * 2. Próximo vídeo não concluído (nextVideoId)
 * 3. Primeiro vídeo da lista
 */
$currentVideoId = (int)($_GET['v'] ?? 0);
if (!$currentVideoId) {
    $currentVideoId = $progress['nextVideoId'] ?: ($videos[0]['id'] ?? 0);
}

/**
 * Localiza o objeto do vídeo atual
 */
$currentVideo = null;
foreach ($videos as $v) { 
    if ((int)$v['id'] === $currentVideoId) { 
        $currentVideo = $v; 
        break; 
    } 
}
if (!$currentVideo && !empty($videos)) {
    $currentVideo = $videos[0];
}

/**
 * Determina o próximo vídeo na sequência
 * Usado para o botão "Próxima aula"
 */
$nextId = null;
$ordered = array_values($videos);
for ($i = 0; $i < count($ordered); $i++) {
    if ((int)$ordered[$i]['id'] === (int)($currentVideo['id'] ?? 0) && isset($ordered[$i + 1])) { 
        $nextId = (int)$ordered[$i + 1]['id']; 
        break; 
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: ESTILOS ESPECÍFICOS DA PÁGINA
// ═══════════════════════════════════════════════════════════════════════════

echo '<style>
/* Responsividade do grid principal */
@media (max-width: 980px){ 
    .train-grid{ grid-template-columns: 1fr !important; } 
}

/* Efeitos de transição nos botões */
.button, .button.ghost{
    position:relative; 
    overflow:hidden;
    transition: transform .18s ease, filter .18s ease, opacity .18s ease, box-shadow .18s ease;
}
.button:hover{ filter:brightness(1.05) }
.button:active{ transform: translateY(1px) }
.button[disabled]{ opacity:.65; cursor:not-allowed }

/* Estado de loading nos botões */
.button.is-loading, .button.ghost.is-loading{ 
    pointer-events:none; 
    color:transparent !important 
}

/* Spinner de loading */
.button.is-loading::after, .button.ghost.is-loading::after{
    content:""; 
    position:absolute; 
    inset:auto auto auto auto; 
    left:50%; top:50%;
    width:18px; height:18px; 
    margin-left:-9px; margin-top:-9px; 
    border-radius:50%;
    border:3px solid rgba(255,106,0,.25); 
    border-top-color:#ff6a00; 
    animation:spin .8s linear infinite;
}

@keyframes spin{ to{ transform:rotate(360deg) } }
</style>';
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ LAYOUT PRINCIPAL: Grid de 2 colunas                                   ║
     ║                                                                        ║
     ║ Coluna 1 (1.1fr): Detalhes do treinamento + Player                    ║
     ║ Coluna 2 (0.9fr): Lista de aulas + Recompensa                         ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<div class="train-grid" style="display:grid; grid-template-columns: 1.1fr .9fr; gap:18px">
    
    <!-- ════════════════════════════════════════════════════════════════════
         COLUNA ESQUERDA: Detalhes + Player
         ════════════════════════════════════════════════════════════════════ -->
    <section class="card" style="padding:0; overflow:hidden">
        
        <!-- Capa do treinamento -->
        <div style="position:relative">
            <img 
                src="<?= htmlspecialchars($training['cover_image'] ?: url_for('/assets/img/login_hero.svg')) ?>" 
                alt="" 
                style="width:100%; height:200px; object-fit:cover"
            >
            <?php if (!empty($training['reward_image'])): ?>
                <img 
                    src="<?= htmlspecialchars($training['reward_image']) ?>" 
                    alt="recompensa" 
                    style="position:absolute; right:12px; bottom:12px; width:56px; height:56px; border-radius:14px; border:1px solid var(--stroke)"
                >
            <?php endif; ?>
        </div>

        <!-- Informações do treinamento -->
        <div style="padding:18px">
            <h2 style="margin:0; font-weight:900"><?= htmlspecialchars($training['title']) ?></h2>
            <div style="color:#9aa4b2; margin-top:6px">
                <?= nl2br(htmlspecialchars($training['objective'])) ?>
            </div>

            <!-- Badges de metadados -->
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; color:#cbd5e1; font-size:.92rem">
                <span class="badge"><?= htmlspecialchars($training['difficulty']) ?></span>
                <?php if(!empty($training['estimated_minutes'])): ?>
                    <span class="badge"><?= (int)$training['estimated_minutes'] ?> min</span>
                <?php endif; ?>
                <?php if(!empty($training['tags'])): ?>
                    <span class="badge"><?= htmlspecialchars($training['tags']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Barra de progresso geral -->
            <div style="margin-top:12px">
                <div style="height:10px; background:rgba(255,255,255,.08); border-radius:999px; overflow:hidden">
                    <div style="height:100%; width:<?= (int)$progress['percent'] ?>%; background:linear-gradient(135deg,var(--brand),var(--brand-2))"></div>
                </div>
                <div style="display:flex; justify-content:space-between; margin-top:6px; color:#cbd5e1; font-size:.9rem">
                    <span>Progresso</span>
                    <strong><?= (int)$progress['percent'] ?>% (<?= (int)$progress['done'] ?>/<?= (int)$progress['total'] ?>)</strong>
                </div>
            </div>
        </div>

        <!-- ────────────────────────────────────────────────────────────────
             PLAYER DE VÍDEO
             
             Renderiza o player apropriado baseado no provider:
             - youtube: iframe YouTube nocookie
             - vimeo: iframe Vimeo
             - cloudflare: iframe Cloudflare Stream
             - mux: elemento video com HLS
             - default: elemento video com MP4
             ──────────────────────────────────────────────────────────────── -->
        <?php if ($currentVideo): ?>
            <div style="padding:0 18px 18px">
                <div class="card" style="padding:12px; overflow:hidden">
                    
                    <!-- Info do vídeo atual -->
                    <div style="display:flex; gap:14px; align-items:flex-start; flex-wrap:wrap">
                        <img 
                            src="<?= htmlspecialchars($currentVideo['thumb_image'] ?: url_for('/assets/img/avatar.svg')) ?>" 
                            style="width:120px; height:68px; object-fit:cover; border-radius:12px; border:1px solid var(--stroke)"
                        >
                        <div>
                            <div style="font-weight:800"><?= htmlspecialchars($currentVideo['title']) ?></div>
                            <?php if(!empty($currentVideo['duration_seconds'])): ?>
                                <div style="color:#9aa4b2; font-size:.9rem; margin-top:2px">
                                    Duração: ~<?= (int)ceil($currentVideo['duration_seconds']/60) ?> min
                                </div>
                            <?php endif; ?>
                            <?php if(!empty($currentVideo['summary'])): ?>
                                <div style="color:#cbd5e1; margin-top:6px">
                                    <?= nl2br(htmlspecialchars($currentVideo['summary'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Container do player -->
                    <div style="margin-top:12px; background:rgba(255,255,255,.06); border:1px solid var(--stroke); border-radius:12px; overflow:hidden">
                        <?php
                        /**
                         * Renderização do player baseado no provider
                         * Cada provider tem sua própria forma de embed
                         */
                        $src = '';
                        switch ($currentVideo['video_provider']) {
                            case 'youtube':
                                // YouTube: extrai ID e usa nocookie para privacidade
                                $vid = youtube_id_from($currentVideo['video_ref']);
                                if ($vid) {
                                    $params = 'rel=0&modestbranding=1&controls=1&playsinline=1';
                                    $src = "https://www.youtube-nocookie.com/embed/{$vid}?{$params}";
                                    echo "<iframe src=\"$src\" style=\"width:100%; aspect-ratio:16/9\" allow=\"autoplay; encrypted-media; picture-in-picture\" allowfullscreen></iframe>";
                                } else {
                                    echo "<div style='padding:16px;color:#fecaca'>Link do YouTube inválido.</div>";
                                }
                                break;
                                
                            case 'cloudflare':
                                // Cloudflare Stream: URL direta do embed
                                $src = htmlspecialchars($currentVideo['video_ref']);
                                echo "<iframe src=\"$src\" style=\"width:100%; aspect-ratio:16/9\" allow=\"autoplay; encrypted-media\" allowfullscreen></iframe>";
                                break;
                                
                            case 'vimeo':
                                // Vimeo: URL do embed
                                $src = htmlspecialchars($currentVideo['video_ref']);
                                echo "<iframe src=\"$src\" style=\"width:100%; aspect-ratio:16/9\" frameborder=\"0\" allow=\"autoplay; fullscreen; picture-in-picture\" allowfullscreen></iframe>";
                                break;
                                
                            case 'mux':
                                // Mux: HLS via elemento video
                                $src = htmlspecialchars($currentVideo['video_ref']);
                                echo "<video controls style=\"width:100%; aspect-ratio:16/9\"><source src=\"$src\" type=\"application/x-mpegURL\"></video>";
                                break;
                                
                            default:
                                // MP4 ou outro: elemento video padrão
                                $src = htmlspecialchars($currentVideo['video_ref']);
                                echo "<video controls style=\"width:100%; aspect-ratio:16/9\"><source src=\"$src\" type=\"video/mp4\"></video>";
                        }
                        ?>
                    </div>

                    <!-- Botões de ação -->
                    <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap">
                        <?php $isDone = isset($doneIds[$currentVideo['id']]); ?>
                        
                        <!-- Botão: Concluir aula -->
                        <button class="button" id="btnComplete" <?= $isDone ? 'disabled' : '' ?>>
                            <?= $isDone ? 'Aula concluída' : 'Concluir aula' ?>
                        </button>

                        <?php $trainingAlready100 = ((int)$progress['percent'] === 100); ?>
                        
                        <?php if ($nextId): ?>
                            <!-- Botão: Próxima aula (se houver) -->
                            <a class="button ghost" id="btnNext"
                               href="<?= url_for('/pages/treinamento.php') ?>?id=<?= (int)$trainingId ?>&v=<?= (int)$nextId ?>"
                               <?= $isDone ? '' : 'style="pointer-events:none; opacity:.6"'?>>
                                Próxima aula →
                            </a>
                        <?php else: ?>
                            <!-- Botão: Finalizar treinamento (última aula) -->
                            <button class="button ghost" id="btnFinish" <?= $trainingAlready100 ? '' : 'disabled' ?>>
                                Finalizar Treinamento
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- ════════════════════════════════════════════════════════════════════
         COLUNA DIREITA: Lista de Aulas + Recompensa
         ════════════════════════════════════════════════════════════════════ -->
    <aside class="card" style="padding:12px">
        <div style="font-weight:900; padding:6px 6px 10px">Trilha de aulas</div>
        
        <!-- Lista de aulas com scroll -->
        <div style="display:flex; flex-direction:column; gap:8px; max-height:72vh; overflow:auto; padding-right:6px">
            <?php foreach($videos as $v): 
                $done = isset($doneIds[$v['id']]); 
            ?>
                <a class="card" 
                   href="<?= url_for('/pages/treinamento.php') ?>?id=<?= (int)$trainingId ?>&v=<?= (int)$v['id'] ?>"
                   style="display:flex; gap:10px; padding:8px; align-items:center; border:1px solid var(--stroke)">
                    
                    <!-- Thumbnail da aula -->
                    <img 
                        src="<?= htmlspecialchars($v['thumb_image'] ?: url_for('/assets/img/avatar.svg')) ?>" 
                        style="width:72px; height:40px; object-fit:cover; border-radius:8px; border:1px solid var(--stroke)"
                    >
                    
                    <!-- Info da aula -->
                    <div style="flex:1">
                        <div style="font-weight:700; font-size:.98rem">
                            <?= htmlspecialchars($v['title']) ?>
                        </div>
                        <?php if(!empty($v['duration_seconds'])): ?>
                            <div style="color:#9aa4b2; font-size:.85rem">
                                ~<?= (int)ceil($v['duration_seconds']/60) ?> min
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Badge de concluída -->
                    <?php if ($done): ?>
                        <span class="badge" title="Concluída">✔</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Seção de recompensa -->
        <div style="margin-top:12px">
            <div style="font-weight:800; margin-bottom:4px">Recompensa</div>
            <?php if(!empty($training['reward_image'])): ?>
                <img 
                    src="<?= htmlspecialchars($training['reward_image']) ?>" 
                    alt="recompensa" 
                    style="width:80px; height:80px; border-radius:16px; border:1px solid var(--stroke)"
                >
            <?php else: ?>
                <div style="color:#9aa4b2">Sem imagem definida.</div>
            <?php endif; ?>
            <div style="color:#cbd5e1; font-size:.9rem; margin-top:6px">
                Conclua todas as aulas para ganhar este ícone no seu perfil.
            </div>
        </div>
    </aside>
</div>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ JAVASCRIPT: Interações da Página                                      ║
     ║                                                                        ║
     ║ Funções:                                                              ║
     ║ - setLoading: adiciona/remove estado de loading nos botões            ║
     ║ - postJSON: requisições AJAX robustas                                 ║
     ║ - Handlers para btnComplete e btnFinish                               ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<script>
/**
 * setLoading() — Adiciona/remove estado de loading em um botão
 * 
 * @param {HTMLElement} el - Elemento do botão
 * @param {boolean} isLoading - Se deve mostrar loading
 */
function setLoading(el, isLoading){
    if (!el) return;
    if (isLoading) { 
        el.classList.add("is-loading"); 
        el.setAttribute("disabled", "disabled"); 
    } else { 
        el.classList.remove("is-loading"); 
        el.removeAttribute("disabled"); 
    }
}

/**
 * postJSON() — Requisição POST robusta com tratamento de erros
 * 
 * @param {string} url - URL do endpoint
 * @param {object} payload - Dados a enviar
 * @returns {object} - Resposta parseada
 * @throws {Error} - Se resposta for inválida ou erro
 * 
 * @robustez
 * Tolera "lixo" antes do JSON (warnings do PHP, etc.)
 * Extrai o último bloco JSON válido da resposta
 */
async function postJSON(url, payload){
    const r = await fetch(url, { 
        method: "POST", 
        headers: { "Content-Type": "application/json" }, 
        body: JSON.stringify(payload || {}) 
    });
    const text = await r.text();
    
    // Tenta parsear JSON diretamente
    let data = null;
    try { 
        data = JSON.parse(text); 
    } catch(e) {
        // Se falhar, tenta extrair o último bloco JSON
        const m = text.match(/\{[\s\S]*\}$/);
        if (m) { data = JSON.parse(m[0]); }
    }
    
    if (!data) throw new Error("Resposta inválida do servidor.");
    if (!r.ok || data.status !== "ok") {
        const msg = data.message || ("HTTP " + r.status);
        throw new Error(msg);
    }
    return data;
}

// Referências aos botões
const btnComplete = document.getElementById('btnComplete');
const btnNext     = document.getElementById('btnNext');
const btnFinish   = document.getElementById('btnFinish');

/**
 * Handler: Concluir aula
 * 
 * Envia requisição para marcar o vídeo como concluído
 * Atualiza UI para refletir a conclusão
 */
if (btnComplete) {
    btnComplete.addEventListener('click', async () => {
        try {
            setLoading(btnComplete, true);
            
            const data = await postJSON('<?= url_for("/pages/training_complete_video.php") ?>', {
                training_id: <?= (int)$trainingId ?>,
                video_id: <?= (int)($currentVideo['id'] ?? 0) ?>
            });

            // Sucesso: atualiza UI
            btnComplete.textContent = 'Aula concluída';
            
            // Libera botão de próxima aula
            if (btnNext) { 
                btnNext.style.pointerEvents = 'auto'; 
                btnNext.style.opacity = '1'; 
            }
            
            // Libera botão de finalizar
            if (btnFinish) { 
                btnFinish.removeAttribute('disabled'); 
            }

            // Feedback visual se completou o treinamento
            if (btnFinish && data.training_completed) {
                btnFinish.classList.add('pulse-win');
            }
            
        } catch (err) {
            alert(err.message || 'Falha ao concluir.');
            btnComplete.textContent = 'Tentar novamente';
            setLoading(btnComplete, false);
        }
    });
}

/**
 * Handler: Finalizar treinamento
 * 
 * Envia requisição para finalizar e conceder recompensa
 * Redireciona para lista de treinamentos após sucesso
 */
if (btnFinish) {
    btnFinish.addEventListener('click', async () => {
        try {
            setLoading(btnFinish, true);
            
            await postJSON('<?= url_for("/pages/training_finalize.php") ?>', { 
                training_id: <?= (int)$trainingId ?> 
            });
            
            // Redireciona com delay para feedback visual
            setTimeout(() => { 
                window.location.href = '<?= url_for("/pages/treinamentos.php") ?>'; 
            }, 600);
            
        } catch (err) {
            alert(err.message || 'Não foi possível finalizar.');
            setLoading(btnFinish, false);
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
