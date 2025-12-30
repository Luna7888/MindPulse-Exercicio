<?php 
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ MENU_ITEMS.PHP — Lista de Itens do Menu de Navegação                     ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Definir os links de navegação da sidebar e menu mobile    ║
 * ║                Arquivo único usado em ambos os contextos (DRY)           ║
 * ║                                                                           ║
 * ║ @acesso        Usuários autenticados (todos os níveis)                   ║
 * ║ @escopo        Por empresa (links respeitam company_id)                  ║
 * ║                                                                           ║
 * ║ @seções        1. Colaborador: links disponíveis para todos              ║
 * ║                2. Admin: links apenas para Admin Geral/Gestor            ║
 * ║                3. Sessão: logout                                         ║
 * ║                                                                           ║
 * ║ @permissões    canAccessAdmin() controla visibilidade da seção Admin     ║
 * ║                                                                           ║
 * ║ @usado_em      sidebar.php (desktop) e header.php (menu mobile)          ║
 * ║                                                                           ║
 * ║ @dependências  auth.php (url_for, canAccessAdmin)                        ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

/**
 * Inclui funções de autenticação
 * Necessário para url_for() e canAccessAdmin()
 */
require_once __DIR__ . '/auth.php'; 
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ NAV: Container principal de navegação                                 ║
     ║                                                                        ║
     ║ Estrutura semântica: <nav> para acessibilidade                        ║
     ║ Classe .mh-nav define estilos de layout (flex column, gaps)           ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<nav class="mh-nav">

    <!-- ════════════════════════════════════════════════════════════════════
         SEÇÃO: COLABORADOR
         
         Links disponíveis para TODOS os usuários autenticados:
         - Admin Geral
         - Gestor
         - Colaborador
         
         Estes são os recursos básicos que qualquer usuário pode acessar
         ════════════════════════════════════════════════════════════════════ -->
    <div class="mh-section">
        <!-- Título da seção (estilizado como label pequeno) -->
        <h6 class="mh-sec-title">Colaborador</h6>

        <!-- ────────────────────────────────────────────────────────────────
             LINK: INÍCIO (Dashboard do colaborador)
             
             Página principal após login
             Mostra KPIs, progresso em treinamentos, checklists pendentes
             
             Acesso: Todos os usuários
             ──────────────────────────────────────────────────────────────── -->
        <a class="mh-item" href="<?= url_for('/pages/home.php') ?>">
            <span class="mh-ico">
                <!-- Ícone: Casa (home) -->
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 3l9 8h-2v9h-6v-6H11v6H5v-9H3l9-8z"/>
                </svg>
            </span>
            <span class="label">Início</span>
        </a>

        <!-- ────────────────────────────────────────────────────────────────
             LINK: TREINAMENTOS
             
             Lista de treinamentos disponíveis para o usuário
             Filtrado por cargos (roles) do usuário
             
             Acesso: Todos os usuários
             Escopo: Treinamentos da empresa atual + cargos do usuário
             ──────────────────────────────────────────────────────────────── -->
        <a class="mh-item" href="<?= url_for('/pages/treinamentos.php') ?>">
            <span class="mh-ico">
                <!-- Ícone: Play (vídeo/treinamento) -->
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M8 5v14l11-7z"/>
                </svg>
            </span>
            <span class="label">Treinamentos</span>
        </a>

        <!-- ────────────────────────────────────────────────────────────────
             LINK: CHECKLISTS
             
             Lista de checklists que o usuário deve executar
             Filtrado por cargos (roles) do usuário
             
             Acesso: Todos os usuários
             Escopo: Checklists da empresa atual + cargos do usuário
             ──────────────────────────────────────────────────────────────── -->
        <a class="mh-item" href="<?= url_for('/pages/checklists.php') ?>">
            <span class="mh-ico">
                <!-- Ícone: Check mark (tarefa concluída) -->
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 11l3 3L22 4l-2-2-8 8-3-3-9 9 2 2 8-8z"/>
                </svg>
            </span>
            <span class="label">Checklists</span>
        </a>

        <!-- ────────────────────────────────────────────────────────────────
             LINK: FEEDBACK
             
             Canal de comunicação colaborador → gestor
             Permite enviar feedbacks, sugestões, ocorrências
             
             Acesso: Todos os usuários
             Escopo: Feedbacks da empresa atual
             ──────────────────────────────────────────────────────────────── -->
        <a class="mh-item" href="<?= url_for('/pages/feedback.php') ?>">
            <span class="mh-ico">
                <!-- Ícone: Balão de chat (feedback/comunicação) -->
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M21 15a4 4 0 0 1-4 4H8l-5 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8z"/>
                </svg>
            </span>
            <span class="label">Feedback</span>
        </a>
    </div><!-- /.mh-section Colaborador -->

    <!-- ════════════════════════════════════════════════════════════════════
         SEÇÃO: ADMIN
         
         Links disponíveis apenas para usuários com permissão administrativa:
         - Admin Geral: vê tudo de todas as empresas
         - Gestor: vê apenas da sua empresa
         
         canAccessAdmin() retorna true para type='Admin'
         
         Esta seção inteira é OCULTADA para Colaboradores
         ════════════════════════════════════════════════════════════════════ -->
    <?php if (canAccessManager()): ?>
    <div class="mh-section">
        <h6 class="mh-sec-title">Admin</h6>

        <!-- ────────────────────────────────────────────────────────────────
             LINK: EMPRESAS
             
             Gerenciamento de empresas cadastradas
             Admin Geral: vê todas as empresas
             Gestor: vê apenas sua empresa
             
             Acesso: Admin Geral, Gestor
             ──────────────────────────────────────────────────────────────── -->
        <a class="mh-item" href="<?= url_for('/pages/empresas.php') ?>">
            <span class="mh-ico">
                <!-- Ícone: Prédio (empresa) -->
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 21V3h8v4h10v14H3Zm10-2h6V9h-6v10ZM5 19h6V5H5v14Z"/>
                </svg>
            </span>
            <span class="label">Empresas</span>
        </a>

        <!-- ────────────────────────────────────────────────────────────────
             LINK: DASHBOARD ADMIN
             
             Visão gerencial com KPIs, gráficos e rankings
             
             Acesso: Admin Geral, Gestor
             Escopo: Dados da empresa atual (ou todas para Admin Geral)
             ──────────────────────────────────────────────────────────────── -->
        <a class="mh-item" href="<?= url_for('/pages/admin_dashboard.php') ?>">
            <span class="mh-ico">
                <!-- Ícone: Dashboard/gráficos -->
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M3 3h8v8H3V3zm10 0h8v5h-8V3zM3 13h5v8H3v-8zm7 0h11v8H10v-8z"/>
                </svg>
            </span>
            <span class="label">Dashboard</span>
        </a>

        <!-- ────────────────────────────────────────────────────────────────
             LINK: CHAMADOS INTERNOS
             
             Visualização e gestão de feedbacks enviados por colaboradores
             Permite alterar status: aberto → em andamento → concluído
             
             Acesso: Admin Geral, Gestor
             Escopo: Chamados da empresa atual
             ──────────────────────────────────────────────────────────────── -->
        <a class="mh-item" href="<?= url_for('/pages/chamados.php') ?>">
            <span class="mh-ico">
                <!-- Ícone: Lista (chamados/tickets) -->
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M4 4h16v2H4zm0 6h16v2H4zm0 6h10v2H4z"/>
                </svg>
            </span>
            <span class="label">Chamados Internos</span>
        </a>

        <!-- ────────────────────────────────────────────────────────────────
             LINK: COLABORADORES
             
             Lista e gestão de colaboradores da empresa
             Mostra progresso, recompensas, aniversariantes
             
             Acesso: Admin Geral, Gestor
             Escopo: Colaboradores da empresa atual
             ──────────────────────────────────────────────────────────────── -->
        <a class="mh-item" href="<?= url_for('/pages/colaboradores.php') ?>">
            <span class="mh-ico">
                <!-- Ícone: Pessoa (colaborador) -->
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z"/>
                </svg>
            </span>
            <span class="label">Colaboradores</span>
        </a>

        <!-- ────────────────────────────────────────────────────────────────
             LINK: NOVO TREINAMENTO
             
             Formulário para criar novo treinamento com vídeo-aulas
             
             Acesso: Admin Geral, Gestor
             Escopo: Cria treinamento na empresa atual
             ──────────────────────────────────────────────────────────────── -->
        <a class="mh-item" href="<?= url_for('/pages/training_new.php') ?>">
            <span class="mh-ico">
                <!-- Ícone: Plus (adicionar) -->
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="label">Novo Treinamento</span>
        </a>

        <!-- ────────────────────────────────────────────────────────────────
             LINK: NOVO COLABORADOR
             
             Formulário para cadastrar novo colaborador
             
             Acesso: Admin Geral, Gestor
             Escopo: Vincula à empresa atual
             ──────────────────────────────────────────────────────────────── -->
        <a class="mh-item" href="<?= url_for('/pages/collaborator_new.php') ?>">
            <span class="mh-ico">
                <!-- Ícone: Pessoa com plus -->
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M15 8a4 4 0 1 1-4-4 4 4 0 0 1 4 4ZM4 20v-1c0-3.31 2.69-6 6-6h0c3.31 0 6 2.69 6 6v1H4Z"/>
                </svg>
            </span>
            <span class="label">Novo Colaborador</span>
        </a>

        <!-- ────────────────────────────────────────────────────────────────
             LINK: NOVO CHECKLIST
             
             Formulário para criar novo checklist com tarefas
             
             Acesso: Admin Geral, Gestor
             Escopo: Cria checklist na empresa atual
             ──────────────────────────────────────────────────────────────── -->
        <a class="mh-item" href="<?= url_for('/pages/checklist_new.php') ?>">
            <span class="mh-ico">
                <!-- Ícone: Pessoa (reutilizado, poderia ser checklist) -->
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M15 8a4 4 0 1 1-4-4 4 4 0 0 1 4 4ZM4 20v-1c0-3.31 2.69-6 6-6h0c3.31 0 6 2.69 6 6v1H4Z"/>
                </svg>
            </span>
            <span class="label">Novo Checklist</span>
        </a>
    </div><!-- /.mh-section Admin -->
    <?php endif; ?>

    <!-- ════════════════════════════════════════════════════════════════════
         SEÇÃO: SESSÃO
         
         Opções relacionadas à sessão do usuário
         Disponível para todos os usuários autenticados
         ════════════════════════════════════════════════════════════════════ -->
    <div class="mh-section">
        <h6 class="mh-sec-title">Sessão</h6>

        <!-- ────────────────────────────────────────────────────────────────
             LINK: SAIR (Logout)
             
             Encerra a sessão do usuário e redireciona para login
             
             Acesso: Todos os usuários
             ──────────────────────────────────────────────────────────────── -->
        <a class="mh-item" href="<?= url_for('/auth/logout.php') ?>">
            <span class="mh-ico">
                <!-- Ícone: Seta saindo (logout) -->
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M10 17l5-5-5-5v3H3v4h7v3zM20 3h-8v2h8v14h-8v2h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/>
                </svg>
            </span>
            <span class="label">Sair</span>
        </a>
    </div><!-- /.mh-section Sessão -->

</nav><!-- /.mh-nav -->
