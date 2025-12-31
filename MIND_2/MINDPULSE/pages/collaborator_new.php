<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ COLLABORATOR_NEW.PHP — Formulário de Cadastro de Colaborador             ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Permitir que gestores cadastrem novos colaboradores       ║
 * ║                com dados pessoais, empresas e cargos                     ║
 * ║                                                                           ║
 * ║ @acesso        Admin Geral | Gestor (canAccessAdmin)                     ║
 * ║ @escopo        Global (pode associar a múltiplas empresas)               ║
 * ║                                                                           ║
 * ║ @campos        - Foto (URL)                                              ║
 * ║                - Nome completo (obrigatório)                             ║
 * ║                - Email (obrigatório)                                     ║
 * ║                - Senha (opcional)                                        ║
 * ║                - Aniversário                                             ║
 * ║                - Telefone                                                ║
 * ║                - Status (ativo/inativo)                                  ║
 * ║                - Tipo (Colaborador/Admin)                                ║
 * ║                - Empresas (checkboxes)                                   ║
 * ║                - Cargos (checkboxes)                                     ║
 * ║                - Observações                                             ║
 * ║                                                                           ║
 * ║ @submit        POST → collaborator_save.php                              ║
 * ║                                                                           ║
 * ║ @dependências  layout_start.php, auth.php, db.php                        ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INCLUSÃO DE DEPENDÊNCIAS E VERIFICAÇÃO DE ACESSO
// ═══════════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../includes/layout_start.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$userCompanyId = $_SESSION['user']['company_id'] ?? 0;

/**
 * Verifica permissão administrativa
 */
if (!canAccessManager()) { 
    http_response_code(403); 
    echo '<div class="card" style="padding:20px">Acesso negado</div>'; 
    require_once __DIR__ . '/../includes/layout_end.php'; 
    exit; 
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CARREGAMENTO DE DADOS PARA SELECTS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Lista de empresas para checkboxes
 */
/**
 * LÓGICA DE CARREGAMENTO DE EMPRESAS
 */
// ═══════════════════════════════════════════════════════════════════════════
// NOVA LÓGICA: Busca empresa vinculada na tabela user_company
// ═══════════════════════════════════════════════════════════════════════════

if (isAdmin()) {
    // Admin vê tudo
    $companies = $pdo->query("SELECT id, name FROM companies ORDER BY name")->fetchAll();
} else {
    // 1. Pega ID do usuário logado
    $currentUserId = $_SESSION['user']['id'] ?? 0;

    // 2. Descobre qual empresa ele pertence na tabela user_company
    $stmtLink = $pdo->prepare("SELECT company_id FROM user_company WHERE user_id = ? LIMIT 1");
    $stmtLink->execute([$currentUserId]);
    $link = $stmtLink->fetch();

    if ($link) {
        // 3. Busca os dados dessa empresa específica
        $stmtComp = $pdo->prepare("SELECT id, name FROM companies WHERE id = ?");
        $stmtComp->execute([$link['company_id']]);
        $companies = $stmtComp->fetchAll();
    } else {
        // Se não achar vínculo, lista vazia
        $companies = [];
    }
}

/**
 * Lista de cargos para checkboxes
 */
$roles = $pdo->query("SELECT id, name FROM roles ORDER BY name")->fetchAll();
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ ESTILOS ESPECÍFICOS DO FORMULÁRIO                                     ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<style>
/* ═══════════════════════════════════════════════════════════════════════════
   LAYOUT DO FORMULÁRIO
   ═══════════════════════════════════════════════════════════════════════════ */
.form-grid{
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 14px;
}

@media(max-width: 980px){
    .form-grid{ grid-template-columns: 1fr }
}

/* ═══════════════════════════════════════════════════════════════════════════
   SEÇÕES DO CARD
   ═══════════════════════════════════════════════════════════════════════════ */
.card-section{
    padding: 12px; 
    border: 1px solid var(--stroke); 
    border-radius: 12px; 
    background: rgba(255,255,255,.04);
}

/* ═══════════════════════════════════════════════════════════════════════════
   ELEMENTOS DIVERSOS
   ═══════════════════════════════════════════════════════════════════════════ */
.badge-btn{
    border: 1px dashed var(--stroke); 
    background: transparent; 
    color: #e8edf7; 
    border-radius: 12px; 
    padding: .6rem .9rem; 
    cursor: pointer;
}

.small{ font-size: .9rem; color: #9aa4b2 }

/* Preview do avatar */
.preview{
    width: 96px; 
    height: 96px; 
    border-radius: 16px; 
    border: 1px solid var(--stroke); 
    object-fit: cover; 
    background: rgba(255,255,255,.06);
}
</style>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ FORMULÁRIO DE CADASTRO                                                ║
     ║                                                                        ║
     ║ Envia dados via POST para collaborator_save.php                       ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<form class="card" style="padding:16px" method="POST" action="<?= url_for('/pages/collaborator_save.php') ?>">
    
    <!-- Cabeçalho -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px">
        <h2 style="margin:0; font-weight:900">Novo Colaborador</h2>
        <button class="button" type="submit" id="btnSave">Salvar</button>
    </div>

    <div class="form-grid">
        
        <!-- ════════════════════════════════════════════════════════════════
             COLUNA 1: Dados Pessoais
             ════════════════════════════════════════════════════════════════ -->
        <div class="card-section">
            
            <!-- Foto (URL) + Preview -->
            <div style="display:flex; gap:12px; align-items:center">
                <img id="avatarPrev" class="preview" src="<?= url_for('/assets/img/avatar.svg') ?>" alt="">
                <div style="flex:1">
                    <label class="label">Foto (URL)</label>
                    <input class="input" name="avatar_url" id="avatarUrl" placeholder="/assets/img/users/julia.png ou https://...">
                    <div class="small">Dica: use 256×256px.</div>
                </div>
            </div>

            <!-- Nome completo -->
            <label class="label" style="margin-top:10px">Nome completo*</label>
            <input class="input" name="name" required>

            <!-- Email e Senha -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px">
                <div>
                    <label class="label">Email*</label>
                    <input class="input" name="email" type="email" required>
                </div>
                <div>
                    <label class="label">Senha (opcional)</label>
                    <input class="input" name="password" type="password" placeholder="deixe vazio p/ definir depois">
                </div>
            </div>

            <!-- Aniversário e Telefone -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px">
                <div>
                    <label class="label">Aniversário</label>
                    <input class="input" name="birthday" type="date">
                </div>
                <div>
                    <label class="label">Telefone</label>
                    <input class="input" name="phone" placeholder="(DDD) 99999-9999">
                </div>
            </div>

            <!-- Status e Tipo -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px">
                <div>
                    <label class="label">Status</label>
                    <select class="input" name="status">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
                <div>
                    <label class="label">Tipo</label>   <!-- filtrando possibildiade de criar outro gestor e admin-->
                    <select class="input" name="type" id="typeSelect">
                        <option value="Colaborador">Colaborador</option>
                        <?php if (isAdmin()): ?>
                            <option value="Admin">Admin</option>
                            <option value="Gestor">Gestor</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════
             COLUNA 2: Empresas, Cargos e Observações
             ════════════════════════════════════════════════════════════════ -->
        <div class="card-section">
            
            <!-- Empresas (checkboxes) -->
            <label class="label">Empresas (acesso)</label>
            <div class="card" style="padding:10px; max-height:180px; overflow:auto">
                <?php foreach ($companies as $c): ?>
                    <label style="display:flex; align-items:center; gap:8px; margin:6px 0">
                        <input type="checkbox" name="companies[]" value="<?= (int)$c['id'] ?>" 
                            <?= (!isAdmin()) ? 'checked onclick="return false;"' : '' ?>> 
                        <?= htmlspecialchars($c['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Cargos (checkboxes) -->
            <label class="label" style="margin-top:10px">Cargo(s)</label>
            <div class="card" style="padding:10px; max-height:220px; overflow:auto">
                <?php foreach ($roles as $r): ?>
                    <label style="display:flex; align-items:center; gap:8px; margin:6px 0">
                        <input type="checkbox" name="roles[]" value="<?= (int)$r['id'] ?>"> 
                        <?= htmlspecialchars($r['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Observações -->
            <label class="label" style="margin-top:10px">Observações</label>
            <textarea class="input" name="notes" rows="5" placeholder="Ex.: horário, restrições, documentação..."></textarea>
        </div>
    </div>

    <!-- Botões de ação (rodapé) -->
    <div style="margin-top:14px; display:flex; gap:8px">
        <button class="button" type="submit" id="btnSave2">Salvar</button>
        <a class="button ghost" href="<?= url_for('/pages/colaboradores.php') ?>">Cancelar</a>
    </div>
</form>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ JAVASCRIPT: Preview de Avatar + Loading nos Botões                    ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<script>
/**
 * Referências aos elementos
 */
const avatarUrl  = document.getElementById('avatarUrl');
const avatarPrev = document.getElementById('avatarPrev');

/**
 * Atualiza preview da foto quando URL é digitada
 */
[avatarUrl].forEach(el => {
    el.addEventListener('input', () => { 
        if (el.value.trim()) {
            avatarPrev.src = el.value.trim(); 
        }
    });
});

/**
 * setLoading() — Controla estado de loading do botão
 * 
 * @param {Element} el - Elemento do botão
 * @param {boolean} isLoading - true para ativar loading
 */
function setLoading(el, isLoading) {
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
 * Ativa loading nos botões ao submeter
 */
const btnSave = document.getElementById('btnSave');
const btnSave2 = document.getElementById('btnSave2');

document.querySelector('form').addEventListener('submit', () => { 
    setLoading(btnSave, true); 
    setLoading(btnSave2, true); 
});


// Transforma o valor do PHP em uma constante que o navegador entende

const GESTOR_COMPANY_ID = <?= (int)$userCompanyId ?>;


//esse escopo serve para o admin e o gestor terem todos os direitos disponiveis , as caixinhas marcadas
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('select[name="type"]');
    const roleChecks = document.querySelectorAll('input[name="roles[]"]');
    const companyChecks = document.querySelectorAll('input[name="companies[]"]');

    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            const selectedType = this.value;

            if (selectedType === 'Admin' || selectedType === 'Gestor') {
                // 1. MARCA TUDO: Para Admin ou Gestor
                roleChecks.forEach(cb => cb.checked = true);

                // Se for especificamente Gestor, marca a empresa dele
                if (selectedType === 'Gestor' && GESTOR_COMPANY_ID > 0) {
                    companyChecks.forEach(check => {
                        check.checked = (parseInt(check.value) === GESTOR_COMPANY_ID);
                    });
                }
            } else {
                // 2. DESMARCA TUDO: Se for Colaborador
                roleChecks.forEach(cb => cb.checked = false);
                companyChecks.forEach(cb => cb.checked = false);
            }
        });
    }
});


</script>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>


