<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ CHECKLIST_RUN.PHP — Execução de Checklist (Marcar Tarefas)               ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Permitir que colaboradores executem um checklist,         ║
 * ║                marcando/desmarcando tarefas como feitas                  ║
 * ║                                                                           ║
 * ║ @acesso        Colaboradores com acesso ao checklist                     ║
 * ║ @escopo        Por empresa e checklist específico                        ║
 * ║                                                                           ║
 * ║ @parâmetros    GET:                                                      ║
 * ║                - id: ID do checklist                                     ║
 * ║                - period: 'current' (atual) ou 'prev' (anterior)          ║
 * ║                                                                           ║
 * ║ @exibe         - Cabeçalho sticky com título e tabs de período           ║
 * ║                - Barra de progresso do período                           ║
 * ║                - Lista de tarefas com botão de toggle                    ║
 * ║                                                                           ║
 * ║ @ajax          checklist_toggle.php (marcar/desmarcar)                   ║
 * ║                                                                           ║
 * ║ @dependências  layout_start.php, checklist.php                           ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INCLUSÃO DE DEPENDÊNCIAS
// ═══════════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../includes/layout_start.php';
require_once __DIR__ . '/../includes/checklist.php';

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: PARÂMETROS E VALIDAÇÃO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Dados do usuário e empresa atual
 */
$userId    = (int)$_SESSION['user']['id'];
$companyId = currentCompanyId();

/**
 * ID do checklist (via GET)
 */
$clId = (int)($_GET['id'] ?? 0);

/**
 * Modo de período:
 * - 'current': período atual (padrão)
 * - 'prev': período anterior (para regularização)
 */
$mode = ($_GET['period'] ?? 'current') === 'prev' ? 'prev' : 'current';

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CARREGAMENTO DO CHECKLIST
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Busca o checklist pelo ID e empresa
 * Verifica se está ativo
 */
$st = $pdo->prepare("SELECT * FROM checklists WHERE id=? AND company_id=? AND is_active=1");
$st->execute([$clId, $companyId]);
$checklist = $st->fetch();

/**
 * Se não encontrar, exibe mensagem e encerra
 */
if (!$checklist) { 
    echo '<div class="card" style="padding:20px">Checklist não encontrado.</div>'; 
    require_once __DIR__ . '/../includes/layout_end.php'; 
    exit; 
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CARREGAMENTO DE TAREFAS E PROGRESSO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Lista de tarefas do checklist
 */
$tasks = checklistTasks($pdo, $clId);

/**
 * Calcula a period_key baseado no modo e frequência
 * 
 * period_key_for(): retorna chave do período atual
 * period_key_prev(): retorna chave do período anterior
 */
$pkey = $mode === 'prev' ? period_key_prev($checklist['frequency']) : period_key_for($checklist['frequency']);

/**
 * Pré-calcula progresso das tarefas
 * 
 * Para cada tarefa, verifica se está feita no período atual
 * Monta mapa de status e conta totais
 */
$doneCount = 0; 
$totalCount = count($tasks); 
$doneMap = [];

foreach ($tasks as $t) {
    $done = isTaskDone($pdo, (int)$t['id'], $companyId, $pkey);
    $doneMap[(int)$t['id']] = (bool)$done;
    if ($done) $doneCount++;
}

/**
 * Calcula percentual de progresso
 */
$pct = $totalCount ? (int)round(($doneCount / $totalCount) * 100) : 0;
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ ESTILOS ESPECÍFICOS DA PÁGINA                                         ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<style>
/* ═══════════════════════════════════════════════════════════════════════════
   LAYOUT GERAL
   ═══════════════════════════════════════════════════════════════════════════ */
.run-wrap{
    display: flex; 
    flex-direction: column; 
    gap: 12px;
}

/* ═══════════════════════════════════════════════════════════════════════════
   CABEÇALHO STICKY
   
   Fica fixo no topo ao rolar, com blur de fundo
   Contém: título, período, tabs e botão concluir
   ═══════════════════════════════════════════════════════════════════════════ */
.run-head{
    position: sticky; 
    top: calc(var(--mh-header-h) + 4px); 
    z-index: 5;
    display: flex; 
    flex-wrap: wrap; 
    gap: 10px; 
    align-items: center; 
    justify-content: space-between;
    padding: 10px; 
    border-radius: 14px; 
    border: 1px solid var(--stroke);
    background: linear-gradient(180deg, rgba(20,24,36,.80), rgba(20,24,36,.60)); 
    backdrop-filter: blur(6px);
}

.run-title{
    display: flex; 
    flex-direction: column;
}

.run-title h2{ margin: 0; font-weight: 900; font-size: 1.2rem }
.run-sub{ color: #cbd5e1; font-size: .92rem }

/* ═══════════════════════════════════════════════════════════════════════════
   PILLS (Tabs Atual/Anterior)
   ═══════════════════════════════════════════════════════════════════════════ */
.pills{
    display: inline-flex; 
    gap: 8px; 
    padding: 4px; 
    border-radius: 999px; 
    border: 1px solid var(--stroke); 
    background: rgba(255,255,255,.04);
}

.pill{
    display: inline-flex; 
    align-items: center; 
    padding: 8px 12px; 
    border-radius: 999px; 
    font-weight: 800;
    color: #e8edf7; 
    text-decoration: none; 
    border: 1px solid transparent; 
    transition: .15s ease;
}

.pill:hover{ background: rgba(255,255,255,.06) }
.pill.active{ 
    background: linear-gradient(135deg, #ff6a00, #ff9153); 
    color: #0f1117; 
    border-color: transparent 
}

/* ═══════════════════════════════════════════════════════════════════════════
   BOTÕES
   ═══════════════════════════════════════════════════════════════════════════ */
.run-actions{
    display: flex; 
    gap: 8px;
}

.btn{
    display: inline-flex; 
    align-items: center; 
    gap: 8px; 
    padding: 10px 14px; 
    border-radius: 12px;
    border: 1px solid var(--stroke); 
    text-decoration: none; 
    font-weight: 900; 
    transition: .15s ease;
}

.btn.primary{ 
    background: linear-gradient(135deg, #ff6a00, #ff9153); 
    color: #0f1117; 
    border-color: transparent 
}

.btn.ghost{ 
    background: rgba(255,255,255,.04); 
    color: #e8edf7 
}

.btn:hover{ transform: translateY(-1px) }

/* Estado de loading */
.btn.is-loading{ 
    position: relative; 
    color: transparent; 
    pointer-events: none 
}

.btn.is-loading::after{ 
    content: ""; 
    position: absolute; 
    left: 50%; 
    top: 50%; 
    width: 16px; 
    height: 16px; 
    margin: -8px 0 0 -8px; 
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,.35); 
    border-top-color: #fff; 
    animation: spin .8s linear infinite 
}

@keyframes spin{ to{ transform: rotate(360deg) } }

/* ═══════════════════════════════════════════════════════════════════════════
   BARRA DE PROGRESSO DO PERÍODO
   ═══════════════════════════════════════════════════════════════════════════ */
.progress-box{
    border: 1px solid var(--stroke); 
    border-radius: 14px; 
    padding: 10px;
    background: linear-gradient(160deg, rgba(255,255,255,.05), rgba(255,255,255,.03));
}

.progress{ margin-top: 6px }
.progress .bar{ 
    height: 10px; 
    background: rgba(255,255,255,.08); 
    border-radius: 999px; 
    overflow: hidden 
}

.progress .fill{ 
    height: 100%; 
    background: linear-gradient(135deg, var(--brand), var(--brand-2)) 
}

.progress .meta{ 
    display: flex; 
    justify-content: space-between; 
    margin-top: 4px; 
    color: #cbd5e1; 
    font-size: .9rem 
}

/* ═══════════════════════════════════════════════════════════════════════════
   LISTA DE TAREFAS
   ═══════════════════════════════════════════════════════════════════════════ */
.list{
    display: flex; 
    flex-direction: column; 
    gap: 10px;
}

.task{
    display: grid; 
    grid-template-columns: 1.15fr .7fr .7fr 160px; 
    gap: 10px; 
    align-items: center;
    border: 1px solid var(--stroke); 
    border-radius: 14px; 
    background: rgba(255,255,255,.04); 
    padding: 10px;
    transition: border-color .18s ease, transform .18s ease, box-shadow .18s ease;
}

.task:hover{ 
    border-color: rgba(255,255,255,.22); 
    transform: translateY(-1px); 
    box-shadow: 0 10px 22px rgba(0,0,0,.25) 
}

.name{ font-weight: 900 }
.small{ color: #9aa4b2; font-size: .9rem }

/* ═══════════════════════════════════════════════════════════════════════════
   BADGES
   ═══════════════════════════════════════════════════════════════════════════ */
.badge{
    display: inline-flex; 
    align-items: center; 
    gap: 8px; 
    padding: 6px 10px; 
    border-radius: 999px;
    border: 1px solid var(--stroke); 
    background: rgba(255,255,255,.04); 
    font-weight: 800;
}

.badge.light{ 
    background: #fff; 
    color: #0f1117; 
    border: 1px solid #e5e7eb 
}

.badge.prio .flames{ letter-spacing: 1px }

/* ═══════════════════════════════════════════════════════════════════════════
   BOTÃO DE ESTADO (Marcar feito / Feito)
   ═══════════════════════════════════════════════════════════════════════════ */
.state{
    display: inline-flex; 
    align-items: center; 
    justify-content: center; 
    gap: 10px;
    padding: 12px 14px; 
    border-radius: 12px; 
    border: 1px solid var(--stroke); 
    cursor: pointer; 
    position: relative;
    background: rgba(255,255,255,.04); 
    color: #e8edf7; 
    font-weight: 900;
}

/* Estado: tarefa concluída */
.state.done{ 
    background: linear-gradient(135deg, #22c55e, #86efac); 
    color: #0f1117; 
    border-color: transparent 
}

/* Estado: loading */
.state.is-loading{ color: transparent }
.state.is-loading::after{ 
    content: ""; 
    position: absolute; 
    inset: 0; 
    margin: auto; 
    width: 16px; 
    height: 16px; 
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,.35); 
    border-top-color: #fff; 
    animation: spin .8s linear infinite 
}

/* ═══════════════════════════════════════════════════════════════════════════
   RESPONSIVIDADE MOBILE
   ═══════════════════════════════════════════════════════════════════════════ */
@media(max-width:980px){
    .task{ grid-template-columns: 1fr; align-items: stretch }
    .name{ order: 1 }
    .badge.period{ order: 2; justify-self: flex-start }
    .badge.prio{ order: 3; justify-self: flex-start }
    .state{ order: 4; width: 100% }
    .run-actions{ width: 100%; justify-content: space-between }
}

@media(max-width:560px){
    .run-title h2{ font-size: 1.05rem }
}

/* Desativa hover lift em touch devices */
@media (hover:none){
    .task:hover{ transform: none; box-shadow: none }
    .btn:hover{ transform: none }
}
</style>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ CONTEÚDO DA PÁGINA                                                    ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<div class="run-wrap">

    <!-- ════════════════════════════════════════════════════════════════════
         CABEÇALHO STICKY
         
         Contém:
         - Título do checklist
         - Período atual e frequência
         - Tabs para alternar entre período atual e anterior
         - Botão "Concluir" (volta para lista)
         ════════════════════════════════════════════════════════════════════ -->
    <div class="run-head">
        <div class="run-title">
            <h2><?= htmlspecialchars($checklist['title']) ?></h2>
            <div class="run-sub">
                Período: <strong><?= htmlspecialchars($pkey) ?></strong>
                • Frequência: <strong><?= strtoupper($checklist['frequency']) ?></strong>
                <?php if ($mode === 'prev'): ?>
                    <span class="badge" style="margin-left:6px; background:#ff4d4f; color:#0f1117; border:none">
                        Período anterior
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="run-actions">
            <!-- Tabs de período -->
            <div class="pills" role="tablist" aria-label="Período">
                <a class="pill <?= $mode === 'current' ? 'active' : '' ?>" 
                   role="tab" 
                   aria-selected="<?= $mode === 'current' ? 'true' : 'false' ?>"
                   href="<?= url_for('/pages/checklist_run.php') ?>?id=<?= (int)$clId ?>&period=current">
                    Atual
                </a>
                <a class="pill <?= $mode === 'prev' ? 'active' : '' ?>" 
                   role="tab" 
                   aria-selected="<?= $mode === 'prev' ? 'true' : 'false' ?>"
                   href="<?= url_for('/pages/checklist_run.php') ?>?id=<?= (int)$clId ?>&period=prev">
                    Anterior
                </a>
            </div>
            
            <!-- Botão concluir (volta para lista) -->
            <a class="btn primary" href="<?= url_for('/pages/checklists.php') ?>">Concluir</a>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════
         BARRA DE PROGRESSO
         
         Mostra quantas tarefas foram concluídas no período
         ════════════════════════════════════════════════════════════════════ -->
    <div class="progress-box">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap">
            <div style="font-weight:900">Progresso deste período</div>
            <div class="small"><?= (int)$doneCount ?>/<?= (int)$totalCount ?> tarefas</div>
        </div>
        <div class="progress">
            <div class="bar">
                <div class="fill" style="width:<?= (int)$pct ?>%"></div>
            </div>
            <div class="meta">
                <span></span>
                <strong><?= (int)$pct ?>%</strong>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════
         LISTA DE TAREFAS
         
         Cada tarefa mostra:
         - Nome e observações
         - Badge de período
         - Badge de prioridade (flames)
         - Botão de toggle (marcar/desmarcar)
         ════════════════════════════════════════════════════════════════════ -->
    <?php if (empty($tasks)): ?>
        <div class="card" style="padding:20px">Sem tarefas cadastradas.</div>
    <?php else: ?>
        <div class="list">
            <?php foreach ($tasks as $t):
                $tid    = (int)$t['id'];
                $done   = !empty($doneMap[$tid]);
                $flames = str_repeat('🔥', max(1, min(5, (int)$t['priority'])));
            ?>
                <!-- Linha de tarefa -->
                <div class="task" data-task="<?= $tid ?>">
                    
                    <!-- Nome e observações -->
                    <div class="name">
                        <?= htmlspecialchars($t['name']) ?>
                        <?php if (!empty($t['notes'])): ?>
                            <div class="small"><?= nl2br(htmlspecialchars($t['notes'])) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Badge de período -->
                    <div class="badge period badge light">
                        <?= htmlspecialchars(period_label($t['period'])) ?>
                    </div>

                    <!-- Badge de prioridade -->
                    <div class="badge prio">
                        <span class="flames"><?= $flames ?></span>
                        <span class="small">Prioridade <?= (int)$t['priority'] ?></span>
                    </div>

                    <!-- Botão de toggle -->
                    <button 
                        class="state <?= $done ? 'done' : '' ?>" 
                        data-action="<?= $done ? 'uncheck' : 'check' ?>" 
                        aria-pressed="<?= $done ? 'true' : 'false' ?>"
                    >
                        <?= $done ? 'Feito ✓' : 'Marcar feito' ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ JAVASCRIPT: Toggle de Tarefas via AJAX                                ║
     ║                                                                        ║
     ║ Ao clicar no botão de estado:                                         ║
     ║ 1. Envia requisição para checklist_toggle.php                         ║
     ║ 2. Atualiza visual do botão (done/pending)                            ║
     ║ 3. Mostra feedback visual (animação de scale)                         ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<script>
/**
 * postJSON() — Requisição POST com JSON
 * 
 * @param {string} url - URL do endpoint
 * @param {object} payload - Dados a enviar
 * @returns {object} - Resposta parseada
 */
async function postJSON(url, payload) {
    const r = await fetch(url, {
        method: 'POST', 
        headers: {'Content-Type': 'application/json'}, 
        body: JSON.stringify(payload)
    });
    const t = await r.text(); 
    try { 
        return JSON.parse(t); 
    } catch(e) { 
        throw new Error(t); 
    }
}

/**
 * Event listeners para todos os botões de estado
 */
document.querySelectorAll('.task .state').forEach(btn => {
    btn.addEventListener('click', async () => {
        // Obtém dados da tarefa
        const row    = btn.closest('.task');
        const taskId = parseInt(row.dataset.task, 10);
        const action = btn.dataset.action;

        // Mostra loading
        btn.classList.add('is-loading'); 
        btn.disabled = true;

        try {
            // Envia requisição de toggle
            const res = await postJSON('<?= url_for("/pages/checklist_toggle.php") ?>', {
                action, 
                task_id: taskId, 
                checklist_id: <?= (int)$clId ?>,
                frequency: '<?= $checklist['frequency'] ?>',
                period: '<?= $mode ?>'
            });
            
            if (res.status && res.status !== 'ok') { 
                throw new Error(res.message || 'Falha'); 
            }

            // Toggle visual do botão
            if (action === 'check') {
                btn.classList.add('done'); 
                btn.dataset.action = 'uncheck'; 
                btn.textContent = 'Feito ✓'; 
                btn.setAttribute('aria-pressed', 'true');
            } else {
                btn.classList.remove('done'); 
                btn.dataset.action = 'check'; 
                btn.textContent = 'Marcar feito'; 
                btn.setAttribute('aria-pressed', 'false');
            }

            // Feedback visual sutil (animação de scale)
            btn.animate([
                {transform: 'scale(1.00)'}, 
                {transform: 'scale(1.03)'}, 
                {transform: 'scale(1.00)'}
            ], {duration: 220, easing: 'ease-out'});

        } catch(e) {
            alert('Erro: ' + e.message);
        } finally {
            btn.classList.remove('is-loading'); 
            btn.disabled = false;
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
