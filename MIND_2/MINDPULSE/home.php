<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ HOME.PHP (RAIZ) — Página Inicial Pós-Login (Redirecionamento Legado)     ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Página de boas-vindas simplificada após login             ║
 * ║                Exibe informações básicas do usuário e links rápidos      ║
 * ║                                                                           ║
 * ║ @acesso        Admin Geral | Gestor | Colaborador (todos os níveis)      ║
 * ║                Requer autenticação (requireLogin via layout_start)       ║
 * ║                                                                           ║
 * ║ @escopo        Por empresa (company_id da sessão)                        ║
 * ║                                                                           ║
 * ║ @fluxo         1. Usuário faz login com sucesso                          ║
 * ║                2. É redirecionado para /pages/home.php (principal)       ║
 * ║                3. Este arquivo é um fallback/alternativo                 ║
 * ║                                                                           ║
 * ║ @nota          A home principal está em /pages/home.php                  ║
 * ║                Este arquivo na raiz é mantido por compatibilidade        ║
 * ║                                                                           ║
 * ║ @dependências  includes/layout_start.php (estrutura, auth, sessão)       ║
 * ║                includes/layout_end.php (fechamento HTML)                 ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INCLUSÃO DO LAYOUT BASE
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Inclui o layout_start.php que:
 * - Verifica se o usuário está logado (requireLogin)
 * - Inicia a sessão PHP
 * - Inclui header, sidebar e abre as tags HTML/body
 * - Conecta ao banco de dados
 * 
 * Se o usuário não estiver logado, será redirecionado para /login.php
 */
require_once __DIR__ . '/includes/layout_start.php';
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ CARD DE BOAS-VINDAS                                                    ║
     ║                                                                        ║
     ║ Propósito: Saudar o usuário e indicar próximos passos                 ║
     ║ Acesso: Todos os usuários autenticados                                ║
     ║ Escopo: Mostra nome do usuário da sessão                              ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<div class="card" style="padding:20px; display:flex; align-items:center; justify-content:space-between">
    
    <!-- ════════════════════════════════════════════════════════════════════
         LADO ESQUERDO: Saudação personalizada
         
         $_SESSION['user']['name'] contém o nome do usuário logado
         htmlspecialchars() previne XSS escapando caracteres especiais
         O operador ?? '' retorna string vazia se name não existir
         ════════════════════════════════════════════════════════════════════ -->
    <div>
        <!-- Nome do usuário em destaque -->
        <div style="font-weight:900; font-size:1.2rem">
            Olá, <?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?>!
        </div>
        
        <!-- Instrução para o usuário -->
        <div style="color:#cbd5e1; margin-top:4px">
            Bem-vindo(a) à Mindhub. Selecione uma empresa no topo para trabalhar.
        </div>
    </div>
    
    <!-- ════════════════════════════════════════════════════════════════════
         LADO DIREITO: Badge indicando sessão ativa
         
         Feedback visual de que o usuário está autenticado
         .brand-dot é a bolinha laranja característica da marca
         ════════════════════════════════════════════════════════════════════ -->
    <div class="badge">
        <span class="brand-dot"></span> 
        Sessão ativa
    </div>
</div>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ GRID DE INFORMAÇÕES E AÇÕES RÁPIDAS                                   ║
     ║                                                                        ║
     ║ Layout: Grid de 12 colunas para flexibilidade                         ║
     ║ Responsivo: Colunas se adaptam em telas menores                       ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<div style="display:grid; gap:16px; grid-template-columns: repeat(12,1fr); margin-top:16px">
    
    <!-- ════════════════════════════════════════════════════════════════════
         CARD 1: PERFIL DO USUÁRIO (4 colunas)
         
         Propósito: Mostrar informações básicas do usuário logado
         Acesso: Todos os usuários (veem seus próprios dados)
         ════════════════════════════════════════════════════════════════════ -->
    <div class="card" style="grid-column: span 4; padding:18px">
        <div style="font-weight:800; margin-bottom:6px">Seu perfil</div>
        
        <div style="color:#cbd5e1; font-size:.95rem">
            <!-- Tipo do usuário: Admin ou Colaborador
                 Determina o nível de acesso na plataforma -->
            Tipo: <strong><?= htmlspecialchars($_SESSION['user']['type']) ?></strong><br/>
            
            <!-- Lista de cargos do usuário
                 Cargos determinam quais treinamentos/checklists o usuário vê -->
            Cargos:
            <?php if (!empty($_SESSION['roles'])): ?>
                <?php 
                /**
                 * Mapeia o array de roles para extrair apenas os nomes
                 * array_map aplica a função fn($r)=>$r['name'] a cada elemento
                 * implode junta os nomes com vírgula e espaço
                 */
                ?>
                <?= htmlspecialchars(implode(', ', array_map(fn($r)=>$r['name'], $_SESSION['roles']))) ?>
            <?php else: ?>
                Nenhum cargo atribuído
            <?php endif; ?>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════
         CARD 2: ACESSO RÁPIDO (8 colunas)
         
         Propósito: Links diretos para as principais funcionalidades
         Acesso: Condicional baseado em permissões
         
         canAccessAdmin() retorna true para Admin Geral e Gestor
         Links administrativos só aparecem para quem tem permissão
         ════════════════════════════════════════════════════════════════════ -->
    <div class="card" style="grid-column: span 8; padding:18px">
        <div style="font-weight:800; margin-bottom:6px">Acesso rápido</div>
        
        <div style="display:flex; gap:10px; flex-wrap:wrap">
            <!-- LINKS PARA TODOS OS USUÁRIOS -->
            
            <!-- Treinamentos: disponível para todos
                 Colaboradores assistem, Gestores/Admins também gerenciam -->
            <a class="button" href="<?= url_for('/pages/treinamentos.php') ?>">Treinamentos</a>
            
            <!-- Meus dados: perfil pessoal do usuário -->
            <a class="button" href="<?= url_for('/pages/meus_dados.php') ?>">Meus dados</a>
            
            <!-- LINKS ADMINISTRATIVOS (apenas Admin Geral e Gestor) -->
            <?php if (canAccessAdmin()): ?>
                <!-- Empresas: gerenciamento de empresas (Admin Geral vê todas) -->
                <a class="button" href="<?= url_for('/pages/empresas.php') ?>">Empresas</a>
                
                <!-- Usuários: gerenciamento de colaboradores -->
                <a class="button" href="<?= url_for('/pages/usuarios.php') ?>">Usuários</a>
                
                <!-- Configurações: ajustes do sistema -->
                <a class="button" href="<?= url_for('/pages/config.php') ?>">Configurações</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
/**
 * Inclui o layout_end.php que:
 * - Fecha as divs abertas no layout_start
 * - Fecha as tags </main>, </body> e </html>
 * - Pode incluir scripts globais de fechamento
 */
require_once __DIR__ . '/includes/layout_end.php'; 
?>
