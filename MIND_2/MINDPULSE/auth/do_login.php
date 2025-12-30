<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ DO_LOGIN.PHP — Processamento de Autenticação de Usuários                 ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Validar credenciais e criar sessão de usuário             ║
 * ║                Este é o endpoint que recebe o POST do formulário de      ║
 * ║                login e decide se o usuário pode entrar ou não            ║
 * ║                                                                           ║
 * ║ @acesso        Público (recebe POST do formulário de login)              ║
 * ║ @método        POST (email, password)                                    ║
 * ║ @escopo        Global (pré-autenticação)                                 ║
 * ║                                                                           ║
 * ║ @fluxo         1. Recebe email e senha via POST                          ║
 * ║                2. Busca usuário no banco pelo email                      ║
 * ║                3. Verifica senha com password_verify()                   ║
 * ║                4. Se válido: cria sessão e redireciona para home         ║
 * ║                5. Se inválido: redireciona para login com erro           ║
 * ║                                                                           ║
 * ║ @segurança     - Usa password_verify() (seguro contra timing attacks)    ║
 * ║                - Não expõe se email existe ou não (mensagem genérica)    ║
 * ║                - Redireciona em vez de exibir erros inline               ║
 * ║                                                                           ║
 * ║ @dependências  includes/db.php (findUserByEmail, getUserCompanies, etc.) ║
 * ║                includes/auth.php (url_for)                               ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INICIALIZAÇÃO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Inicia a sessão PHP se ainda não estiver ativa
 * 
 * A sessão é necessária para:
 * - Armazenar dados do usuário após login
 * - Manter o usuário autenticado entre requisições
 */
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Inclui as dependências necessárias
 * 
 * db.php: conexão $pdo e funções findUserByEmail, getUserCompanies, getUserRoles
 * auth.php: função url_for para construir URLs
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CAPTURA E VALIDAÇÃO DE DADOS DO FORMULÁRIO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Captura o email do formulário
 * 
 * trim() remove espaços em branco do início e fim
 * ?? '' retorna string vazia se não existir (evita undefined index)
 */
$email = trim($_POST['email'] ?? '');

/**
 * Captura a senha do formulário
 * 
 * NÃO usamos trim() na senha para preservar espaços intencionais
 * (algumas pessoas usam espaços em senhas)
 */
$password = $_POST['password'] ?? '';

/**
 * Validação básica: campos obrigatórios
 * 
 * Se email ou senha estiverem vazios, redireciona para login com erro
 * ?e=1 indica erro de credenciais (mensagem genérica por segurança)
 */
if (!$email || !$password) { 
    header('Location: ' . url_for('/login.php') . '?e=1'); 
    exit; 
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: BUSCA DO USUÁRIO NO BANCO DE DADOS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Busca o usuário pelo email
 * 
 * findUserByEmail() retorna:
 * - Array com dados do usuário se encontrado
 * - false se não encontrado
 * 
 * Campos retornados: id, name, email, password_hash, type, avatar_url
 */
$user = findUserByEmail($pdo, $email);

/**
 * Verifica se o usuário existe
 * 
 * Se não existir, redireciona com erro genérico
 * NÃO informamos que o email não existe (segurança contra enumeração)
 */
if (!$user) { 
    header('Location: ' . url_for('/login.php') . '?e=1'); 
    exit; 
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: VERIFICAÇÃO DA SENHA
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Verifica se a senha está correta
 * 
 * password_verify() compara a senha em texto plano com o hash armazenado
 * 
 * Vantagens de password_verify():
 * - Seguro contra timing attacks (tempo constante)
 * - Funciona com qualquer algoritmo usado por password_hash()
 * - Automaticamente compatível com hashes antigos e novos
 * 
 * Se a senha estiver incorreta, redireciona com erro genérico
 */
if (!password_verify($password, $user['password_hash'])) {
    header('Location: ' . url_for('/login.php') . '?e=1'); 
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CARREGAMENTO DE DADOS COMPLEMENTARES
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Busca as empresas que o usuário pode acessar
 * 
 * Um usuário pode ter acesso a múltiplas empresas (multiempresa)
 * Esta lista é usada no seletor de empresas do header
 * 
 * Retorna array de empresas: [['id' => 1, 'trade_name' => 'Empresa X'], ...]
 */
$companies = getUserCompanies($pdo, (int)$user['id']);

/**
 * Busca os cargos/funções do usuário
 * 
 * Os cargos determinam:
 * - Quais treinamentos o usuário vê
 * - Quais checklists o usuário executa
 * 
 * Retorna array de cargos: [['id' => 1, 'name' => 'Analista'], ...]
 */
$roles = getUserRoles($pdo, (int)$user['id']);

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CRIAÇÃO DA SESSÃO DO USUÁRIO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Armazena os dados do usuário na sessão
 * 
 * $_SESSION['user'] contém as informações básicas do usuário logado
 * Estes dados ficam disponíveis em todas as páginas durante a sessão
 * 
 * Campos armazenados:
 * - id: identificador único (usado em queries)
 * - name: nome para exibição
 * - email: email do usuário
 * - type: 'Admin' ou 'Colaborador' (define permissões)
 * - avatar_url: foto de perfil (ou avatar padrão se não tiver)
 */
$_SESSION['user'] = [
    'id' => (int)$user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'type' => $user['type'],
    'avatar_url' => $user['avatar_url'] ?: url_for('/assets/img/avatar.svg')
];

/**
 * Armazena os cargos do usuário na sessão
 * 
 * Usado para:
 * - Exibir cargos no perfil
 * - Filtrar treinamentos e checklists por cargo
 */
$_SESSION['roles'] = $roles;

/**
 * Armazena a lista de empresas na sessão
 * 
 * Usado para:
 * - Popular o seletor de empresas no header
 * - Validar troca de empresa
 */
$_SESSION['companies'] = $companies;

/**
 * Define a empresa atual (primeira da lista)
 * 
 * Se o usuário tem acesso a múltiplas empresas, começa com a primeira
 * O usuário pode trocar de empresa pelo seletor no header
 * 
 * Se não tiver nenhuma empresa, fica null
 */
$_SESSION['current_company'] = $companies[0] ?? null;

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: REDIRECIONAMENTO PÓS-LOGIN
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Redireciona para a página inicial do painel
 * 
 * Após login bem-sucedido, o usuário é levado para /pages/home.php
 * onde verá seu dashboard personalizado
 * 
 * exit; é obrigatório para garantir que nada mais seja executado
 */
header('Location: ' . url_for('/pages/home.php')); 
exit;
