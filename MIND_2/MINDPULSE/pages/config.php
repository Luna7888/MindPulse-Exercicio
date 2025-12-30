<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ CONFIG.PHP — Página de Configurações do Sistema (Placeholder)            ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Placeholder para futuras configurações do sistema         ║
 * ║                                                                           ║
 * ║ @acesso        Admin Geral | Gestor (canAccessAdmin)                     ║
 * ║ @status        Em desenvolvimento                                        ║
 * ║                                                                           ║
 * ║ @futuro        - Configurações de empresa                                ║
 * ║                - Personalização de marca                                 ║
 * ║                - Configurações de notificações                           ║
 * ║                - Integrações                                             ║
 * ║                                                                           ║
 * ║ @dependências  layout_start.php                                          ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INCLUSÃO DE DEPENDÊNCIAS
// ═══════════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../includes/layout_start.php'; 

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: VERIFICAÇÃO DE ACESSO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Verifica permissão administrativa
 * Apenas Admin Geral e Gestores podem acessar configurações
 */
if (!canAccessAdmin()) { 
    http_response_code(403); 
    echo '<div class="card" style="padding:20px">Acesso negado</div>'; 
    require_once __DIR__ . '/../includes/layout_end.php'; 
    exit; 
} 
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ CONTEÚDO DA PÁGINA                                                    ║
     ║                                                                        ║
     ║ Placeholder - funcionalidade em desenvolvimento                       ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<div class='card' style='padding:20px'>
    Admin: Configurações
</div>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
