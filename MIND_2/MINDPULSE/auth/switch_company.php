<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ SWITCH_COMPANY.PHP — Troca de Empresa Ativa na Sessão                    ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Permitir que usuários multi-empresa alternem entre        ║
 * ║                as empresas que têm acesso                                ║
 * ║                                                                           ║
 * ║ @acesso        Usuários autenticados com acesso a múltiplas empresas     ║
 * ║ @método        POST (JSON) ou GET (fallback)                             ║
 * ║ @escopo        Global (altera contexto de empresa da sessão)             ║
 * ║                                                                           ║
 * ║ @parâmetros    company_id: ID da empresa para ativar                     ║
 * ║                                                                           ║
 * ║ @fluxo         1. Usuário seleciona empresa no dropdown do header        ║
 * ║                2. JavaScript envia POST com company_id                   ║
 * ║                3. Este arquivo valida se usuário tem acesso              ║
 * ║                4. Se válido: atualiza sessão e retorna sucesso           ║
 * ║                5. Se inválido: retorna erro                              ║
 * ║                                                                           ║
 * ║ @segurança     - Valida se usuário tem acesso à empresa solicitada       ║
 * ║                - Não permite acessar empresas não vinculadas             ║
 * ║                                                                           ║
 * ║ @retorno       JSON: {status: 'ok'} ou {status: 'error', message: '...'}║
 * ║                                                                           ║
 * ║ @dependências  includes/db.php, includes/auth.php                        ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INICIALIZAÇÃO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Inicia a sessão PHP
 */
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Inicia buffer de saída
 * 
 * ob_start() captura qualquer saída (echo, print, warnings)
 * Isso permite limpar tudo antes de enviar JSON puro
 * Evita que erros ou includes quebrem a resposta JSON
 */
ob_start();

/**
 * Inclui dependências
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

/**
 * Exige que o usuário esteja logado
 * 
 * Se não estiver, será redirecionado para login
 */
requireLogin();

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÃO AUXILIAR PARA RESPOSTA JSON
// ═══════════════════════════════════════════════════════════════════════════

/**
 * respond_json() — Envia resposta JSON e encerra execução
 * 
 * @param array $arr Dados a serem convertidos para JSON
 * @param int $code Código HTTP (200 = sucesso, 400 = erro cliente, 500 = erro servidor)
 * 
 * Esta função:
 * 1. Limpa qualquer saída anterior (warnings, echos acidentais)
 * 2. Define o código HTTP
 * 3. Define Content-Type como JSON
 * 4. Envia o JSON
 * 5. Encerra a execução
 */
function respond_json($arr, $code = 200) {
    // Limpa todos os buffers de saída acumulados
    while (ob_get_level()) ob_end_clean();
    
    // Define o código de status HTTP
    http_response_code($code);
    
    // Define o Content-Type como JSON com UTF-8
    header('Content-Type: application/json; charset=utf-8');
    
    // Converte array para JSON e envia
    // JSON_UNESCAPED_UNICODE mantém caracteres especiais legíveis
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    
    // Encerra a execução
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: PROCESSAMENTO DA REQUISIÇÃO
// ═══════════════════════════════════════════════════════════════════════════

try {
    // ─────────────────────────────────────────────────────────────────────
    // PASSO 1: Capturar o company_id da requisição
    // 
    // Aceita três formatos:
    // 1. JSON no body (POST via fetch/AJAX)
    // 2. Form data (POST tradicional)
    // 3. Query string (GET - fallback)
    // ─────────────────────────────────────────────────────────────────────
    
    /**
     * Lê o corpo da requisição como string
     * php://input contém o body raw do POST
     */
    $raw = file_get_contents('php://input');
    
    /**
     * Tenta decodificar como JSON
     * Se não for JSON válido, retorna null
     */
    $in = json_decode($raw, true);
    
    /**
     * Extrai company_id de uma das fontes disponíveis
     */
    $cid = 0;
    
    if (is_array($in) && isset($in['company_id'])) {
        // Fonte 1: JSON body (requisição AJAX)
        $cid = (int)$in['company_id'];
    } elseif (isset($_POST['company_id'])) {
        // Fonte 2: Form data POST
        $cid = (int)$_POST['company_id'];
    } elseif (isset($_GET['company_id'])) {
        // Fonte 3: Query string GET (fallback)
        $cid = (int)$_GET['company_id'];
    }
    
    /**
     * Valida se company_id é válido (maior que zero)
     */
    if ($cid <= 0) {
        throw new Exception('company_id inválido');
    }

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 2: Verificar se o usuário tem acesso à empresa
    // ─────────────────────────────────────────────────────────────────────
    
    /**
     * Obtém o ID do usuário logado da sessão
     */
    // ─────────────────────────────────────────────────────────────────────
    // PASSO 2: Verificar se o usuário tem acesso à empresa
    // ─────────────────────────────────────────────────────────────────────
    
    $userId = (int)$_SESSION['user']['id'];
    $row = null;

    if (isAdmin()) {
        // SE FOR ADMIN: Acesso VIP (busca direto na tabela companies)
        // Não verifica a tabela user_company
        $sql = "SELECT id, name, trade_name FROM companies WHERE id = ? LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([$cid]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
    } else {
        // SE FOR GESTOR: Acesso Restrito (precisa do vínculo)
        $sql = "SELECT c.id, c.name, c.trade_name
                FROM companies c
                JOIN user_company uc ON uc.company_id = c.id
                WHERE uc.user_id = ? AND c.id = ? 
                LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([$userId, $cid]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Se não encontrou, usuário não tem acesso ou empresa não existe
     */
    if (!$row) {
        throw new Exception('Você não tem acesso a esta empresa.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 3: Atualizar a sessão com a nova empresa
    // ─────────────────────────────────────────────────────────────────────
    
    // Atualiza a empresa atual
    $_SESSION['current_company'] = [
        'id' => (int)$row['id'], 
        'trade_name' => $row['trade_name']
    ];

    // Atualiza a lista de empresas na sessão (opcional, mas bom pra manter sincronizado)
    if (isAdmin()) {
        $st2 = $pdo->query("SELECT id, name, trade_name FROM companies ORDER BY trade_name");
        $_SESSION['companies'] = $st2->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $st2 = $pdo->prepare("SELECT c.id, c.name, c.trade_name 
                              FROM companies c
                              JOIN user_company uc ON uc.company_id = c.id
                              WHERE uc.user_id = ? 
                              ORDER BY c.trade_name");
        $st2->execute([$userId]);
        $_SESSION['companies'] = $st2->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recarrega a lista completa de empresas do usuário
     * 
     * Isso garante que o seletor do header esteja sempre atualizado
     * (caso novas empresas tenham sido adicionadas)
     */
    $st2 = $pdo->prepare("SELECT c.id, c.name, c.trade_name 
                          FROM companies c
                          JOIN user_company uc ON uc.company_id = c.id
                          WHERE uc.user_id = ? 
                          ORDER BY c.trade_name");
    $st2->execute([$userId]);
    $_SESSION['companies'] = $st2->fetchAll(PDO::FETCH_ASSOC);

    // ─────────────────────────────────────────────────────────────────────
    // PASSO 4: Responder conforme o tipo de requisição
    // ─────────────────────────────────────────────────────────────────────
    
    /**
     * Verifica o método HTTP para decidir o tipo de resposta
     * 
     * GET: Provavelmente um link direto, redireciona de volta
     * POST: Provavelmente AJAX, retorna JSON
     */
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        // Requisição GET: redireciona para a página anterior
        $back = $_SERVER['HTTP_REFERER'] ?? (defined('BASE_URL') ? BASE_URL . '/pages/home.php' : '/');
        while (ob_get_level()) ob_end_clean();
        header('Location: ' . $back); 
        exit;
    } else {
        // Requisição POST: retorna JSON de sucesso
        respond_json(['status' => 'ok']);
    }
    
} catch (Throwable $e) {
    // ─────────────────────────────────────────────────────────────────────
    // TRATAMENTO DE ERROS
    // ─────────────────────────────────────────────────────────────────────
    
    /**
     * Em caso de erro, responde de acordo com o método
     */
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        // GET: exibe mensagem de texto simples
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(400);
        echo 'Erro ao trocar empresa: ' . $e->getMessage();
        exit;
    } else {
        // POST: retorna JSON de erro
        respond_json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
}
