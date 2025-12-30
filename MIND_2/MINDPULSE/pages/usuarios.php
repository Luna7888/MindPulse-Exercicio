<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ USUARIOS.PHP — Página de Gestão de Usuários (Placeholder)                ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Placeholder para futura gestão de usuários do sistema     ║
 * ║                                                                           ║
 * ║ @acesso        Admin Geral | Gestor (canAccessAdmin)                     ║
 * ║ @status        Em desenvolvimento                                        ║
 * ║                                                                           ║
 * ║ @futuro        - Lista de todos os usuários                              ║
 * ║                - Edição de permissões                                    ║
 * ║                - Ativação/desativação de contas                          ║
 * ║                - Reset de senha                                          ║
 * ║                                                                           ║
 * ║ @nota          Para gestão de colaboradores por empresa, use             ║
 * ║                colaboradores.php                                         ║
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
 * Apenas Admin Geral e Gestores podem acessar gestão de usuários
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
    Admin: Usuários
</div>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
