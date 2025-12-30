-- ╔═══════════════════════════════════════════════════════════════════════════╗
-- ║ SCHEMA.SQL — Estrutura do Banco de Dados da Plataforma Mindpulse         ║
-- ╠═══════════════════════════════════════════════════════════════════════════╣
-- ║                                                                           ║
-- ║ @objetivo      Definir todas as tabelas, relacionamentos e dados         ║
-- ║                iniciais necessários para o funcionamento da plataforma   ║
-- ║                                                                           ║
-- ║ @como_usar     Execute este arquivo no MySQL/phpMyAdmin para criar       ║
-- ║                a estrutura completa do banco de dados                    ║
-- ║                                                                           ║
-- ║ @idempotente   Sim - usa IF NOT EXISTS e ON DUPLICATE KEY                ║
-- ║                Pode ser executado múltiplas vezes sem erro               ║
-- ║                                                                           ║
-- ║ @engine        InnoDB (suporta transações e chaves estrangeiras)         ║
-- ║ @charset       utf8mb4 (suporta emojis e caracteres especiais)           ║
-- ║                                                                           ║
-- ╚═══════════════════════════════════════════════════════════════════════════╝


-- ═══════════════════════════════════════════════════════════════════════════
-- TABELA: USERS (Usuários)
-- 
-- Propósito: Armazena todos os usuários da plataforma
-- Níveis: Admin (Admin Geral/Gestor) e Colaborador
-- 
-- Relacionamentos:
-- - user_company: empresas que o usuário pode acessar
-- - user_role: cargos/funções do usuário
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS users (
    -- ID único do usuário (chave primária auto-incremento)
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Nome completo do usuário (obrigatório)
    -- Exibido em saudações, listas e perfil
    name VARCHAR(120) NOT NULL,
    
    -- Email do usuário (obrigatório e único)
    -- Usado como identificador de login
    -- UNIQUE garante que não existam duplicatas
    email VARCHAR(160) NOT NULL UNIQUE,
    
    -- Hash da senha (gerado com password_hash do PHP)
    -- NUNCA armazenar senha em texto plano!
    -- VARCHAR(255) comporta qualquer algoritmo de hash
    password_hash VARCHAR(255) NOT NULL,
    
    -- Tipo/Nível do usuário
    -- 'Admin': acesso administrativo (Admin Geral ou Gestor)
    -- 'Colaborador': acesso de execução (treinamentos, checklists)
    -- DEFAULT 'Colaborador': novos usuários são colaboradores por padrão
    type ENUM('Admin','Colaborador') NOT NULL DEFAULT 'Colaborador',
    
    -- URL da foto de perfil (opcional)
    -- Pode ser caminho local (/assets/img/...) ou URL externa
    avatar_url VARCHAR(255) DEFAULT NULL,
    
    -- Data de criação do registro
    -- CURRENT_TIMESTAMP: preenchido automaticamente
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ═══════════════════════════════════════════════════════════════════════════
-- TABELA: COMPANIES (Empresas)
-- 
-- Propósito: Armazena as empresas/lojas cadastradas na plataforma
-- Conceito multiempresa: cada empresa tem seus próprios dados isolados
-- 
-- Relacionamentos:
-- - user_company: usuários vinculados à empresa
-- - trainings: treinamentos da empresa
-- - checklists: checklists da empresa
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS companies (
    -- ID único da empresa (chave primária)
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Nome da empresa (razão social ou nome fantasia)
    -- Exibido no seletor de empresas e listas
    name VARCHAR(160) NOT NULL,
    
    -- Data de criação do registro
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ═══════════════════════════════════════════════════════════════════════════
-- TABELA: ROLES (Cargos/Funções)
-- 
-- Propósito: Define os cargos que podem ser atribuídos aos usuários
-- Exemplos: Analista, Coordenador, Instrutor, Atendente, Gerente
-- 
-- Uso:
-- - Determina quais treinamentos o usuário vê (role_training)
-- - Determina quais checklists o usuário executa (checklist_role)
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS roles (
    -- ID único do cargo
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Nome do cargo (único para evitar duplicatas)
    -- Exemplos: 'Analista', 'Coordenador', 'Instrutor'
    name VARCHAR(100) NOT NULL UNIQUE
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ═══════════════════════════════════════════════════════════════════════════
-- TABELA: USER_COMPANY (Relacionamento Usuário ↔ Empresa)
-- 
-- Propósito: Define quais empresas cada usuário pode acessar
-- Tipo: Tabela de junção (many-to-many)
-- 
-- Regras de negócio:
-- - Admin Geral: pode ter acesso a TODAS as empresas
-- - Gestor: acesso a UMA empresa específica
-- - Colaborador: acesso a UMA empresa específica
-- 
-- ON DELETE CASCADE: se usuário ou empresa for deletado,
-- o vínculo é removido automaticamente
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS user_company (
    -- ID do usuário (referência à tabela users)
    user_id INT NOT NULL,
    
    -- ID da empresa (referência à tabela companies)
    company_id INT NOT NULL,
    
    -- Chave primária composta: evita duplicatas de vínculo
    PRIMARY KEY (user_id, company_id),
    
    -- Chave estrangeira: garante integridade referencial com users
    -- CASCADE: deleta vínculo se usuário for deletado
    CONSTRAINT fk_uc_user FOREIGN KEY (user_id) 
        REFERENCES users(id) ON DELETE CASCADE,
    
    -- Chave estrangeira: garante integridade referencial com companies
    -- CASCADE: deleta vínculo se empresa for deletada
    CONSTRAINT fk_uc_company FOREIGN KEY (company_id) 
        REFERENCES companies(id) ON DELETE CASCADE
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ═══════════════════════════════════════════════════════════════════════════
-- TABELA: USER_ROLE (Relacionamento Usuário ↔ Cargo)
-- 
-- Propósito: Define quais cargos cada usuário possui
-- Tipo: Tabela de junção (many-to-many)
-- 
-- Um usuário pode ter múltiplos cargos
-- Isso afeta quais treinamentos e checklists ele vê
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS user_role (
    -- ID do usuário
    user_id INT NOT NULL,
    
    -- ID do cargo
    role_id INT NOT NULL,
    
    -- Chave primária composta
    PRIMARY KEY (user_id, role_id),
    
    -- Chaves estrangeiras com CASCADE
    CONSTRAINT fk_ur_user FOREIGN KEY (user_id) 
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ur_role FOREIGN KEY (role_id) 
        REFERENCES roles(id) ON DELETE CASCADE
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ═══════════════════════════════════════════════════════════════════════════
-- SEÇÃO: DADOS INICIAIS (SEED)
-- 
-- Propósito: Popular o banco com dados mínimos para funcionamento
-- Inclui: usuário admin, empresas de exemplo, cargos básicos
-- ═══════════════════════════════════════════════════════════════════════════

-- ─────────────────────────────────────────────────────────────────────────────
-- SEED: Usuário Admin Master
-- 
-- Email: admin@mindhub.local
-- Senha: admin123 (hash bcrypt)
-- 
-- ON DUPLICATE KEY UPDATE: se já existir, não faz nada
-- Isso torna o script idempotente (pode rodar múltiplas vezes)
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO users (name, email, password_hash, type, avatar_url)
VALUES (
    'Admin Master',                  -- Nome do admin
    'admin@mindhub.local',           -- Email de login
    '$2y$10$FqwnhK9gP0aXUo5m0C9q/uQ9sB3Hq0nW7C3VtG6J1oVxjA1F7vFBS', -- Hash de 'admin123'
    'Admin',                         -- Tipo Admin
    NULL                             -- Sem avatar
)
ON DUPLICATE KEY UPDATE email=email; -- Se já existe, não altera nada

-- ─────────────────────────────────────────────────────────────────────────────
-- SEED: Empresas de Exemplo
-- 
-- Cria duas empresas para demonstração
-- Em produção, serão criadas pelo Admin Geral
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO companies (name) 
VALUES ('Empresa Alpha'), ('Empresa Beta')
ON DUPLICATE KEY UPDATE name=name;

-- ─────────────────────────────────────────────────────────────────────────────
-- SEED: Cargos Básicos
-- 
-- Cargos comuns que podem ser usados em qualquer empresa
-- Gestores podem criar cargos adicionais conforme necessidade
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO roles (name) 
VALUES ('Analista'), ('Coordenador'), ('Instrutor')
ON DUPLICATE KEY UPDATE name=name;

-- ─────────────────────────────────────────────────────────────────────────────
-- SEED: Vincular Admin a TODAS as Empresas
-- 
-- CROSS JOIN: combina cada linha de users com cada linha de companies
-- WHERE filtra apenas o admin
-- INSERT IGNORE: ignora se o vínculo já existir
-- 
-- Resultado: Admin terá acesso a Empresa Alpha e Empresa Beta
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO user_company (user_id, company_id)
SELECT u.id, c.id 
FROM users u 
CROSS JOIN companies c 
WHERE u.email='admin@mindhub.local';

-- ─────────────────────────────────────────────────────────────────────────────
-- SEED: Vincular Admin a TODOS os Cargos
-- 
-- Similar ao anterior, mas para cargos
-- Admin terá todos os cargos (Analista, Coordenador, Instrutor)
-- Isso permite que ele veja todos os treinamentos e checklists
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO user_role (user_id, role_id)
SELECT u.id, r.id 
FROM users u 
CROSS JOIN roles r 
WHERE u.email='admin@mindhub.local';


-- ═══════════════════════════════════════════════════════════════════════════
-- FIM DO SCHEMA BÁSICO
-- 
-- Tabelas adicionais (trainings, training_videos, checklists, etc.)
-- são criadas automaticamente pelo sistema quando necessário
-- ou podem ser adicionadas em scripts de migração separados
-- ═══════════════════════════════════════════════════════════════════════════
