<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ LOGIN.PHP — Página de Autenticação da Plataforma Mindpulse               ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Exibir formulário de login e permitir autenticação        ║
 * ║                Esta é a porta de entrada para todos os usuários:         ║
 * ║                Admin Geral, Gestor e Colaborador                         ║
 * ║                                                                           ║
 * ║ @acesso        Público (qualquer pessoa pode ver esta página)            ║
 * ║ @escopo        Global (não depende de empresa - pré-autenticação)        ║
 * ║                                                                           ║
 * ║ @fluxo         1. Usuário acessa /login.php                              ║
 * ║                2. Se já estiver logado → redireciona para /pages/home    ║
 * ║                3. Se não estiver logado → exibe formulário               ║
 * ║                4. Ao submeter → envia para /auth/do_login.php            ║
 * ║                                                                           ║
 * ║ @dependências  includes/auth.php (sessão, url_for, BASE_URL)             ║
 * ║                assets/css/styles.css (estilos globais)                   ║
 * ║                assets/img/ (logo, imagens do layout)                     ║
 * ║                                                                           ║
 * ║ @segurança     - Não expõe dados sensíveis                               ║
 * ║                - Formulário usa POST (não expõe senha na URL)            ║
 * ║                - Redireciona usuários já autenticados                    ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INICIALIZAÇÃO E VERIFICAÇÕES
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Inicia a sessão PHP se ainda não estiver ativa
 * 
 * session_status() retorna:
 * - PHP_SESSION_DISABLED (0): sessões desabilitadas
 * - PHP_SESSION_NONE (1): sessões habilitadas, mas nenhuma existe
 * - PHP_SESSION_ACTIVE (2): sessão já está ativa
 * 
 * Só chamamos session_start() se for PHP_SESSION_NONE para evitar warnings
 */
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Inclui o arquivo de autenticação
 * Fornece: url_for(), requireLogin(), isAdmin(), BASE_URL, etc.
 */
require_once __DIR__ . '/includes/auth.php';

/**
 * VERIFICAÇÃO DE USUÁRIO JÁ LOGADO
 * 
 * Se $_SESSION['user'] existe e não está vazio, significa que o usuário
 * já está autenticado. Neste caso, não faz sentido mostrar o login.
 * 
 * Redirecionamos para a home do painel para melhor UX.
 * O exit; é obrigatório após header() para parar a execução.
 */
if (!empty($_SESSION['user'])) { 
    header('Location: ' . url_for('/pages/home.php')); 
    exit; 
}

/**
 * CAPTURA DE ERRO DE LOGIN
 * 
 * Se o usuário veio redirecionado de do_login.php com erro,
 * a URL terá ?e=1 (ou outro código de erro)
 * 
 * $_GET['e'] ?? null usa o operador null coalescing:
 * - Se 'e' existe em $_GET, usa seu valor
 * - Se não existe, retorna null (evita undefined index)
 */
$error = $_GET['e'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ ESTRUTURA HTML DA PÁGINA DE LOGIN                                     ║
     ║ Layout: Grid de 2 colunas (Story + Formulário)                        ║
     ║ Responsivo: Em mobile, Story desaparece e formulário ocupa tudo       ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<head>
    <!-- ═══════════════════════════════════════════════════════════════════
         SEÇÃO: META TAGS E CONFIGURAÇÕES DO DOCUMENTO
         ═══════════════════════════════════════════════════════════════════ -->
    
    <!-- Define a codificação de caracteres como UTF-8 (suporta acentos, emojis, etc.) -->
    <meta charset="utf-8"/>
    
    <!-- Configura viewport para responsividade mobile
         width=device-width: largura igual à do dispositivo
         initial-scale=1: zoom inicial de 100% -->
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    
    <!-- Título da aba do navegador -->
    <title>Mindhub — Login</title>
    
    <!-- ═══════════════════════════════════════════════════════════════════
         SEÇÃO: FONTES EXTERNAS (Google Fonts)
         ═══════════════════════════════════════════════════════════════════ -->
    
    <!-- Preconnect: estabelece conexão antecipada com Google Fonts
         Isso acelera o carregamento das fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Fonte Inter: usada em toda a plataforma
         Pesos: 400 (normal), 600 (semi-bold), 900 (black/extra-bold)
         display=swap: mostra texto com fonte fallback enquanto carrega -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;900&display=swap" rel="stylesheet">
    
    <!-- ═══════════════════════════════════════════════════════════════════
         SEÇÃO: ESTILOS (CSS Global + Específico do Login)
         ═══════════════════════════════════════════════════════════════════ -->
    
    <!-- CSS global da aplicação (variáveis, componentes reutilizáveis) -->
    <link rel="stylesheet" href="<?= url_for('/assets/css/styles.css') ?>"/>
    
    <!-- ESTILOS ESPECÍFICOS DA PÁGINA DE LOGIN
         Estes estilos são inline porque são exclusivos desta página
         e não precisam ser carregados em outras partes do sistema -->
    <style>
    /* ═══════════════════════════════════════════════════════════════════════
       VARIÁVEIS CSS (Custom Properties)
       Definem a paleta de cores e são reutilizadas em todo o arquivo
       ═══════════════════════════════════════════════════════════════════════ */
    :root{
      --brand:#ff6a00;       /* Cor principal da marca (laranja vibrante) */
      --brand2:#ff9153;      /* Cor secundária/gradiente (laranja claro) */
      --bg:#0f1117;          /* Fundo principal (quase preto) */
      --panel:#141824;       /* Fundo de painéis/cards (cinza escuro) */
      --stroke:rgba(255,255,255,.12); /* Bordas sutis (branco 12% opacidade) */
      --text:#e8edf7;        /* Texto principal (branco suave) */
      --muted:#9aa4b2;       /* Texto secundário/desabilitado (cinza) */
    }
    
    /* Reset básico: todos os elementos usam box-sizing border-box
       Isso faz padding e border serem incluídos na largura total */
    *{box-sizing:border-box}
    
    /* ═══════════════════════════════════════════════════════════════════════
       BODY: Configuração principal da página
       ═══════════════════════════════════════════════════════════════════════ */
    body{
      margin:0;  /* Remove margem padrão do navegador */
      
      /* BACKGROUND: Efeito de gradientes radiais sobrepostos
         Cria um efeito de "luz" vindo dos cantos da tela */
      background:
        /* Gradiente 1: Luz laranja no canto superior esquerdo */
        radial-gradient(1200px 600px at 10% -10%, rgba(255,106,0,.18), transparent 60%),
        /* Gradiente 2: Luz laranja mais suave no canto inferior direito */
        radial-gradient(900px 500px at 110% 110%, rgba(255,145,83,.14), transparent 60%),
        /* Cor de fundo sólida por baixo */
        var(--bg);
      
      color:var(--text);  /* Cor do texto padrão */
      
      /* Fonte: Inter como principal, com fallbacks para cada sistema operacional */
      font-family:Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      
      /* Centraliza o conteúdo vertical e horizontalmente */
      min-height:100vh;      /* Altura mínima = 100% da viewport */
      display:flex;          /* Flexbox para centralização */
      align-items:center;    /* Centraliza verticalmente */
      justify-content:center;/* Centraliza horizontalmente */
    }
    
    /* ═══════════════════════════════════════════════════════════════════════
       AUTH-SHELL: Container principal do conteúdo de login
       ═══════════════════════════════════════════════════════════════════════ */
    .auth-shell{
      width:100%;           /* Ocupa toda a largura disponível */
      max-width:1100px;     /* Mas não passa de 1100px (legibilidade) */
      padding:16px;         /* Espaçamento interno para mobile */
    }
    
    /* ═══════════════════════════════════════════════════════════════════════
       GRID: Layout de 2 colunas (Story à esquerda, Form à direita)
       ═══════════════════════════════════════════════════════════════════════ */
    .grid{
      display:grid;
      grid-template-columns: 420px 1fr; /* Coluna 1: 420px fixo, Coluna 2: resto */
      gap:18px;                          /* Espaço entre as colunas */
    }
    
    /* RESPONSIVIDADE: Em telas menores que 980px, vira coluna única */
    @media (max-width: 980px){ 
      .grid{ grid-template-columns:1fr; } /* Apenas 1 coluna */
    }

    /* ═══════════════════════════════════════════════════════════════════════
       STORY: Lado esquerdo com imagem estilo "Instagram Story"
       Propósito: Visual atrativo e branding da plataforma
       ═══════════════════════════════════════════════════════════════════════ */
    .story-wrap{
      position:relative;      /* Para posicionamento de elementos internos */
      display:flex;           /* Flexbox para centralizar */
      align-items:center;     /* Centraliza verticalmente */
      justify-content:center; /* Centraliza horizontalmente */
    }
    
    .story{
      width:100%;             /* Largura total do container */
      max-width:420px;        /* Máximo 420px */
      aspect-ratio:9/16;      /* Proporção de story (vertical) */
      border-radius:28px;     /* Bordas bem arredondadas */
      overflow:hidden;        /* Esconde conteúdo que ultrapassa */
      border:1px solid var(--stroke); /* Borda sutil */
      background:#000;        /* Fundo preto (caso imagem não carregue) */
      position:relative;      /* Para o overlay e badges */
      
      /* Sombra elaborada para efeito de profundidade */
      box-shadow: 0 20px 60px rgba(0,0,0,.4), /* Sombra externa */
                  0 0 0 6px rgba(255,255,255,.03) inset; /* Brilho interno sutil */
    }
    
    /* Imagem dentro do story: cobre todo o espaço */
    .story img{
      width:100%; 
      height:100%; 
      object-fit:cover;       /* Cobre sem distorcer */
      filter:saturate(1.08) contrast(1.02); /* Leve ajuste de cores */
    }
    
    /* Overlay escuro na parte inferior do story (para legibilidade do texto) */
    .story::after{
      content:"";             /* Pseudo-elemento vazio */
      position:absolute;      /* Posicionado sobre a imagem */
      inset:0;                /* Cobre toda a área */
      pointer-events:none;    /* Não interfere em cliques */
      /* Gradiente: transparente em cima, escuro embaixo */
      background:linear-gradient(180deg, rgba(0,0,0,.05), rgba(0,0,0,.45));
    }
    
    /* Badge no topo do story (ex: "Mindhub • RH & Treinamentos") */
    .story-badge{
      position:absolute;      /* Posicionado no canto superior */
      top:10px; 
      left:10px;
      display:flex;           /* Flexbox para alinhar ícone + texto */
      align-items:center; 
      gap:8px;
      padding:8px 10px; 
      border-radius:999px;    /* Totalmente arredondado (pill) */
      border:1px solid var(--stroke);
      backdrop-filter: blur(6px); /* Efeito de vidro fosco */
      background:rgba(20,24,36,.5); /* Semi-transparente */
      font-weight:700;
    }
    
    /* Bolinha colorida dentro do badge */
    .story-badge .dot{
      width:10px;
      height:10px;
      border-radius:50%;      /* Círculo perfeito */
      background:linear-gradient(135deg,var(--brand),var(--brand2)); /* Gradiente laranja */
      box-shadow:0 0 8px rgba(255,106,0,.8); /* Brilho/glow */
    }
    
    /* Legenda na parte inferior do story */
    .story-caption{
      position:absolute;
      left:0; right:0; bottom:0; /* Alinhado na base */
      padding:16px;
      /* Gradiente para legibilidade sobre a imagem */
      background:linear-gradient(180deg, transparent, rgba(0,0,0,.65));
      font-weight:600;
    }
    
    .story-caption small{
      color:#cbd5e1;          /* Cor mais clara para subtexto */
      font-weight:400;        /* Peso normal */
    }

    /* ═══════════════════════════════════════════════════════════════════════
       PANEL: Lado direito com o formulário de login
       Propósito: Área funcional onde o usuário insere credenciais
       ═══════════════════════════════════════════════════════════════════════ */
    .panel{
      border:1px solid var(--stroke);
      border-radius:20px;     /* Bordas arredondadas */
      /* Fundo com gradiente sutil e semi-transparência */
      background:linear-gradient(160deg, rgba(20,24,36,.72), rgba(20,24,36,.58));
      backdrop-filter: blur(12px); /* Efeito de vidro fosco */
      padding:22px;
      display:flex;
      flex-direction:column;  /* Elementos empilhados verticalmente */
      gap:10px;               /* Espaço entre elementos */
      box-shadow: 0 10px 40px rgba(0,0,0,.35); /* Sombra de profundidade */
    }
    
    /* Cabeçalho do painel (título + badge) */
    .header{
      display:flex;
      align-items:center;
      justify-content:space-between; /* Título à esquerda, badge à direita */
      gap:10px;
    }
    
    .header .title{
      font-size:1.6rem;       /* Título grande */
      font-weight:900;        /* Extra bold */
    }
    
    /* Badge pequeno no header */
    .header .badge{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:6px 10px;
      border-radius:999px;    /* Pill shape */
      /* Fundo com gradiente sutil */
      background:linear-gradient(135deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
      border:1px solid var(--stroke);
    }
    
    .header .badge .dot{
      width:10px;
      height:10px;
      border-radius:50%;
      background:linear-gradient(135deg,var(--brand),var(--brand2));
    }
    
    /* Labels dos campos do formulário */
    .label{
      font-weight:600;
      color:gray;
      margin-top:6px;
      display:block;          /* Ocupa linha inteira */
    }
    
    /* Campos de input (email, senha) */
    .input{
      width:100%;
      padding:12px 44px 12px 12px; /* Padding extra à direita para ícone do olho */
      border-radius:12px;
      border:1px solid var(--stroke);
      background:rgba(255,255,255,.04); /* Fundo semi-transparente */
      color:var(--text);
      outline:none;           /* Remove outline padrão do navegador */
    }
    
    /* Estado de foco do input: destaque visual */
    .input:focus{
      border-color:rgba(255,145,83,.8); /* Borda laranja */
      box-shadow:0 0 0 3px rgba(255,145,83,.2); /* Glow suave */
    }
    
    /* Container do input (para posicionar o ícone do olho) */
    .row{position:relative}
    
    /* Ícone do olho (mostrar/ocultar senha) */
    .eye{
      position:absolute;
      right:10px;
      top:50%;
      transform:translateY(-50%); /* Centraliza verticalmente */
      width:24px;
      height:24px;
      color:#cbd5e1;
      cursor:pointer;
      opacity:.9;
    }
    
    /* Linha de ajuda (checkbox "Lembrar" + link "Esqueci senha") */
    .helper{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:8px;
      color:#cbd5e1;
      font-size:.92rem;
    }
    
    /* Estilo do checkbox */
    .checkbox input{
      vertical-align:middle;
      accent-color:var(--brand); /* Cor do checkbox quando marcado */
    }
    
    /* Botões (primário e ghost) */
    .button{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:8px;
      padding:12px 14px;
      border-radius:12px;
      border:1px solid var(--stroke);
      /* Gradiente laranja (cor principal da marca) */
      background:linear-gradient(135deg,var(--brand),var(--brand2));
      color:#0f1117;          /* Texto escuro para contraste */
      font-weight:800;
      text-decoration:none;
      transition:.2s transform ease; /* Animação suave no hover */
    }
    
    .button:hover{
      transform:translateY(-1px); /* Leve elevação no hover */
    }
    
    /* Botão ghost: transparente com borda */
    .button.ghost{
      background:rgba(255,255,255,.04);
      color:var(--text);
    }
    
    /* Linha divisória horizontal */
    .hr{
      height:1px;
      background:var(--stroke);
      margin:10px 0;
    }

    /* ═══════════════════════════════════════════════════════════════════════
       ALERT: Mensagem de erro de login
       Exibida quando as credenciais são inválidas
       ═══════════════════════════════════════════════════════════════════════ */
    .alert{
      padding:.8rem;
      border:1px solid rgba(239,68,68,.5); /* Borda vermelha */
      color:#fecaca;          /* Texto vermelho claro */
      background:rgba(239,68,68,.08); /* Fundo vermelho sutil */
      border-radius:12px;
    }

    /* ═══════════════════════════════════════════════════════════════════════
       ANIMAÇÃO: Efeito de flutuação do story
       Propósito: Adiciona vida e movimento sutil à página
       ═══════════════════════════════════════════════════════════════════════ */
    @keyframes float {
      0%{transform:translateY(0)}      /* Posição inicial */
      50%{transform:translateY(-6px)}  /* Sobe 6px no meio */
      100%{transform:translateY(0)}    /* Volta à posição inicial */
    }
    
    /* Aplica a animação ao story: 8 segundos, loop infinito */
    .story{animation:float 8s ease-in-out infinite}
    </style>
    
    <!-- ═══════════════════════════════════════════════════════════════════
         SEÇÃO: JAVASCRIPT GLOBAL
         Expõe BASE_URL para scripts que precisam construir URLs
         ═══════════════════════════════════════════════════════════════════ -->
    <script>
    /**
     * Variável global BASE_URL
     * 
     * Usada por scripts JavaScript para construir URLs corretas
     * Exemplo: se o site está em /MINDPULSE, BASE_URL = "/MINDPULSE"
     * 
     * htmlspecialchars previne XSS escapando caracteres especiais
     * ENT_QUOTES escapa tanto aspas simples quanto duplas
     */
    window.BASE_URL="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>";
    </script>
</head>

<body>
    <!-- ╔═══════════════════════════════════════════════════════════════════╗
         ║ AUTH-SHELL: Container principal de toda a página de login         ║
         ║ Centraliza o conteúdo e define largura máxima                     ║
         ╚═══════════════════════════════════════════════════════════════════╝ -->
    <div class="auth-shell">
        
        <!-- ╔═══════════════════════════════════════════════════════════════╗
             ║ GRID: Layout de 2 colunas                                      ║
             ║ Coluna 1: Story visual (branding)                              ║
             ║ Coluna 2: Formulário de login (funcional)                      ║
             ╚═══════════════════════════════════════════════════════════════╝ -->
        <div class="grid">

            <!-- ════════════════════════════════════════════════════════════
                 COLUNA ESQUERDA: STORY (Visual/Branding)
                 
                 Propósito: 
                 - Criar impacto visual positivo
                 - Comunicar a proposta de valor da plataforma
                 - Estilo inspirado em stories do Instagram
                 
                 Em mobile (< 980px): Esta seção fica oculta
                 ════════════════════════════════════════════════════════════ -->
            <div class="story-wrap">
                <div class="story">
                    <!-- Imagem principal do story
                         Proporção 9:16 (vertical, como story)
                         Pode ser trocada por outra imagem institucional -->
                    <img src="<?= url_for('/assets/img/login_story.jpg') ?>" alt="Mindhub Story">
                    
                    <!-- Badge no topo: identifica a marca -->
                    <div class="story-badge">
                        <span class="dot"></span> <!-- Bolinha laranja animada -->
                        Mindhub • RH & Treinamentos
                    </div>
                    
                    <!-- Legenda na base: proposta de valor -->
                    <div class="story-caption">
                        <div style="font-size:1.1rem">Aprendizado que vira performance</div>
                        <small>Trilhas gamificadas • Checklists • Feedbacks • Certificados</small>
                    </div>
                </div>
            </div>

            <!-- ════════════════════════════════════════════════════════════
                 COLUNA DIREITA: FORMULÁRIO DE LOGIN
                 
                 Propósito:
                 - Coletar credenciais do usuário (email + senha)
                 - Enviar para /auth/do_login.php via POST
                 - Exibir erros de autenticação quando houver
                 
                 Acesso: Público (qualquer pessoa pode ver)
                 Segurança: 
                 - Usa POST (não expõe senha na URL)
                 - novalidate desativa validação HTML5 (validamos no backend)
                 - autocomplete="off" evita sugestões do navegador
                 ════════════════════════════════════════════════════════════ -->
            <form class="panel" method="POST" action="<?= url_for('/auth/do_login.php') ?>" autocomplete="off" novalidate>
                
                <!-- Logo da plataforma -->
                <img src="<?= url_for('/assets/img/logo.png') ?>" style="width: 300px;" alt="Mindhub"> 
                
                <!-- Cabeçalho do formulário -->
                <div class="header">
                    <div>
                        <!-- Título de boas-vindas com emoji -->
                        <div class="title">Bem-vindo(a) 👋</div>
                        <!-- Subtítulo explicativo -->
                        <div style="color:var(--muted)">Acesse sua conta para continuar</div>
                    </div>
                    <!-- Badge da marca (decorativo) -->
                    <div class="badge">
                        <span class="dot"></span> 
                        Mindhub
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════
                     MENSAGEM DE ERRO (condicional)
                     
                     Exibida apenas quando $error não é null/vazio
                     Isso acontece quando do_login.php redireciona com ?e=1
                     ════════════════════════════════════════════════════════ -->
                <?php if($error): ?>
                    <div class="alert">Credenciais inválidas. Tente novamente.</div>
                <?php endif; ?>

                <!-- ════════════════════════════════════════════════════════
                     CAMPO: EMAIL
                     
                     name="email" - nome do campo para $_POST['email']
                     type="email" - validação básica de formato
                     required - campo obrigatório (validação HTML5)
                     ════════════════════════════════════════════════════════ -->
                <label class="label" for="email">E-mail</label>
                <div class="row">
                    <input 
                        class="input" 
                        id="email" 
                        name="email" 
                        type="email" 
                        placeholder="voce@empresa.com" 
                        required
                    />
                </div>

                <!-- ════════════════════════════════════════════════════════
                     CAMPO: SENHA
                     
                     name="password" - nome do campo para $_POST['password']
                     type="password" - oculta os caracteres digitados
                     Inclui ícone de olho para mostrar/ocultar senha
                     ════════════════════════════════════════════════════════ -->
                <label class="label" for="password">Senha</label>
                <div class="row">
                    <input 
                        class="input" 
                        id="password" 
                        name="password" 
                        type="password" 
                        placeholder="Sua senha" 
                        required
                    />
                    
                    <!-- Ícone SVG do olho (toggle mostrar/ocultar senha)
                         O traço diagonal (#eyeSlash) indica que a senha está oculta
                         Ao clicar, o JavaScript remove o traço e muda type para "text" -->
                    <svg id="eye" class="eye" viewBox="0 0 24 24" fill="none" aria-label="Mostrar/ocultar senha">
                        <!-- Formato do olho -->
                        <path d="M2 12s3.8-7 10-7 10 7 10 7-3.8 7-10 7S2 12 2 12Z" stroke="currentColor" stroke-width="1.6" opacity=".9"/>
                        <!-- Pupila (círculo central) -->
                        <circle cx="12" cy="12" r="3.2" stroke="currentColor" stroke-width="1.6"/>
                        <!-- Traço diagonal (indica "oculto") - removido quando visível -->
                        <path id="eyeSlash" d="M4 20L20 4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                    </svg>
                </div>

                <!-- ════════════════════════════════════════════════════════
                     LINHA DE AJUDA: Checkbox "Lembrar" + Link "Esqueci senha"
                     ════════════════════════════════════════════════════════ -->
                <div class="helper">
                    <!-- Checkbox para manter sessão ativa por mais tempo
                         name="remember" value="1" - enviado como $_POST['remember'] = "1" -->
                    <label class="checkbox">
                        <input type="checkbox" name="remember" value="1"/> 
                        <span>Lembrar</span>
                    </label>
                    
                    <!-- Link para recuperação de senha (TODO: implementar) -->
                    <a href="#" style="opacity:.85">Esqueci a senha</a>
                </div>

                <!-- ════════════════════════════════════════════════════════
                     BOTÕES DE AÇÃO
                     ════════════════════════════════════════════════════════ -->
                <div style="display:flex; gap:.8rem; margin-top:8px">
                    <!-- Botão primário: submete o formulário -->
                    <button class="button" type="submit">Entrar</button>
                    
                    <!-- Botão secundário: redireciona para página de ajuda externa -->
                    <button class="button ghost" type="button" onclick="location.href='https://retorne.app'">
                        Precisa de ajuda?
                    </button>
                </div>

                <!-- Linha divisória -->
                <div class="hr"></div>
                
                <!-- Texto legal/termos de uso -->
                <div style="font-size:.9rem; color:var(--muted)">
                    Ao entrar, você concorda com os termos de uso e privacidade.
                </div>
            </form>

        </div><!-- /.grid -->
    </div><!-- /.auth-shell -->

    <!-- ════════════════════════════════════════════════════════════════════
         SEÇÃO: JAVASCRIPT DA PÁGINA
         
         Funcionalidades:
         1. Toggle de visibilidade da senha (ícone do olho)
         2. Navegação por teclado (Enter no email foca na senha)
         ════════════════════════════════════════════════════════════════════ -->
    <script>
    /**
     * IIFE (Immediately Invoked Function Expression)
     * 
     * Encapsula o código em um escopo próprio para não poluir o global
     * O código é executado imediatamente quando a página carrega
     */
    (function(){
        // Referências aos elementos do DOM
        const pwd = document.getElementById('password'); // Campo de senha
        const eye = document.getElementById('eye');       // Ícone do olho
        
        // Estado: a senha está visível ou não?
        let visible = false;

        /**
         * Função toggle: alterna visibilidade da senha
         * 
         * Quando chamada:
         * 1. Inverte o estado (visible = !visible)
         * 2. Muda o type do input entre 'text' e 'password'
         * 3. Mostra/oculta o traço diagonal do ícone do olho
         */
        function toggle(){
            visible = !visible;
            
            // Muda o tipo do input
            // 'text' = mostra os caracteres
            // 'password' = oculta os caracteres
            pwd.type = visible ? 'text' : 'password';
            
            // Mostra/oculta o traço do olho
            // display: 'none' = senha visível (sem traço)
            // display: 'block' = senha oculta (com traço)
            document.getElementById('eyeSlash').style.display = visible ? 'none' : 'block';
        }
        
        // Adiciona evento de clique no ícone do olho
        eye.addEventListener('click', toggle);
        
        /**
         * Navegação por teclado: Enter no campo email
         * 
         * Quando o usuário pressiona Enter no campo de email,
         * em vez de submeter o formulário, focamos no campo de senha
         * Isso melhora a UX, especialmente em mobile
         */
        document.getElementById('email').addEventListener('keydown', e => {
            if(e.key === 'Enter'){ 
                e.preventDefault(); // Previne submit do formulário
                pwd.focus();        // Foca no campo de senha
            }
        });
    })();
    </script>
</body>
</html>
