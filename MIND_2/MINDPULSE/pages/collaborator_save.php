<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ COLLABORATOR_SAVE.PHP — Processamento de Cadastro de Colaborador         ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Processar o formulário de cadastro de colaborador         ║
 * ║                e salvar no banco de dados                                ║
 * ║                                                                           ║
 * ║ @acesso        Admin Geral | Gestor (canAccessAdmin)                     ║
 * ║ @método        POST (formulário)                                         ║
 * ║                                                                           ║
 * ║ @parâmetros    POST:                                                     ║
 * ║                - name: nome completo (obrigatório)                       ║
 * ║                - email: email (obrigatório, único)                       ║
 * ║                - password: senha (opcional)                              ║
 * ║                - avatar_url: URL da foto                                 ║
 * ║                - birthday: data de aniversário                           ║
 * ║                - phone: telefone                                         ║
 * ║                - status: 1 (ativo) ou 0 (inativo)                        ║
 * ║                - type: 'Colaborador' ou 'Admin'                          ║
 * ║                - companies[]: array de IDs de empresas                   ║
 * ║                - roles[]: array de IDs de cargos                         ║
 * ║                - notes: observações                                      ║
 * ║                                                                           ║
 * ║ @tolerância    Verifica existência de colunas antes de inserir           ║
 * ║                (compatibilidade com diferentes versões do schema)        ║
 * ║                                                                           ║
 * ║ @transação     Usa beginTransaction/commit/rollBack                      ║
 * ║                                                                           ║
 * ║ @redireciona   colaboradores.php (sucesso) ou collaborator_new.php       ║
 * ║                                                                           ║
 * ║ @dependências  db.php, auth.php                                          ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INICIALIZAÇÃO E VERIFICAÇÕES
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Inicia sessão se necessário
 */
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Inclui dependências
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$type = $_POST['type'] ?? 'Colaborador';
/**
 * Exige autenticação e permissão administrativa
 */
requireLogin();
if (!canAccessManager()) { 
    http_response_code(403); 
    exit('Acesso negado'); 
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÃO AUXILIAR
// ═══════════════════════════════════════════════════════════════════════════

/**
 * hasColumn() — Verifica se uma coluna existe na tabela
 * 
 * @param PDO $pdo Conexão
 * @param string $table Nome da tabela
 * @param string $col Nome da coluna
 * @return bool true se a coluna existe
 * 
 * @tolerância
 * Permite que o script funcione mesmo se o schema estiver incompleto
 */
function hasColumn(PDO $pdo, string $table, string $col): bool {
    $st = $pdo->prepare("
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = ? 
        AND COLUMN_NAME = ? 
        LIMIT 1
    ");
    $st->execute([$table, $col]);
    return (bool)$st->fetchColumn();
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: EXTRAÇÃO E VALIDAÇÃO DE DADOS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Extrai dados do formulário
 */
$name      = trim($_POST['name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = trim($_POST['password'] ?? '');
$avatar    = trim($_POST['avatar_url'] ?? '');
$birthday  = ($_POST['birthday'] ?? '') ?: null;
$phone     = trim($_POST['phone'] ?? '');
$status    = (int)($_POST['status'] ?? 1);
$companies = $_POST['companies'] ?? [];
$roles     = $_POST['roles'] ?? [];
$notes     = trim($_POST['notes'] ?? '');

// ---------------------------------------------------------
// LÓGICA DO TIPO DE USUÁRIO (Blindagem de Segurança)
// ---------------------------------------------------------
// 2. AGORA A TRAVA: Se o usuário logado NÃO for Admin...
if (!isAdmin()) {
    // ... ignoramos o que veio do formulário e forçamos "Colaborador", Mais facil pensar assim do que fazer uma volta com o gestor
    $type = 'Colaborador'; 
}
// ---------------------------------------------------------
// SEGURANÇA: EMPRESAS (Blindagem Anti-DevTools)
// ---------------------------------------------------------
// Se o usuário NÃO for Admin (é Gestor)...
if (!isAdmin()) {
    // ... descobrimos qual é a empresa DELE...
    $myCompanyId = currentCompanyId(); // Função do auth.php que pega o ID real
    
    // ... e forçamos o array de empresas ter APENAS a dele.
    // Isso anula qualquer ID falso que ele tenha enviado pelo HTML.
    $companies = $myCompanyId ? [$myCompanyId] : [];
}


if ($name === '' || $email === '') {    //parar tudo se email e nome estiverem vazios
    header('Location: ' . url_for('/pages/collaborator_new.php')); 
    exit; 
}

/**
 * Verifica se email já está cadastrado
 */
$dup = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$dup->execute([$email]);
if ($dup->fetch()) { 
    http_response_code(409); 
    exit('Email já cadastrado.'); 
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: PERSISTÊNCIA NO BANCO DE DADOS
// ═══════════════════════════════════════════════════════════════════════════

$inTx = false;

try {
    /**
     * Inicia transação de forma segura
     */
    if (method_exists($pdo, 'beginTransaction')) {
        if (!$pdo->inTransaction()) { 
            $inTx = $pdo->beginTransaction(); 
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 1: Monta INSERT dinamicamente (apenas colunas existentes)
    // ─────────────────────────────────────────────────────────────────────
    
    /**
     * Colunas obrigatórias
     */
    $cols = ['name', 'email', 'password_hash', 'type'];
    
    /**
     * Valores correspondentes
     * Se senha não foi informada, gera uma aleatória
     */
    $vals = [ 
        $name, 
        $email, 
        $password 
            ? password_hash($password, PASSWORD_DEFAULT) 
            : password_hash(bin2hex(random_bytes(6)), PASSWORD_DEFAULT), 
        $type 
    ];

    /**
     * Adiciona colunas opcionais se existirem no schema
     */
    if (hasColumn($pdo, 'users', 'avatar_url')) { 
        $cols[] = 'avatar_url'; 
        $vals[] = $avatar ?: null; 
    }
    if (hasColumn($pdo, 'users', 'birthday')) { 
        $cols[] = 'birthday'; 
        $vals[] = $birthday; 
    }
    if (hasColumn($pdo, 'users', 'phone')) { 
        $cols[] = 'phone'; 
        $vals[] = $phone ?: null; 
    }
    if (hasColumn($pdo, 'users', 'status')) { 
        $cols[] = 'status'; 
        $vals[] = $status; 
    }
    if (hasColumn($pdo, 'users', 'created_at')) { 
        $cols[] = 'created_at'; 
        $vals[] = date('Y-m-d H:i:s'); 
    }

    /**
     * Monta e executa o INSERT
     */
    $place = rtrim(str_repeat('?,', count($vals)), ',');
    $sql = "INSERT INTO users (" . implode(',', $cols) . ") VALUES ($place)";
    $st = $pdo->prepare($sql);
    $st->execute($vals);
    
    /**
     * Obtém o ID do usuário recém-criado
     */
    $userId = (int)$pdo->lastInsertId();

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 2: Salva observações na tabela user_meta
    // ─────────────────────────────────────────────────────────────────────
    
    /**
     * Cria tabela user_meta se não existir
     */
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_meta (
        user_id INT NOT NULL, 
        meta_key VARCHAR(64) NOT NULL, 
        meta_value TEXT,
        PRIMARY KEY (user_id, meta_key),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    /**
     * Insere ou atualiza observações
     */
    if ($notes !== '') {
        $m = $pdo->prepare("
            INSERT INTO user_meta (user_id, meta_key, meta_value)
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)
        ");
        $m->execute([$userId, 'notes', $notes]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 3: Associa usuário às empresas
    // ─────────────────────────────────────────────────────────────────────
    
    if (!empty($companies)) {
        $insUC = $pdo->prepare("INSERT IGNORE INTO user_company (user_id, company_id) VALUES (?, ?)");
        foreach ($companies as $cid) { 
            $cid = (int)$cid; 
            if ($cid > 0) {
                $insUC->execute([$userId, $cid]); 
            }
        }
    } else {
        /**
         * Se nenhuma empresa foi selecionada, associa à empresa atual
         */
        $currentCompany = currentCompanyId();
        if ($currentCompany) {
            $pdo->prepare("INSERT IGNORE INTO user_company (user_id, company_id) VALUES (?, ?)")
                ->execute([$userId, (int)$currentCompany]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 4: Associa usuário aos cargos
    // ─────────────────────────────────────────────────────────────────────
    
    if (!empty($roles)) {
        $insUR = $pdo->prepare("INSERT IGNORE INTO user_role (user_id, role_id) VALUES (?, ?)");
        foreach ($roles as $rid) { 
            $rid = (int)$rid; 
            if ($rid > 0) {
                $insUR->execute([$userId, $rid]); 
            }
        }
    }

    /**
     * Confirma a transação
     */
    if ($inTx && $pdo->inTransaction()) { 
        $pdo->commit(); 
    }
    
    /**
     * Redireciona para a lista de colaboradores
     */
    header('Location: ' . url_for('/pages/colaboradores.php')); 
    exit;

} catch (Throwable $e) {
    /**
     * Em caso de erro, reverte a transação
     */
    if ($inTx && method_exists($pdo, 'inTransaction') && $pdo->inTransaction()) {
        try { 
            $pdo->rollBack(); 
        } catch (Throwable $ignore) {}
    }
    
    /**
     * Retorna erro 500 com mensagem
     */
    http_response_code(500);
    echo "Erro ao salvar colaborador: " . $e->getMessage();
}
