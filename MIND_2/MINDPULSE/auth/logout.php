<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ LOGOUT.PHP — Encerramento de Sessão do Usuário                           ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Encerrar a sessão do usuário e redirecioná-lo para login  ║
 * ║                                                                           ║
 * ║ @acesso        Usuários autenticados (qualquer nível)                    ║
 * ║ @método        GET (link direto)                                         ║
 * ║ @escopo        Global                                                    ║
 * ║                                                                           ║
 * ║ @fluxo         1. Usuário clica em "Sair" no menu                        ║
 * ║                2. Este arquivo é acessado                                ║
 * ║                3. Sessão é limpa e destruída                             ║
 * ║                4. Usuário é redirecionado para página de login           ║
 * ║                                                                           ║
 * ║ @segurança     - Limpa todos os dados da sessão                          ║
 * ║                - Destrói a sessão completamente                          ║
 * ║                - Não deixa dados residuais                               ║
 * ║                                                                           ║
 * ║ @dependências  includes/auth.php (url_for)                               ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INICIALIZAÇÃO DA SESSÃO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Inicia a sessão se não estiver ativa
 * 
 * Precisamos iniciar a sessão antes de poder destruí-la
 * Se já estiver ativa, session_start() não faz nada
 */
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Inclui o arquivo de autenticação
 * 
 * Necessário apenas para a função url_for() que constrói URLs corretas
 */
require_once __DIR__ . '/../includes/auth.php';

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: LIMPEZA E DESTRUIÇÃO DA SESSÃO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Limpa todos os dados da sessão
 * 
 * $_SESSION = [] substitui o array de sessão por um array vazio
 * Isso remove todos os dados: user, roles, companies, current_company, etc.
 * 
 * Alternativa: session_unset() faz o mesmo, mas $_SESSION = [] é mais explícito
 */
$_SESSION = [];

/**
 * Destrói a sessão completamente
 * 
 * session_destroy() faz:
 * - Remove o arquivo de sessão do servidor
 * - Invalida o ID de sessão
 * - Libera recursos associados
 * 
 * Após isso, qualquer tentativa de acessar $_SESSION retornará dados vazios
 * (até que uma nova sessão seja iniciada)
 * 
 * NOTA: O cookie de sessão no navegador permanece, mas é inválido
 * Para segurança máxima, poderia-se também remover o cookie:
 * setcookie(session_name(), '', time()-3600, '/');
 */
session_destroy();

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: REDIRECIONAMENTO PARA LOGIN
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Redireciona para a página de login
 * 
 * Após o logout, o usuário é levado de volta à página de login
 * Se tentar acessar qualquer página protegida, será redirecionado
 * novamente para login (pois requireLogin() verificará sessão vazia)
 * 
 * exit; garante que nenhum código adicional seja executado
 */
header('Location: ' . url_for('/login.php')); 
exit;
