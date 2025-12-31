-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 31/12/2025 às 02:24
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `rhtrain`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `checklists`
--

CREATE TABLE `checklists` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `title` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `frequency` enum('daily','weekly','biweekly','monthly') NOT NULL DEFAULT 'daily',
  `default_role_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `checklists`
--

INSERT INTO `checklists` (`id`, `company_id`, `title`, `description`, `frequency`, `default_role_id`, `is_active`, `created_by`, `created_at`) VALUES
(1, 1, 'CHECKLIST ABERTURA', '', 'daily', 7, 1, 3, '2025-10-07 23:09:15'),
(2, 1, 'CHECKLIST SEMANAL', 'descrição teste!', 'weekly', 2, 1, 3, '2025-10-07 23:20:42'),
(3, 8, 'PRÉ OPERAÇÃO', 'esse é o preparo para começar o dia bem, de 16:30 as 18:00', 'daily', 10, 1, 3, '2025-10-15 01:09:40'),
(4, 5, 'Abertura do Bar', '', 'daily', 7, 1, 3, '2025-10-21 13:07:27'),
(5, 5, 'Abertura Do Bar (Manhã)', '', 'daily', 7, 1, 3, '2025-10-28 17:22:02'),
(6, 5, 'Fechamento Bar (Salão)', '', 'daily', 7, 1, 3, '2025-10-29 12:30:49'),
(7, 4, 'Salao', 'FAzer comida', 'daily', 8, 1, 2, '2025-12-29 23:21:05'),
(8, 7, 'Abertura', 'Vamos pra la', 'daily', 9, 1, 3, '2025-12-29 23:22:21'),
(9, 1, 'Trocar Oléo', 'Ta horrivel aquilo na cozinha, auzxiliar, troque', 'biweekly', 11, 1, 38, '2025-12-30 15:55:47'),
(22, 1, 'Reciclar Lixo da cozinha', 'tem muito plastico', 'weekly', 11, 1, 39, '2025-12-30 16:24:00'),
(23, 1, 'oi', 'io', 'daily', 11, 1, 39, '2025-12-30 18:26:26'),
(24, 10, 'Ze vai lavar louca', 'ZEEE', 'daily', 9, 1, 60, '2025-12-31 00:05:11'),
(25, 10, 'Analista ve isso', 'analista ve', 'daily', 12, 1, 60, '2025-12-31 00:10:35'),
(26, 10, 'sdasdasd', 'asdadada', 'daily', 11, 1, 60, '2025-12-31 01:09:07');

-- --------------------------------------------------------

--
-- Estrutura para tabela `checklist_role`
--

CREATE TABLE `checklist_role` (
  `checklist_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `checklist_role`
--

INSERT INTO `checklist_role` (`checklist_id`, `role_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(2, 1),
(2, 2),
(2, 3),
(2, 7),
(2, 8),
(2, 9),
(2, 10),
(2, 11),
(2, 12),
(3, 10),
(4, 7),
(4, 12),
(5, 7),
(6, 7),
(8, 2),
(9, 11),
(22, 8),
(22, 9),
(22, 11),
(23, 11),
(24, 8),
(24, 9),
(24, 10),
(24, 11),
(25, 1),
(26, 9);

-- --------------------------------------------------------

--
-- Estrutura para tabela `checklist_tasks`
--

CREATE TABLE `checklist_tasks` (
  `id` int(11) NOT NULL,
  `checklist_id` int(11) NOT NULL,
  `priority` tinyint(4) NOT NULL DEFAULT 3,
  `name` varchar(255) NOT NULL,
  `period` enum('inicio_dia','final_dia','inicio_semana','final_semana') NOT NULL DEFAULT 'final_dia',
  `notes` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `checklist_tasks`
--

INSERT INTO `checklist_tasks` (`id`, `checklist_id`, `priority`, `name`, `period`, `notes`, `is_active`) VALUES
(1, 1, 5, 'Verificar as etiquetas da geladeira', 'final_dia', NULL, 1),
(2, 1, 3, 'Trocar as etiquetas da geladeira da praça quente', 'final_dia', NULL, 1),
(3, 2, 5, 'Verificar as etiquetas da geladeira', 'inicio_semana', 'Realizar com cuidado essa parte e fazer o treinamento se preciso', 1),
(4, 2, 3, 'Trocar as etiquetas da geladeira da praça quente', 'final_semana', NULL, 1),
(5, 3, 5, 'Ligar o computador', 'inicio_dia', NULL, 1),
(6, 3, 3, 'Olhar os freezer verificar se as bebidas estão geladas', 'inicio_dia', 'caso não esteja informar o gerente da empresa e colocar AS BEBIDAS em outro local possivel de gelar', 1),
(7, 3, 3, 'Abrir o google chromo e abrir os sistema', 'inicio_dia', NULL, 1),
(8, 4, 5, 'Verificar Funcionamento dos Equipamentos', 'inicio_dia', 'Verificar se houve alguma avaria ou dano aos equipamentos no turno anterior (Relatar ao superior / responsável o quanto antes de forma escrita)', 1),
(9, 4, 5, 'Aferir Temperatura dos Freezers', 'inicio_dia', 'Registrar a temperatura de cada freezer na tabela de temperatura e assinar ao lado após isso', 1),
(10, 4, 5, 'Verificar Se tem Broinha (Qualidade deve ser Verificada Junto)', 'inicio_dia', 'verificar a quantidade e qualidade das broinhas que são servidas junto do café', 1),
(11, 4, 4, 'Fazer pedido de broinha para cozinha (se necessário)', 'final_dia', 'formalizar pedido a cozinha caso as broinhas não atinjam a qualidade ou quantidade aceitável para o serviço', 1),
(12, 4, 5, 'Lavar louça do turno da noite (Não pode haver)', 'inicio_dia', 'Lavar louça que tenha sobrado do turno anterior e registrar ocorrência ao superior ou responsável', 1),
(13, 4, 3, 'Limpar Vidro dos freezers do bar', 'inicio_dia', 'Utilizar álcool ou produto indicado para limpar a vitrine dos freezers', 1),
(14, 4, 4, 'Verificar gelo do freezer', 'inicio_dia', NULL, 1),
(15, 4, 5, 'Verificar limpeza da maquina de café', 'inicio_dia', 'Verificar a limpeza e o estado da maquina de café (Limpar e ou registrar ocorrência ao superior se ocorrer avaria ou mal uso)', 1),
(16, 4, 4, 'Limpeza do bar (Chão, pia, balcão e escorredor)', 'inicio_dia', 'Fazer serviço de limpeza básico para iniciar o serviço', 1),
(17, 4, 3, 'Verificar Validade do Chopp Claro', 'inicio_dia', 'Verificar e ou registrar a um superior ou responsável se houver risco de vencimento', 1),
(18, 4, 3, 'Verificar Validade do Chopp Escuro', 'inicio_dia', 'Verificar e ou registrar a um superior ou responsável se houver risco de vencimento', 1),
(19, 4, 2, 'Limpar Maquina de Gelo', 'inicio_dia', 'Limpeza periódica da maquina de gelo para evitar contaminação cruzada com insumos e produtos', 1),
(20, 4, 4, 'Limpar Reservatório da Chopeira', 'inicio_dia', 'Verificar e limpar o reservatório da Chopeira para o início do serviço', 1),
(21, 4, 4, 'Cortar Frutas e verificar a Qualidade', 'inicio_dia', 'Fazer mise en place do bar e verificar a qualidade dos insumos (Registrar ao superior ou responsável caso haja qualidade ruim)', 1),
(22, 5, 2, 'Limpar Maquina de Gelo', 'inicio_dia', 'Limpeza periódica da maquina de gelo para evitar contaminação cruzada com insumos e produtos', 1),
(23, 5, 2, 'Limpar Vidro dos freezers do bar', 'inicio_dia', 'Utilizar álcool ou produto indicado para limpar a vitrine dos freezers', 1),
(24, 5, 4, 'Fazer pedido de broinha para cozinha (se necessário)', 'final_dia', 'formalizar pedido a cozinha caso as broinhas não atinjam a qualidade ou quantidade aceitável para o serviço', 1),
(25, 5, 4, 'Verificar gelo do freezer', 'inicio_dia', NULL, 1),
(26, 5, 5, 'Limpeza do bar (Chão, pia, balcão e escorredor)', 'inicio_dia', 'Fazer serviço de limpeza básico para iniciar o serviço', 1),
(27, 5, 5, 'Limpar Reservatório da Chopeira', 'inicio_dia', 'Verificar e limpar o reservatório da Chopeira para o início do serviço', 1),
(28, 5, 4, 'Cortar Frutas e verificar a Qualidade', 'inicio_dia', 'Fazer mise en place do bar e verificar a qualidade dos insumos (Registrar ao superior ou responsável caso haja qualidade ruim)', 1),
(29, 5, 5, 'Verificar Funcionamento dos Equipamentos', 'inicio_dia', 'Verificar se houve alguma avaria ou dano aos equipamentos no turno anterior (Relatar ao superior / responsável o quanto antes de forma escrita)', 1),
(30, 5, 4, 'Aferir Temperatura dos Freezers', 'inicio_dia', 'Registrar a temperatura de cada freezer na tabela de temperatura e assinar ao lado após isso', 1),
(31, 5, 4, 'Verificar Se tem Broinha (Qualidade deve ser Verificada Junto)', 'inicio_dia', 'verificar a quantidade e qualidade das broinhas que são servidas junto do café', 1),
(32, 5, 5, 'Lavar louça do turno da noite (Não pode haver)', 'inicio_dia', 'Lavar louça que tenha sobrado do turno anterior e registrar ocorrência ao superior ou responsável', 1),
(33, 5, 5, 'Verificar limpeza da maquina de café', 'inicio_dia', 'Verificar a limpeza e o estado da maquina de café', 1),
(34, 5, 5, 'Fazer a Limpeza da Cafeteira se necessário (Não pode Haver)', 'inicio_dia', 'Limpar e ou registrar ocorrência ao superior se ocorrer avaria ou mal uso', 1),
(35, 6, 3, 'Cortar Frutas e verificar a Qualidade (Se Necessário)', 'final_dia', 'Fazer mise en place do bar e verificar a qualidade dos insumos (Registrar ao superior ou responsável caso haja qualidade ruim)', 1),
(36, 6, 4, 'Verificar Se tem Broinha (Qualidade deve ser Verificada Junto)', 'final_dia', 'verificar a quantidade e qualidade das broinhas que são servidas junto do café', 1),
(37, 6, 3, 'Fazer pedido de broinha para cozinha (se necessário)', 'final_dia', 'formalizar pedido a cozinha caso as broinhas não atinjam a qualidade ou quantidade aceitável para o serviço', 1),
(38, 6, 5, 'Limpeza do bar (pia, balcão e escorredor)', 'final_dia', 'Fazer serviço de limpeza básico', 1),
(39, 6, 5, 'Verificar limpeza da maquina de café', 'final_dia', 'Verificar a limpeza e o estado da maquina de café', 1),
(40, 6, 5, 'Fazer a limpeza da maquina de café', 'final_dia', 'Retirar o reservatório de água, retirar o reservatório de capsulas e limpar tudo para o próximo turno.', 1),
(41, 6, 5, 'Lavar louça', 'final_dia', 'Lavar a Louça e deixar tudo limpo para o próximo turno. (Caso haja impossibilidade dessa tarefa, favor notificar a gerente e a equipe do próximo turno)', 1),
(42, 7, 3, 'PEixe', 'final_dia', 'Frito', 1),
(43, 8, 3, 'Correr', 'final_dia', NULL, 1),
(44, 9, 5, 'TROCA O OLEO', 'final_dia', 'PFV', 1),
(57, 22, 5, 'Tirar os lixos', 'final_dia', NULL, 1),
(58, 23, 3, 'oi', 'final_dia', NULL, 1),
(59, 23, 3, 'oi', 'final_dia', NULL, 1),
(60, 24, 5, 'vai limpar', 'final_dia', NULL, 1),
(61, 26, 3, 'sdadsad', 'final_dia', NULL, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `checklist_task_done`
--

CREATE TABLE `checklist_task_done` (
  `id` bigint(20) NOT NULL,
  `checklist_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `period_key` varchar(16) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `was_late` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `checklist_task_done`
--

INSERT INTO `checklist_task_done` (`id`, `checklist_id`, `task_id`, `user_id`, `company_id`, `period_key`, `completed_at`, `was_late`) VALUES
(1, 1, 2, 3, 1, '2025-10-08', '2025-10-07 23:09:29', 0),
(3, 1, 2, 3, 1, '2025-10-07', '2025-10-07 23:18:54', 1),
(4, 2, 4, 3, 1, '2025-W40', '2025-10-07 23:21:11', 1),
(5, 2, 4, 3, 1, '2025-W41', '2025-10-07 23:21:15', 0),
(6, 2, 3, 3, 1, '2025-W41', '2025-10-07 23:21:39', 0),
(7, 1, 1, 3, 1, '2025-10-08', '2025-10-07 23:25:07', 0),
(8, 2, 3, 3, 1, '2025-W40', '2025-10-07 23:25:16', 1),
(9, 1, 1, 3, 1, '2025-10-07', '2025-10-07 23:25:27', 1),
(10, 1, 2, 3, 1, '2025-10-09', '2025-10-09 13:20:40', 0),
(11, 1, 1, 3, 1, '2025-10-09', '2025-10-09 13:20:42', 0),
(12, 1, 2, 3, 1, '2025-10-15', '2025-10-15 03:08:11', 0),
(15, 1, 1, 3, 1, '2025-10-15', '2025-10-15 03:15:40', 0),
(16, 1, 2, 3, 1, '2025-10-14', '2025-10-15 03:15:45', 1),
(17, 1, 1, 3, 1, '2025-10-14', '2025-10-15 03:15:46', 1),
(20, 3, 6, 3, 8, '2025-10-16', '2025-10-15 23:54:12', 0),
(21, 3, 7, 3, 8, '2025-10-16', '2025-10-15 23:54:15', 0),
(24, 4, 19, 3, 5, '2025-11-03', '2025-11-03 16:46:23', 0),
(25, 1, 2, 3, 1, '2025-11-06', '2025-11-06 15:59:48', 0),
(27, 2, 3, 3, 1, '2025-W45', '2025-11-09 17:28:59', 0),
(30, 2, 4, 3, 1, '2025-W47', '2025-11-21 14:17:29', 0),
(31, 1, 2, 3, 1, '2025-12-09', '2025-12-09 16:54:33', 0),
(32, 2, 4, 3, 1, '2025-W50', '2025-12-09 16:54:44', 0),
(34, 1, 2, 3, 1, '2025-12-29', '2025-12-29 20:19:34', 0),
(35, 1, 1, 3, 1, '2025-12-29', '2025-12-29 20:19:35', 0),
(36, 8, 43, 3, 7, '2025-12-30', '2025-12-29 23:32:24', 0),
(39, 24, 60, 61, 10, '2025-12-31', '2025-12-31 00:05:44', 0),
(40, 26, 61, 60, 10, '2025-12-31', '2025-12-31 01:09:15', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `name` varchar(160) NOT NULL,
  `trade_name` varchar(160) NOT NULL,
  `document` varchar(160) NOT NULL,
  `logo_url` varchar(160) NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `companies`
--

INSERT INTO `companies` (`id`, `name`, `trade_name`, `document`, `logo_url`, `is_active`, `created_at`) VALUES
(1, 'Empresa Alpha', 'Empresa Alpha', '111', '', 1, '2025-10-07 20:19:38'),
(2, 'Empresa Beta', 'Empresa Beta', '222', '', 1, '2025-10-07 20:19:38'),
(5, 'Mindhub teste Ltda', 'Mindhub teste Ltda', '333', '', 1, '2025-10-08 00:07:14'),
(6, 'Retorne Tecnologia Ltda', 'Retorne', '98798798798798', 'https://sistema.trinks.com/hubfs/LP%20TRINKS%20DE%20VANTAGENS_retorne%20app_retorne%20app%20-%20icon.png', 1, '2025-10-08 00:10:03'),
(7, 'Arianna Helena Patisserie produção de bolos e doces Ltda', 'Beju&Magi', '38093432000167', 'www.bejuemagi.com', 1, '2025-10-15 00:09:33'),
(8, 'RM1 FOOD LTDA', 'MALICE PIZZARIA MEIER', '60667575000194', 'https://storage.googleapis.com/prod-cardapio-web/uploads/company/logo/26935/4da1d48d2ae7c478696656159880752_ufwbkk.jpg', 1, '2025-10-15 00:25:53'),
(9, 'Teste', 'teste', '28372983', '', 1, '2025-10-15 02:26:05'),
(10, 'Uburguer Comércio de Alimentos Ltda', 'Uburguer', '43959955000183', '', 1, '2025-10-15 03:15:39'),
(11, 'Sulamita Gastronomia', 'Sulamita Gastronomia', '9999999999', '', 1, '2025-10-24 14:13:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `feedback_tickets`
--

CREATE TABLE `feedback_tickets` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sentiment_key` varchar(20) NOT NULL,
  `sentiment_score` tinyint(4) NOT NULL,
  `category` varchar(32) NOT NULL,
  `subject` varchar(160) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('aberto','em_andamento','concluido') NOT NULL DEFAULT 'aberto',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `feedback_tickets`
--

INSERT INTO `feedback_tickets` (`id`, `company_id`, `user_id`, `sentiment_key`, `sentiment_score`, `category`, `subject`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'estressado', 1, 'ocorrencia', NULL, 'Assunto: Fui mal tratado\n\nO Zé me ofendeu. falou besteira pra todo mundo e depois veio me ofender no privado.', 'em_andamento', '2025-10-07 23:39:06', '2025-10-07 23:39:17'),
(2, 1, 3, 'estressado', 1, 'ocorrencia', NULL, 'Assunto: Fulano roubou minha marmita\n\nnao eh a 1 e nem a seghunda vez que isso acontece! preciso que me ajudem', 'concluido', '2025-10-09 13:23:50', '2025-10-09 13:24:22'),
(3, 5, 3, 'bem', 4, 'infra_recursos', NULL, 'Assunto: Adicionar cargo ASG\n\nGostaria que adicionasse o cargo de ASG para poder cadastrar corretamente os funcionários', 'aberto', '2025-10-15 11:17:30', '2025-10-15 11:17:30'),
(4, 5, 3, 'bem', 4, 'melhoria_processo', NULL, 'Assunto: Poder editar o cadastro dos colaboradores\n\nPoder editar os colaboradores economiza tempo e deixa o programa preparado para mudanças ou intercorrências.', 'aberto', '2025-10-15 11:19:05', '2025-10-15 11:19:05'),
(5, 10, 61, 'sobrecarregado', 2, 'feedback_geral', NULL, 'Assunto: oiii\n\nto triste spider', 'concluido', '2025-12-31 00:28:27', '2025-12-31 00:30:21');

-- --------------------------------------------------------

--
-- Estrutura para tabela `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'Analista'),
(10, 'Atendente de Delivery'),
(11, 'Auxiliar de Cozinha'),
(9, 'Chapeiro(a)'),
(2, 'Coordenador'),
(8, 'Cozinheiro(a)'),
(7, 'Garçom/Garçonete'),
(12, 'Gerente de Loja'),
(3, 'Instrutor');

-- --------------------------------------------------------

--
-- Estrutura para tabela `role_training`
--

CREATE TABLE `role_training` (
  `role_id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `role_training`
--

INSERT INTO `role_training` (`role_id`, `training_id`) VALUES
(1, 7),
(1, 9),
(2, 7),
(2, 9),
(3, 9),
(7, 1),
(7, 2),
(7, 5),
(7, 7),
(7, 9),
(8, 1),
(8, 3),
(8, 6),
(8, 7),
(8, 9),
(9, 1),
(9, 4),
(9, 7),
(9, 9),
(10, 1),
(10, 2),
(10, 5),
(10, 7),
(10, 9),
(11, 1),
(11, 3),
(11, 4),
(11, 6),
(11, 7),
(11, 9),
(12, 1),
(12, 2),
(12, 3),
(12, 4),
(12, 5),
(12, 6),
(12, 7),
(12, 9);

-- --------------------------------------------------------

--
-- Estrutura para tabela `trainings`
--

CREATE TABLE `trainings` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `objective` text NOT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `reward_image` varchar(255) DEFAULT NULL,
  `difficulty` enum('Iniciante','Intermediário','Avançado') DEFAULT 'Iniciante',
  `estimated_minutes` int(11) DEFAULT 0,
  `tags` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `trainings`
--

INSERT INTO `trainings` (`id`, `company_id`, `title`, `objective`, `description`, `cover_image`, `reward_image`, `difficulty`, `estimated_minutes`, `tags`, `is_active`, `created_at`) VALUES
(1, 1, 'Boas Práticas de Higiene & Manipulação', 'Padronizar higiene pessoal, manipulação segura e sanitização de superfícies.', 'Conteúdo prático: EPI, lavagem de mãos, contaminação cruzada, temperatura segura e limpeza.', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 'https://static.vecteezy.com/system/resources/previews/027/291/500/non_2x/3d-rendered-medal-reward-rating-rank-verified-quality-badge-icon-png.png', 'Iniciante', 45, 'Higiene, Segurança, Qualidade', 1, '2025-10-07 21:33:56'),
(2, 1, 'Atendimento de Salão: Jeito Brasileiro', 'Elevar a experiência do cliente: acolhimento, timing e upsell respeitoso.', 'Recepção, apresentação do cardápio, sugestões (PFs, feijoada, moqueca) e objeções.', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 'https://thumbs.dreamstime.com/b/star-medal-d-icon-achievement-rewards-digital-trophy-symbol-games-competitions-white-background-star-medal-d-360430545.jpg', 'Intermediário', 40, 'Atendimento, Vendas, Experiência', 1, '2025-10-07 21:33:56'),
(3, 1, 'Cozinha Brasileira: Execução Consistente', 'Padronizar preparo de PF, feijoada, farofa, arroz/feijão e grelhados.', 'Fichas técnicas, rendimento, ponto e emplatamento rápido.', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 'https://peakeen.com/wp-content/uploads/2025/03/chef-medal5.webp', 'Intermediário', 60, 'Cozinha, Padronização, Qualidade', 1, '2025-10-07 21:33:56'),
(4, 1, 'Chapa & Fritadeira: Segurança e Produtividade', 'Operar com segurança, velocidade e padrão de cocção.', 'Setup da estação, ponto, troca de óleo, limpeza e checklists.', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 'https://s.alicdn.com/@sc04/kf/H0d011630b2f645aab849c2da2adeb6b0i.jpg', 'Iniciante', 35, 'Chapa, Fritadeira, Segurança', 1, '2025-10-07 21:33:56'),
(5, 1, 'Delivery & Embalagem: Padrão de Qualidade', 'Garantir que o pedido chegue quente, íntegro e apresentável.', 'Picking, conferência, selagem, rotas e NPS.', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 'https://s.alicdn.com/@sc04/kf/H253f389adca64421a131573659fa4738G.jpg', 'Intermediário', 30, 'Delivery, Embalagem, Experiência', 1, '2025-10-07 21:33:56'),
(6, 1, 'Mise en Place & Gestão de Estoque', 'Organizar produção e insumos para reduzir perdas e atrasos.', 'Planejamento do dia, pré-preparo, PVPS, inventário e comunicação cozinha-salão.', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 'https://s.alicdn.com/@sc04/kf/Hbf81d0160fd44ba58ff1f5d4a08adfdb2.jpg', 'Avançado', 50, 'Mise en Place, Estoque, Redução de Perdas', 1, '2025-10-07 21:33:56'),
(7, 1, 'Como limpar a chapa', 'Aprenda o processo correto para manter a chapa higienizada e limpa', 'Aprenda o processo correto para manter a chapa higienizada e limpaAprenda o processo correto para manter a chapa higienizada e limpa', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 'https://www.trophiesplusmedals.co.uk/media/catalog/product/a/9/a902a_2.jpg?quality=80&bg-color=255,255,255&fit=bounds&height=650&width=572&canvas=572:650', 'Intermediário', 45, 'Higiene', 1, '2025-10-07 21:48:52'),
(9, 10, 'Treinamento de incendio', 'Garantir que vcs se virem num incendio', 'descriçao', '', '', 'Iniciante', 10, 'Segurança', 1, '2025-12-31 00:14:11');

-- --------------------------------------------------------

--
-- Estrutura para tabela `training_videos`
--

CREATE TABLE `training_videos` (
  `id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `summary` text DEFAULT NULL,
  `video_provider` enum('cloudflare','mux','vimeo','youtube','url') NOT NULL DEFAULT 'youtube',
  `video_ref` varchar(255) NOT NULL,
  `thumb_image` varchar(255) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT 0,
  `order_index` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `training_videos`
--

INSERT INTO `training_videos` (`id`, `training_id`, `title`, `summary`, `video_provider`, `video_ref`, `thumb_image`, `duration_seconds`, `order_index`, `is_active`) VALUES
(1, 1, 'EP1 • Higiene Pessoal Essencial', 'Uniforme, EPI e lavagem correta de mãos.', 'youtube', 'https://www.youtube.com/watch?v=KZDY51vOn2g', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 420, 1, 1),
(2, 1, 'EP2 • Contaminação Cruzada', 'Separação de áreas, tábuas e fluxo.', 'youtube', 'https://www.youtube.com/watch?v=v2cR2cajXUA', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 480, 2, 1),
(3, 1, 'EP3 • Temperatura & Armazenamento', 'Zona de perigo e validade.', 'youtube', 'https://www.youtube.com/watch?v=Q3W0Xw_iG9s', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 540, 3, 1),
(4, 2, 'EP1 • Recepção & Primeira Impressão', 'Saudação, tempo de espera e acomodação.', 'youtube', 'https://www.youtube.com/watch?v=EQd-8mYk9jU', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 360, 1, 1),
(5, 2, 'EP2 • Cardápio e Sugestões', 'Storytelling dos pratos e bebidas.', 'youtube', 'https://www.youtube.com/watch?v=8c0VbOb8w2k', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 420, 2, 1),
(6, 2, 'EP3 • Upsell sem Forçar', 'Combos, sobremesas e cafés.', 'youtube', 'https://www.youtube.com/watch?v=9bZkp7q19f0', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 300, 3, 1),
(7, 3, 'EP1 • Mise en Place do PF', 'Pré-preparo, cortes, porcionamento.', 'youtube', 'https://www.youtube.com/watch?v=1APwq1df6Mw', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 540, 1, 1),
(8, 3, 'EP2 • Feijão, Arroz e Farofa', 'Textura, sabor, conservação.', 'youtube', 'https://www.youtube.com/watch?v=G1IbRujko-A', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 600, 2, 1),
(9, 3, 'EP3 • Feijoada Padronizada', 'Cortes, dessalgue, cocção e finalização.', 'youtube', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 540, 3, 1),
(10, 4, 'EP1 • Setup & Padrão de Chapa', 'Temperatura, organização e sequência.', 'youtube', 'https://www.youtube.com/watch?v=Zi_XLOBDo_Y', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 360, 1, 1),
(11, 4, 'EP2 • Fritadeira com Segurança', 'Troca de óleo, cesta, ponto e segurança.', 'youtube', 'https://www.youtube.com/watch?v=2Vv-BfVoq4g', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 420, 2, 1),
(12, 5, 'EP1 • Picking & Conferência', 'Checklist de itens e lacres.', 'youtube', 'https://www.youtube.com/watch?v=fJ9rUzIMcZQ', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 300, 1, 1),
(13, 5, 'EP2 • Embalagem Inteligente', 'Evitar vazamentos e manter temperatura/textura.', 'youtube', 'https://www.youtube.com/watch?v=3JZ_D3ELwOQ', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 320, 2, 1),
(14, 6, 'EP1 • Planejamento & PVPS', 'Organização de câmaras e validade.', 'youtube', 'https://www.youtube.com/watch?v=LsoLEjrDogU', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 420, 1, 1),
(15, 6, 'EP2 • Pré-preparo & Rendimento', 'Porcionamento, etiquetagem e controle de perdas.', 'youtube', 'https://www.youtube.com/watch?v=hT_nvWreIhg', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 480, 2, 1),
(16, 7, 'Limpar Chapa', 'Nessa aula vc aprenderá tudo', 'youtube', 'QOtNkBkj_UI', 'https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg', 60, 1, 1),
(18, 9, 'Educação no incendio', 'Vejam', 'youtube', 'https://www.youtube.com/watch?v=kPB0Xy4vBTk', 'https://static.preparaenem.com/conteudo_legenda/ad5b4cbf93f07bf0665ee5db05f950be.jpg', 100, 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `type` enum('Admin','Colaborador','Gestor') NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `birthday` date DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `type`, `avatar_url`, `created_at`, `birthday`, `phone`, `status`) VALUES
(1, 'Admin Master', 'admin@mindhub.local', '$2y$10$FqwnhK9gP0aXUo5m0C9q/uQ9sB3Hq0nW7C3VtG6J1oVxjA1F7vFBS', 'Admin', NULL, '2025-10-07 20:19:38', NULL, NULL, 1),
(3, 'Admin', 'admin@mindhub.com', '$2y$10$056KuEfbaKuigBaKJMObGOta2ZKYzi5CoASAYF9B/e732hYT0HuSa', 'Admin', NULL, '2025-10-07 20:35:04', NULL, NULL, 1),
(4, 'Rafael Gestor', 'rmelobarbosa@gmail.com', '$2y$10$A/oc2gKDA//gVeiuF83sJu3rOjs0FT7Jk6Y1GUO6NIlhUTJgxdVIO', 'Colaborador', 'https://img.icons8.com/?size=1200&id=23347&format=png', '2025-10-07 22:07:33', '1989-02-27', '21997868300', 1),
(6, 'Rafael Barbosa', 'rafael@gmail.com', '$2y$10$X.q9wxlCdvlQ4q9E3Df5tudmPYZojSMkgHsukC6GbwyE/ZF2D4KSy', 'Colaborador', 'https://img.icons8.com/?size=1200&id=23347&format=png', '2025-10-07 22:08:16', '1989-02-27', '21997868300', 1),
(7, 'Rafael Barbosa', 'teste@mindhub.com', '$2y$10$/BVcHEGL6VjK1/z6lVujE.KhurfspVTdWaSHYf7JkmozQa.T5KLbW', 'Colaborador', 'https://img.icons8.com/?size=1200&id=23347&format=png', '2025-10-08 03:12:24', '1989-11-27', '21997868300', 1),
(8, 'ABIGAIL BRASIL', 'abigail@mindhub.com', '$2y$10$B0SpwDO3WRaTtWx9skxfS.0ZeKnSrVMXCn7T1Bf1Tq6rSwmjMGhs2', 'Colaborador', NULL, '2025-10-14 21:11:49', NULL, NULL, 1),
(9, 'Ailton de Jesus', 'Ailton@mindhub.com', '$2y$10$sTfVFOR.CXGiIvsMbIKiFueipK3792wwTmA36E59ZrzNS2ZlmyQ2a', 'Colaborador', NULL, '2025-10-14 21:13:22', NULL, NULL, 1),
(10, 'Alexandre Barbosa do Nascimento', 'Alexandre@mindhub.com', '$2y$10$T93AeLD9FZOfmvPN.VhwT.2Tn37TBGk.UWRG6QNpmz7MTzZyM9pfm', 'Colaborador', NULL, '2025-10-14 21:14:06', NULL, NULL, 1),
(11, 'Amanda Lemos da Silva', 'Amanda@mindhub.com', '$2y$10$1w82nrLM5PzYwGM/8IhEGu.VHS8LKyXQFDItRUrw4WK2DJ8fSZWcC', 'Colaborador', NULL, '2025-10-14 21:15:25', NULL, NULL, 1),
(12, 'Bruna Felix', 'bruna@espetobrasileiro.com.br', '$2y$10$TQd38PcK/3WvgE0vwoGjU.f4KbY3bG8dm/QMOy9202/EVxjlI9R2G', 'Colaborador', NULL, '2025-10-15 03:04:54', NULL, NULL, 1),
(13, 'Carol', 'carolssouza456@gmail.com', '$2y$10$acLsGRMzuAXCBHEOHnM5/eyQ1ZgSlsRYmlnhweQY.91yL/W3wAYpK', 'Colaborador', NULL, '2025-10-15 03:46:27', '1997-10-17', '021972805646', 1),
(14, 'Celina Lidia La Valle', 'Celina@mindhub.com', '$2y$10$qMJ/zjrVbPWdDLRi404hjeoPGvNFrBAQomqt2g/yor7djehdLy2oK', 'Colaborador', NULL, '2025-10-15 04:57:06', NULL, NULL, 1),
(15, 'Kelly Carvalho', 'kellycarsilva15@gmail.com', '$2y$10$epvKJIMxp0TIWoSH75t/PuLzm25NPrXqf4TDcyZ17uJDBWCKM/yj.', 'Admin', NULL, '2025-10-15 06:21:21', '1994-12-10', '11951478947', 1),
(16, 'Williane Silva', 'Wilianesilva455@gmail.com', '$2y$10$Hfl9yunaSnN1N0EvyD8VROvWM5I0EBXs9Pt4Id3vZPHC26WTQiHKO', 'Colaborador', NULL, '2025-10-15 06:49:43', NULL, NULL, 1),
(17, 'CLAUDIA MARIA SAYAO DUTRA', 'Claudia@mindhub.com', '$2y$10$ZBLGqWq4p.t950WamqXP6OGvv2w9Cjjm7Edxne/52yWx3yIyBEHnW', 'Colaborador', NULL, '2025-10-15 14:09:39', NULL, NULL, 1),
(18, 'Evandro Fernandes Do Nascimento', 'Evandro@mindhub.com', '$2y$10$e80.NSKsVhI5hampHM4t1euJyg39.lSW0ZCO4Jg3pxqrjO7k/HvpS', 'Colaborador', NULL, '2025-10-15 14:10:34', NULL, NULL, 1),
(19, 'JESSICA LEN REIFFE CARDOSO', 'Jessica@mindhub.com', '$2y$10$6ZfQG3OSHoDKpgqgqPeD5eQkmlSU7fur60dIS.JxKcd6kOiouqLUW', 'Colaborador', NULL, '2025-10-15 14:12:33', NULL, NULL, 1),
(20, 'JESSICA LINDALVA RAMOS FERREIRA', 'Jessical@mindhub.com', '$2y$10$1QNAyGoD2..MoasXlN5Hd.OWZuKRMfLWz5StLBaliv/pZ768IkeP6', 'Colaborador', NULL, '2025-10-15 14:13:51', NULL, NULL, 1),
(21, 'LAYSA REIFFE DIAS', 'Laysa@mindhub.com', '$2y$10$fFGlKfV7u7q2PZ0axm.syO6KvAR54jgebYwL1QrHlS2rXVeF6i.Nq', 'Colaborador', NULL, '2025-10-15 14:14:29', NULL, NULL, 1),
(22, 'MAURICIO NEVES DOS SANTOS', 'Mauricio@mindhub.com', '$2y$10$QoQeleGH1CmjVI8wkeXgNOmcrWNguFexnJtSLt8NvLTyU5IGW8oTm', 'Colaborador', NULL, '2025-10-15 14:14:58', NULL, NULL, 1),
(23, 'VIRGINIA DE MORAES COSTA', 'Virginia@mindhub.com', '$2y$10$R9wa1kAfI4op0acDlE/3d.Xp0IocPHXJ1ugHeyXVNqruuGPh/YX8a', 'Colaborador', NULL, '2025-10-15 14:15:49', NULL, NULL, 1),
(24, 'Williane da Silva', 'willianesilva455@gmail.com', '$2y$10$W4bpEk6gu2IMjd.E3sN3ruRbdUOqlzZTh0Wr6nuJpQOjs4iKtHn..', 'Colaborador', NULL, '2025-10-15 23:58:31', NULL, NULL, 1),
(25, 'Admin teste', 'admin@teste.com', '$2y$10$3KYuiWAjrxNTNKoesove5.V0co06HN9/ieLubEenkBzEdSLcPTRfG', 'Admin', NULL, '2025-10-16 04:48:05', '1989-02-27', NULL, 1),
(26, 'Thais Vasconcelos', 'sulamitagastronomia@gmail.com', '$2y$10$n8fBdlUMiNy4P8caPFvAEutR.rbAU/t7cjV/bOTrESuiGRzDWYWtG', 'Admin', NULL, '2025-10-24 17:14:46', '1990-01-01', '219999999999', 1),
(27, 'Jorgiane', 'jo@sulamita.com.br', '$2y$10$K7WPDeb7UgX8mo5sNBHDP.O4YHRnvFP8DEb4GRjSs5/Ef47dor3kW', 'Colaborador', NULL, '2025-11-04 04:57:10', '2000-01-01', '21977263406', 1),
(28, 'Jane', 'jane@sulamita.com.br', '$2y$10$VwzXOMFyOX6PhirUTBeYieeWmkyYy/QqhuzQv0Gi1j4I8d.ihQ7Sy', 'Colaborador', NULL, '2025-11-04 04:58:13', '2000-01-01', '21967000906', 1),
(29, 'Marcia', 'marcia@sulamita.com.br', '$2y$10$IkQh5ZiBQXQVqtEQULAA6ueEkDpGnMid055NLyvyYAzDV5Eo41K7K', 'Colaborador', NULL, '2025-11-04 04:58:47', '2000-01-01', '21991980338', 1),
(30, 'Samara', 'samara@sulamita.com.br', '$2y$10$2Z3UxHYomNTK6F5mdCOgM.dNEr.bMv87rjEVMIDiq/j7nzMGZyU4S', 'Colaborador', NULL, '2025-11-04 04:59:45', '2000-01-01', '21991473176', 1),
(31, 'Marcela', 'marcela@sulamita.com.br', '$2y$10$4FNruxH6/KU28rG6CWdleu2qjq/C2j2/vL/bZMmdiF6bNpjPnsHHC', 'Colaborador', NULL, '2025-11-04 05:01:13', NULL, '21981220387', 1),
(32, 'Thais Felix', 'thais.felix@sulamita.com.br', '$2y$10$e5ZPJCCd6ZJHnLMaCZTEJ.czbPuRTg2NS.4QhtW.OWc3c7AH0gVQu', 'Colaborador', NULL, '2025-11-04 05:02:05', '2000-01-01', '21964133986', 1),
(33, 'Maylla', 'maylla@gmail.com', '$2y$10$k2ztJUdVz6Crteow/gxcPuHk6GwmjJXQqEJC.4G.Gf76NoG0DhVwS', 'Colaborador', NULL, '2025-11-04 05:02:37', '2000-01-01', '21996143105', 1),
(34, 'Eduardo', 'oeduardoluna@gmail.com', '$2y$10$6cFYVWqKXwHZGsyzhHQ2UuK10AunCmhNIsU9jU.FheTv3b/Fsh9gK', 'Colaborador', NULL, '2025-12-30 00:42:57', '2006-03-07', '21972871523', 1),
(60, 'Peter Parker', 'spider@email.com', '$2y$10$jz8wRialmajpdH1ANvsRH.cYktwfHXgNwXZtOZQuW2ggYhtVIfoHq', 'Gestor', 'https://i.pinimg.com/1200x/5c/55/5b/5c555bb917c4f27c0e8a750b8dd15451.jpg', '2025-12-30 22:45:21', '1962-07-10', '219765232764', 1),
(61, 'Zé da Chapa', 'ze@email.com', '$2y$10$qCnN2V3./MVxNcoPBWuvROtHwf.Qkb5XDJlppL4wnRKLWmXBBSoRy', 'Colaborador', 'https://media.istockphoto.com/id/172241948/pt/foto/homem-feio.jpg?s=2048x2048&w=is&k=20&c=sOWuA2hs0pUBT_qzRDLnwxGTwYLIGPZ23fnuCKtW_B0=', '2025-12-30 22:46:43', '1955-03-07', '21976436723', 1),
(64, 'Juliana Pires', 'ju.pi@email.com', '$2y$10$DRTYLUgVlrIAXKlp/cSWf.KEJZsTD86DzMe7BFxgY3sGCeJakNo86', 'Colaborador', NULL, '2025-12-31 03:57:59', '7321-09-12', NULL, 1),
(67, 'Analista teste', 'analista@email.com', '$2y$10$YqcOHzHeCEeXk30/CzZaJ.KU9l8P3SPX4JYeDKFFB1tEIBJ90WtCy', 'Colaborador', NULL, '2025-12-31 04:09:38', '5452-03-12', NULL, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_company`
--

CREATE TABLE `user_company` (
  `user_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `user_company`
--

INSERT INTO `user_company` (`user_id`, `company_id`) VALUES
(1, 1),
(3, 1),
(3, 2),
(3, 5),
(3, 6),
(3, 7),
(3, 8),
(3, 9),
(3, 10),
(3, 11),
(4, 1),
(4, 2),
(6, 1),
(6, 2),
(7, 1),
(7, 2),
(8, 5),
(9, 5),
(10, 5),
(11, 5),
(12, 2),
(13, 8),
(14, 5),
(15, 10),
(16, 10),
(17, 5),
(18, 5),
(19, 5),
(20, 5),
(21, 5),
(22, 5),
(23, 5),
(24, 10),
(25, 9),
(26, 11),
(27, 11),
(28, 11),
(29, 11),
(30, 11),
(31, 11),
(32, 11),
(33, 11),
(34, 1),
(60, 10),
(61, 10),
(64, 10),
(67, 10);

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_meta`
--

CREATE TABLE `user_meta` (
  `user_id` int(11) NOT NULL,
  `meta_key` varchar(64) NOT NULL,
  `meta_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `user_meta`
--

INSERT INTO `user_meta` (`user_id`, `meta_key`, `meta_value`) VALUES
(4, 'notes', 'Folguista');

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_role`
--

CREATE TABLE `user_role` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `user_role`
--

INSERT INTO `user_role` (`user_id`, `role_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(3, 1),
(3, 2),
(3, 3),
(3, 7),
(3, 8),
(3, 9),
(3, 10),
(3, 11),
(3, 12),
(4, 1),
(4, 2),
(4, 7),
(4, 8),
(4, 9),
(4, 10),
(4, 11),
(4, 12),
(6, 1),
(6, 2),
(6, 7),
(6, 8),
(6, 9),
(6, 10),
(6, 11),
(6, 12),
(7, 7),
(8, 11),
(9, 8),
(10, 7),
(11, 7),
(12, 12),
(13, 10),
(14, 3),
(15, 12),
(16, 11),
(17, 12),
(18, 8),
(19, 7),
(20, 11),
(21, 11),
(22, 12),
(23, 11),
(24, 11),
(25, 1),
(25, 12),
(26, 1),
(26, 2),
(26, 3),
(26, 7),
(26, 8),
(26, 9),
(26, 10),
(26, 11),
(26, 12),
(27, 11),
(28, 11),
(30, 1),
(31, 8),
(32, 7),
(32, 10),
(33, 7),
(33, 10),
(34, 9),
(60, 1),
(60, 2),
(60, 3),
(60, 7),
(60, 8),
(60, 9),
(60, 10),
(60, 11),
(60, 12),
(61, 8),
(61, 9),
(64, 11),
(67, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_training_reward`
--

CREATE TABLE `user_training_reward` (
  `user_id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `reward_image` varchar(255) NOT NULL,
  `awarded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `user_training_reward`
--

INSERT INTO `user_training_reward` (`user_id`, `training_id`, `reward_image`, `awarded_at`) VALUES
(3, 1, '/assets/img/rewards/higiene_badge.png', '2025-10-07 21:55:46'),
(3, 2, '/assets/img/rewards/salao_badge.png', '2025-10-07 21:59:54'),
(3, 7, 'https://static.vecteezy.com/system/resources/previews/027/291/500/non_2x/3d-rendered-medal-reward-rating-rank-verified-quality-badge-icon-png.png', '2025-10-07 21:49:15'),
(3, 9, '/assets/img/reward_default.png', '2025-12-31 00:15:29');

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_video_progress`
--

CREATE TABLE `user_video_progress` (
  `user_id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `completed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `user_video_progress`
--

INSERT INTO `user_video_progress` (`user_id`, `video_id`, `completed_at`) VALUES
(3, 1, '2025-10-07 21:39:33'),
(3, 2, '2025-10-07 21:39:51'),
(3, 3, '2025-10-07 21:55:45'),
(3, 4, '2025-10-07 21:59:29'),
(3, 5, '2025-10-07 21:59:43'),
(3, 6, '2025-10-07 21:59:54'),
(3, 7, '2025-10-07 21:40:40'),
(3, 16, '2025-10-07 21:49:15'),
(3, 18, '2025-12-31 00:15:29');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `checklists`
--
ALTER TABLE `checklists`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `checklist_role`
--
ALTER TABLE `checklist_role`
  ADD PRIMARY KEY (`checklist_id`,`role_id`),
  ADD KEY `fk_cr_ro` (`role_id`);

--
-- Índices de tabela `checklist_tasks`
--
ALTER TABLE `checklist_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `checklist_id` (`checklist_id`);

--
-- Índices de tabela `checklist_task_done`
--
ALTER TABLE `checklist_task_done`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_task_period` (`task_id`,`company_id`,`period_key`),
  ADD KEY `checklist_id` (`checklist_id`,`period_key`);

--
-- Índices de tabela `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `feedback_tickets`
--
ALTER TABLE `feedback_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company_status` (`company_id`,`status`,`created_at`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`);

--
-- Índices de tabela `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Índices de tabela `role_training`
--
ALTER TABLE `role_training`
  ADD PRIMARY KEY (`role_id`,`training_id`),
  ADD KEY `fk_rt_training` (`training_id`);

--
-- Índices de tabela `trainings`
--
ALTER TABLE `trainings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_trainings_company_title` (`company_id`,`title`(150));

--
-- Índices de tabela `training_videos`
--
ALTER TABLE `training_videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tv_order` (`training_id`,`order_index`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `user_company`
--
ALTER TABLE `user_company`
  ADD PRIMARY KEY (`user_id`,`company_id`),
  ADD KEY `fk_uc_company` (`company_id`);

--
-- Índices de tabela `user_meta`
--
ALTER TABLE `user_meta`
  ADD PRIMARY KEY (`user_id`,`meta_key`);

--
-- Índices de tabela `user_role`
--
ALTER TABLE `user_role`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `fk_ur_role` (`role_id`);

--
-- Índices de tabela `user_training_reward`
--
ALTER TABLE `user_training_reward`
  ADD PRIMARY KEY (`user_id`,`training_id`),
  ADD KEY `fk_utr_training` (`training_id`);

--
-- Índices de tabela `user_video_progress`
--
ALTER TABLE `user_video_progress`
  ADD PRIMARY KEY (`user_id`,`video_id`),
  ADD KEY `fk_uvp_video` (`video_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `checklists`
--
ALTER TABLE `checklists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `checklist_tasks`
--
ALTER TABLE `checklist_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT de tabela `checklist_task_done`
--
ALTER TABLE `checklist_task_done`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de tabela `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `feedback_tickets`
--
ALTER TABLE `feedback_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de tabela `trainings`
--
ALTER TABLE `trainings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `training_videos`
--
ALTER TABLE `training_videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `checklist_role`
--
ALTER TABLE `checklist_role`
  ADD CONSTRAINT `fk_cr_cl` FOREIGN KEY (`checklist_id`) REFERENCES `checklists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cr_ro` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `checklist_tasks`
--
ALTER TABLE `checklist_tasks`
  ADD CONSTRAINT `fk_ct_cl` FOREIGN KEY (`checklist_id`) REFERENCES `checklists` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `checklist_task_done`
--
ALTER TABLE `checklist_task_done`
  ADD CONSTRAINT `fk_done_task` FOREIGN KEY (`task_id`) REFERENCES `checklist_tasks` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `feedback_tickets`
--
ALTER TABLE `feedback_tickets`
  ADD CONSTRAINT `fk_fb_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fb_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `role_training`
--
ALTER TABLE `role_training`
  ADD CONSTRAINT `fk_rt_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rt_training` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `trainings`
--
ALTER TABLE `trainings`
  ADD CONSTRAINT `fk_train_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `training_videos`
--
ALTER TABLE `training_videos`
  ADD CONSTRAINT `fk_tv_training` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_company`
--
ALTER TABLE `user_company`
  ADD CONSTRAINT `fk_uc_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_meta`
--
ALTER TABLE `user_meta`
  ADD CONSTRAINT `user_meta_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_role`
--
ALTER TABLE `user_role`
  ADD CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_training_reward`
--
ALTER TABLE `user_training_reward`
  ADD CONSTRAINT `fk_utr_training` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_utr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_video_progress`
--
ALTER TABLE `user_video_progress`
  ADD CONSTRAINT `fk_uvp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uvp_video` FOREIGN KEY (`video_id`) REFERENCES `training_videos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
