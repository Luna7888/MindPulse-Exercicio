<?php 
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ SIDEBAR.PHP — Menu Lateral Fixo (Desktop)                                ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Renderizar a barra de navegação lateral fixa              ║
 * ║                Visível apenas em desktop (>980px)                        ║
 * ║                                                                           ║
 * ║ @acesso        Usuários autenticados (todos os níveis)                   ║
 * ║ @escopo        Global (presente em todas as páginas do painel)           ║
 * ║                                                                           ║
 * ║ @componentes   - Logo da plataforma                                      ║
 * ║                - Menu de navegação (via menu_items.php)                  ║
 * ║                                                                           ║
 * ║ @responsivo    - Desktop (>980px): sidebar fixa à esquerda              ║
 * ║                - Mobile (≤980px): sidebar oculta, usa menu do header    ║
 * ║                                                                           ║
 * ║ @dependências  auth.php (url_for)                                        ║
 * ║                menu_items.php (itens do menu)                            ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

/**
 * Inclui funções de autenticação
 * Necessário para url_for() que gera URLs corretas
 */
require_once __DIR__ . '/auth.php'; 
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ ESTILOS DA SIDEBAR                                                    ║
     ║                                                                        ║
     ║ Inline para garantir carregamento imediato                            ║
     ║ Evita FOUC (Flash of Unstyled Content)                                ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<style>
/* ═══════════════════════════════════════════════════════════════════════════
   VARIÁVEIS CSS DA SIDEBAR
   ═══════════════════════════════════════════════════════════════════════════ */
:root{
  --mh-header-h:64px;                   /* Altura do header (para offset) */
  --mh-stroke: rgba(255,255,255,.12);   /* Cor de bordas */
}

/* ═══════════════════════════════════════════════════════════════════════════
   SIDEBAR FIXA
   
   Posicionada à esquerda, abaixo do header
   Ocupa toda a altura disponível
   Visível apenas em desktop (>980px)
   ═══════════════════════════════════════════════════════════════════════════ */
.mh-sidebar{
  position:fixed;               /* Fixa no viewport */
  top:var(--mh-header-h);       /* Começa abaixo do header */
  left:0;                       /* Alinhada à esquerda */
  bottom:0;                     /* Vai até o final da tela */
  width:260px;                  /* Largura fixa */
  z-index:500;                  /* Abaixo do header (1000) */
  
  /* Fundo com gradiente escuro */
  background:linear-gradient(160deg,rgba(20,24,36,.95),rgba(20,24,36,.88));
  
  /* Borda direita */
  border-right:1px solid var(--mh-stroke);
  
  /* Padding interno */
  padding:12px 10px;
  
  /* Scroll se conteúdo for maior que a tela */
  overflow:auto;
}

/* ═══════════════════════════════════════════════════════════════════════════
   SCROLLBAR CUSTOMIZADA
   
   Estilo elegante para a barra de rolagem
   ═══════════════════════════════════════════════════════════════════════════ */

/* Firefox */
.mh-sidebar{ 
  scrollbar-width:thin; 
  scrollbar-color: rgba(255,255,255,.25) transparent; 
}

/* WebKit (Chrome, Safari, Edge) */
.mh-sidebar::-webkit-scrollbar{ width:8px }
.mh-sidebar::-webkit-scrollbar-thumb{ 
  background:rgba(255,255,255,.25); 
  border-radius:10px 
}
.mh-sidebar::-webkit-scrollbar-track{ background:transparent }

/* ═══════════════════════════════════════════════════════════════════════════
   RESPONSIVIDADE: Oculta sidebar em mobile
   
   Em telas ≤980px, a navegação é feita pelo menu do header
   ═══════════════════════════════════════════════════════════════════════════ */
@media (max-width:980px){ .mh-sidebar{ display:none } }

/* ═══════════════════════════════════════════════════════════════════════════
   ESTILOS DO MENU DE NAVEGAÇÃO
   
   Usados tanto na sidebar quanto no menu mobile
   ═══════════════════════════════════════════════════════════════════════════ */

/* Container do menu */
.mh-nav{ 
  display:flex; 
  flex-direction:column; 
  gap:16px;                     /* Espaço entre seções */
}

/* Seção do menu (ex: Colaborador, Admin) */
.mh-section{ 
  display:flex; 
  flex-direction:column; 
  gap:6px;                      /* Espaço entre itens */
}

/* Título da seção */
.mh-sec-title{ 
  color:#9aa4b2;                /* Cinza claro */
  letter-spacing:.08em;         /* Espaçamento entre letras */
  font-weight:800; 
  text-transform:uppercase;     /* MAIÚSCULAS */
  font-size:.72rem; 
  margin:6px 6px;
}

/* Item do menu (link) */
.mh-item{
  display:flex; 
  align-items:center; 
  gap:10px;
  padding:10px 10px; 
  border-radius:12px; 
  color:#e8edf7;                /* Texto claro */
  text-decoration:none;
  border:1px solid transparent;
  transition:.18s ease;
  position:relative; 
  overflow:hidden;
}

/* Hover no item */
.mh-item:hover{ 
  background:rgba(255,255,255,.06); 
  border-color:var(--mh-stroke);
}

/* Container do ícone */
.mh-ico{ 
  width:28px;
  height:28px; 
  display:grid; 
  place-items:center;           /* Centraliza o ícone */
  border-radius:10px; 
  background:rgba(255,255,255,.06); 
  border:1px solid var(--mh-stroke);
}

/* Ícone SVG */
.mh-ico svg{ 
  width:16px;
  height:16px;
  color:#e8edf7;
}

/* Logo da marca na sidebar */
.mh-brand-side{
  display:flex; 
  align-items:center; 
  justify-content:center; 
  padding:6px 0 10px;
}

.mh-brand-side img{height:48px}
</style>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ ESTRUTURA HTML DA SIDEBAR                                             ║
     ║                                                                        ║
     ║ Contém logo + menu de navegação                                       ║
     ║ Os itens do menu vêm de menu_items.php (reutilizado no mobile)        ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<aside class="mh-sidebar" id="mhSidebar">
    <!-- Logo da plataforma -->
    <div class="mh-brand-side">
        <img src="<?= url_for('/assets/img/logo.png') ?>" alt="Mindhub">
    </div>
    
    <!-- Menu de navegação (incluído de arquivo separado para DRY) -->
    <?php include __DIR__ . '/menu_items.php'; ?>
</aside>
