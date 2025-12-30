<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ CHECKLIST_NEW.PHP — Formulário de Criação de Novo Checklist              ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Permitir que gestores criem novos checklists operacionais ║
 * ║                com tarefas, frequência e permissões por cargo            ║
 * ║                                                                           ║
 * ║ @acesso        Admin Geral | Gestor (canAccessAdmin)                     ║
 * ║ @escopo        Por empresa (company_id)                                  ║
 * ║                                                                           ║
 * ║ @campos        - Título (obrigatório)                                    ║
 * ║                - Descrição                                               ║
 * ║                - Frequência (diária/semanal/quinzenal/mensal)            ║
 * ║                - Cargo responsável padrão                                ║
 * ║                - Cargos com acesso                                       ║
 * ║                - Lista de tarefas com:                                   ║
 * ║                  • Nome da tarefa                                        ║
 * ║                  • Período (início/fim do dia/semana)                    ║
 * ║                  • Prioridade (1-5 🔥)                                   ║
 * ║                  • Observações                                           ║
 * ║                                                                           ║
 * ║ @submit        POST → checklist_save.php                                 ║
 * ║                                                                           ║
 * ║ @dependências  layout_start.php, db.php, auth.php                        ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INCLUSÃO DE DEPENDÊNCIAS E VERIFICAÇÃO DE ACESSO
// ═══════════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../includes/layout_start.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

/**
 * Verifica permissão administrativa
 * Apenas Admin Geral e Gestores podem criar checklists
 */
if (!canAccessManager()) { 
    http_response_code(403); 
    echo '<div class="card" style="padding:20px">Acesso negado</div>'; 
    require_once __DIR__ . '/../includes/layout_end.php'; 
    exit; 
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CARREGAMENTO DE DADOS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * ID da empresa atual
 */
$companyId = currentCompanyId();

/**
 * Lista de cargos disponíveis para seleção
 * Usado para: cargo padrão e cargos com acesso
 */
$roles = [];
try { 
    $roles = $pdo->query("SELECT id, name FROM roles ORDER BY name")->fetchAll() ?: []; 
} catch(Throwable $e) { 
    $roles = []; 
}
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ ESTILOS ESPECÍFICOS DO FORMULÁRIO                                     ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<style>
/* ═══════════════════════════════════════════════════════════════════════════
   LAYOUT GERAL DO FORMULÁRIO
   ═══════════════════════════════════════════════════════════════════════════ */
.form-shell{
    display: flex; 
    flex-direction: column; 
    gap: 12px;
}

.form-grid{
    display: grid; 
    grid-template-columns: 1.1fr .9fr; 
    gap: 14px;
}

@media(max-width:980px){
    .form-grid{ grid-template-columns: 1fr }
}

/* ═══════════════════════════════════════════════════════════════════════════
   CARDS DE SEÇÃO
   ═══════════════════════════════════════════════════════════════════════════ */
.card-sec{
    border: 1px solid var(--stroke); 
    border-radius: 16px; 
    background: linear-gradient(160deg, rgba(255,255,255,.05), rgba(255,255,255,.03)); 
    padding: 12px;
}

.header-line{
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 10px; 
    gap: 10px; 
    flex-wrap: wrap;
}

.header-line h2{ margin: 0; font-weight: 900 }

/* ═══════════════════════════════════════════════════════════════════════════
   LISTA DE CARGOS (com scroll suave)
   ═══════════════════════════════════════════════════════════════════════════ */
.roles-box{
    padding: 8px; 
    max-height: 200px; 
    overflow: auto; 
    border: 1px solid var(--stroke); 
    border-radius: 12px; 
    background: rgba(255,255,255,.04);
}

/* Scrollbar customizada */
.roles-box{ scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.25) transparent }
.roles-box::-webkit-scrollbar{ width: 8px }
.roles-box::-webkit-scrollbar-thumb{ background: rgba(255,255,255,.25); border-radius: 10px }

/* ═══════════════════════════════════════════════════════════════════════════
   LINHA DE TAREFA (grid responsivo)
   ═══════════════════════════════════════════════════════════════════════════ */
.row{
    display: grid; 
    grid-template-columns: 1.2fr 0.9fr 0.9fr 1fr auto; 
    gap: 8px; 
    align-items: center; 
    margin-bottom: 8px;
}

@media(max-width:1100px){
    .row{ grid-template-columns: 1.2fr 1fr 1fr auto }
}

@media(max-width:720px){
    .row{ grid-template-columns: 1fr }
}

.row .input, .row select{ width: 100% }

/* Labels mini em mobile para contexto */
@media(max-width:720px){
    .field-label{ font-size: .8rem; color: #9aa4b2; margin-top: 4px }
}

/* ═══════════════════════════════════════════════════════════════════════════
   BOTÕES E ESTADOS
   ═══════════════════════════════════════════════════════════════════════════ */
.actions{
    display: flex; 
    gap: 8px; 
    flex-wrap: wrap;
}

.button.is-loading{
    position: relative; 
    color: transparent; 
    pointer-events: none;
}

.button.is-loading::after{
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
    animation: spin .8s linear infinite;
}

@keyframes spin{ to{ transform: rotate(360deg) } }

/* ═══════════════════════════════════════════════════════════════════════════
   PICKER DE PRIORIDADE (🔥)
   
   Componente visual para selecionar prioridade 1-5
   Usa flames (🔥) clicáveis e dots para indicar nível
   ═══════════════════════════════════════════════════════════════════════════ */
.prio-wrap{
    display: flex; 
    align-items: center; 
    gap: 8px;
}

.prio{
    display: inline-flex; 
    align-items: center; 
    gap: 6px; 
    border: 1px solid var(--stroke); 
    border-radius: 12px; 
    padding: 6px 8px; 
    background: rgba(255,255,255,.04);
}

.prio .flame{
    font-size: 20px; 
    opacity: .45; 
    transition: .12s ease; 
    cursor: pointer; 
    user-select: none;
}

.prio .flame.on{ opacity: 1; transform: translateY(-1px) }
.prio .flame:hover{ transform: translateY(-2px) }

.prio .hint{ color: #cbd5e1; font-size: .85rem; margin-left: 6px }

.prio .scale{ display: flex; gap: 2px }
.prio .scale .dot{ width: 6px; height: 6px; border-radius: 50%; background: rgba(255,255,255,.25) }
.prio .scale .dot.fill{ background: linear-gradient(135deg, #ff6a00, #ff9153) }

/* ═══════════════════════════════════════════════════════════════════════════
   BADGES E CHIPS
   ═══════════════════════════════════════════════════════════════════════════ */
.badge-soft{
    display: inline-flex; 
    align-items: center; 
    gap: 8px; 
    padding: 6px 10px; 
    border-radius: 999px; 
    border: 1px solid var(--stroke); 
    background: rgba(255,255,255,.04); 
    font-weight: 800;
}

/* ═══════════════════════════════════════════════════════════════════════════
   BOTÃO "ADICIONAR TAREFA"
   ═══════════════════════════════════════════════════════════════════════════ */
.add-line{ margin-top: 10px }
</style>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ FORMULÁRIO DE CRIAÇÃO DE CHECKLIST                                    ║
     ║                                                                        ║
     ║ Envia dados via POST para checklist_save.php                          ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<form class="card form-shell" method="POST" action="<?= url_for('/pages/checklist_save.php') ?>" id="clForm">
    
    <!-- ════════════════════════════════════════════════════════════════════
         CABEÇALHO: Título + Botões de ação
         ════════════════════════════════════════════════════════════════════ -->
    <div class="header-line">
        <h2>Novo Checklist</h2>
        <div class="actions">
            <button class="button" type="submit" id="btnSubmit">Salvar</button>
            <a class="button ghost" href="<?= url_for('/pages/checklists.php') ?>">Cancelar</a>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════
         GRID: Informações básicas + Configurações
         ════════════════════════════════════════════════════════════════════ -->
    <div class="form-grid">
        
        <!-- Coluna 1: Título e Descrição -->
        <div class="card-sec">
            <label class="label">Título*</label>
            <input class="input" name="title" required placeholder="Ex.: Abertura do Salão — Manhã">

            <label class="label" style="margin-top:10px">Descrição</label>
            <textarea class="input" name="description" rows="3" placeholder="Objetivo, padrões e observações gerais do checklist."></textarea>
        </div>

        <!-- Coluna 2: Frequência e Cargos -->
        <div class="card-sec">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
                <!-- Frequência -->
                <div>
                    <label class="label">Frequência</label>
                    <select class="input" name="frequency">
                        <option value="daily">Diária</option>
                        <option value="weekly">Semanal</option>
                        <option value="biweekly">Quinzenal</option>
                        <option value="monthly">Mensal</option>
                    </select>
                </div>
                
                <!-- Cargo responsável padrão -->
                <div>
                    <label class="label">Cargo responsável (padrão)</label>
                    <select class="input" name="default_role_id">
                        <option value="">—</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int)$r['id'] ?>">
                                <?= htmlspecialchars($r['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Cargos com acesso (checkboxes) -->
            <label class="label" style="margin-top:10px">Cargos com acesso</label>
            <div class="roles-box">
                <?php foreach ($roles as $r): ?>
                    <label style="display:flex; align-items:center; gap:8px; margin:4px 0">
                        <input type="checkbox" name="roles[]" value="<?= (int)$r['id'] ?>"> 
                        <?= htmlspecialchars($r['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <!-- Dica sobre cargo padrão -->
            <div class="badge-soft" style="margin-top:10px">
                <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                    <path d="M12 2l4 7h-8l4-7zm0 20c-4.418 0-8-3.582-8-8h2a6 6 0 1012 0h2c0 4.418-3.582 8-8 8z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════
         SEÇÃO: Lista de Tarefas
         
         Tarefas são adicionadas dinamicamente via JavaScript
         Cada tarefa tem: nome, período, prioridade, observações
         ════════════════════════════════════════════════════════════════════ -->
    <div class="card-sec" style="margin-top:12px">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap">
            <div style="font-weight:900">Tarefas</div>
            <span class="small" style="color:#9aa4b2">
                Período é a janela esperada (início/fim do dia/semana)
            </span>
        </div>

        <!-- Container onde as tarefas serão inseridas -->
        <div id="taskList"></div>
        
        <!-- Botão para adicionar nova tarefa -->
        <button type="button" class="button ghost add-line" id="btnAdd">
            + Adicionar tarefa
        </button>
    </div>

    <!-- Botões de ação (rodapé) -->
    <div class="actions" style="margin-top:6px">
        <button class="button" type="submit" id="btnSubmitBottom">Salvar Checklist</button>
        <a class="button ghost" href="<?= url_for('/pages/checklists.php') ?>">Cancelar</a>
    </div>
</form>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ JAVASCRIPT: Gerenciamento Dinâmico de Tarefas                         ║
     ║                                                                        ║
     ║ Funcionalidades:                                                       ║
     ║ - Adicionar/remover linhas de tarefa                                  ║
     ║ - Picker de prioridade interativo (🔥)                                ║
     ║ - Estado de loading no submit                                         ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<script>
// Referências aos elementos do DOM
const taskList = document.getElementById('taskList');
const btnAdd = document.getElementById('btnAdd');
const btnSubmit = document.getElementById('btnSubmit');
const btnSubmitBottom = document.getElementById('btnSubmitBottom');

// Contador de índice para nomes únicos dos campos
let idx = 0;

/**
 * prioPickerHTML() — Gera HTML do picker de prioridade
 * 
 * @param {string} name - Nome do campo hidden
 * @param {number} initial - Valor inicial (1-5)
 * @returns {string} - HTML do componente
 * 
 * @componente
 * - 5 flames (🔥) clicáveis
 * - Hint mostrando valor atual
 * - Escala visual com dots
 * - Input hidden com o valor real
 */
function prioPickerHTML(name, initial = 3) {
    const id = 'p' + Math.random().toString(36).slice(2, 8);
    
    // Gera os 5 flames
    const flames = Array.from({length: 5}).map((_, i) => `
        <span class="flame ${i < initial ? 'on' : ''}" data-v="${i + 1}" title="Prioridade ${i + 1}">🔥</span>
    `).join('');
    
    // Gera os dots da escala
    const dots = Array.from({length: 5}).map((_, i) => `
        <span class="dot ${i < initial ? 'fill' : ''}"></span>
    `).join('');
    
    return `
        <div class="prio-wrap">
            <div class="prio" data-target="${id}">
                ${flames}
                <span class="hint">x<span class="hint-n">${initial}</span></span>
                <span class="scale" aria-hidden="true">${dots}</span>
            </div>
            <input type="hidden" id="${id}" name="${name}" value="${initial}">
        </div>
    `;
}

/**
 * bindPrioPickers() — Adiciona interatividade aos pickers de prioridade
 * 
 * @param {Element} scope - Elemento pai para buscar pickers (default: document)
 * 
 * @comportamento
 * Ao clicar em uma flame:
 * 1. Atualiza o input hidden com o novo valor
 * 2. Atualiza o hint visual
 * 3. Acende/apaga flames conforme o valor
 * 4. Preenche/esvazia dots conforme o valor
 */
function bindPrioPickers(scope = document) {
    scope.querySelectorAll('.prio').forEach(pr => {
        const targetId = pr.dataset.target;
        const input = document.getElementById(targetId);
        const hint = pr.querySelector('.hint-n');
        const flames = pr.querySelectorAll('.flame');
        const dots = pr.querySelectorAll('.dot');

        pr.addEventListener('click', e => {
            const f = e.target.closest('.flame'); 
            if (!f) return;
            
            const val = parseInt(f.dataset.v, 10);
            input.value = val;
            hint.textContent = val;
            
            // Atualiza visual das flames e dots
            flames.forEach((el, i) => el.classList.toggle('on', i < val));
            dots.forEach((el, i) => el.classList.toggle('fill', i < val));
        });
    });
}

/**
 * addRow() — Adiciona uma nova linha de tarefa
 * 
 * @param {object} values - Valores pré-preenchidos (opcional)
 *   - name: nome da tarefa
 *   - period: período (inicio_dia, final_dia, etc.)
 *   - priority: prioridade 1-5
 *   - notes: observações
 * 
 * @estrutura
 * Cada linha contém:
 * 1. Input de nome (obrigatório)
 * 2. Select de período
 * 3. Picker de prioridade
 * 4. Input de observações
 * 5. Botão de remover
 */
function addRow(values = {}) {
    const id = idx++;
    const name     = values.name     || '';
    const period   = values.period   || 'final_dia';
    const priority = values.priority || 3;
    const notes    = values.notes    || '';

    const el = document.createElement('div');
    el.className = 'row';
    el.innerHTML = `
        <!-- Campo: Nome da tarefa -->
        <div>
            <div class="field-label">Tarefa</div>
            <input class="input" name="tasks[${id}][name]" placeholder="Descrever a tarefa" value="${name.replace(/"/g, '&quot;')}" required>
        </div>

        <!-- Campo: Período -->
        <div>
            <div class="field-label">Período</div>
            <select class="input" name="tasks[${id}][period]">
                <option value="inicio_dia" ${period === 'inicio_dia' ? 'selected' : ''}>Início do dia</option>
                <option value="final_dia" ${period === 'final_dia' ? 'selected' : ''}>Até o final do dia</option>
                <option value="inicio_semana" ${period === 'inicio_semana' ? 'selected' : ''}>Início da semana</option>
                <option value="final_semana" ${period === 'final_semana' ? 'selected' : ''}>Até o final da semana</option>
            </select>
        </div>

        <!-- Campo: Prioridade (picker 🔥) -->
        <div>
            <div class="field-label">Prioridade</div>
            ${prioPickerHTML(`tasks[${id}][priority]`, priority)}
        </div>

        <!-- Campo: Observações -->
        <div>
            <div class="field-label">Observações</div>
            <input class="input" name="tasks[${id}][notes]" placeholder="Opcional" value="${notes.replace(/"/g, '&quot;')}">
        </div>

        <!-- Botão: Remover tarefa -->
        <div style="display:flex; align-items:flex-end; justify-content:flex-end">
            <button class="button ghost" type="button" onclick="this.closest('.row').remove()" title="Remover">✕</button>
        </div>
    `;
    
    taskList.appendChild(el);
    bindPrioPickers(el);
}

// Event listener para o botão "Adicionar tarefa"
btnAdd.addEventListener('click', () => addRow());

// Adiciona 2 linhas iniciais vazias
addRow(); 
addRow();

/**
 * setLoading() — Controla estado de loading dos botões de submit
 * 
 * @param {boolean} on - true para ativar loading, false para desativar
 */
function setLoading(on) {
    [btnSubmit, btnSubmitBottom].forEach(b => { 
        if (!b) return; 
        b.classList.toggle('is-loading', on); 
        b.disabled = on; 
    });
}

// Ativa loading ao submeter o formulário
document.getElementById('clForm').addEventListener('submit', () => setLoading(true));
</script>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
