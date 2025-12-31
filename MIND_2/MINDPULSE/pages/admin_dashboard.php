<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ ADMIN_DASHBOARD.PHP — Painel Administrativo com KPIs e Gráficos          ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INCLUSÃO DE DEPENDÊNCIAS E VERIFICAÇÃO DE ACESSO
// ═══════════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../includes/layout_start.php';
require_once __DIR__ . '/../includes/auth.php';

// Verifica permissão (Admin ou Gestor)
if (!canAccessManager()) { 
    http_response_code(403); 
    echo '<div class="card" style="padding:20px">Acesso negado</div>'; 
    require_once __DIR__ . '/../includes/layout_end.php'; 
    exit; 
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES AUXILIARES DE CONSULTA
// ═══════════════════════════════════════════════════════════════════════════

function qval(PDO $pdo, string $sql, array $p = [], $def = 0) {
    try { 
        $st = $pdo->prepare($sql); 
        $st->execute($p); 
        return (int)$st->fetchColumn(); 
    } catch(Throwable $e) { 
        return (int)$def; 
    }
}

function qall(PDO $pdo, string $sql, array $p = []) {
    try { 
        $st = $pdo->prepare($sql); 
        $st->execute($p); 
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: []; 
    } catch(Throwable $e) { 
        return []; 
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// LÓGICA DE FILTRO (ADMIN vs GESTOR)
// ═══════════════════════════════════════════════════════════════════════════

$isGlobal = isAdmin(); // true = Admin Geral, false = Gestor
$sqlWhere = "";        // Filtro SQL
$sqlParams = [];       // Parâmetros SQL

if (!$isGlobal) {
    // Busca ID da empresa do gestor
    $uid = $_SESSION['user']['id'];
    $stmt = $pdo->prepare("SELECT company_id FROM user_company WHERE user_id = ? LIMIT 1");
    $stmt->execute([$uid]);
    $myCid = $stmt->fetchColumn();

    if ($myCid) {
        $sqlWhere  = " WHERE company_id = ? ";
        $sqlParams = [$myCid];
    } else {
        $sqlWhere  = " WHERE 1=0 "; // Trava de segurança
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: COLETA DE DADOS - TOTAIS (KPIs)
// ═══════════════════════════════════════════════════════════════════════════

if ($isGlobal) {
    // Admin: conta tudo
    $totCompanies = qval($pdo, "SELECT COUNT(*) FROM companies");
    $totUsers     = qval($pdo, "SELECT COUNT(*) FROM users");
} else {
    // Gestor: conta só dele
    $totCompanies = 1; 
    $totUsers     = qval($pdo, "SELECT COUNT(*) FROM user_company $sqlWhere", $sqlParams);
}

// Checklists e Treinos (usam filtro padrão)
$totChecklists = qval($pdo, "SELECT COUNT(*) FROM checklists $sqlWhere", $sqlParams);
$totTrainings  = qval($pdo, "SELECT COUNT(*) FROM trainings $sqlWhere", $sqlParams);

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: COLETA DE DADOS - GRÁFICO
// ═══════════════════════════════════════════════════════════════════════════

$months = [];
$start  = new DateTime('first day of -11 months');
for ($i = 0; $i < 12; $i++) { 
    $m = (clone $start)->modify("+$i month"); 
    $months[] = $m->format('Y-m'); 
}

function monthlySeriesFiltered(PDO $pdo, string $table, string $where, array $params) {
    $clause = "";
    if ($where) {
        $clause = " AND " . substr($where, 6); // Transforma WHERE em AND
    }

    $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') ym, COUNT(id) n 
            FROM $table 
            WHERE 1=1 $clause 
            GROUP BY ym";
            
    $rows = qall($pdo, $sql, $params);
    $map = []; 
    foreach ($rows as $r) { 
        if (!empty($r['ym'])) $map[$r['ym']] = (int)$r['n']; 
    }
    return $map;
}

// Admin vê gráfico de empresas, Gestor vê vazio
$seriesCompanies = ($isGlobal) 
    ? monthlySeriesFiltered($pdo, 'companies', "", []) 
    : []; 

$seriesTrainings = monthlySeriesFiltered($pdo, 'trainings', $sqlWhere, $sqlParams);

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: RANKINGS
// ═══════════════════════════════════════════════════════════════════════════

$userJoin  = "";
$userWhere = "";
$rankingParams = [];

if (!$isGlobal && isset($myCid)) {
    // Gestor: Join para filtrar usuários da empresa dele
    $userJoin  = " JOIN user_company uc ON uc.user_id = u.id ";
    $userWhere = " WHERE uc.company_id = ? ";
    $rankingParams = [$myCid];
}

// 1. Ranking Usuários
$rankUsers = qall($pdo, "
    SELECT u.id, u.name, u.avatar_url, COUNT(ur.id) rewards
    FROM users u
    $userJoin
    LEFT JOIN user_rewards ur ON ur.user_id = u.id
    $userWhere
    GROUP BY u.id
    HAVING rewards > 0
    ORDER BY rewards DESC, u.name ASC
    LIMIT 10
", $rankingParams);

if (empty($rankUsers)) {
    // Fallback se não tiver recompensas
    $rankUsers = qall($pdo, "
        SELECT u.id, u.name, u.avatar_url, COUNT(utd.training_id) rewards
        FROM users u
        $userJoin
        JOIN user_training_done utd ON utd.user_id = u.id
        $userWhere
        GROUP BY u.id
        ORDER BY rewards DESC, u.name ASC
        LIMIT 10
    ", $rankingParams);
}

// 2. Ranking Empresas / Total Treinamentos
$compWhere = $isGlobal ? "" : " WHERE c.id = ? ";

$rankCompaniesTrain = qall($pdo, "
    SELECT c.id, c.name, COUNT(t.id) trainings
    FROM companies c
    LEFT JOIN trainings t ON t.company_id = c.id
    $compWhere
    GROUP BY c.id
    ORDER BY trainings DESC, c.name ASC
    LIMIT 10
", $sqlParams);

// 3. Top 5 Checklists
$top5Checklist = qall($pdo, "
    SELECT c.id, c.name, COUNT(ch.id) qnt
    FROM companies c
    LEFT JOIN checklists ch ON ch.company_id = c.id
    $compWhere
    GROUP BY c.id
    ORDER BY qnt DESC, c.name ASC
    LIMIT 5
", $sqlParams);

// Prepara JS
$labels   = $months;
$barVals  = array_map(fn($ym) => $seriesCompanies[$ym] ?? 0, $months);
$lineVals = array_map(fn($ym) => $seriesTrainings[$ym] ?? 0, $months);
?>

<style>
/* Grid Layout */
.dash-grid{ display: grid; grid-template-columns: 1.25fr .75fr; gap: 12px; }
@media(max-width:1100px){ .dash-grid{ grid-template-columns: 1fr } }

/* KPIs */
.kpis{ display: grid; grid-template-columns: repeat(4, minmax(180px, 1fr)); gap: 12px; margin-bottom: 12px; }
@media(max-width:1100px){ .kpis{ grid-template-columns: repeat(2, 1fr) } }
.kpi{ padding: 14px; border-radius: 16px; border: 1px solid var(--stroke); display: flex; gap: 10px; align-items: center; background: linear-gradient(135deg, rgba(255,106,0,.12), rgba(255,106,0,.06)); }
.kpi .n{ font-size: 1.8rem; font-weight: 900 }
.kpi .t{ color: #cbd5e1 }

/* Cards & Elements */
.cardx{ border: 1px solid var(--stroke); border-radius: 16px; background: rgba(255,255,255,.04); padding: 14px; }
.title{ margin: 0 0 8px; font-weight: 900 }
.small{ color: #9aa4b2 }
#chart{ width: 100%; height: 320px; display: block; }
.rank{ display: flex; flex-direction: column; gap: 8px; }
.r-item{ display: grid; grid-template-columns: 40px 1fr 72px; gap: 10px; align-items: center; border: 1px solid var(--stroke); background: rgba(255,255,255,.03); border-radius: 12px; padding: 8px; }
.r-item img{ width: 40px; height: 40px; border-radius: 12px; object-fit: cover; border: 1px solid var(--stroke); }
.badge{ border: 1px solid var(--stroke); border-radius: 999px; padding: 4px 10px; }
.table{ width: 100%; border-collapse: separate; border-spacing: 0 10px; }
.tr{ display: grid; grid-template-columns: 1fr 140px; gap: 10px; align-items: center; border: 1px solid var(--stroke); border-radius: 12px; background: linear-gradient(135deg, rgba(255,255,255,.03), rgba(255,255,255,.04)); padding: 10px; }
.progress{ height: 8px; background: rgba(255,255,255,.08); border-radius: 999px; overflow: hidden; }
.progress > span{ display: block; height: 100%; background: linear-gradient(135deg, #ff6a00, #ff9153); }
</style>

<h2 style="margin:0 0 8px; font-weight:900">
    <?= $isGlobal ? 'Painel do Administrador' : 'Dashboard da Empresa' ?>
</h2>

<div class="kpis">
    <div class="kpi">
        <div class="n"><?= $totCompanies ?></div>
        <div>
            <div><strong><?= $isGlobal ? 'Empresas' : 'Minha Empresa' ?></strong></div>
            <div class="t">cadastradas</div>
        </div>
    </div>
    
    <div class="kpi">
        <div class="n"><?= $totUsers ?></div>
        <div>
            <div><strong>Colaboradores</strong></div>
            <div class="t">contas ativas</div>
        </div>
    </div>
    
    <div class="kpi">
        <div class="n"><?= $totChecklists ?></div>
        <div>
            <div><strong>Checklists</strong></div>
            <div class="t">totais</div>
        </div>
    </div>
    
    <div class="kpi">
        <div class="n"><?= $totTrainings ?></div>
        <div>
            <div><strong>Treinamentos</strong></div>
            <div class="t">publicados</div>
        </div>
    </div>
</div>

<div class="dash-grid">
    
    <div class="cardx">
        <h3 class="title">
            <?= $isGlobal ? 'Crescimento — Empresas × Treinamentos' : 'Evolução dos Treinamentos' ?>
        </h3>
        <canvas id="chart" width="900" height="320"></canvas>
        <div class="small" style="margin-top:6px">
            <?php if($isGlobal): ?>Barras: novas empresas / mês • <?php endif; ?>
            Linha: novos treinamentos / mês
        </div>
    </div>

    <div class="cardx">
        <h3 class="title">Rankings</h3>
        <div style="display:grid; grid-template-columns:1fr; gap:12px">
            
            <div>
                <div class="small" style="margin-bottom:6px">
                    <strong><?= $isGlobal ? 'Colaboradores com mais recompensas' : 'Meus Destaques (Equipe)' ?></strong>
                </div>
                <div class="rank">
                    <?php if (empty($rankUsers)): ?>
                        <div class="small">Sem dados suficientes.</div>
                    <?php else: 
                        $i = 1; 
                        foreach ($rankUsers as $u): 
                    ?>
                        <div class="r-item">
                            <img src="<?= htmlspecialchars(($u['avatar_url'] ?? '') ?: url_for('/assets/img/avatar.svg')) ?>" alt="">
                            <div><strong>#<?= $i++ ?></strong> — <?= htmlspecialchars($u['name']) ?></div>
                            <div class="badge"><?= (int)($u['rewards'] ?? 0) ?> 🏅</div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <div>
                <div class="small" style="margin:10px 0 6px">
                    <strong><?= $isGlobal ? 'Empresas com mais treinamentos' : 'Total de Treinamentos' ?></strong>
                </div>
                <div class="rank">
                    <?php if (empty($rankCompaniesTrain)): ?>
                        <div class="small">Sem dados suficientes.</div>
                    <?php else: 
                        $i = 1; 
                        foreach ($rankCompaniesTrain as $c): 
                    ?>
                        <div class="r-item">
                            <div style="width:40px; height:40px; border-radius:12px; border:1px solid var(--stroke); display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,.03)">
                                <strong><?= $i++ ?></strong>
                            </div>
                            <div><?= htmlspecialchars($c['name']) ?></div>
                            <div class="badge"><?= (int)$c['trainings'] ?> 🎓</div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="cardx" style="margin-top:12px">
    <h3 class="title">
        <?= $isGlobal ? 'Top 5 empresas com mais checklists' : 'Volume de Checklists' ?>
    </h3>
    
    <?php if (empty($top5Checklist)): ?>
        <div class="small">Sem dados suficientes.</div>
    <?php else:
        $max = max(array_map(fn($r) => (int)$r['qnt'], $top5Checklist)) ?: 1;
        
        foreach ($top5Checklist as $row):
            $pct = (int)round(((int)$row['qnt'] / $max) * 100);
    ?>
        <div class="tr">
            <div>
                <div style="font-weight:800"><?= htmlspecialchars($row['name']) ?></div>
                <div class="progress" style="margin-top:6px">
                    <span style="width:<?= $pct ?>%"></span>
                </div>
            </div>
            <div style="text-align:right">
                <span class="badge"><?= (int)$row['qnt'] ?> ✅</span>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>

<script>
const labels   = <?= json_encode($labels) ?>;
const barVals  = <?= json_encode($barVals) ?>;
const lineVals = <?= json_encode($lineVals) ?>;
const isGlobal = <?= json_encode($isGlobal) ?>; // Passa flag para o JS

(function(){
    const cv = document.getElementById('chart');
    const ctx = cv.getContext('2d');
    const W = cv.width, H = cv.height;
    const padL = 48, padR = 24, padT = 14, padB = 36;
    const innerW = W - padL - padR;
    const innerH = H - padT - padB;

    const maxY = Math.max(1, ...barVals, ...lineVals);
    const stepY = Math.max(1, Math.ceil(maxY / 4));

    // Fundo
    ctx.fillStyle = 'rgba(255,255,255,0.03)'; 
    ctx.fillRect(padL, padT, innerW, innerH);
    ctx.strokeStyle = 'rgba(255,255,255,0.12)'; 
    ctx.lineWidth = 1;

    // Grid Y
    ctx.font = '12px Inter, system-ui, sans-serif'; 
    ctx.fillStyle = '#9aa4b2';
    for (let y = 0; y <= maxY; y += stepY) {
        const yy = padT + innerH - (y / maxY) * innerH;
        ctx.beginPath(); ctx.moveTo(padL, yy); ctx.lineTo(padL + innerW, yy); ctx.stroke();
        ctx.fillText(String(y), 8, yy + 4);
    }

    const n = labels.length;
    const barW = innerW / n * 0.55;
    const gap = innerW / n;

    // Barras (Só desenha se for global/admin)
    if (isGlobal) {
        for (let i = 0; i < n; i++) {
            const x = padL + i * gap + (gap - barW) / 2;
            const v = barVals[i] || 0;
            const h = (v / maxY) * innerH;
            const y = padT + innerH - h;
            ctx.fillStyle = 'rgba(255,106,0,0.35)'; ctx.fillRect(x, y, barW, h);
            ctx.strokeStyle = 'rgba(255,106,0,0.6)'; ctx.strokeRect(x, y, barW, h);
        }
    }

    // Linha
    ctx.beginPath();
    for (let i = 0; i < n; i++) {
        const x = padL + i * gap + gap / 2;
        const v = lineVals[i] || 0;
        const y = padT + innerH - (v / maxY) * innerH;
        if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
    }
    ctx.strokeStyle = '#ff9153'; ctx.lineWidth = 2; ctx.stroke();

    // Pontos
    for (let i = 0; i < n; i++) {
        const x = padL + i * gap + gap / 2;
        const v = lineVals[i] || 0;
        const y = padT + innerH - (v / maxY) * innerH;
        ctx.fillStyle = '#ff9153'; ctx.beginPath(); ctx.arc(x, y, 3, 0, Math.PI * 2); ctx.fill();
    }

    // Eixo X
    ctx.fillStyle = '#9aa4b2'; ctx.textAlign = 'center';
    labels.forEach((lb, i) => {
        const x = padL + i * gap + gap / 2;
        ctx.fillText(lb, x, H - 12);
    });

    // Legenda no Canvas
    ctx.textAlign = 'left'; 
    
    // Só mostra legenda de Barras se for Admin
    if (isGlobal) {
        ctx.fillStyle = '#e8edf7';
        ctx.fillText('Barras: Empresas', padL, padT - 2);
    }
    
    ctx.fillStyle = '#ff9153'; 
    // Ajusta posição da legenda da linha dependendo se a outra existe
    const xPos = isGlobal ? padL + 140 : padL;
    ctx.fillText('— Treinamentos', xPos, padT - 2);
})();
</script>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>