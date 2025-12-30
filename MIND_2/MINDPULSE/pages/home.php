<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ PAGES/HOME.PHP — Dashboard Principal do Colaborador                      ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Exibir visão geral personalizada para cada usuário        ║
 * ║                Dashboard com KPIs, progresso e acesso rápido             ║
 * ║                                                                           ║
 * ║ @acesso        Admin Geral | Gestor | Colaborador (todos os níveis)      ║
 * ║ @escopo        Por empresa (company_id da sessão)                        ║
 * ║                                                                           ║
 * ║ @exibe         - Hero com foto, nome e recompensas do usuário            ║
 * ║                - KPIs: pendências, atrasos, treinamentos                 ║
 * ║                - Grid de treinamentos com progresso                      ║
 * ║                - Donut chart de aulas concluídas                         ║
 * ║                - Resumo de checklists                                    ║
 * ║                                                                           ║
 * ║ @dependências  layout_start.php, training.php, checklist.php             ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INCLUSÃO DE DEPENDÊNCIAS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Inclui o layout base (header, sidebar, estrutura HTML)
 * Também executa requireLogin() - se não logado, redireciona
 */
require_once __DIR__ . '/../includes/layout_start.php';

/**
 * Inclui funções de treinamentos
 * Fornece: trainingsForUser(), userTrainingProgress(), etc.
 */
require_once __DIR__ . '/../includes/training.php';

/**
 * Inclui funções de checklists
 * Fornece: totalsForUser(), checklistsForUser(), etc.
 */
require_once __DIR__ . '/../includes/checklist.php';

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CAPTURA DE DADOS DO USUÁRIO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Dados do usuário da sessão
 * Definidos em do_login.php após autenticação bem-sucedida
 */
$user      = $_SESSION['user'] ?? [];
$userId    = (int)($user['id'] ?? 0);
$companyId = currentCompanyId();  // Empresa atualmente selecionada

/**
 * Avatar do usuário
 * Se não tiver foto definida, usa o avatar padrão
 */
$avatar = ($user['avatar_url'] ?? '') ?: url_for('/assets/img/avatar.svg');

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CARREGAMENTO DE TREINAMENTOS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Busca treinamentos acessíveis ao usuário
 * 
 * Filtrado por:
 * - Empresa atual (company_id)
 * - Cargos do usuário (roles)
 * - Status ativo (is_active = 1)
 */
$trainings = trainingsForUser($pdo, $userId, $companyId);

/**
 * Contadores de status dos treinamentos
 */
$totalTrainings = count($trainings);
$doneTrainings = $inProgressTrainings = $notStartedTrainings = 0;

/**
 * Totalizadores para o gráfico donut
 * Soma de todas as aulas de todos os treinamentos
 */
$totalVideosAll = 0;
$doneVideosAll  = 0;

/**
 * Lista de recompensas conquistadas
 * Imagens de badges/troféus dos treinamentos concluídos
 */
$rewards = [];

/**
 * Loop pelos treinamentos para calcular estatísticas
 */
foreach ($trainings as $t) {
    $tid = (int)$t['id'];
    
    /**
     * Obtém progresso do usuário neste treinamento
     * Retorna: ['percent', 'done', 'total', 'nextVideoId']
     */
    $progress = userTrainingProgress($pdo, $userId, $tid);
    $p = (int)($progress['percent'] ?? 0);

    // Acumula totais para o donut
    $totalVideosAll += (int)($progress['total'] ?? 0);
    $doneVideosAll  += (int)($progress['done']  ?? 0);

    // Categoriza o treinamento por status
    if ($p >= 100) {
        $doneTrainings++;
        // Se concluído, adiciona a recompensa (se houver)
        if (!empty($t['reward_image'])) {
            $rewards[] = $t['reward_image'];
        }
    } elseif ($p > 0) {
        $inProgressTrainings++;
    } else {
        $notStartedTrainings++;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CARREGAMENTO DE CHECKLISTS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Obtém totais de checklists do usuário
 * 
 * Retorna array com:
 * - overdue: tarefas em atraso
 * - today: pendentes de hoje (diárias)
 * - week: pendentes da semana (semanais/quinzenais)
 * - month: pendentes do mês (mensais)
 */
$totalsCL   = totalsForUser($pdo, $userId, $companyId);
$pendingCL  = (int)$totalsCL['today'] + (int)$totalsCL['week'] + (int)$totalsCL['month'];
$overdueCL  = (int)$totalsCL['overdue'];

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CARREGAMENTO DE CARGOS DO USUÁRIO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Busca os nomes dos cargos do usuário
 * Exibidos no hero do dashboard
 */
// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CARREGAMENTO DE CARGOS DO USUÁRIO
// ═══════════════════════════════════════════════════════════════════════════

$displayRoles = [];
$userType = $user['type'] ?? 'Colaborador';

// Se for Admin ou Gestor, define apenas o tipo para exibição
if ($userType === 'Admin' || $userType === 'Gestor') {
    $displayRoles = [$userType]; 
} else {
    try {
        // Para colaboradores comuns, busca a lista de cargos técnicos no banco
        $st = $pdo->prepare("SELECT r.name FROM roles r JOIN user_role ur ON ur.role_id=r.id WHERE ur.user_id=? ORDER BY r.name");
        $st->execute([$userId]);
        $displayRoles = array_column($st->fetchAll(PDO::FETCH_ASSOC), 'name');
    } catch(Throwable $e) {
        $displayRoles = ['Colaborador'];
    }
}
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ ESTILOS ESPECÍFICOS DO DASHBOARD                                      ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<style>
/* ═══════════════════════════════════════════════════════════════════════════
   HERO: Seção de destaque com perfil do usuário
   ═══════════════════════════════════════════════════════════════════════════ */
.hero {
    display: grid;
    grid-template-columns: 120px 1fr;  /* Foto | Informações */
    gap: 14px;
    align-items: center;
    border: 1px solid var(--stroke);
    border-radius: 16px;
    /* Gradiente laranja sutil de fundo */
    background: linear-gradient(135deg, rgba(255,106,0,.12), rgba(255,106,0,.06));
    padding: 14px;
}

/* Responsivo: foto menor em mobile */
@media(max-width:720px) {
    .hero { grid-template-columns: 80px 1fr; }
}

/* Foto de perfil no hero */
.hero .pic {
    width: 120px;
    height: 120px;
    border-radius: 22px;
    object-fit: cover;
    border: 2px solid var(--stroke);
    background: #0f1117;
}

@media(max-width:720px) {
    .hero .pic { width: 80px; height: 80px; border-radius: 16px; }
}

/* Nome do usuário */
.hero h2 { margin: 0; font-weight: 900; }

/* Cargos do usuário */
.hero .roles { color: #cbd5e1; }

/* Grid de recompensas */
.rewards { display: flex; gap: 8px; flex-wrap: wrap; }
.rewards img {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    border: 1px solid var(--stroke);
    object-fit: cover;
}

/* ═══════════════════════════════════════════════════════════════════════════
   KPIs: Cards de indicadores chave
   ═══════════════════════════════════════════════════════════════════════════ */
.kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(180px, 1fr));
    gap: 12px;
    margin-top: 14px;
}

/* Responsivo: 2 colunas em tablet */
@media(max-width:980px) {
    .kpis { grid-template-columns: repeat(2, 1fr); }
}

/* Card individual de KPI */
.kpi {
    padding: 14px;
    border-radius: 16px;
    border: 1px solid var(--stroke);
    display: flex;
    gap: 10px;
    align-items: center;
    background: rgba(255,255,255,.04);
}

/* Número grande do KPI */
.kpi .n { font-size: 1.8rem; font-weight: 900; }

/* Texto descritivo do KPI */
.kpi .t { color: #cbd5e1; font-size: .92rem; }

/* ═══════════════════════════════════════════════════════════════════════════
   LAYOUT DE 2 COLUNAS (Treinamentos | Donut + Checklists)
   ═══════════════════════════════════════════════════════════════════════════ */
.cols {
    display: grid;
    grid-template-columns: 1.1fr .9fr;
    gap: 12px;
    margin-top: 14px;
}

@media(max-width:980px) {
    .cols { grid-template-columns: 1fr; }
}

/* ═══════════════════════════════════════════════════════════════════════════
   CARDS GENÉRICOS
   ═══════════════════════════════════════════════════════════════════════════ */
.cardx {
    border: 1px solid var(--stroke);
    border-radius: 16px;
    background: rgba(255,255,255,.04);
    padding: 14px;
}

.section-title { margin: 0 0 8px; font-weight: 900; }

/* ═══════════════════════════════════════════════════════════════════════════
   GRID DE TREINAMENTOS
   ═══════════════════════════════════════════════════════════════════════════ */
.tgrid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 10px;
}

/* Card de treinamento individual */
.tcard {
    border: 1px solid var(--stroke);
    border-radius: 14px;
    overflow: hidden;
    background: rgba(255,255,255,.03);
}

.tcard img {
    width: 100%;
    height: 120px;
    object-fit: cover;
}

.tcard .inner { padding: 10px; }

/* Barra de progresso */
.progress {
    height: 8px;
    background: rgba(255,255,255,.08);
    border-radius: 999px;
    overflow: hidden;
}

.progress > span {
    display: block;
    height: 100%;
    background: linear-gradient(135deg, var(--brand), var(--brand-2));
}

/* ═══════════════════════════════════════════════════════════════════════════
   GRÁFICO DONUT
   ═══════════════════════════════════════════════════════════════════════════ */
.donut-wrap { display: flex; gap: 16px; align-items: center; }
.donut canvas { width: 140px; height: 140px; }
.donut-legend { display: flex; flex-direction: column; gap: 6px; }

/* Badge com bolinha colorida */
.badge-dot { display: inline-flex; align-items: center; gap: 8px; }
.badge-dot .dot { width: 12px; height: 12px; border-radius: 50%; }

.small { color: #9aa4b2; }
</style>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ HERO: Seção de perfil do usuário                                      ║
     ║                                                                        ║
     ║ Exibe: foto, nome, cargos e recompensas conquistadas                  ║
     ║ Acesso: Todos os usuários (veem seus próprios dados)                  ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<div class="hero">
    <!-- Foto de perfil -->
    <img class="pic" src="<?= htmlspecialchars($avatar) ?>" alt="Perfil">
    
    <div>
        <!-- Nome do usuário -->
        <h2><?= htmlspecialchars($user['name'] ?? 'Colaborador') ?></h2>
        
        <!-- Cargos do usuário -->
        <div class="roles">
            <?= !empty($displayRoles) ? htmlspecialchars(implode(' • ', $displayRoles)) : '—' ?>  <!-- so troquei roles por display roles-->
        </div>
        
        <!-- Recompensas conquistadas -->
        <?php if (!empty($rewards)): ?>
            <div class="small" style="margin:6px 0 4px">Recompensas conquistadas</div>
            <div class="rewards">
                <?php foreach ($rewards as $src): ?>
                    <img src="<?= htmlspecialchars($src) ?>" alt="reward">
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="small" style="margin-top:6px">
                Sem recompensas ainda — bora conquistar! 🚀
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ KPIs: Indicadores principais                                          ║
     ║                                                                        ║
     ║ Mostra: pendências, atrasos, total de treinamentos, concluídos        ║
     ║ Acesso: Todos os usuários (dados próprios)                            ║
     ║ Escopo: Empresa atual                                                 ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<div class="kpis">
    <!-- KPI: Atividades pendentes (checklists) -->
    <div class="kpi">
        <div class="n"><?= $pendingCL ?></div>
        <div>
            <div><strong>Atividades pendentes</strong></div>
            <div class="t">Checklists de hoje/semana/mês</div>
        </div>
    </div>
    
    <!-- KPI: Atividades em atraso (link para ver detalhes) -->
    <a class="kpi" href="<?= url_for('/pages/checklists.php') ?>?f=overdue" style="text-decoration:none">
        <div class="n"><?= $overdueCL ?></div>
        <div>
            <div><strong>Atividades em atraso</strong></div>
            <div class="t">Ver pendências</div>
        </div>
    </a>
    
    <!-- KPI: Total de treinamentos disponíveis -->
    <div class="kpi">
        <div class="n"><?= $totalTrainings ?></div>
        <div>
            <div><strong>Treinamentos disponíveis</strong></div>
            <div class="t">Nesta organização</div>
        </div>
    </div>
    
    <!-- KPI: Treinamentos concluídos -->
    <div class="kpi">
        <div class="n"><?= $doneTrainings ?></div>
        <div>
            <div><strong>Concluídos</strong></div>
            <div class="t">Você finalizou <?= $doneTrainings ?> trilha(s)</div>
        </div>
    </div>
</div>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ LAYOUT DE 2 COLUNAS                                                   ║
     ║                                                                        ║
     ║ Coluna 1: Grid de treinamentos com progresso                          ║
     ║ Coluna 2: Gráfico donut + resumo de checklists                        ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<div class="cols">
    
    <!-- ════════════════════════════════════════════════════════════════════
         COLUNA ESQUERDA: Treinamentos
         ════════════════════════════════════════════════════════════════════ -->
    <div class="cardx">
        <h3 class="section-title">Seu avanço em treinamentos</h3>

        <!-- Sub-KPIs de treinamentos -->
        <div class="kpis" style="grid-template-columns:repeat(3,1fr);margin-top:4px">
            <div class="kpi">
                <div class="n"><?= $inProgressTrainings ?></div>
                <div>
                    <div><strong>Em andamento</strong></div>
                    <div class="t">continue de onde parou</div>
                </div>
            </div>
            <div class="kpi">
                <div class="n"><?= $notStartedTrainings ?></div>
                <div>
                    <div><strong>Não iniciado</strong></div>
                    <div class="t">que tal começar um novo?</div>
                </div>
            </div>
            <div class="kpi">
                <div class="n"><?= $doneTrainings ?></div>
                <div>
                    <div><strong>Concluídos</strong></div>
                    <div class="t">parabéns! 💪</div>
                </div>
            </div>
        </div>

        <!-- Grid de cards de treinamentos -->
        <?php if (empty($trainings)): ?>
            <div class="cardx" style="margin-top:10px">
                Nenhum treinamento disponível para seus cargos.
            </div>
        <?php else: ?>
            <div class="tgrid" style="margin-top:12px">
                <?php foreach ($trainings as $t):
                    $pr = userTrainingProgress($pdo, $userId, (int)$t['id']);
                    $pct = (int)($pr['percent'] ?? 0);
                ?>
                <div class="tcard">
                    <!-- Capa do treinamento -->
                    <img src="<?= htmlspecialchars($t['cover_image'] ?: url_for('/assets/img/login_hero.svg')) ?>" alt="">
                    
                    <div class="inner">
                        <!-- Título -->
                        <div style="font-weight:800"><?= htmlspecialchars($t['title']) ?></div>
                        
                        <!-- Objetivo -->
                        <div class="small" style="margin:2px 0">
                            <?= htmlspecialchars($t['objective'] ?? '') ?>
                        </div>
                        
                        <!-- Barra de progresso -->
                        <div class="progress" style="margin-top:8px">
                            <span style="width:<?= $pct ?>%"></span>
                        </div>
                        
                        <!-- Porcentagem e botão de ação -->
                        <div class="small" style="display:flex;justify-content:space-between;margin-top:4px">
                            <span><?= $pct ?>%</span>
                            <a class="button ghost" href="<?= url_for('/pages/treinamento.php') ?>?id=<?= (int)$t['id'] ?>">
                                <?= $pct > 0 && $pct < 100 ? 'Continuar' : ($pct >= 100 ? 'Revisar' : 'Começar') ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════
         COLUNA DIREITA: Donut + Checklists
         ════════════════════════════════════════════════════════════════════ -->
    <div class="cardx">
        <h3 class="section-title">Seu ritmo de aprendizagem</h3>

        <!-- Gráfico Donut com legenda -->
        <div class="donut-wrap" style="margin-top:6px">
            <!-- Canvas do gráfico (renderizado via JavaScript) -->
            <div class="donut">
                <canvas id="donutCanvas" width="180" height="180"></canvas>
            </div>
            
            <!-- Legenda do gráfico -->
            <div class="donut-legend">
                <div class="badge-dot">
                    <span class="dot" style="background:#00e0a4"></span>
                    Aulas concluídas: <strong><?= $doneVideosAll ?></strong>
                </div>
                <div class="badge-dot">
                    <span class="dot" style="background:#2b3245"></span>
                    Aulas pendentes: <strong><?= max(0, $totalVideosAll - $doneVideosAll) ?></strong>
                </div>
                <div class="small">
                    Total de aulas nos seus treinamentos: <?= $totalVideosAll ?>
                </div>
                <a class="button" href="<?= url_for('/pages/treinamentos.php') ?>" style="margin-top:6px">
                    Ver treinamentos
                </a>
            </div>
        </div>

        <!-- Resumo de Checklists -->
        <div class="cardx" style="margin-top:12px">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <h4 style="margin:0;font-weight:900">Checklists</h4>
                <a class="button ghost" href="<?= url_for('/pages/checklists.php') ?>">Abrir checklists</a>
            </div>
            
            <div class="kpis" style="grid-template-columns:repeat(2,1fr);margin-top:8px">
                <!-- Atrasos -->
                <a class="kpi" href="<?= url_for('/pages/checklists.php') ?>?f=overdue" style="text-decoration:none">
                    <div class="n"><?= $overdueCL ?></div>
                    <div>
                        <div><strong>Em atraso</strong></div>
                        <div class="t">corrija já</div>
                    </div>
                </a>
                
                <!-- Pendentes -->
                <div class="kpi">
                    <div class="n"><?= $pendingCL ?></div>
                    <div>
                        <div><strong>Pendentes</strong></div>
                        <div class="t">para hoje/semana/mês</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ JAVASCRIPT: Renderização do gráfico Donut                             ║
     ║                                                                        ║
     ║ Desenha um gráfico circular usando Canvas API puro                    ║
     ║ Sem dependência de bibliotecas externas (Chart.js, etc.)              ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<script>
/**
 * IIFE para renderizar o gráfico donut
 * Executado imediatamente quando a página carrega
 */
(function(){
    // Dados do PHP injetados no JavaScript
    const done = <?= (int)$doneVideosAll ?>;     // Aulas concluídas
    const total = <?= (int)$totalVideosAll ?>;   // Total de aulas
    const pend = Math.max(0, total - done);      // Aulas pendentes

    // Obtém o elemento canvas
    const cv = document.getElementById('donutCanvas');
    if (!cv) return;  // Sai se não encontrar

    // Obtém o contexto 2D para desenho
    const ctx = cv.getContext('2d');
    
    // Calcula centro e raio do círculo
    const cx = cv.width / 2;   // Centro X
    const cy = cv.height / 2;  // Centro Y
    const r = Math.min(cx, cy) - 8;  // Raio (com margem)

    /**
     * Função auxiliar para desenhar um arco
     * @param {number} start - Ângulo inicial (radianos)
     * @param {number} value - Valor a representar
     * @param {string} color - Cor do arco
     * @returns {number} - Ângulo final (para encadear arcos)
     */
    function arc(start, value, color) {
        const frac = total > 0 ? value / total : 0;  // Fração do total
        const end = start + frac * Math.PI * 2;       // Ângulo final

        ctx.beginPath();
        ctx.arc(cx, cy, r, start, end);
        ctx.strokeStyle = color;
        ctx.lineWidth = 22;      // Espessura do arco
        ctx.lineCap = 'round';   // Pontas arredondadas
        ctx.stroke();

        return end;
    }

    // Desenha o fundo (círculo cinza)
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.strokeStyle = '#2b3245';
    ctx.lineWidth = 22;
    ctx.stroke();

    // Desenha o arco de progresso (verde)
    let a = -Math.PI / 2;  // Começa no topo (12h)
    a = arc(a, done, '#00e0a4');

    // Desenha a porcentagem no centro
    ctx.fillStyle = '#e8edf7';
    ctx.font = '700 16px Inter, system-ui, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    const pct = total > 0 ? Math.round(done / total * 100) : 0;
    ctx.fillText(pct + '%', cx, cy);
})();
</script>

<?php 
/**
 * Fecha as tags HTML abertas em layout_start.php
 */
require_once __DIR__ . '/../includes/layout_end.php'; 
?>
