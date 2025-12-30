<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ AUTH.PHP — Funções de Autenticação e Autorização                         ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Fornecer funções para controle de acesso e permissões     ║
 * ║                Este é o arquivo CENTRAL de segurança da plataforma       ║
 * ║                                                                           ║
 * ║ @acesso        Interno (incluído por praticamente todos os arquivos)     ║
 * ║ @escopo        Global (funções usadas em toda a aplicação)               ║
 * ║                                                                           ║
 * ║ @funções       url_for()        - Gera URLs corretas                     ║
 * ║                requireLogin()   - Exige autenticação                     ║
 * ║                isAdmin()        - Verifica se é Admin                    ║
 * ║                canAccessAdmin() - Verifica acesso administrativo         ║
 * ║                currentCompanyId() - Retorna empresa atual                ║
 * ║                                                                           ║
 * ║ @modelo_permissões                                                       ║
 * ║   - Admin Geral: acesso total, todas as empresas                        ║
 * ║   - Gestor: acesso admin, apenas sua empresa                            ║
 * ║   - Colaborador: acesso execução, apenas sua empresa                    ║
 * ║                                                                           ║
 * ║ @dependências  includes/config.php (BASE_URL, constantes)                ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INICIALIZAÇÃO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Inicia a sessão PHP se ainda não estiver ativa
 * 
 * A sessão é ESSENCIAL para:
 * - Manter o usuário logado entre requisições
 * - Armazenar dados do usuário ($_SESSION['user'])
 * - Armazenar empresa atual ($_SESSION['current_company'])
 * - Armazenar cargos ($_SESSION['roles'])
 * 
 * session_status() verifica o estado atual:
 * - PHP_SESSION_NONE: nenhuma sessão ativa (precisamos iniciar)
 * - PHP_SESSION_ACTIVE: sessão já ativa (não fazer nada)
 */
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Inclui o arquivo de configuração
 * 
 * Fornece:
 * - BASE_URL: para construção de URLs
 * - Constantes de banco de dados
 * - Configurações da aplicação
 */
require_once __DIR__ . '/config.php';

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE UTILIDADE
// ═══════════════════════════════════════════════════════════════════════════

/**
 * url_for() — Gera URL completa a partir de caminho relativo
 * 
 * @param string $path Caminho relativo (ex: '/pages/home.php')
 * @return string URL completa (ex: '/MINDPULSE/pages/home.php')
 * 
 * @exemplo
 * // Se BASE_URL = '/MINDPULSE':
 * url_for('/login.php')           → '/MINDPULSE/login.php'
 * url_for('/assets/css/style.css') → '/MINDPULSE/assets/css/style.css'
 * 
 * // Se BASE_URL = '' (raiz):
 * url_for('/login.php')           → '/login.php'
 * 
 * @por_que_usar
 * - Garante URLs corretas independente de onde a app está instalada
 * - Evita hardcoding de caminhos
 * - Facilita migração entre ambientes (dev/staging/prod)
 */
function url_for(string $path): string { 
    /**
     * rtrim(BASE_URL, '/') remove barra final da BASE_URL se houver
     * Isso evita URLs com barras duplicadas: '/MINDPULSE//login.php'
     * 
     * O $path já deve começar com '/' por convenção
     */
    return rtrim(BASE_URL, '/') . $path; 
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE AUTENTICAÇÃO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * requireLogin() — Exige que o usuário esteja autenticado
 * 
 * @return void (redireciona se não logado, continua se logado)
 * 
 * @uso
 * Deve ser chamada no início de QUALQUER página protegida:
 * ```php
 * require_once 'includes/auth.php';
 * requireLogin(); // Se não logado, redireciona para login
 * // Código protegido aqui...
 * ```
 * 
 * @fluxo
 * 1. Verifica se $_SESSION['user'] existe e não está vazio
 * 2. Se vazio: redireciona para /login.php e encerra
 * 3. Se existe: continua a execução normalmente
 * 
 * @segurança
 * - O exit; após header() é OBRIGATÓRIO
 * - Sem ele, o código continuaria executando mesmo após redirect
 */
function requireLogin(): void {
    if (empty($_SESSION['user'])) { 
        header('Location: ' . url_for('/login.php')); 
        exit; 
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE AUTORIZAÇÃO (PERMISSÕES)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * isAdmin() — Verifica se o usuário atual é Administrador
 * 
 * @return bool true se Admin, false se Colaborador ou não logado
 * 
 * @níveis_de_usuário
 * - 'Admin': pode ser Admin Geral (todas empresas) ou Gestor (uma empresa)
 * - 'Colaborador': apenas execução (treinamentos, checklists)
 * 
 * @uso
 * ```php
 * if (isAdmin()) {
 *     // Mostra opções administrativas
 * } else {
 *     // Mostra apenas opções de colaborador
 * }
 * ```
 * 
 * @nota
 * Esta função verifica apenas o TIPO do usuário, não o ESCOPO (empresa)
 * Para verificar escopo, use currentCompanyId() em conjunto
 */
function isAdmin(): bool {
    /**
     * isset() verifica se a chave existe no array
     * && garante que ambas condições sejam verdadeiras
     * === 'Admin' compara tipo E valor (mais seguro que ==)
     */
    return isset($_SESSION['user']['type']) && $_SESSION['user']['type'] === 'Admin';
}

/**
 * canAccessManage() — Verifica permissão para áreas de gestão
 * Atende ao requisito multiempresa para Admin e Gestor
 */
function canAccessManager(): bool {
    // Verifica se existe a sessão e se o tipo é Admin OU Gestor
    return isset($_SESSION['user']['type']) && 
           ($_SESSION['user']['type'] === 'Admin' || $_SESSION['user']['type'] === 'Gestor');
}
/**
 * canAccessAdmin() — Verifica se pode acessar funcionalidades administrativas
 * 
 * @return bool true se pode acessar área admin
 * 
 * @diferença_de_isAdmin
 * Atualmente é um alias de isAdmin(), mas existe separadamente para:
 * - Permitir lógica mais complexa no futuro
 * - Diferenciar "ser admin" de "poder acessar área admin"
 * - Facilitar implementação de permissões granulares
 * 
 * @futuro
 * Pode evoluir para verificar permissões específicas:
 * - canManageTraining()
 * - canManageChecklists()
 * - canManageUsers()
 * 
 * @uso
 * ```php
 * if (canAccessAdmin()) {
 *     echo '<a href="/pages/empresas.php">Gerenciar Empresas</a>';
 * }
 * ```
 */

// Segurança para que o gestor nao possa ter privilegios de ADM
function canAccessAdmin(): bool { 
    return isAdmin(); 
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE CONTEXTO (EMPRESA ATUAL)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * currentCompanyId() — Retorna o ID da empresa atualmente selecionada
 * 
 * @return int|null ID da empresa ou null se nenhuma selecionada
 * 
 * @conceito_multiempresa
 * Um usuário pode ter acesso a múltiplas empresas, mas trabalha
 * em UMA empresa por vez. Esta função retorna qual está ativa.
 * 
 * @onde_é_definido
 * $_SESSION['current_company'] é definido em:
 * - do_login.php: primeira empresa do usuário
 * - switch_company.php: quando troca de empresa
 * 
 * @uso
 * ```php
 * $companyId = currentCompanyId();
 * if (!$companyId) {
 *     echo "Selecione uma empresa para continuar";
 *     exit;
 * }
 * 
 * // Buscar dados apenas desta empresa
 * $sql = "SELECT * FROM trainings WHERE company_id = ?";
 * $stmt->execute([$companyId]);
 * ```
 * 
 * @segurança
 * SEMPRE use currentCompanyId() em queries que envolvem dados de empresa
 * Isso garante isolamento multiempresa (um gestor não vê dados de outra)
 */
function currentCompanyId(): ?int { 
    /**
     * Operador null coalescing (??)
     * Retorna o valor à esquerda se existir e não for null
     * Caso contrário, retorna o valor à direita (null neste caso)
     */
    return $_SESSION['current_company']['id'] ?? null; 
}
