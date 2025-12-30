<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ FEEDBACK.PHP — Canal de Feedback para Colaboradores                      ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Permitir que colaboradores enviem feedbacks/chamados      ║
 * ║                para a liderança com sentimento gamificado                ║
 * ║                                                                           ║
 * ║ @acesso        Colaboradores autenticados                                ║
 * ║ @escopo        Por empresa (company_id)                                  ║
 * ║                                                                           ║
 * ║ @exibe         - Picker de sentimentos (emojis gamificados)              ║
 * ║                - Formulário: tipo, assunto, mensagem                     ║
 * ║                - Histórico de chamados do usuário                        ║
 * ║                                                                           ║
 * ║ @gamificação   Sentimentos com emojis e scores:                          ║
 * ║                😊 Muito feliz (5) → 😢 Muito triste (1)                  ║
 * ║                                                                           ║
 * ║ @ajax          feedback_submit.php (enviar feedback)                     ║
 * ║                                                                           ║
 * ║ @dependências  layout_start.php, feedback.php                            ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INCLUSÃO DE DEPENDÊNCIAS
// ═══════════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../includes/layout_start.php';
require_once __DIR__ . '/../includes/feedback.php';

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CARREGAMENTO DE DADOS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Dados do usuário e empresa
 */
$userId    = (int)$_SESSION['user']['id'];
$companyId = currentCompanyId();

/**
 * Lista de sentimentos disponíveis (com emojis e scores)
 */
$sentiments = fb_sentiments();

/**
 * Lista de categorias de feedback
 */
$cats = fb_categories();

/**
 * Histórico de chamados do usuário
 */
$my = fb_my_tickets($pdo, $companyId, $userId);
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ ESTILOS ESPECÍFICOS DA PÁGINA                                         ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<style>
/* ═══════════════════════════════════════════════════════════════════════════
   LAYOUT PRINCIPAL (2 colunas)
   ═══════════════════════════════════════════════════════════════════════════ */
.fb-grid{
    display: grid; 
    grid-template-columns: 1fr .9fr; 
    gap: 14px;
}

@media(max-width: 980px){
    .fb-grid{ grid-template-columns: 1fr }
}

.card-sec{
    border: 1px solid var(--stroke); 
    border-radius: 14px; 
    background: rgba(255,255,255,.05); 
    padding: 14px;
}

/* ═══════════════════════════════════════════════════════════════════════════
   INPUTS COM CONTRASTE ALTO (escopo local)
   
   Inputs claros para melhor legibilidade dentro do card escuro
   ═══════════════════════════════════════════════════════════════════════════ */
.card-sec .label{
    display: block; 
    font-weight: 700; 
    color: #e8edf7; 
    margin-bottom: 6px;
}

.card-sec .input,
.card-sec textarea{
    width: 100%; 
    border: 1px solid #e5e7eb; 
    border-radius: 12px;
    background: #ffffff; 
    color: #111827; 
    padding: 11px 12px; 
    outline: none;
    box-shadow: 0 1px 0 rgba(0,0,0,.05);
}

.card-sec .input:focus,
.card-sec textarea:focus{
    border-color: #ff9153; 
    box-shadow: 0 0 0 3px rgba(255,145,83,.25);
}

.card-sec select.input{
    appearance: none; 
    color-scheme: light;
}

.card-sec select.input option{
    background: #fff; 
    color: #111827;
}

/* ═══════════════════════════════════════════════════════════════════════════
   PICKER DE SENTIMENTOS (gamificado)
   
   Grid de emojis clicáveis que representam o estado emocional
   ═══════════════════════════════════════════════════════════════════════════ */
.sents{
    display: grid; 
    grid-template-columns: repeat(5, minmax(80px, 1fr)); 
    gap: 10px;
}

@media(max-width: 980px){
    .sents{ grid-template-columns: repeat(3, minmax(80px, 1fr)) }
}

@media(max-width: 560px){
    .sents{ grid-template-columns: repeat(2, minmax(80px, 1fr)) }
}

.sent{
    border: 1px solid var(--stroke); 
    border-radius: 14px; 
    padding: 12px; 
    text-align: center; 
    cursor: pointer;
    transition: .18s ease; 
    background: linear-gradient(135deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
}

.sent:hover{
    transform: translateY(-1px); 
    border-color: rgba(255,145,83,.6);
}

.sent .big{ font-size: 30px }
.sent .t{ font-weight: 900; margin-top: 6px; color: #e8edf7 }
.sent .hint{ color: #9aa4b2; font-size: .85rem; margin-top: 2px }

/* Estado ativo (selecionado) */
.sent.active{
    outline: 2px solid rgba(255,106,0,.55);
    background: linear-gradient(135deg, rgba(255,255,255,.10), rgba(255,255,255,.04));
    box-shadow: 0 8px 24px rgba(255,106,0,.15) inset, 0 6px 20px rgba(0,0,0,.25);
}

/* ═══════════════════════════════════════════════════════════════════════════
   BOTÕES
   ═══════════════════════════════════════════════════════════════════════════ */
.btn{
    display: inline-flex; 
    align-items: center; 
    gap: 8px; 
    padding: 11px 14px; 
    border-radius: 12px; 
    border: 1px solid var(--stroke);
    background: linear-gradient(135deg, #ff6a00, #ff9153); 
    color: #0f1117; 
    font-weight: 900;
}

.btn:hover{ transform: translateY(-1px) }

/* Estado de loading */
.btn.is-loading{
    color: transparent; 
    position: relative; 
    pointer-events: none;
}

.btn.is-loading::after{
    content: ""; 
    position: absolute; 
    width: 16px; 
    height: 16px; 
    border-radius: 50%;
    border: 2px solid rgba(0,0,0,.2); 
    border-top-color: #0f1117; 
    left: 50%; 
    top: 50%; 
    margin: -8px 0 0 -8px; 
    animation: spin .8s linear infinite;
}

@keyframes spin{ to{ transform: rotate(360deg) } }

.small{ color: #cbd5e1 }

/* ═══════════════════════════════════════════════════════════════════════════
   TABELA DE HISTÓRICO (melhor contraste)
   ═══════════════════════════════════════════════════════════════════════════ */
.table{
    width: 100%; 
    border-collapse: separate; 
    border-spacing: 0;
}

.table thead th{
    color: #e8edf7; 
    font-weight: 800; 
    border-bottom: 1px solid var(--stroke); 
    padding: 10px 8px; 
    background: rgba(255,255,255,.04);
}

.table td{
    border-bottom: 1px solid var(--stroke); 
    padding: 10px 8px; 
    vertical-align: top; 
    color: #e8edf7;
}

.table tr:hover td{
    background: rgba(255,255,255,.03);
}
</style>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ TÍTULO DA PÁGINA                                                      ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<h2 style="margin:0 0 10px; font-weight:900">Como podemos te ajudar?</h2>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ GRID: Formulário + Histórico                                          ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<div class="fb-grid">
    
    <!-- ════════════════════════════════════════════════════════════════════
         FORMULÁRIO DE FEEDBACK
         
         1. Picker de sentimentos (emojis)
         2. Tipo e Assunto
         3. Mensagem
         4. Botão enviar
         ════════════════════════════════════════════════════════════════════ -->
    <form class="card-sec" id="fbForm" onsubmit="return false;">
        
        <!-- Picker de Sentimentos -->
        <div style="font-weight:900; margin-bottom:8px">Seu sentimento</div>
        <div class="sents" id="sentPicker">
            <?php foreach ($sentiments as $s): ?>
                <button 
                    type="button" 
                    class="sent" 
                    data-key="<?= $s['key'] ?>" 
                    data-score="<?= (int)$s['score'] ?>"
                >
                    <div class="big"><?= $s['emoji'] ?></div>
                    <div class="t"><?= htmlspecialchars($s['title']) ?></div>
                    <?php if (!empty($s['subtitle'])): ?>
                        <div class="hint"><?= htmlspecialchars($s['subtitle']) ?></div>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Tipo e Assunto -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:12px">
            <div>
                <label class="label">Tipo</label>
                <select class="input" name="category" required>
                    <?php foreach ($cats as $k => $v): ?>
                        <option value="<?= $k ?>"><?= htmlspecialchars($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label">Assunto (opcional)</label>
                <input class="input" name="subject" placeholder="Resumo em 1 linha">
            </div>
        </div>

        <!-- Mensagem -->
        <label class="label" style="margin-top:10px">Mensagem à liderança</label>
        <textarea 
            class="input" 
            name="message" 
            rows="5" 
            placeholder="Conte o contexto, o impacto e (se quiser) uma proposta de solução." 
            required
        ></textarea>

        <!-- Campos hidden para sentimento selecionado -->
        <input type="hidden" name="sentiment_key" id="sentKey" required>
        <input type="hidden" name="sentiment_score" id="sentScore" required>

        <!-- Botão de envio -->
        <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap; align-items:center">
            <button class="btn" id="btnSend">Enviar feedback</button>
            <span class="small">Só a gerência verá seu relato.</span>
        </div>
    </form>

    <!-- ════════════════════════════════════════════════════════════════════
         HISTÓRICO DE CHAMADOS
         
         Mostra feedbacks anteriores do usuário com status
         ════════════════════════════════════════════════════════════════════ -->
    <div class="card-sec">
        <div style="font-weight:900; margin-bottom:8px">Meus chamados</div>
        
        <?php if (empty($my)): ?>
            <div class="small">Você ainda não enviou feedbacks.</div>
        <?php else: ?>
            <div style="overflow:auto; -webkit-overflow-scrolling:touch">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Quando</th>
                            <th>Sentimento</th>
                            <th>Tipo</th>
                            <th>Mensagem</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my as $t): ?>
                            <tr>
                                <td class="small">
                                    <?= date('d/m/Y H:i', strtotime($t['created_at'])) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $t['sentiment_key']))) ?>
                                </td>
                                <td class="small">
                                    <?= htmlspecialchars($cats[$t['category']] ?? $t['category']) ?>
                                </td>
                                <td>
                                    <?= nl2br(htmlspecialchars($t['message'])) ?>
                                </td>
                                <td>
                                    <?= fb_status_badge($t['status']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ JAVASCRIPT: Picker de Sentimentos + Envio via AJAX                    ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<script>
/**
 * Referências aos elementos
 */
const picker = document.getElementById('sentPicker');
const keyEl  = document.getElementById('sentKey');
const scEl   = document.getElementById('sentScore');

/**
 * Picker de sentimentos
 * 
 * Ao clicar em um emoji:
 * 1. Remove classe 'active' de todos
 * 2. Adiciona 'active' ao clicado
 * 3. Atualiza campos hidden com key e score
 */
picker.addEventListener('click', e => {
    const btn = e.target.closest('.sent'); 
    if (!btn) return;
    
    // Remove active de todos
    picker.querySelectorAll('.sent').forEach(x => x.classList.remove('active'));
    
    // Adiciona active ao clicado
    btn.classList.add('active');
    
    // Atualiza campos hidden
    keyEl.value = btn.dataset.key;
    scEl.value  = btn.dataset.score;
});

/**
 * postJSON() — Requisição POST com JSON
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
 * Envio do feedback
 * 
 * Valida campos e envia para feedback_submit.php
 * Recarrega página em caso de sucesso
 */
const btnSend = document.getElementById('btnSend');

btnSend.addEventListener('click', async () => {
    const f = document.getElementById('fbForm');
    
    // Validações
    if (!keyEl.value || !scEl.value) { 
        alert('Selecione seu sentimento.'); 
        return; 
    }
    if (!f.message.value.trim()) { 
        alert('Descreva sua mensagem.'); 
        return; 
    }

    // Mostra loading
    btnSend.classList.add('is-loading');
    
    try {
        const res = await postJSON('<?= url_for("/pages/feedback_submit.php") ?>', {
            sentiment_key: keyEl.value,
            sentiment_score: parseInt(scEl.value, 10),
            category: f.category.value,
            subject: f.subject.value,
            message: f.message.value
        });
        
        if (res.status === 'ok') { 
            location.reload(); 
        } else { 
            alert(res.message || 'Erro ao enviar'); 
        }
    } catch(e) { 
        alert('Erro: ' + e.message); 
    } finally { 
        btnSend.classList.remove('is-loading'); 
    }
});
</script>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
