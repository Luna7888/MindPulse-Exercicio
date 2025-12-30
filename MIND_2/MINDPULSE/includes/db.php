<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ DB.PHP — Conexão com Banco de Dados e Funções de Acesso a Dados          ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Estabelecer conexão PDO com MySQL e fornecer funções      ║
 * ║                auxiliares para operações comuns no banco de dados        ║
 * ║                                                                           ║
 * ║ @acesso        Interno (incluído por arquivos que precisam do banco)     ║
 * ║ @escopo        Global (conexão $pdo disponível após inclusão)            ║
 * ║                                                                           ║
 * ║ @variáveis     $pdo: objeto PDO conectado ao MySQL                       ║
 * ║                                                                           ║
 * ║ @funções       findUserByEmail() - Busca usuário por email               ║
 * ║                getUserCompanies() - Lista empresas do usuário            ║
 * ║                getUserRoles() - Lista cargos do usuário                  ║
 * ║                                                                           ║
 * ║ @segurança     - Usa PDO com prepared statements (previne SQL injection) ║
 * ║                - Modo de erro EXCEPTION (erros são lançados, não         ║
 * ║                  silenciados)                                            ║
 * ║                - Prepared statements nativos (não emulados)              ║
 * ║                                                                           ║
 * ║ @dependências  includes/config.php (DB_HOST, DB_NAME, DB_USER, DB_PASS)  ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INCLUSÃO DE CONFIGURAÇÕES
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Inclui o arquivo de configuração que define:
 * - DB_HOST: servidor do banco
 * - DB_NAME: nome do banco
 * - DB_USER: usuário
 * - DB_PASS: senha
 */
require_once __DIR__ . '/config.php';

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CONEXÃO COM O BANCO DE DADOS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Bloco try/catch para tratamento de erros de conexão
 * 
 * Se a conexão falhar, o catch captura a exceção e exibe
 * uma mensagem amigável (sem expor detalhes técnicos)
 */
try {
    /**
     * DSN (Data Source Name) — String de conexão
     * 
     * Formato: "driver:host=...;dbname=...;charset=..."
     * 
     * Parâmetros:
     * - mysql: driver do banco de dados
     * - host: servidor (de DB_HOST)
     * - dbname: banco de dados (de DB_NAME)
     * - charset: codificação utf8mb4 (suporta emojis e caracteres especiais)
     */
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    /**
     * Cria a conexão PDO
     * 
     * @param $dsn String de conexão
     * @param DB_USER Usuário do banco
     * @param DB_PASS Senha do banco
     * @param array Opções de configuração do PDO
     * 
     * Opções importantes:
     * 
     * PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
     * - Lança exceções em caso de erro (em vez de retornar false)
     * - Permite tratamento adequado com try/catch
     * - Evita erros silenciosos difíceis de debugar
     * 
     * PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
     * - Retorna arrays associativos por padrão
     * - $row['name'] em vez de $row[0]
     * - Mais legível e menos propenso a erros
     * 
     * PDO::ATTR_EMULATE_PREPARES => false
     * - Usa prepared statements NATIVOS do MySQL
     * - Mais seguro contra SQL injection
     * - Tipos de dados são preservados (int continua int)
     */
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
} catch (PDOException $e) {
    /**
     * Tratamento de erro de conexão
     * 
     * Em caso de falha (servidor offline, credenciais erradas, etc):
     * - Retorna HTTP 500 (Internal Server Error)
     * - Exibe mensagem genérica (não expõe detalhes técnicos)
     * - Encerra a execução
     * 
     * Em produção, considere:
     * - Logar o erro real ($e->getMessage()) em arquivo
     * - Enviar alerta para equipe de infra
     * - Exibir página de erro amigável
     */
    http_response_code(500);
    die("Erro ao conectar no banco.");
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE ACESSO A DADOS (USUÁRIOS)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * findUserByEmail() — Busca um usuário pelo email
 * 
 * @param PDO $pdo Conexão com o banco
 * @param string $email Email do usuário a buscar
 * @return array|false Dados do usuário ou false se não encontrado
 * 
 * @uso_principal Login (do_login.php)
 * 
 * @campos_retornados
 * - id: ID do usuário
 * - name: Nome completo
 * - email: Email
 * - password_hash: Hash da senha (para verificação)
 * - type: 'Admin' ou 'Colaborador'
 * - avatar_url: URL da foto de perfil
 * 
 * @segurança
 * - Usa prepared statement (previne SQL injection)
 * - LIMIT 1 otimiza a query (para no primeiro resultado)
 * 
 * @exemplo
 * ```php
 * $user = findUserByEmail($pdo, 'admin@mindhub.com');
 * if ($user && password_verify($senha, $user['password_hash'])) {
 *     // Login bem-sucedido
 * }
 * ```
 */
function findUserByEmail(PDO $pdo, string $email) {
    /**
     * Prepared Statement com placeholder ?
     * 
     * O ? é substituído pelo valor de forma segura
     * PDO escapa automaticamente caracteres especiais
     * Isso previne SQL injection
     */
    $st = $pdo->prepare("SELECT id, name, email, password_hash, type, avatar_url FROM users WHERE email = ? LIMIT 1");
    
    /**
     * execute() executa a query com os parâmetros
     * [$email] é um array com os valores para cada ?
     */
    $st->execute([$email]); 
    
    /**
     * fetch() retorna a primeira linha ou false se não houver
     */
    return $st->fetch();
}

/**
 * getUserCompanies() — Lista todas as empresas que o usuário pode acessar
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $userId ID do usuário
 * @return array Lista de empresas (pode ser vazia)
 * 
 * @uso_principal
 * - Login: popular $_SESSION['companies']
 * - Header: popular o seletor de empresas
 * 
 * @campos_retornados
 * - id: ID da empresa
 * - trade_name: Nome fantasia (exibido no seletor)
 * 
 * @relacionamento
 * Usa a tabela user_company para fazer o JOIN
 * Um usuário pode ter acesso a múltiplas empresas
 * 
 * @exemplo
 * ```php
 * $companies = getUserCompanies($pdo, 123);
 * // [
 * //   ['id' => 1, 'trade_name' => 'Empresa Alpha'],
 * //   ['id' => 2, 'trade_name' => 'Empresa Beta']
 * // ]
 * ```
 */
function getUserCompanies(PDO $pdo, int $userId) {
    /**
     * JOIN entre user_company e companies
     * 
     * user_company: tabela de relacionamento (user_id, company_id)
     * companies: tabela com dados das empresas
     * 
     * WHERE filtra pelo usuário
     * ORDER BY ordena alfabeticamente
     */
    $sql = "SELECT c.id, c.trade_name 
            FROM user_company uc 
            JOIN companies c ON c.id = uc.company_id 
            WHERE uc.user_id = ? 
            ORDER BY c.name";
    
    $st = $pdo->prepare($sql); 
    $st->execute([$userId]); 
    
    /**
     * fetchAll() retorna TODAS as linhas como array
     * Se não houver resultados, retorna array vazio []
     */
    return $st->fetchAll();
}

/**
 * getUserRoles() — Lista todos os cargos do usuário
 * 
 * @param PDO $pdo Conexão com o banco
 * @param int $userId ID do usuário
 * @return array Lista de cargos (pode ser vazia)
 * 
 * @uso_principal
 * - Login: popular $_SESSION['roles']
 * - Home: exibir cargos do usuário
 * - Treinamentos: filtrar por cargo
 * 
 * @campos_retornados
 * - id: ID do cargo
 * - name: Nome do cargo (ex: 'Analista', 'Coordenador')
 * 
 * @relacionamento
 * Usa a tabela user_role para fazer o JOIN
 * Um usuário pode ter múltiplos cargos
 * 
 * @impacto
 * Os cargos determinam:
 * - Quais treinamentos o usuário vê (via role_training)
 * - Quais checklists o usuário executa (via checklist_role)
 * 
 * @exemplo
 * ```php
 * $roles = getUserRoles($pdo, 123);
 * // [
 * //   ['id' => 1, 'name' => 'Analista'],
 * //   ['id' => 2, 'name' => 'Instrutor']
 * // ]
 * ```
 */
function getUserRoles(PDO $pdo, int $userId) {
    /**
     * JOIN entre user_role e roles
     * 
     * user_role: tabela de relacionamento (user_id, role_id)
     * roles: tabela com dados dos cargos
     */
    $sql = "SELECT r.id, r.name 
            FROM user_role ur 
            JOIN roles r ON r.id = ur.role_id 
            WHERE ur.user_id = ? 
            ORDER BY r.name";
    
    $st = $pdo->prepare($sql); 
    $st->execute([$userId]); 
    
    return $st->fetchAll();
}
