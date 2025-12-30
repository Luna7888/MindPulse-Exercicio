<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ ADMIN_DASHBOARD.PHP — Painel Administrativo com KPIs e Gráficos          ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Fornecer visão gerencial da plataforma para admins        ║
 * ║                com KPIs, gráficos de crescimento e rankings              ║
 * ║                                                                           ║
 * ║ @acesso        Admin Geral | Gestor (canAccessAdmin)                     ║
 * ║ @escopo        Global (dados de todas as empresas)                       ║
 * ║                                                                           ║
 * ║ @exibe         - KPIs: total de empresas, usuários, checklists, treinos  ║
 * ║                - Gráfico: crescimento mensal (barras + linha)            ║
 * ║                - Rankings: colaboradores com mais recompensas            ║
 * ║                - Rankings: empresas com mais treinamentos                ║
 * ║                - Top 5: empresas com mais checklists                     ║
 * ║                                                                           ║
 * ║ @gráfico       Canvas puro (sem bibliotecas externas)                    ║
 * ║                - Barras: novas empresas por mês                          ║
 * ║                - Linha: novos treinamentos por mês                       ║
 * ║                                                                           ║
 * ║ @dependências  layout_start.php, auth.php                                ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INCLUSÃO DE DEPENDÊNCIAS E VERIFICAÇÃO DE ACESSO
// ═══════════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../includes/layout_start.php';
require_once __DIR__ . '/../includes/auth.php';

/**
 * Verifica permissão de acesso administrativo
 * 
 * canAccessAdmin() retorna true para Admin Geral e Gestor
 * Se não tiver permissão, exibe mensagem de erro e encerra
 */
if (!canAccessAdmin()) { 
    http_response_code(403); 
    echo '<div class="card" style="padding:20px">Acesso negado</div>'; 
    require_once __DIR__ . '/../includes/layout_end.php'; 
    exit; 
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES AUXILIARES DE CONSULTA
// ═══════════════════════════════════════════════════════════════════════════

/**
 * qval() — Executa query e retorna um valor único (COUNT, SUM, etc.)
 * 
 * @param PDO $pdo Conexão com o banco
 * @param string $sql Query SQL (deve retornar uma única coluna)
 * @param array $p Parâmetros para prepared statement
 * @param mixed $def Valor padrão em caso de erro
 * @return int Valor retornado pela query ou default
 * 
 * @tolerância
 * Captura exceções silenciosamente para não quebrar o dashboard
 * se alguma tabela não existir ou houver erro de schema
 */
function qval(PDO $pdo, string $sql, array $p = [], $def = 0) {
    try { 
        $st = $pdo->prepare($sql); 
        $st->execute($p); 
        return (int)$st->fetchColumn(); 
    } catch(Throwable $e) { 
        return (int)$def; 
    }
}

/**
 * qall() — Executa query e retorna todas as linhas
 * 
 * @param PDO $pdo Conexão com o banco
 * @param string $sql Query SQL
 * @param array $p Parâmetros para prepared statement
 * @return array Array de resultados ou array vazio em caso de erro
 * 
 * @tolerância
 * Retorna array vazio em caso de erro (não quebra a página)
 */
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
// SEÇÃO: COLETA DE DADOS - TOTAIS (KPIs)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Totais simples para os cards de KPI
 * Cada query conta registros de uma tabela específica
 */
$totCompanies  = qval($pdo, "SELECT COUNT(*) FROM companies");
$totUsers      = qval($pdo, "SELECT COUNT(*) FROM users");
$totChecklists = qval($pdo, "SELECT COUNT(*) FROM checklists");
$totTrainings  = qval($pdo, "SELECT COUNT(*) FROM trainings");

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: COLETA DE DADOS - SÉRIES MENSAIS (GRÁFICO)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Gera array com os últimos 12 meses no formato 'YYYY-MM'
 * Usado como labels do eixo X do gráfico
 */
$months = [];
$start  = new DateTime('first day of -11 months');
for ($i = 0; $i < 12; $i++) { 
    $m = (clone $start)->modify("+$i month"); 
    $months[] = $m->format('Y-m'); 
}

/**
 * monthlySeries() — Agrupa registros por mês de criação
 * 
 * @param PDO $pdo Conexão
 * @param string $table Nome da tabela
 * @param string $dateCol Coluna de data (padrão: created_at)
 * @param string $idCol Coluna de ID para contagem
 * @return array Mapa [YYYY-MM => quantidade]
 * 
 * @exemplo
 * monthlySeries($pdo, 'companies') 
 * // ['2025-01' => 5, '2025-02' => 3, ...]
 */
function monthlySeries(PDO $pdo, string $table, string $dateCol = 'created_at', string $idCol = 'id') {
    $rows = qall($pdo, "SELECT DATE_FORMAT($dateCol, '%Y-%m') ym, COUNT($idCol) n FROM $table GROUP BY ym");
    $map = []; 
    foreach ($rows as $r) { 
        if (!empty($r['ym'])) {
            $map[$r['ym']] = (int)$r['n']; 
        }
    }
    return $map;
}

/**
 * Séries para o gráfico
 * - seriesCompanies: novas empresas por mês
 * - seriesTrainings: novos treinamentos por mês
 */
$seriesCompanies = monthlySeries($pdo, 'companies', 'created_at', 'id');
$seriesTrainings = monthlySeries($pdo, 'trainings', 'created_at', 'id');

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: COLETA DE DADOS - RANKINGS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Ranking: Colaboradores com mais recompensas
 * 
 * Tenta primeiro a tabela user_rewards
 * Se não existir ou estiver vazia, faz fallback para user_training_done
 * 
 * @tolerância
 * Funciona mesmo se as tabelas não existirem
 */
$rankUsers = qall($pdo, "
    SELECT u.id, u.name, u.avatar_url, COUNT(ur.id) rewards
    FROM user_rewards ur
    JOIN users u ON u.id = ur.user_id
    GROUP BY u.id
    ORDER BY rewards DESC, u.name ASC
    LIMIT 10
");

// Fallback: se user_rewards está vazio, tenta user_training_done
if (empty($rankUsers)) {
    $rankUsers = qall($pdo, "
        SELECT u.id, u.name, u.avatar_url, COUNT(utd.training_id) rewards
        FROM users u
        JOIN user_training_done utd ON utd.user_id = u.id
        GROUP BY u.id
        ORDER BY rewards DESC, u.name ASC
        LIMIT 10
    ");
}

/**
 * Ranking: Empresas com mais treinamentos
 * 
 * LEFT JOIN garante que empresas sem treinamentos também apareçam
 */
$rankCompaniesTrain = qall($pdo, "
    SELECT c.id, c.name, COUNT(t.id) trainings
    FROM companies c
    LEFT JOIN trainings t ON t.company_id = c.id
    GROUP BY c.id
    ORDER BY trainings DESC, c.name ASC
    LIMIT 10
");

/**
 * Top 5: Empresas com mais checklists
 * 
 * Usado na seção de barras de progresso
 */
$top5Checklist = qall($pdo, "
    SELECT c.id, c.name, COUNT(ch.id) qnt
    FROM companies c
    LEFT JOIN checklists ch ON ch.company_id = c.id
    GROUP BY c.id
    ORDER BY qnt DESC, c.name ASC
    LIMIT 5
");

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: PREPARAÇÃO DE DADOS PARA JAVASCRIPT
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Converte os dados PHP para arrays JavaScript
 * 
 * labels: meses para o eixo X
 * barVals: valores das barras (empresas)
 * lineVals: valores da linha (treinamentos)
 */
$labels   = $months;
$barVals  = array_map(fn($ym) => $seriesCompanies[$ym] ?? 0, $months);
$lineVals = array_map(fn($ym) => $seriesTrainings[$ym] ?? 0, $months);
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ ESTILOS ESPECÍFICOS DO DASHBOARD ADMIN                                ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<style>
/* ═══════════════════════════════════════════════════════════════════════════
   LAYOUT PRINCIPAL: Grid de 2 colunas
   ═══════════════════════════════════════════════════════════════════════════ */
.dash-grid{
    display: grid; 
    grid-template-columns: 1.25fr .75fr; 
    gap: 12px;
}

@media(max-width:1100px){
    .dash-grid{ grid-template-columns: 1fr }
}

/* ═══════════════════════════════════════════════════════════════════════════
   KPIs: Cards de indicadores principais
   ═══════════════════════════════════════════════════════════════════════════ */
.kpis{
    display: grid; 
    grid-template-columns: repeat(4, minmax(180px, 1fr)); 
    gap: 12px; 
    margin-bottom: 12px;
}

@media(max-width:1100px){
    .kpis{ grid-template-columns: repeat(2, 1fr) }
}

.kpi{
    padding: 14px; 
    border-radius: 16px; 
    border: 1px solid var(--stroke); 
    display: flex; 
    gap: 10px; 
    align-items: center;
    background: linear-gradient(135deg, rgba(255,106,0,.12), rgba(255,106,0,.06));
}

.kpi .n{ font-size: 1.8rem; font-weight: 900 }
.kpi .t{ color: #cbd5e1 }

/* ═══════════════════════════════════════════════════════════════════════════
   CARDS GENÉRICOS
   ═══════════════════════════════════════════════════════════════════════════ */
.cardx{
    border: 1px solid var(--stroke); 
    border-radius: 16px; 
    background: rgba(255,255,255,.04); 
    padding: 14px;
}

.title{ margin: 0 0 8px; font-weight: 900 }
.small{ color: #9aa4b2 }

/* ═══════════════════════════════════════════════════════════════════════════
   GRÁFICO CANVAS
   ═══════════════════════════════════════════════════════════════════════════ */
#chart{
    width: 100%; 
    height: 320px; 
    display: block;
}

/* ═══════════════════════════════════════════════════════════════════════════
   RANKINGS
   ═══════════════════════════════════════════════════════════════════════════ */
.rank{
    display: flex; 
    flex-direction: column; 
    gap: 8px;
}

.r-item{
    display: grid; 
    grid-template-columns: 40px 1fr 72px; 
    gap: 10px; 
    align-items: center; 
    border: 1px solid var(--stroke);
    background: rgba(255,255,255,.03); 
    border-radius: 12px; 
    padding: 8px;
}

.r-item img{
    width: 40px; 
    height: 40px; 
    border-radius: 12px; 
    object-fit: cover; 
    border: 1px solid var(--stroke);
}

.badge{
    border: 1px solid var(--stroke); 
    border-radius: 999px; 
    padding: 4px 10px;
}

/* ═══════════════════════════════════════════════════════════════════════════
   TABELA TOP 5 (barras de progresso)
   ═══════════════════════════════════════════════════════════════════════════ */
.table{
    width: 100%; 
    border-collapse: separate; 
    border-spacing: 0 10px;
}

.tr{
    display: grid; 
    grid-template-columns: 1fr 140px; 
    gap: 10px; 
    align-items: center; 
    border: 1px solid var(--stroke); 
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(255,255,255,.03), rgba(255,255,255,.04)); 
    padding: 10px;
}

.progress{
    height: 8px; 
    background: rgba(255,255,255,.08); 
    border-radius: 999px; 
    overflow: hidden;
}

.progress > span{
    display: block; 
    height: 100%; 
    background: linear-gradient(135deg, #ff6a00, #ff9153);
}
</style>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ TÍTULO DA PÁGINA                                                      ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<h2 style="margin:0 0 8px; font-weight:900">Painel do Administrador</h2>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ SEÇÃO: KPIs (4 cards de indicadores)                                  ║
     ║                                                                        ║
     ║ Mostra totais globais da plataforma                                   ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<div class="kpis">
    <!-- KPI: Total de empresas -->
    <div class="kpi">
        <div class="n"><?= $totCompanies ?></div>
        <div>
            <div><strong>Empresas</strong></div>
            <div class="t">cadastradas</div>
        </div>
    </div>
    
    <!-- KPI: Total de colaboradores -->
    <div class="kpi">
        <div class="n"><?= $totUsers ?></div>
        <div>
            <div><strong>Colaboradores</strong></div>
            <div class="t">contas ativas</div>
        </div>
    </div>
    
    <!-- KPI: Total de checklists -->
    <div class="kpi">
        <div class="n"><?= $totChecklists ?></div>
        <div>
            <div><strong>Checklists</strong></div>
            <div class="t">totais</div>
        </div>
    </div>
    
    <!-- KPI: Total de treinamentos -->
    <div class="kpi">
        <div class="n"><?= $totTrainings ?></div>
        <div>
            <div><strong>Treinamentos</strong></div>
            <div class="t">publicados</div>
        </div>
    </div>
</div>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ SEÇÃO: GRID PRINCIPAL (Gráfico + Rankings)                            ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<div class="dash-grid">
    
    <!-- ════════════════════════════════════════════════════════════════════
         COLUNA ESQUERDA: Gráfico de Crescimento
         
         Barras: novas empresas por mês
         Linha: novos treinamentos por mês
         ════════════════════════════════════════════════════════════════════ -->
    <div class="cardx">
        <h3 class="title">Crescimento — Empresas × Treinamentos</h3>
        <canvas id="chart" width="900" height="320"></canvas>
        <div class="small" style="margin-top:6px">
            Barras: novas empresas / mês • Linha: novos treinamentos / mês
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════
         COLUNA DIREITA: Rankings
         ════════════════════════════════════════════════════════════════════ -->
    <div class="cardx">
        <h3 class="title">Rankings</h3>
        <div style="display:grid; grid-template-columns:1fr; gap:12px">
            
            <!-- Ranking: Colaboradores com mais recompensas -->
            <div>
                <div class="small" style="margin-bottom:6px">
                    <strong>Colaboradores com mais recompensas</strong>
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

            <!-- Ranking: Empresas com mais treinamentos -->
            <div>
                <div class="small" style="margin:10px 0 6px">
                    <strong>Empresas com mais treinamentos</strong>
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

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ SEÇÃO: TOP 5 EMPRESAS COM MAIS CHECKLISTS                             ║
     ║                                                                        ║
     ║ Exibe barras de progresso proporcionais                               ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<div class="cardx" style="margin-top:12px">
    <h3 class="title">Top 5 empresas com mais checklists</h3>
    
    <?php if (empty($top5Checklist)): ?>
        <div class="small">Sem dados suficientes.</div>
    <?php else:
        // Calcula o máximo para proporção das barras
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

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ JAVASCRIPT: Renderização do Gráfico Canvas                            ║
     ║                                                                        ║
     ║ Gráfico artesanal sem dependências externas                           ║
     ║ - Barras: empresas por mês                                            ║
     ║ - Linha: treinamentos por mês                                         ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<script>
// Dados injetados do PHP
const labels   = <?= json_encode($labels) ?>;    // Meses (eixo X)
const barVals  = <?= json_encode($barVals) ?>;   // Valores das barras
const lineVals = <?= json_encode($lineVals) ?>;  // Valores da linha

/**
 * IIFE: Renderiza o gráfico de barras + linha
 * 
 * Usa Canvas API puro (sem Chart.js ou outras bibliotecas)
 * Isso reduz dependências e tamanho do bundle
 */
(function(){
    // Obtém o canvas e contexto 2D
    const cv = document.getElementById('chart');
    const ctx = cv.getContext('2d');
    
    // Dimensões do canvas
    const W = cv.width, H = cv.height;
    
    // Padding (margens internas)
    const padL = 48, padR = 24, padT = 14, padB = 36;
    const innerW = W - padL - padR;
    const innerH = H - padT - padB;

    // Calcula o valor máximo do eixo Y
    const maxY = Math.max(1, ...barVals, ...lineVals);
    const stepY = Math.max(1, Math.ceil(maxY / 4));

    // ─────────────────────────────────────────────────────────────────────
    // DESENHA O FUNDO
    // ─────────────────────────────────────────────────────────────────────
    ctx.fillStyle = 'rgba(255,255,255,0.03)'; 
    ctx.fillRect(padL, padT, innerW, innerH);
    ctx.strokeStyle = 'rgba(255,255,255,0.12)'; 
    ctx.lineWidth = 1;

    // ─────────────────────────────────────────────────────────────────────
    // DESENHA O GRID DO EIXO Y
    // ─────────────────────────────────────────────────────────────────────
    ctx.font = '12px Inter, system-ui, sans-serif'; 
    ctx.fillStyle = '#9aa4b2';
    
    for (let y = 0; y <= maxY; y += stepY) {
        const yy = padT + innerH - (y / maxY) * innerH;
        ctx.beginPath(); 
        ctx.moveTo(padL, yy); 
        ctx.lineTo(padL + innerW, yy); 
        ctx.stroke();
        ctx.fillText(String(y), 8, yy + 4);
    }

    // ─────────────────────────────────────────────────────────────────────
    // DESENHA AS BARRAS (Empresas)
    // ─────────────────────────────────────────────────────────────────────
    const n = labels.length;
    const barW = innerW / n * 0.55;  // Largura da barra
    const gap = innerW / n;           // Espaço entre barras

    for (let i = 0; i < n; i++) {
        const x = padL + i * gap + (gap - barW) / 2;
        const v = barVals[i] || 0;
        const h = (v / maxY) * innerH;
        const y = padT + innerH - h;
        
        // Preenche a barra
        ctx.fillStyle = 'rgba(255,106,0,0.35)';
        ctx.fillRect(x, y, barW, h);
        
        // Contorno da barra
        ctx.strokeStyle = 'rgba(255,106,0,0.6)'; 
        ctx.strokeRect(x, y, barW, h);
    }

    // ─────────────────────────────────────────────────────────────────────
    // DESENHA A LINHA (Treinamentos)
    // ─────────────────────────────────────────────────────────────────────
    ctx.beginPath();
    for (let i = 0; i < n; i++) {
        const x = padL + i * gap + gap / 2;
        const v = lineVals[i] || 0;
        const y = padT + innerH - (v / maxY) * innerH;
        
        if (i === 0) ctx.moveTo(x, y); 
        else ctx.lineTo(x, y);
    }
    ctx.strokeStyle = '#ff9153'; 
    ctx.lineWidth = 2; 
    ctx.stroke();

    // ─────────────────────────────────────────────────────────────────────
    // DESENHA OS PONTOS DA LINHA
    // ─────────────────────────────────────────────────────────────────────
    for (let i = 0; i < n; i++) {
        const x = padL + i * gap + gap / 2;
        const v = lineVals[i] || 0;
        const y = padT + innerH - (v / maxY) * innerH;
        
        ctx.fillStyle = '#ff9153'; 
        ctx.beginPath(); 
        ctx.arc(x, y, 3, 0, Math.PI * 2); 
        ctx.fill();
    }

    // ─────────────────────────────────────────────────────────────────────
    // DESENHA OS RÓTULOS DO EIXO X (Meses)
    // ─────────────────────────────────────────────────────────────────────
    ctx.fillStyle = '#9aa4b2'; 
    ctx.textAlign = 'center';
    labels.forEach((lb, i) => {
        const x = padL + i * gap + gap / 2;
        ctx.fillText(lb, x, H - 12);
    });

    // ─────────────────────────────────────────────────────────────────────
    // DESENHA A LEGENDA
    // ─────────────────────────────────────────────────────────────────────
    ctx.textAlign = 'left'; 
    ctx.fillStyle = '#e8edf7';
    ctx.fillText('Barras: Empresas', padL, padT - 2);
    ctx.fillStyle = '#ff9153'; 
    ctx.fillText('— Treinamentos', padL + 140, padT - 2);
})();
</script>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
