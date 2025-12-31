<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ HEADER.PHP — Cabeçalho Fixo da Aplicação                                 ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Renderizar o header fixo no topo de todas as páginas      ║
 * ║                protegidas, com navegação e controles essenciais          ║
 * ║                                                                           ║
 * ║ @acesso        Usuários autenticados (todos os níveis)                   ║
 * ║ @escopo        Global (presente em todas as páginas do painel)           ║
 * ║                                                                           ║
 * ║ @componentes   - Logo/título da plataforma                               ║
 * ║                - Seletor de empresa (multiempresa)                       ║
 * ║                - Avatar do usuário com menu dropdown                     ║
 * ║                - Botão hambúrguer (mobile)                               ║
 * ║                - Menu mobile off-canvas                                  ║
 * ║                                                                           ║
 * ║ @responsivo    - Desktop (>980px): header completo, sidebar visível      ║
 * ║                - Mobile (≤980px): hambúrguer + menu overlay              ║
 * ║                                                                           ║
 * ║ @dependências  auth.php (url_for, sessão)                                ║
 * ║                menu_items.php (itens do menu mobile)                     ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INICIALIZAÇÃO E DADOS DA SESSÃO
// ═══════════════════════════════════════════════════════════════════════════

if (session_status() === PHP_SESSION_NONE) session_start();

// Importante: Incluir db.php para poder fazer consultas
require_once __DIR__ . '/db.php'; 
require_once __DIR__ . '/auth.php';

$user            = $_SESSION['user'] ?? null;
$currentCompany  = $_SESSION['current_company'] ?? null;

// ---------------------------------------------------------
// 🔒 LÓGICA DE EMPRESAS NO HEADER
// ---------------------------------------------------------
if (isAdmin()) {
    // ADMIN: Busca TUDO (*) para garantir que venha 'trade_name' e 'name'
    // Ordena pelo nome (razão social)
    $companies = $pdo->query("SELECT * FROM companies ORDER BY name ASC")->fetchAll();
    
    // Admin prefere ver a Razão Social (name)
    $displayKey = 'name'; 
} else {
    // GESTOR: Usa a lista da sessão
    $companies = $_SESSION['companies'] ?? [];
    
    // Gestor prefere ver o Nome Fantasia (trade_name)
    $displayKey = 'trade_name';
}
// ---------------------------------------------------------

$avatar = ($user['avatar_url'] ?? '') ?: url_for('/assets/img/avatar.svg');
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ ESTILOS DO HEADER                                                     ║
     ║                                                                        ║
     ║ Inline para garantir carregamento imediato (evita FOUC)               ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<style>
/* ═══════════════════════════════════════════════════════════════════════════
   VARIÁVEIS CSS DO HEADER
   ═══════════════════════════════════════════════════════════════════════════ */
:root{
  --mh-header-h: 64px;                    /* Altura do header */
  --mh-stroke: rgba(255,255,255,.12);     /* Cor de bordas */
  --mh-text:#e8edf7;                      /* Cor do texto */
  --mh-muted:#9aa4b2;                     /* Cor do texto secundário */
  --mh-brand:#ff6a00;                     /* Cor primária da marca */
  --mh-brand2:#ff9153;                    /* Cor secundária para gradientes */
}

/* ═══════════════════════════════════════════════════════════════════════════
   HEADER FIXO
   
   Posicionado no topo da tela, sempre visível
   Usa backdrop-filter para efeito de vidro fosco
   ═══════════════════════════════════════════════════════════════════════════ */
.mh-header{
  position:fixed;              /* Fixo no viewport */
  left:0; right:0; top:0;      /* Ocupa toda a largura no topo */
  z-index:1000;                /* Acima de outros elementos */
  height:var(--mh-header-h);   /* Altura definida por variável */
  
  /* Layout: 2 colunas (esquerda e direita) */
  display:grid;
  grid-template-columns:1fr auto;
  align-items:center;
  padding:10px 14px;
  
  /* Fundo com gradiente escuro e efeito de vidro */
  background:linear-gradient(180deg,rgba(20,24,36,.96),rgba(20,24,36,.88));
  border-bottom:1px solid var(--mh-stroke);
  backdrop-filter:blur(8px);
}

/* Lado esquerdo: burger + logo */
.mh-left{display:flex; align-items:center; gap:10px}

/* Título e subtítulo */
.mh-title{font-weight:900; color:var(--mh-text)}
.mh-sub{color:var(--mh-muted); margin-left:6px}

/* Em mobile: esconde título/subtítulo (mostra só burger) */
@media (max-width:980px){.mh-title,.mh-sub{display:none}}

/* Lado direito: select + avatar */
.mh-right{display:flex; align-items:center; gap:10px}

/* ═══════════════════════════════════════════════════════════════════════════
   SELECT DE EMPRESA
   
   Dropdown para trocar entre empresas (usuários multiempresa)
   Estilo claro para melhor legibilidade em qualquer fundo
   ═══════════════════════════════════════════════════════════════════════════ */
.mh-org{
  position:relative;
  display:flex;
  align-items:center;
  gap:8px;
  padding:6px 12px;
  min-width:220px;
  border-radius:999px;          /* Totalmente arredondado (pill) */
  background:#ffffff;           /* Fundo branco */
  border:1px solid #e5e7eb;     /* Borda cinza clara */
  box-shadow:0 1px 0 rgba(0,0,0,.05);
}

/* Select interno: remove aparência padrão */
.mh-org select{
  appearance:none;              /* Remove seta nativa do navegador */
  background:transparent;
  border:none;
  width:100%;
  color:#111827;                /* Texto escuro */
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;       /* Reticências se texto for longo */
  color-scheme: light;          /* Força tema claro no mobile */
}

/* Opções do select */
.mh-org select option{background:#ffffff; color:#111827}

/* Ícone de seta (chevron) */
.mh-org .chev{
  position:absolute;
  right:10px;
  width:16px;
  height:16px;
  color:#111827;
  opacity:.95;
}

/* Responsividade do select */
@media (max-width:980px){
  .mh-right{gap:8px}
  .mh-org{min-width:0; width:min(70vw, 320px); padding-right:34px}
  .mh-org .chev{right:8px}
}
@media (max-width:560px){.mh-org{width:calc(100vw - 120px)}}
@media (max-width:420px){
  .mh-org{width:calc(100vw - 110px)}
  .mh-user img{width:34px; height:34px}
}

/* ═══════════════════════════════════════════════════════════════════════════
   AVATAR DO USUÁRIO + SUBMENU
   ═══════════════════════════════════════════════════════════════════════════ */
.mh-userwrap{position:relative}

/* Botão do avatar */
.mh-user{
  display:flex;
  align-items:center;
  gap:8px;
  padding:4px;
  border-radius:14px;
  background:rgba(255,255,255,.04);
  border:1px solid var(--mh-stroke);
  cursor:pointer;
}

/* Foto de perfil */
.mh-user img{
  width:40px;
  height:40px;
  border-radius:12px;
  object-fit:cover;
  border:1px solid var(--mh-stroke);
}

/* Menu dropdown do usuário */
.mh-menu{
  position:absolute;
  right:0;
  top:calc(100% + 8px);
  min-width:180px;
  z-index:1100;
  display:none;                 /* Oculto por padrão */
  background:linear-gradient(160deg,rgba(20,24,36,.98),rgba(20,24,36,.92));
  border:1px solid var(--mh-stroke);
  border-radius:14px;
  padding:6px;
  box-shadow:0 12px 28px rgba(0,0,0,.35);
}

/* Menu visível (classe adicionada via JS) */
.mh-menu.show{display:block}

/* Links do menu */
.mh-menu a{
  display:flex;
  align-items:center;
  gap:10px;
  padding:10px 12px;
  border-radius:10px;
  color:#e8edf7;
  text-decoration:none;
}

.mh-menu a:hover{background:rgba(255,255,255,.06)}
.mh-menu svg{width:18px; height:18px}

/* ═══════════════════════════════════════════════════════════════════════════
   BOTÃO HAMBÚRGUER (Mobile)
   
   Visível apenas em telas ≤980px
   Abre o menu off-canvas
   ═══════════════════════════════════════════════════════════════════════════ */
.mh-burger{
  display:none;                 /* Oculto em desktop */
  width:40px;
  height:40px;
  border-radius:12px;
  border:1px solid var(--mh-stroke);
  background:rgba(255,255,255,.04);
  align-items:center;
  justify-content:center;
  gap:4px;
  cursor:pointer;
}

/* Linhas do hambúrguer */
.mh-burger span{
  display:block;
  width:18px;
  height:2px;
  background:#fff;
  border-radius:2px;
}

/* Mostra o burger em mobile */
@media (max-width:980px){.mh-burger{display:flex}}

/* ═══════════════════════════════════════════════════════════════════════════
   MENU MOBILE (Off-canvas)
   
   Painel que desliza do topo quando o hambúrguer é clicado
   Ocupa toda a área abaixo do header
   ═══════════════════════════════════════════════════════════════════════════ */

/* Backdrop: overlay escuro atrás do menu */
.mh-backdrop{
  position:fixed;
  inset:0;                      /* Cobre toda a tela */
  z-index:900;
  background:rgba(0,0,0,.45);
  opacity:0;                    /* Invisível por padrão */
  pointer-events:none;          /* Não intercepta cliques */
  transition:.2s ease;
}

.mh-backdrop.show{opacity:1; pointer-events:auto}

/* Painel do menu */
.mh-mpanel{
  position:fixed;
  left:0; right:0;
  top:var(--mh-header-h);       /* Começa abaixo do header */
  z-index:950;
  height:calc(100vh - var(--mh-header-h));  /* Ocupa o resto da tela */
  background:linear-gradient(160deg,rgba(20,24,36,.98),rgba(20,24,36,.92));
  border-bottom:1px solid var(--mh-stroke);
  transform:translateY(-12px);  /* Começa ligeiramente acima */
  opacity:0;
  pointer-events:none;
  transition:.22s ease;
  overflow:hidden;
}

/* Menu aberto */
.mh-mpanel.open{transform:translateY(0); opacity:1; pointer-events:auto}

/* Área rolável interna */
.mh-mpanel .inner{
  height:100%;
  overflow-y:auto;
  -webkit-overflow-scrolling:touch;  /* Scroll suave no iOS */
  padding:12px 12px 18px;
  scrollbar-width:thin;
  scrollbar-color: rgba(255,255,255,.3) transparent;
}

/* Scrollbar customizada (WebKit) */
.mh-mpanel .inner::-webkit-scrollbar{width:8px}
.mh-mpanel .inner::-webkit-scrollbar-thumb{background:rgba(255,255,255,.28); border-radius:10px}
.mh-mpanel .inner::-webkit-scrollbar-track{background:transparent}

/* Gradientes de fade indicando scroll disponível */
.mh-mpanel::before,
.mh-mpanel::after{
  content:"";
  position:sticky;
  left:0; right:0;
  display:block;
  z-index:1;
  pointer-events:none;
  height:14px;
}

.mh-mpanel::before{top:0; background:linear-gradient(180deg,rgba(20,24,36,1),rgba(20,24,36,0))}
.mh-mpanel::after{bottom:0; margin-top:-14px; background:linear-gradient(0deg,rgba(20,24,36,1),rgba(20,24,36,0))}

/* Trava scroll do body quando menu está aberto */
body.mh-lock{overflow:hidden; touch-action:none; overscroll-behavior:contain}

/* Esconde menu mobile em desktop */
@media (min-width:981px){.mh-backdrop,.mh-mpanel{display:none}}
</style>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ ESTRUTURA HTML DO HEADER                                              ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<header class="mh-header">
    <!-- ════════════════════════════════════════════════════════════════════
         LADO ESQUERDO: Burger + Logo
         ════════════════════════════════════════════════════════════════════ -->
    <div class="mh-left">
        <!-- Botão hambúrguer (visível apenas em mobile) -->
        <button class="mh-burger" id="mhBurger" aria-label="Abrir menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        
        <!-- Logo/título (visível apenas em desktop) -->
        <div>
            <div class="mh-title">Mindhub</div>
            <div class="mh-sub">RH &amp; Treinamentos</div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════
         LADO DIREITO: Select de empresa + Avatar
         ════════════════════════════════════════════════════════════════════ -->
    <div class="mh-right">
        <!-- Seletor de empresa (apenas se usuário tem acesso a empresas) -->
      <?php 
        // Só mostra o seletor se for Admin OU se tiver mais de 1 empresa na lista
        if (!empty($companies) && (isAdmin() || count($companies) > 1)): 
        ?>
          <div class="mh-org">
              <select id="mhSelectOrg" onchange="mhSwitchOrg(this)" aria-label="Selecionar organização">
                  <?php foreach ($companies as $c): ?>
                      <?php 
                          // Define qual nome mostrar (Razão Social p/ Admin, Fantasia p/ Gestor)
                          $displayKey = isAdmin() ? 'name' : 'trade_name';
                          $nameToShow = $c[$displayKey] ?? $c['trade_name'] ?? $c['name'] ?? ('Empresa #' . $c['id']);
                          
                          if (trim($nameToShow) === '') {
                              $nameToShow = $c['name'] ?? $c['trade_name'] ?? ('Empresa #' . $c['id']);
                          }
                      ?>
                      
                      <option
                          title="<?= htmlspecialchars($nameToShow) ?>"
                          value="<?= (int)$c['id'] ?>"
                          <?= ($currentCompany && $c['id'] == $currentCompany['id']) ? 'selected' : '' ?>>
                          <?= htmlspecialchars($nameToShow) ?>
                      </option>
                  <?php endforeach; ?>
              </select>
              <svg class="chev" viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5H7z"/></svg>
          </div>
      <?php endif; ?>

        <!-- Avatar do usuário com menu dropdown -->
        <div class="mh-userwrap">
            <!-- Botão do avatar -->
            <button class="mh-user" id="mhUserBtn" aria-haspopup="menu" aria-expanded="false" title="<?= htmlspecialchars($user['name'] ?? '') ?>">
                <img src="<?= htmlspecialchars($avatar) ?>" alt="Perfil">
            </button>
            
            <!-- Menu dropdown -->
            <nav class="mh-menu" id="mhUserMenu" role="menu" aria-label="Menu do usuário">
                <a href="<?= url_for('/pages/meus_dados.php') ?>" role="menuitem">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zm-9 9a9 9 0 1118 0H3z"/></svg>
                    Meu perfil
                </a>
                <a href="<?= url_for('/auth/logout.php') ?>" role="menuitem">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 13v-2H7V7l-5 5 5 5v-4h9zM20 3h-8v2h8v14h-8v2h8a2 2 0 002-2V5a2 2 0 00-2-2z"/></svg>
                    Sair
                </a>
            </nav>
        </div>
    </div>
</header>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ MENU MOBILE (Off-canvas)                                              ║
     ║                                                                        ║
     ║ Backdrop + painel com menu de navegação                               ║
     ║ Abre quando o hambúrguer é clicado                                    ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->

<!-- Overlay escuro (fecha o menu ao clicar) -->
<div class="mh-backdrop" id="mhBackdrop"></div>

<!-- Painel do menu -->
<div class="mh-mpanel" id="mhMpanel">
    <div class="inner">
        <!-- Logo -->
        <div class="logo">
            <img style="height: 60px;" src="<?= url_for('/assets/img/logo.png') ?>" alt="Mindhub">
        </div>
        
        <!-- Itens do menu (mesmo conteúdo da sidebar) -->
        <?php include __DIR__ . '/menu_items.php'; ?>
    </div>
</div>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ JAVASCRIPT DO HEADER                                                  ║
     ║                                                                        ║
     ║ Funções:                                                              ║
     ║ - mhPostJSON: requisições AJAX genéricas                              ║
     ║ - mhSwitchOrg: trocar empresa via AJAX                                ║
     ║ - Toggle do menu mobile                                               ║
     ║ - Toggle do submenu do avatar                                         ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<script>
/**
 * mhPostJSON() — Envia requisição POST com JSON
 * 
 * @param {string} url - URL do endpoint
 * @param {object} payload - Dados a enviar
 * @returns {object} - Resposta parseada como JSON
 * 
 * @exemplo
 * const res = await mhPostJSON('/api/endpoint', {key: 'value'});
 * if (res.status === 'ok') { ... }
 */
async function mhPostJSON(url, payload){
    const r = await fetch(url, {
        method:'POST', 
        headers:{'Content-Type':'application/json'}, 
        body:JSON.stringify(payload||{})
    });
    const t = await r.text(); 
    try {
        return JSON.parse(t);
    } catch(e) {
        return {status:'error', message:t};
    }
}

/**
 * mhSwitchOrg() — Troca a empresa ativa
 * 
 * Chamada quando o usuário seleciona outra empresa no dropdown
 * Envia requisição AJAX e recarrega a página se sucesso
 * 
 * @param {HTMLSelectElement} sel - Elemento select
 */
async function mhSwitchOrg(sel){
    if(!sel?.value) return;
    
    const res = await mhPostJSON('<?= url_for("/auth/switch_company.php") ?>', {
        company_id: parseInt(sel.value, 10)
    });
    
    if(res.status === 'ok') {
        location.reload();  // Recarrega para atualizar dados da nova empresa
    } else {
        alert(res.message || 'Não foi possível trocar a organização.');
    }
}

/**
 * IIFE: Controle do menu mobile
 * 
 * Gerencia abertura/fechamento do menu off-canvas
 * Bloqueia scroll do body quando menu está aberto
 */
(function(){
    const burger = document.getElementById('mhBurger');
    const panel  = document.getElementById('mhMpanel');
    const back   = document.getElementById('mhBackdrop');

    /**
     * Abre o menu mobile
     */
    function open(){
        panel.classList.add('open');
        back.classList.add('show');
        burger?.setAttribute('aria-expanded','true');
        document.body.classList.add('mh-lock');  // Bloqueia scroll do fundo
        
        // Reseta scroll do menu ao abrir
        const inner = panel.querySelector('.inner'); 
        inner && (inner.scrollTop = 0);
    }
    
    /**
     * Fecha o menu mobile
     */
    function close(){
        panel.classList.remove('open');
        back.classList.remove('show');
        burger?.setAttribute('aria-expanded','false');
        document.body.classList.remove('mh-lock');  // Libera scroll do fundo
    }

    // Toggle ao clicar no hambúrguer
    burger?.addEventListener('click', ()=> panel.classList.contains('open') ? close() : open());
    
    // Fecha ao clicar no backdrop
    back?.addEventListener('click', close);
    
    // Previne scroll do fundo ao arrastar no backdrop
    back?.addEventListener('touchmove', (e)=>{ e.preventDefault(); }, { passive:false });
    
    // Fecha ao clicar em links dentro do menu
    panel?.addEventListener('click', e=>{ if(e.target.closest('a')) close(); });
})();

/**
 * IIFE: Controle do submenu do avatar
 * 
 * Gerencia abertura/fechamento do dropdown do usuário
 */
(function(){
    const btn = document.getElementById('mhUserBtn');
    const menu = document.getElementById('mhUserMenu');
    
    /**
     * Toggle do menu
     */
    function toggle(){ 
        const on = !menu.classList.contains('show'); 
        menu.classList.toggle('show', on); 
        btn.setAttribute('aria-expanded', on?'true':'false'); 
    }
    
    /**
     * Fecha o menu
     */
    function close(){ 
        menu.classList.remove('show'); 
        btn.setAttribute('aria-expanded','false'); 
    }
    
    // Toggle ao clicar no avatar
    btn?.addEventListener('click', (e)=>{ e.stopPropagation(); toggle(); });
    
    // Fecha ao clicar fora
    document.addEventListener('click', (e)=>{ if(!e.target.closest('.mh-userwrap')) close(); });
    
    // Fecha com ESC
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') close(); });
})();
</script>
