<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ CONFIG.PHP — Configurações Globais da Plataforma Mindpulse               ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Centralizar todas as configurações da aplicação           ║
 * ║                Definir constantes usadas em todo o sistema               ║
 * ║                                                                           ║
 * ║ @acesso        Interno (incluído por outros arquivos PHP)                ║
 * ║ @escopo        Global (configurações afetam toda a plataforma)           ║
 * ║                                                                           ║
 * ║ @constantes    BASE_URL: URL base para links e assets                    ║
 * ║                DB_*: credenciais do banco de dados                       ║
 * ║                APP_*: configurações visuais/branding                     ║
 * ║                                                                           ║
 * ║ @segurança     ⚠️  Este arquivo contém credenciais sensíveis!            ║
 * ║                - Não versionar em repositório público                    ║
 * ║                - Em produção, usar variáveis de ambiente                 ║
 * ║                                                                           ║
 * ║ @dependências  Nenhuma (arquivo base)                                    ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: PROTEÇÃO CONTRA INCLUSÃO DUPLICADA
// ═══════════════════════════════════════════════════════════════════════════

/**
 * APP_BOOTSTRAPPED: Flag de controle de inicialização
 * 
 * Verifica se este arquivo já foi incluído anteriormente
 * Isso evita:
 * - Redefinição de constantes (causaria erro fatal)
 * - Execução duplicada do script BASE_URL no HTML
 * - Overhead desnecessário
 * 
 * defined() retorna true se a constante já existe
 */
if (!defined('APP_BOOTSTRAPPED')) {
    
    /**
     * Define a flag indicando que o bootstrap foi executado
     * Qualquer inclusão futura deste arquivo será ignorada
     */
    define('APP_BOOTSTRAPPED', true);

    // ═══════════════════════════════════════════════════════════════════════
    // SEÇÃO: DETECÇÃO AUTOMÁTICA DE BASE_URL
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * CÁLCULO AUTOMÁTICO DA BASE_URL
     * 
     * Problema: A aplicação pode estar instalada em diferentes caminhos:
     * - http://localhost/MINDPULSE (desenvolvimento)
     * - http://mindhub.com.br (produção na raiz)
     * - http://empresa.com/app/mindhub (subpasta em produção)
     * 
     * Solução: Calcular automaticamente baseado no DOCUMENT_ROOT
     * 
     * Passos:
     * 1. Pegar o DOCUMENT_ROOT do servidor (ex: C:/xampp/htdocs)
     * 2. Pegar o caminho real deste arquivo (ex: C:/xampp/htdocs/MINDPULSE/includes)
     * 3. Subir um nível para pegar a raiz da app (ex: C:/xampp/htdocs/MINDPULSE)
     * 4. Remover o DOCUMENT_ROOT para obter o caminho relativo (ex: /MINDPULSE)
     */
    
    /**
     * $doc: Document Root do servidor web
     * 
     * str_replace('\\','/') converte barras do Windows para Unix
     * rtrim(..., '/') remove barra final se houver
     * ?? '' previne erro se DOCUMENT_ROOT não existir
     */
    $doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    
    /**
     * $appDir: Diretório raiz da aplicação
     * 
     * __DIR__ é o diretório deste arquivo (includes/)
     * realpath(__DIR__ . '/..') sobe um nível (para a raiz da app)
     * str_replace converte barras para padrão Unix
     */
    $appDir = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    
    /**
     * $baseUrl: Caminho relativo da aplicação
     * 
     * str_replace($doc, '', $appDir) remove o document root
     * Exemplo: '/var/www/html/MINDPULSE' - '/var/www/html' = '/MINDPULSE'
     * rtrim remove barra final
     * 
     * Se o resultado for vazio, significa que a app está na raiz
     */
    $baseUrl = rtrim(str_replace($doc, '', $appDir), '/');
    if ($baseUrl === '') $baseUrl = ''; // Mantém vazio se estiver na raiz
    
    /**
     * Define a constante BASE_URL
     * 
     * Usada em todo o sistema para construir URLs:
     * - Links de navegação: BASE_URL . '/pages/home.php'
     * - Assets: BASE_URL . '/assets/css/styles.css'
     * - Formulários: action="<?= BASE_URL ?>/auth/do_login.php"
     */
    define('BASE_URL', $baseUrl);

    // ═══════════════════════════════════════════════════════════════════════
    // SEÇÃO: EXPOSIÇÃO DE BASE_URL PARA JAVASCRIPT
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Injeta BASE_URL como variável JavaScript global
     * 
     * Isso permite que scripts JS construam URLs corretas:
     * - fetch(window.BASE_URL + '/api/endpoint')
     * - location.href = window.BASE_URL + '/pages/home.php'
     * 
     * htmlspecialchars previne XSS escapando caracteres especiais
     * ENT_QUOTES escapa tanto aspas simples quanto duplas
     * 
     * ⚠️  NOTA: Este echo acontece no início do HTML
     * Pode causar problemas se incluído antes de headers HTTP
     */
    echo "<script>window.BASE_URL='" . htmlspecialchars(BASE_URL, ENT_QUOTES) . "';</script>";
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CONFIGURAÇÕES DO BANCO DE DADOS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * DB_HOST: Servidor do banco de dados MySQL
 * 
 * Exemplos:
 * - 'localhost' para desenvolvimento local
 * - '127.0.0.1' para conexão via IP
 * - 'servidor.mysql.uhserver.com' para hospedagem externa
 * 
 * ⚠️  Em produção, considere usar variáveis de ambiente:
 * define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
 */
define('DB_HOST', 'localhost');

/**
 * DB_NAME: Nome do banco de dados
 * 
 * O banco deve existir antes de executar a aplicação
 * Pode ser criado via phpMyAdmin ou comando SQL:
 * CREATE DATABASE rhtrain CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
 */
define('DB_NAME', 'rhtrain');

/**
 * DB_USER: Usuário do banco de dados
 * 
 * Este usuário deve ter permissões para:
 * - SELECT, INSERT, UPDATE, DELETE (operações básicas)
 * - CREATE TABLE (para criação automática de tabelas)
 * - ALTER TABLE (para migrações)
 */
define('DB_USER', 'root');

/**
 * DB_PASS: Senha do banco de dados
 * 
 * ⚠️  SEGURANÇA CRÍTICA:
 * - Nunca commitar senhas reais em repositório público
 * - Em produção, use variáveis de ambiente ou arquivo .env
 * - Considere usar gerenciadores de segredos (AWS Secrets Manager, etc)
 */
define('DB_PASS', '');

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CONFIGURAÇÕES DA APLICAÇÃO (BRANDING)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * APP_NAME: Nome da aplicação
 * 
 * Usado em:
 * - Títulos de páginas
 * - Emails
 * - Identificação visual
 */
define('APP_NAME', 'Mindhub');

/**
 * APP_BRAND_COLOR: Cor principal da marca
 * 
 * Formato: hexadecimal CSS (#RRGGBB)
 * Usada em:
 * - Botões primários
 * - Links
 * - Elementos de destaque
 * - Gradientes
 * 
 * Esta cor laranja (#ff6a00) é a identidade visual da Mindpulse
 */
define('APP_BRAND_COLOR', '#ff6a00');
