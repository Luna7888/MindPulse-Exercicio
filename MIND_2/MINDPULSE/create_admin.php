<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ CREATE_ADMIN.PHP — Script de Criação/Reset do Usuário Administrador      ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Criar ou atualizar o usuário Admin Master da plataforma   ║
 * ║                Útil para setup inicial ou recuperação de acesso          ║
 * ║                                                                           ║
 * ║ @acesso        Público (CUIDADO! Deve ser removido ou protegido em prod) ║
 * ║ @escopo        Global (cria usuário com acesso a toda plataforma)        ║
 * ║                                                                           ║
 * ║ @credenciais   Email: admin@mindhub.com                                  ║
 * ║                Senha: admin123                                           ║
 * ║                                                                           ║
 * ║ @segurança     ⚠️  ATENÇÃO: Este arquivo deve ser:                       ║
 * ║                - Removido após o primeiro uso em produção                ║
 * ║                - Ou protegido por IP/senha adicional                     ║
 * ║                - NUNCA deixar acessível publicamente                     ║
 * ║                                                                           ║
 * ║ @retorno       JSON com status da operação (ok/error)                    ║
 * ║                                                                           ║
 * ║ @dependências  includes/db.php (conexão PDO)                             ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CONFIGURAÇÕES INICIAIS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * declare(strict_types=1)
 * 
 * Habilita tipagem estrita no PHP, fazendo com que:
 * - Funções que esperam int rejeitem strings
 * - Tipos de parâmetros e retornos sejam validados rigorosamente
 * - Erros de tipo sejam lançados como TypeError
 * 
 * Isso aumenta a segurança e previsibilidade do código
 */
declare(strict_types=1);

/**
 * Inicia a sessão PHP se ainda não estiver ativa
 * Necessário para manter contexto entre requisições
 */
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Define o Content-Type da resposta como JSON
 * Isso informa ao cliente (navegador/API) que a resposta é JSON
 * charset=utf-8 garante suporte a caracteres especiais
 */
header('Content-Type: application/json; charset=utf-8');

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CONEXÃO COM O BANCO DE DADOS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Variável que armazenará a conexão PDO
 * Inicializada como null para verificação posterior
 */
$pdo = null;

/**
 * ESTRATÉGIA DE CONEXÃO: Tentativa em múltiplos caminhos
 * 
 * O script tenta encontrar o arquivo db.php em diferentes localizações
 * Isso permite que o script funcione mesmo se movido de pasta
 * 
 * Caminhos tentados (em ordem):
 * 1. Mesmo nível: /includes/db.php (quando na raiz)
 * 2. Um nível acima: /../includes/db.php (quando em subpasta)
 * 3. Dois níveis acima: /../../includes/db.php (quando em subpasta profunda)
 */
$possiblePaths = [
    __DIR__ . '/includes/db.php',        // raiz/mindhub/includes/db.php
    __DIR__ . '/../includes/db.php',     // se estiver dentro de subpasta
    __DIR__ . '/../../includes/db.php',  // fallback para subpasta profunda
];

/**
 * Loop que tenta cada caminho até encontrar o arquivo db.php
 * is_file() verifica se o caminho é um arquivo válido
 * require_once inclui o arquivo apenas uma vez (evita duplicação)
 * break sai do loop assim que encontrar
 */
foreach ($possiblePaths as $p) {
    if (is_file($p)) {
        require_once $p; // Este arquivo define $pdo conectado
        break;
    }
}

/**
 * FALLBACK: Conexão direta se db.php não foi encontrado
 * 
 * Se o arquivo db.php não existir ou não definir $pdo,
 * criamos a conexão diretamente aqui
 * 
 * ⚠️  ATENÇÃO: Credenciais hardcoded são um risco de segurança
 * Em produção, use variáveis de ambiente ou arquivo de config externo
 */
if (!isset($pdo) || !($pdo instanceof PDO)) {
    // Credenciais do banco de dados
    $host = 'rhtrain.mysql.uhserver.com';  // Servidor MySQL
    $db   = 'rhtrain';                      // Nome do banco
    $user = 'rhtrain';                      // Usuário
    $pass = 'TPMBS3cr3t@';                  // Senha
    
    try {
        /**
         * Cria conexão PDO com MySQL
         * 
         * DSN (Data Source Name): mysql:host=...;dbname=...;charset=utf8mb4
         * - mysql: driver do banco
         * - host: servidor
         * - dbname: banco de dados
         * - charset: codificação (utf8mb4 suporta emojis)
         * 
         * Opções PDO:
         * - ERRMODE_EXCEPTION: lança exceções em erros (melhor para debug)
         * - FETCH_ASSOC: retorna arrays associativos por padrão
         * - EMULATE_PREPARES false: usa prepared statements nativos (mais seguro)
         */
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        /**
         * Em caso de erro de conexão:
         * - Retorna HTTP 500 (Internal Server Error)
         * - Envia JSON com detalhes do erro
         * - Encerra a execução
         */
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Falha na conexão com o MySQL.',
            'detail' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: GARANTIA DA ESTRUTURA DO BANCO (TABELA USERS)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * CREATE TABLE IF NOT EXISTS
 * 
 * Cria a tabela users apenas se ela não existir
 * Isso torna o script idempotente (pode rodar múltiplas vezes sem erro)
 * 
 * Estrutura da tabela users:
 * - id: chave primária auto-incremento
 * - name: nome do usuário (obrigatório)
 * - email: email único (usado para login)
 * - password_hash: senha criptografada com password_hash()
 * - type: 'Admin' ou 'Colaborador' (ENUM)
 * - avatar_url: URL da foto de perfil (opcional)
 * - created_at: data de criação (automático)
 */
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(160) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            type ENUM('Admin','Colaborador') NOT NULL DEFAULT 'Colaborador',
            avatar_url VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (Throwable $e) {
    /**
     * Se a criação da tabela falhar, retorna erro
     * Isso pode acontecer por falta de permissão ou sintaxe inválida
     */
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Falha ao validar tabela users.',
        'detail' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CRIAÇÃO/ATUALIZAÇÃO DO USUÁRIO ADMIN
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Credenciais do Admin Master
 * 
 * ⚠️  IMPORTANTE: Altere a senha após o primeiro login!
 * Estas credenciais são apenas para setup inicial
 */
$email = 'admin@mindhub.com';  // Email de login do admin
$plain = 'admin123';           // Senha em texto plano

/**
 * password_hash() com PASSWORD_DEFAULT
 * 
 * Cria um hash seguro da senha usando o algoritmo recomendado atual
 * (atualmente bcrypt, pode mudar em versões futuras do PHP)
 * 
 * O hash gerado inclui:
 * - Algoritmo usado
 * - Custo (work factor)
 * - Salt aleatório
 * - Hash da senha
 * 
 * Exemplo de hash: $2y$10$FqwnhK9gP0aXUo5m0C9q/uQ9sB3Hq0nW7C3VtG6J1oVxjA1F7vFBS
 */
$hash = password_hash($plain, PASSWORD_DEFAULT);

try {
    /**
     * TRANSAÇÃO: Garante atomicidade da operação
     * 
     * beginTransaction() inicia uma transação
     * Se algo der errado, rollBack() desfaz tudo
     * Se tudo der certo, commit() confirma as mudanças
     */
    $pdo->beginTransaction();

    /**
     * SELECT ... FOR UPDATE
     * 
     * Busca o usuário pelo email E bloqueia a linha para atualização
     * FOR UPDATE previne condições de corrida (race conditions)
     * Isso é importante se múltiplas requisições chegarem simultaneamente
     */
    $sel = $pdo->prepare("SELECT id FROM users WHERE email = ? FOR UPDATE");
    $sel->execute([$email]);
    $row = $sel->fetch();

    if ($row) {
        /**
         * USUÁRIO EXISTE: Atualiza a senha e garante que é Admin
         * 
         * Isso é útil para:
         * - Resetar a senha do admin se esquecida
         * - Garantir que o usuário seja Admin (caso tenha sido alterado)
         */
        $upd = $pdo->prepare("UPDATE users SET password_hash = ?, type = 'Admin', name = 'Admin' WHERE id = ?");
        $upd->execute([$hash, (int)$row['id']]);
        $action = 'updated';  // Indica que foi atualização
        $id = (int)$row['id'];
    } else {
        /**
         * USUÁRIO NÃO EXISTE: Cria novo
         * 
         * Insere um novo registro com:
         * - name: 'Admin'
         * - email: admin@mindhub.com
         * - password_hash: hash da senha
         * - type: 'Admin'
         */
        $ins = $pdo->prepare("INSERT INTO users (name,email,password_hash,type) VALUES ('Admin', ?, ?, 'Admin')");
        $ins->execute([$email, $hash]);
        $id = (int)$pdo->lastInsertId();  // Obtém o ID gerado
        $action = 'created';  // Indica que foi criação
    }

    /**
     * commit() confirma todas as operações da transação
     * Se chegou aqui, tudo deu certo
     */
    $pdo->commit();
    
    /**
     * Retorna JSON de sucesso com detalhes da operação
     * 
     * - status: 'ok' indica sucesso
     * - action: 'created' ou 'updated'
     * - user_id: ID do usuário no banco
     * - email: email usado para login
     */
    echo json_encode([
        'status'  => 'ok',
        'action'  => $action,
        'user_id' => $id,
        'email'   => $email
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    /**
     * Em caso de erro, desfaz a transação
     * 
     * inTransaction() verifica se há transação ativa
     * rollBack() desfaz todas as operações desde beginTransaction()
     */
    if ($pdo->inTransaction()) $pdo->rollBack();
    
    /**
     * Retorna erro HTTP 500 com detalhes
     */
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Falha ao criar/atualizar admin.',
        'detail' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
