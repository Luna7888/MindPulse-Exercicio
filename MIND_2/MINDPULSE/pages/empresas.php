<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ EMPRESAS.PHP — Gestão de Empresas (Multi-tenant)                         ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Listar empresas vinculadas ao usuário e permitir          ║
 * ║                cadastro de novas empresas via slide-over                 ║
 * ║                                                                           ║
 * ║ @acesso        Admin Geral | Gestor (canAccessAdmin)                     ║
 * ║ @escopo        Empresas do usuário logado (user_company)                 ║
 * ║                                                                           ║
 * ║ @exibe         - Lista de empresas com logo, nome, CNPJ, status          ║
 * ║                - Busca por nome ou CNPJ                                  ║
 * ║                - Slide-over para cadastro de nova empresa                ║
 * ║                                                                           ║
 * ║ @tolerância    Funciona com schema rico ou minimalista                   ║
 * ║                (fallback se colunas opcionais não existirem)             ║
 * ║                                                                           ║
 * ║ @ajax          company_save.php (criar nova empresa)                     ║
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
 * Verifica permissão administrativa
 */
if (!canAccessManager()) { 
    http_response_code(403); 
    echo '<div class="card" style="padding:20px">Acesso negado</div>'; 
    require_once __DIR__ . '/../includes/layout_end.php'; 
    exit; 
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CARREGAMENTO DE EMPRESAS
// ═══════════════════════════════════════════════════════════════════════════

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CARREGAMENTO DE EMPRESAS (CORRIGIDO)
// ═══════════════════════════════════════════════════════════════════════════

$userId = (int)$_SESSION['user']['id'];
$companies = [];

try {
    if (isAdmin()) {
        // 👑 SE FOR ADMIN: Busca TODAS as empresas do sistema (sem JOIN)
        $sql = "
            SELECT c.id, c.name, c.trade_name, c.document, c.logo_url, c.is_active, c.created_at
            FROM companies c
            ORDER BY COALESCE(c.created_at, NOW()) DESC
        ";
        $st = $pdo->query($sql); // Executa direto
        $companies = $st->fetchAll(PDO::FETCH_ASSOC);

    } else {
        // 👤 SE FOR GESTOR: Busca apenas as vinculadas (com JOIN)
        $sql = "
            SELECT c.id, c.name, c.trade_name, c.document, c.logo_url, c.is_active, c.created_at
            FROM companies c
            JOIN user_company uc ON uc.company_id = c.id
            WHERE uc.user_id = ?
            ORDER BY COALESCE(c.created_at, NOW()) DESC
        ";
        $st = $pdo->prepare($sql); 
        $st->execute([$userId]);
        $companies = $st->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Throwable $e) {
    // Fallback de segurança (caso falte alguma coluna no banco)
    // Se der erro, tenta buscar o mínimo necessário
    if (isAdmin()) {
        $sql = "SELECT id, name FROM companies ORDER BY id DESC";
        $st = $pdo->query($sql);
    } else {
        $sql = "SELECT c.id, c.name FROM companies c JOIN user_company uc ON uc.company_id = c.id WHERE uc.user_id = ?";
        $st = $pdo->prepare($sql);
        $st->execute([$userId]);
    }
    
    // Normaliza o resultado para evitar erros no HTML
    $companies = array_map(function($r) {
        return [
            'id'         => $r['id'],
            'name'       => $r['name'],
            'trade_name' => null,
            'document'   => null,
            'logo_url'   => null,
            'is_active'  => 1,
            'created_at' => null,
        ];
    }, $st->fetchAll(PDO::FETCH_ASSOC));
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÃO AUXILIAR
// ═══════════════════════════════════════════════════════════════════════════

/**
 * cval() — Retorna valor do array ou default
 * 
 * @param array $row Linha de dados
 * @param string $key Chave a buscar
 * @param string $def Valor default se não existir
 * @return mixed Valor encontrado ou default
 */
function cval($row, $key, $def = '—') { 
    return isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null ? $row[$key] : $def; 
}
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ ESTILOS ESPECÍFICOS DA PÁGINA                                         ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<style>
/* ═══════════════════════════════════════════════════════════════════════════
   TOOLBAR (Busca + Botão Nova Empresa)
   ═══════════════════════════════════════════════════════════════════════════ */
.toolbar{
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 10px;
}

/* Campo de busca */
.search{
    display: flex; 
    align-items: center; 
    gap: 8px; 
    border: 1px solid var(--stroke); 
    border-radius: 12px; 
    padding: 8px 10px; 
    background: rgba(255,255,255,.04);
}

.search input{
    background: transparent; 
    border: none; 
    outline: none; 
    color: #e8edf7; 
    min-width: 220px;
}

/* ═══════════════════════════════════════════════════════════════════════════
   TABELA DE EMPRESAS
   ═══════════════════════════════════════════════════════════════════════════ */
.table{
    width: 100%; 
    border-collapse: separate; 
    border-spacing: 0 10px;
}

.row{
    display: grid; 
    grid-template-columns: 60px 1.4fr 1.2fr 1fr .7fr .8fr; 
    gap: 10px; 
    align-items: center; 
    border: 1px solid var(--stroke); 
    border-radius: 12px; 
    background: rgba(255,255,255,.04); 
    padding: 10px;
}

/* Logo da empresa */
.logo{
    width: 46px; 
    height: 46px; 
    border-radius: 12px; 
    object-fit: cover; 
    border: 1px solid var(--stroke); 
    background: #121624;
}

/* Badges de status */
.badge.on{ background: #36cfc9; color: #0f1117 }
.badge.off{ background: #ff4d4f; color: #0f1117 }

.small{ color: #9aa4b2; font-size: .9rem }

/* ═══════════════════════════════════════════════════════════════════════════
   SLIDE-OVER (Painel lateral para nova empresa)
   ═══════════════════════════════════════════════════════════════════════════ */
.overlay{
    position: fixed; 
    inset: 0; 
    background: rgba(0,0,0,.45); 
    opacity: 0; 
    pointer-events: none; 
    transition: .2s; 
    z-index: 1200;
}

.overlay.show{ opacity: 1; pointer-events: auto }

.panel{
    position: fixed; 
    top: 0; 
    right: -520px; 
    width: 520px; 
    max-width: 100%; 
    height: 100%; 
    z-index: 1210;
    background: linear-gradient(160deg, rgba(20,24,36,.98), rgba(20,24,36,.92));
    border-left: 1px solid var(--stroke); 
    box-shadow: -8px 0 24px rgba(0,0,0,.25);
    transition: .3s ease; 
    display: flex; 
    flex-direction: column;
}

.panel.open{ right: 0 }

.panel header{
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    padding: 14px; 
    border-bottom: 1px solid var(--stroke);
}

.panel .body{ padding: 14px; overflow: auto }

/* Input dentro do painel */
.input{
    width: 100%; 
    padding: 10px; 
    border: 1px solid var(--stroke); 
    border-radius: 10px; 
    background: rgba(255,255,255,.04); 
    color: #e8edf7;
}

/* Estado de loading no botão */
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
</style>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ TOOLBAR: Busca + Botão Nova Empresa                                   ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<div class="toolbar">
    <!-- Campo de busca -->
    <div class="search">
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
            <path d="M21 20l-5.6-5.6a7 7 0 10-1.4 1.4L20 21zM4 10a6 6 0 1112 0A6 6 0 014 10z"/>
        </svg>
        <input id="q" placeholder="Buscar empresa por nome ou CNPJ...">
    </div>
    
    <!-- Botão para abrir slide-over -->
    <button class="button" id="btnNew">+ Nova empresa</button>
</div>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ LISTA DE EMPRESAS                                                     ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<?php if (empty($companies)): ?>
    <div class="card" style="padding:20px">
        Você ainda não está vinculado a nenhuma empresa.
    </div>
<?php else: ?>
    <div id="list" style="display:flex; flex-direction:column; gap:10px">
        <?php foreach ($companies as $c):
            /**
             * Prepara texto para busca (nome + nome fantasia + CNPJ)
             */
            $fulltext = mb_strtolower(trim(
                ($c['name'] ?? '') . ' ' . 
                ($c['trade_name'] ?? '') . ' ' . 
                ($c['document'] ?? '')
            ));
            
            /**
             * Logo com fallback
             */
            $logo = cval($c, 'logo_url', url_for('/assets/img/logo.png'));
            
            /**
             * Status ativo/inativo
             */
            $isActive = (int)($c['is_active'] ?? 1) === 1;
            
            /**
             * Data de criação formatada
             */
            $created = $c['created_at'] ? date('d/m/Y', strtotime($c['created_at'])) : '—';
        ?>
            <!-- Linha da empresa -->
            <div class="row item" data-name="<?= htmlspecialchars($fulltext) ?>">
                <!-- Logo -->
                <img class="logo" src="<?= htmlspecialchars($logo) ?>" alt="">
                
                <!-- Nome e Nome Fantasia -->
                <div>
                    <div style="font-weight:900"><?= htmlspecialchars($c['name'] ?? '—') ?></div>
                    <div class="small"><?= htmlspecialchars(cval($c, 'trade_name')) ?></div>
                </div>
                
                <!-- CNPJ -->
                <div>
                    <div class="small">CNPJ</div>
                    <div><?= htmlspecialchars(cval($c, 'document')) ?></div>
                </div>
                
                <!-- Data de criação -->
                <div>
                    <div class="small">Criada em</div>
                    <div><?= $created ?></div>
                </div>
                
                <!-- Status -->
                <div>
                    <div class="small">Status</div>
                    <span class="badge <?= $isActive ? 'on' : 'off' ?>">
                        <?= $isActive ? 'Ativa' : 'Inativa' ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ SLIDE-OVER: Formulário de Nova Empresa                                ║
     ║                                                                        ║
     ║ Painel lateral que desliza da direita                                 ║
     ║ Contém formulário para cadastrar nova empresa                         ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->

<!-- Overlay (fundo escuro) -->
<div class="overlay" id="ovl"></div>

<!-- Painel lateral -->
<div class="panel" id="panel">
    <header>
        <strong>Nova empresa</strong>
        <button class="button ghost" id="btnClose">Fechar</button>
    </header>
    
    <div class="body">
        <form id="formNew" onsubmit="return false;">
            <!-- Nome jurídico -->
            <label class="label">Nome jurídico / Razão Social*</label>
            <input class="input" name="name" required>

            <!-- Nome fantasia -->
            <label class="label" style="margin-top:8px">Nome fantasia</label>
            <input class="input" name="trade_name">

            <!-- CNPJ -->
            <label class="label" style="margin-top:8px">CNPJ</label>
            <input class="input" name="document" placeholder="00.000.000/0001-00">

            <!-- Logo URL -->
            <label class="label" style="margin-top:8px">Logo (URL)</label>
            <input class="input" name="logo_url" placeholder="https://.../logo.png">

            <!-- Status -->
            <label class="label" style="margin-top:8px">Status</label>
            <select class="input" name="is_active">
                <option value="1" selected>Ativa</option>
                <option value="0">Inativa</option>
            </select>

            <!-- Botões de ação -->
            <div style="margin-top:12px; display:flex; gap:8px">
                <button class="button" id="btnSave">Salvar</button>
                <button class="button ghost" type="button" id="btnCancel">Cancelar</button>
            </div>
            
            <div class="small" style="margin-top:8px">
                Ao salvar, você será vinculado à empresa e ela aparecerá no seletor do topo.
            </div>
        </form>
    </div>
</div>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ JAVASCRIPT: Slide-over + Busca + Salvar                               ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<script>
/**
 * Referências aos elementos do slide-over
 */
const ovl = document.getElementById('ovl');
const pnl = document.getElementById('panel');
const btnNew = document.getElementById('btnNew');
const btnClose = document.getElementById('btnClose');
const btnCancel = document.getElementById('btnCancel');
const btnSave = document.getElementById('btnSave');

/**
 * Funções para abrir/fechar o slide-over
 */
function openPanel() { 
    ovl.classList.add('show'); 
    pnl.classList.add('open'); 
}

function closePanel() { 
    ovl.classList.remove('show'); 
    pnl.classList.remove('open'); 
}

/**
 * Event listeners para controle do slide-over
 */
btnNew.addEventListener('click', openPanel);
btnClose.addEventListener('click', closePanel);
btnCancel.addEventListener('click', closePanel);
ovl.addEventListener('click', closePanel);

/**
 * Busca em tempo real
 * Filtra empresas conforme o usuário digita
 */
const q = document.getElementById('q');
q.addEventListener('input', () => {
    const term = q.value.trim().toLowerCase();
    document.querySelectorAll('#list .item').forEach(row => {
        row.style.display = row.dataset.name.includes(term) ? '' : 'none';
    });
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
 * Salvar nova empresa
 * 
 * Coleta dados do formulário e envia para company_save.php
 * Recarrega a página em caso de sucesso
 */
btnSave.addEventListener('click', async () => {
    const f = document.getElementById('formNew');
    
    // Validação básica
    if (!f.name.value.trim()) { 
        alert('Informe o nome da empresa.'); 
        return; 
    }

    // Mostra loading
    btnSave.classList.add('is-loading');
    
    try {
        const res = await postJSON('<?= url_for("/pages/company_save.php") ?>', {
            name: f.name.value.trim(),
            trade_name: f.trade_name.value.trim(),
            document: f.document.value.trim(),
            logo_url: f.logo_url.value.trim(),
            is_active: f.is_active.value === '1'
        });
        
        if (res.status === 'ok') { 
            location.reload(); 
        } else { 
            alert(res.message || 'Falha ao salvar'); 
        }
    } catch(e) { 
        alert('Erro: ' + e.message); 
    } finally { 
        btnSave.classList.remove('is-loading'); 
    }
});
</script>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
