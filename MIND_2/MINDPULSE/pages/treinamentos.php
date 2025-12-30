<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ TREINAMENTOS.PHP — Lista de Treinamentos do Usuário                      ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Exibir todos os treinamentos disponíveis para o usuário   ║
 * ║                com progresso individual em cada um                       ║
 * ║                                                                           ║
 * ║ @acesso        Admin Geral | Gestor | Colaborador (todos os níveis)      ║
 * ║ @escopo        Por empresa + por cargo                                   ║
 * ║                                                                           ║
 * ║ @filtros       - Empresa atual (company_id)                              ║
 * ║                - Cargos do usuário (via role_training)                   ║
 * ║                - Apenas treinamentos ativos (is_active = 1)              ║
 * ║                                                                           ║
 * ║ @exibe         - Grid responsivo de cards                                ║
 * ║                - Capa, título, objetivo, dificuldade, tags               ║
 * ║                - Barra de progresso individual                           ║
 * ║                - Imagem de recompensa (se definida)                      ║
 * ║                - Botão "Começar" ou "Continuar"                          ║
 * ║                                                                           ║
 * ║ @dependências  layout_start.php, training.php                            ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INCLUSÃO DE DEPENDÊNCIAS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Inclui o layout base (header, sidebar, estrutura HTML)
 * Também executa requireLogin() - redireciona se não autenticado
 */
require_once __DIR__ . '/../includes/layout_start.php';

/**
 * Inclui funções de treinamentos
 * Fornece: trainingsForUser(), userTrainingProgress(), etc.
 */
require_once __DIR__ . '/../includes/training.php';

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CAPTURA DE DADOS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * ID do usuário logado
 */
$userId = (int)($_SESSION['user']['id'] ?? 0);

/**
 * ID da empresa atualmente selecionada
 */
$companyId = currentCompanyId();

/**
 * Lista de treinamentos acessíveis ao usuário
 * Filtrados por empresa e cargos
 */
$items = trainingsForUser($pdo, $userId, $companyId);
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ CABEÇALHO DA PÁGINA                                                   ║
     ║                                                                        ║
     ║ Título + badge decorativo                                             ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px">
    <!-- Título da página -->
    <h2 style="margin:0; font-weight:900">Treinamentos</h2>
    
    <!-- Badge decorativo -->
    <span class="badge">
        <span class="brand-dot"></span> 
        Suas jornadas
    </span>
</div>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ CONTEÚDO: Lista de Treinamentos                                       ║
     ║                                                                        ║
     ║ Se não houver treinamentos: mensagem informativa                      ║
     ║ Se houver: grid responsivo de cards                                   ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->

<?php if (empty($items)): ?>
    <!-- ════════════════════════════════════════════════════════════════════
         ESTADO VAZIO: Nenhum treinamento disponível
         
         Pode acontecer quando:
         - Usuário não tem cargos atribuídos
         - Nenhum treinamento está vinculado aos cargos do usuário
         - Empresa não tem treinamentos cadastrados
         ════════════════════════════════════════════════════════════════════ -->
    <div class="card" style="padding:20px">
        Nenhum treinamento disponível para seus cargos nesta empresa.
    </div>
    
<?php else: ?>
    <!-- ════════════════════════════════════════════════════════════════════
         GRID DE TREINAMENTOS
         
         Layout responsivo:
         - Desktop: múltiplas colunas (auto-fill)
         - Mobile: 1 coluna
         
         minmax(280px, 1fr): cada card tem no mínimo 280px
         ════════════════════════════════════════════════════════════════════ -->
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:16px">
        
        <?php foreach($items as $t):
            /**
             * Calcula o progresso do usuário neste treinamento
             * Retorna: ['percent', 'done', 'total', 'nextVideoId']
             */
            $progress = userTrainingProgress($pdo, $userId, (int)$t['id']);
            $p = $progress['percent'];
        ?>
            <!-- ────────────────────────────────────────────────────────────
                 CARD DE TREINAMENTO
                 
                 Estrutura:
                 1. Capa com imagem de recompensa sobreposta
                 2. Informações (título, objetivo, badges)
                 3. Barra de progresso
                 4. Botão de ação
                 ──────────────────────────────────────────────────────────── -->
            <div class="card" style="overflow:hidden">
                
                <!-- Área da capa -->
                <div style="position:relative">
                    <!-- Imagem de capa do treinamento -->
                    <img 
                        src="<?= htmlspecialchars($t['cover_image'] ?: url_for('/assets/img/login_hero.svg')) ?>" 
                        alt="" 
                        style="width:100%; height:160px; object-fit:cover"
                    >
                    
                    <!-- Imagem de recompensa (badge no canto inferior direito) -->
                    <?php if (!empty($t['reward_image'])): ?>
                        <img 
                            src="<?= htmlspecialchars($t['reward_image']) ?>" 
                            alt="recompensa" 
                            style="position:absolute; right:10px; bottom:10px; width:48px; height:48px; border-radius:12px; border:1px solid var(--stroke)"
                        >
                    <?php endif; ?>
                </div>
                
                <!-- Conteúdo do card -->
                <div style="padding:14px">
                    <!-- Título do treinamento -->
                    <div style="font-weight:800; font-size:1.05rem">
                        <?= htmlspecialchars($t['title']) ?>
                    </div>
                    
                    <!-- Objetivo/descrição breve -->
                    <div style="color:#9aa4b2; margin:6px 0">
                        <?= htmlspecialchars($t['objective']) ?>
                    </div>
                    
                    <!-- Badges de metadados -->
                    <div style="display:flex; gap:8px; flex-wrap:wrap; color:#cbd5e1; font-size:.85rem">
                        <!-- Dificuldade (ex: Iniciante, Intermediário, Avançado) -->
                        <span class="badge" style="background:rgba(255,255,255,.06)">
                            <?= htmlspecialchars($t['difficulty']) ?>
                        </span>
                        
                        <!-- Duração estimada em minutos -->
                        <?php if(!empty($t['estimated_minutes'])): ?>
                            <span class="badge" style="background:rgba(255,255,255,.06)">
                                <?= (int)$t['estimated_minutes'] ?> min
                            </span>
                        <?php endif; ?>
                        
                        <!-- Tags/categorias -->
                        <?php if(!empty($t['tags'])): ?>
                            <span class="badge" style="background:rgba(255,255,255,.06)">
                                <?= htmlspecialchars($t['tags']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Barra de progresso -->
                    <div style="margin-top:10px">
                        <!-- Container da barra -->
                        <div style="height:8px; background:rgba(255,255,255,.08); border-radius:999px; overflow:hidden">
                            <!-- Preenchimento (largura proporcional ao progresso) -->
                            <div style="height:100%; width:<?= (int)$p ?>%; background:linear-gradient(135deg,var(--brand),var(--brand-2))"></div>
                        </div>
                        
                        <!-- Label de progresso -->
                        <div style="display:flex; justify-content:space-between; margin-top:6px; color:#cbd5e1; font-size:.85rem">
                            <span>Progresso</span>
                            <strong><?= (int)$p ?>%</strong>
                        </div>
                    </div>

                    <!-- Botão de ação -->
                    <div style="margin-top:12px">
                        <a class="button" href="<?= url_for('/pages/treinamento.php') ?>?id=<?= (int)$t['id'] ?>">
                            <?php 
                            /**
                             * Texto do botão baseado no progresso:
                             * - 0%: "Começar" (ainda não iniciou)
                             * - >0%: "Continuar" (já assistiu alguma aula)
                             */
                            echo $p > 0 ? 'Continuar' : 'Começar'; 
                            ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
    </div>
<?php endif; ?>

<?php 
/**
 * Fecha as tags HTML abertas em layout_start.php
 */
require_once __DIR__ . '/../includes/layout_end.php'; 
?>
