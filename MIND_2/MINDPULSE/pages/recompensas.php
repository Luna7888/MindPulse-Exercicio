<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ RECOMPENSAS.PHP — Galeria de Recompensas do Colaborador                  ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Exibir todas as recompensas conquistadas pelo colaborador ║
 * ║                ao completar treinamentos                                 ║
 * ║                                                                           ║
 * ║ @acesso        Colaboradores autenticados                                ║
 * ║ @escopo        Por usuário (user_id)                                     ║
 * ║                                                                           ║
 * ║ @exibe         - Grid de cards com recompensas                           ║
 * ║                - Imagem da recompensa                                    ║
 * ║                - Nome do treinamento                                     ║
 * ║                - Data de conquista                                       ║
 * ║                                                                           ║
 * ║ @gamificação   Incentiva o colaborador a completar mais treinamentos     ║
 * ║                para colecionar mais recompensas                          ║
 * ║                                                                           ║
 * ║ @dependências  layout_start.php                                          ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INCLUSÃO DE DEPENDÊNCIAS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * layout_start.php já exige autenticação via requireLogin()
 */
require_once __DIR__ . '/../includes/layout_start.php';

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CARREGAMENTO DE DADOS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * ID do usuário logado
 */
$userId = (int)($_SESSION['user']['id'] ?? 0);

/**
 * Busca recompensas do usuário
 * 
 * JOIN com trainings para obter o título do treinamento
 * Ordenado pela data de conquista (mais recente primeiro)
 */
$st = $pdo->prepare("
    SELECT utr.*, t.title 
    FROM user_training_reward utr
    JOIN trainings t ON t.id = utr.training_id
    WHERE utr.user_id = ? 
    ORDER BY utr.awarded_at DESC
");
$st->execute([$userId]);
$rewards = $st->fetchAll();
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ TÍTULO DA PÁGINA                                                      ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<h2 style="margin:0 0 12px; font-weight:900">Minhas Recompensas</h2>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ GRID DE RECOMPENSAS                                                   ║
     ║                                                                        ║
     ║ Cada card mostra:                                                     ║
     ║ - Imagem da recompensa (ícone/badge)                                  ║
     ║ - Nome do treinamento                                                 ║
     ║ - Data de conquista                                                   ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->

<?php if (empty($rewards)): ?>
    <!-- Estado vazio: nenhuma recompensa ainda -->
    <div class="card" style="padding:20px">
        Você ainda não concluiu nenhum treinamento.
    </div>
<?php else: ?>
    <!-- Grid responsivo de recompensas -->
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:14px">
        <?php foreach ($rewards as $r): ?>
            <!-- Card de recompensa -->
            <div class="card" style="padding:14px; text-align:center">
                <!-- Imagem da recompensa -->
                <img 
                    src="<?= htmlspecialchars($r['reward_image']) ?>" 
                    style="width:84px; height:84px; border-radius:16px; border:1px solid var(--stroke)"
                    alt="Recompensa"
                >
                
                <!-- Nome do treinamento -->
                <div style="font-weight:800; margin-top:8px">
                    <?= htmlspecialchars($r['title']) ?>
                </div>
                
                <!-- Data de conquista -->
                <div style="color:#9aa4b2; font-size:.9rem; margin-top:4px">
                    Conquistada em <?= htmlspecialchars($r['awarded_at']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
