<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ INDEX.PHP — Ponto de Entrada Principal da Aplicação Mindpulse            ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Redirecionar usuários para a página de login              ║
 * ║                Este arquivo é o primeiro a ser acessado quando           ║
 * ║                alguém entra em mindpulse.com ou localhost/MINDPULSE      ║
 * ║                                                                           ║
 * ║ @acesso        Público (qualquer pessoa pode acessar)                    ║
 * ║ @escopo        Global (não depende de empresa)                           ║
 * ║                                                                           ║
 * ║ @fluxo         1. Usuário acessa a raiz do site                          ║
 * ║                2. Este arquivo carrega as funções de autenticação        ║
 * ║                3. Redireciona imediatamente para /login.php              ║
 * ║                                                                           ║
 * ║ @dependências  includes/auth.php (para usar a função url_for)            ║
 * ║                                                                           ║
 * ║ @segurança     Não expõe dados sensíveis, apenas redireciona             ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INCLUSÃO DE DEPENDÊNCIAS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Inclui o arquivo de autenticação que contém:
 * - Função url_for() para gerar URLs corretas independente da subpasta
 * - Funções de verificação de login e permissões
 * - Inicialização de sessão
 * 
 * O __DIR__ garante que o caminho seja relativo a este arquivo,
 * evitando problemas quando o arquivo é incluído de outros lugares
 */
require_once __DIR__ . '/includes/auth.php';

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: REDIRECIONAMENTO PRINCIPAL
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Redireciona o usuário para a página de login
 * 
 * - header('Location: ...') envia um cabeçalho HTTP 302 (redirect)
 * - url_for('/login.php') gera a URL correta considerando subpastas
 *   Exemplo: se o site está em /MINDPULSE, gera /MINDPULSE/login.php
 * - exit; encerra a execução imediatamente após o redirect
 *   Isso é OBRIGATÓRIO para evitar que código posterior seja executado
 * 
 * @por_que_redirecionar
 * - Centraliza o ponto de entrada no login
 * - Evita que usuários acessem a raiz sem autenticação
 * - Mantém consistência no fluxo de navegação
 */
header('Location: ' . url_for('/login.php'));
exit;
