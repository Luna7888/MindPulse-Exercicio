<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ LAYOUT_END.PHP — Fechamento do Template Base de Todas as Páginas         ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Fechar as tags HTML abertas em layout_start.php           ║
 * ║                                                                           ║
 * ║ @acesso        Páginas protegidas (usado em conjunto com layout_start)   ║
 * ║ @escopo        Global (fechamento de estrutura comum)                    ║
 * ║                                                                           ║
 * ║ @fecha         - div.content (container interno)                         ║
 * ║                - main.mh-content (área de conteúdo)                      ║
 * ║                - div.app-shell (container principal)                     ║
 * ║                - body                                                    ║
 * ║                - html                                                    ║
 * ║                                                                           ║
 * ║ @uso           Incluir no final de cada página protegida:                ║
 * ║                require_once 'includes/layout_start.php';                 ║
 * ║                // Conteúdo da página aqui                                ║
 * ║                require_once 'includes/layout_end.php';                   ║
 * ║                                                                           ║
 * ║ @dependências  Deve ser usado APÓS layout_start.php                      ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */
?>
            <!-- ════════════════════════════════════════════════════════════
                 FIM DO CONTEÚDO ESPECÍFICO DA PÁGINA
                 
                 As tags abaixo fecham a estrutura aberta em layout_start.php
                 ════════════════════════════════════════════════════════════ -->
            
            </div><!-- /.content - Fecha container interno de conteúdo -->
        </main><!-- /.mh-content - Fecha área principal de conteúdo -->
    </div><!-- /.app-shell - Fecha container principal da aplicação -->
</body>
</html>
