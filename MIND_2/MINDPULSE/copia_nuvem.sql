-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: rhtrain.mysql.uhserver.com    Database: rhtrain
-- ------------------------------------------------------
-- Server version	5.6.22

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `checklist_role`
--

DROP TABLE IF EXISTS `checklist_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `checklist_role` (
  `checklist_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`checklist_id`,`role_id`),
  KEY `fk_cr_ro` (`role_id`),
  CONSTRAINT `fk_cr_cl` FOREIGN KEY (`checklist_id`) REFERENCES `checklists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cr_ro` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_role`
--

LOCK TABLES `checklist_role` WRITE;
/*!40000 ALTER TABLE `checklist_role` DISABLE KEYS */;
INSERT INTO `checklist_role` VALUES (1,1),(2,1),(1,2),(2,2),(8,2),(1,3),(2,3),(1,7),(2,7),(4,7),(5,7),(6,7),(1,8),(2,8),(1,9),(2,9),(1,10),(2,10),(3,10),(1,11),(2,11),(1,12),(2,12),(4,12);
/*!40000 ALTER TABLE `checklist_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklist_task_done`
--

DROP TABLE IF EXISTS `checklist_task_done`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `checklist_task_done` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `checklist_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `period_key` varchar(16) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `was_late` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_task_period` (`task_id`,`company_id`,`period_key`),
  KEY `checklist_id` (`checklist_id`,`period_key`),
  CONSTRAINT `fk_done_task` FOREIGN KEY (`task_id`) REFERENCES `checklist_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_task_done`
--

LOCK TABLES `checklist_task_done` WRITE;
/*!40000 ALTER TABLE `checklist_task_done` DISABLE KEYS */;
INSERT INTO `checklist_task_done` VALUES (1,1,2,3,1,'2025-10-08','2025-10-07 23:09:29',0),(3,1,2,3,1,'2025-10-07','2025-10-07 23:18:54',1),(4,2,4,3,1,'2025-W40','2025-10-07 23:21:11',1),(5,2,4,3,1,'2025-W41','2025-10-07 23:21:15',0),(6,2,3,3,1,'2025-W41','2025-10-07 23:21:39',0),(7,1,1,3,1,'2025-10-08','2025-10-07 23:25:07',0),(8,2,3,3,1,'2025-W40','2025-10-07 23:25:16',1),(9,1,1,3,1,'2025-10-07','2025-10-07 23:25:27',1),(10,1,2,3,1,'2025-10-09','2025-10-09 13:20:40',0),(11,1,1,3,1,'2025-10-09','2025-10-09 13:20:42',0),(12,1,2,3,1,'2025-10-15','2025-10-15 03:08:11',0),(15,1,1,3,1,'2025-10-15','2025-10-15 03:15:40',0),(16,1,2,3,1,'2025-10-14','2025-10-15 03:15:45',1),(17,1,1,3,1,'2025-10-14','2025-10-15 03:15:46',1),(20,3,6,3,8,'2025-10-16','2025-10-15 23:54:12',0),(21,3,7,3,8,'2025-10-16','2025-10-15 23:54:15',0),(24,4,19,3,5,'2025-11-03','2025-11-03 16:46:23',0),(25,1,2,3,1,'2025-11-06','2025-11-06 15:59:48',0),(27,2,3,3,1,'2025-W45','2025-11-09 17:28:59',0),(30,2,4,3,1,'2025-W47','2025-11-21 14:17:29',0),(31,1,2,3,1,'2025-12-09','2025-12-09 16:54:33',0),(32,2,4,3,1,'2025-W50','2025-12-09 16:54:44',0),(34,1,2,3,1,'2025-12-29','2025-12-29 20:19:34',0),(35,1,1,3,1,'2025-12-29','2025-12-29 20:19:35',0),(36,8,43,3,7,'2025-12-30','2025-12-29 23:32:24',0);
/*!40000 ALTER TABLE `checklist_task_done` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklist_tasks`
--

DROP TABLE IF EXISTS `checklist_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `checklist_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `checklist_id` int(11) NOT NULL,
  `priority` tinyint(4) NOT NULL DEFAULT '3',
  `name` varchar(255) NOT NULL,
  `period` enum('inicio_dia','final_dia','inicio_semana','final_semana') NOT NULL DEFAULT 'final_dia',
  `notes` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `checklist_id` (`checklist_id`),
  CONSTRAINT `fk_ct_cl` FOREIGN KEY (`checklist_id`) REFERENCES `checklists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklist_tasks`
--

LOCK TABLES `checklist_tasks` WRITE;
/*!40000 ALTER TABLE `checklist_tasks` DISABLE KEYS */;
INSERT INTO `checklist_tasks` VALUES (1,1,5,'Verificar as etiquetas da geladeira','final_dia',NULL,1),(2,1,3,'Trocar as etiquetas da geladeira da praça quente','final_dia',NULL,1),(3,2,5,'Verificar as etiquetas da geladeira','inicio_semana','Realizar com cuidado essa parte e fazer o treinamento se preciso',1),(4,2,3,'Trocar as etiquetas da geladeira da praça quente','final_semana',NULL,1),(5,3,5,'Ligar o computador','inicio_dia',NULL,1),(6,3,3,'Olhar os freezer verificar se as bebidas estão geladas','inicio_dia','caso não esteja informar o gerente da empresa e colocar AS BEBIDAS em outro local possivel de gelar',1),(7,3,3,'Abrir o google chromo e abrir os sistema','inicio_dia',NULL,1),(8,4,5,'Verificar Funcionamento dos Equipamentos','inicio_dia','Verificar se houve alguma avaria ou dano aos equipamentos no turno anterior (Relatar ao superior / responsável o quanto antes de forma escrita)',1),(9,4,5,'Aferir Temperatura dos Freezers','inicio_dia','Registrar a temperatura de cada freezer na tabela de temperatura e assinar ao lado após isso',1),(10,4,5,'Verificar Se tem Broinha (Qualidade deve ser Verificada Junto)','inicio_dia','verificar a quantidade e qualidade das broinhas que são servidas junto do café',1),(11,4,4,'Fazer pedido de broinha para cozinha (se necessário)','final_dia','formalizar pedido a cozinha caso as broinhas não atinjam a qualidade ou quantidade aceitável para o serviço',1),(12,4,5,'Lavar louça do turno da noite (Não pode haver)','inicio_dia','Lavar louça que tenha sobrado do turno anterior e registrar ocorrência ao superior ou responsável',1),(13,4,3,'Limpar Vidro dos freezers do bar','inicio_dia','Utilizar álcool ou produto indicado para limpar a vitrine dos freezers',1),(14,4,4,'Verificar gelo do freezer','inicio_dia',NULL,1),(15,4,5,'Verificar limpeza da maquina de café','inicio_dia','Verificar a limpeza e o estado da maquina de café (Limpar e ou registrar ocorrência ao superior se ocorrer avaria ou mal uso)',1),(16,4,4,'Limpeza do bar (Chão, pia, balcão e escorredor)','inicio_dia','Fazer serviço de limpeza básico para iniciar o serviço',1),(17,4,3,'Verificar Validade do Chopp Claro','inicio_dia','Verificar e ou registrar a um superior ou responsável se houver risco de vencimento',1),(18,4,3,'Verificar Validade do Chopp Escuro','inicio_dia','Verificar e ou registrar a um superior ou responsável se houver risco de vencimento',1),(19,4,2,'Limpar Maquina de Gelo','inicio_dia','Limpeza periódica da maquina de gelo para evitar contaminação cruzada com insumos e produtos',1),(20,4,4,'Limpar Reservatório da Chopeira','inicio_dia','Verificar e limpar o reservatório da Chopeira para o início do serviço',1),(21,4,4,'Cortar Frutas e verificar a Qualidade','inicio_dia','Fazer mise en place do bar e verificar a qualidade dos insumos (Registrar ao superior ou responsável caso haja qualidade ruim)',1),(22,5,2,'Limpar Maquina de Gelo','inicio_dia','Limpeza periódica da maquina de gelo para evitar contaminação cruzada com insumos e produtos',1),(23,5,2,'Limpar Vidro dos freezers do bar','inicio_dia','Utilizar álcool ou produto indicado para limpar a vitrine dos freezers',1),(24,5,4,'Fazer pedido de broinha para cozinha (se necessário)','final_dia','formalizar pedido a cozinha caso as broinhas não atinjam a qualidade ou quantidade aceitável para o serviço',1),(25,5,4,'Verificar gelo do freezer','inicio_dia',NULL,1),(26,5,5,'Limpeza do bar (Chão, pia, balcão e escorredor)','inicio_dia','Fazer serviço de limpeza básico para iniciar o serviço',1),(27,5,5,'Limpar Reservatório da Chopeira','inicio_dia','Verificar e limpar o reservatório da Chopeira para o início do serviço',1),(28,5,4,'Cortar Frutas e verificar a Qualidade','inicio_dia','Fazer mise en place do bar e verificar a qualidade dos insumos (Registrar ao superior ou responsável caso haja qualidade ruim)',1),(29,5,5,'Verificar Funcionamento dos Equipamentos','inicio_dia','Verificar se houve alguma avaria ou dano aos equipamentos no turno anterior (Relatar ao superior / responsável o quanto antes de forma escrita)',1),(30,5,4,'Aferir Temperatura dos Freezers','inicio_dia','Registrar a temperatura de cada freezer na tabela de temperatura e assinar ao lado após isso',1),(31,5,4,'Verificar Se tem Broinha (Qualidade deve ser Verificada Junto)','inicio_dia','verificar a quantidade e qualidade das broinhas que são servidas junto do café',1),(32,5,5,'Lavar louça do turno da noite (Não pode haver)','inicio_dia','Lavar louça que tenha sobrado do turno anterior e registrar ocorrência ao superior ou responsável',1),(33,5,5,'Verificar limpeza da maquina de café','inicio_dia','Verificar a limpeza e o estado da maquina de café',1),(34,5,5,'Fazer a Limpeza da Cafeteira se necessário (Não pode Haver)','inicio_dia','Limpar e ou registrar ocorrência ao superior se ocorrer avaria ou mal uso',1),(35,6,3,'Cortar Frutas e verificar a Qualidade (Se Necessário)','final_dia','Fazer mise en place do bar e verificar a qualidade dos insumos (Registrar ao superior ou responsável caso haja qualidade ruim)',1),(36,6,4,'Verificar Se tem Broinha (Qualidade deve ser Verificada Junto)','final_dia','verificar a quantidade e qualidade das broinhas que são servidas junto do café',1),(37,6,3,'Fazer pedido de broinha para cozinha (se necessário)','final_dia','formalizar pedido a cozinha caso as broinhas não atinjam a qualidade ou quantidade aceitável para o serviço',1),(38,6,5,'Limpeza do bar (pia, balcão e escorredor)','final_dia','Fazer serviço de limpeza básico',1),(39,6,5,'Verificar limpeza da maquina de café','final_dia','Verificar a limpeza e o estado da maquina de café',1),(40,6,5,'Fazer a limpeza da maquina de café','final_dia','Retirar o reservatório de água, retirar o reservatório de capsulas e limpar tudo para o próximo turno.',1),(41,6,5,'Lavar louça','final_dia','Lavar a Louça e deixar tudo limpo para o próximo turno. (Caso haja impossibilidade dessa tarefa, favor notificar a gerente e a equipe do próximo turno)',1),(42,7,3,'PEixe','final_dia','Frito',1),(43,8,3,'Correr','final_dia',NULL,1);
/*!40000 ALTER TABLE `checklist_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checklists`
--

DROP TABLE IF EXISTS `checklists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `checklists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `title` varchar(160) NOT NULL,
  `description` text,
  `frequency` enum('daily','weekly','biweekly','monthly') NOT NULL DEFAULT 'daily',
  `default_role_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checklists`
--

LOCK TABLES `checklists` WRITE;
/*!40000 ALTER TABLE `checklists` DISABLE KEYS */;
INSERT INTO `checklists` VALUES (1,1,'CHECKLIST ABERTURA','','daily',7,1,3,'2025-10-07 23:09:15'),(2,1,'CHECKLIST SEMANAL','descrição teste!','weekly',2,1,3,'2025-10-07 23:20:42'),(3,8,'PRÉ OPERAÇÃO','esse é o preparo para começar o dia bem, de 16:30 as 18:00','daily',10,1,3,'2025-10-15 01:09:40'),(4,5,'Abertura do Bar','','daily',7,1,3,'2025-10-21 13:07:27'),(5,5,'Abertura Do Bar (Manhã)','','daily',7,1,3,'2025-10-28 17:22:02'),(6,5,'Fechamento Bar (Salão)','','daily',7,1,3,'2025-10-29 12:30:49'),(7,4,'Salao','FAzer comida','daily',8,1,2,'2025-12-29 23:21:05'),(8,7,'Abertura','Vamos pra la','daily',9,1,3,'2025-12-29 23:22:21');
/*!40000 ALTER TABLE `checklists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(160) NOT NULL,
  `trade_name` varchar(160) NOT NULL,
  `document` varchar(160) NOT NULL,
  `logo_url` varchar(160) NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'Empresa Alpha','Empresa Alpha','111','',1,'2025-10-07 20:19:38'),(2,'Empresa Beta','Empresa Beta','222','',1,'2025-10-07 20:19:38'),(5,'Mindhub teste Ltda','Mindhub teste Ltda','333','',1,'2025-10-08 00:07:14'),(6,'Retorne Tecnologia Ltda','Retorne','98798798798798','https://sistema.trinks.com/hubfs/LP%20TRINKS%20DE%20VANTAGENS_retorne%20app_retorne%20app%20-%20icon.png',1,'2025-10-08 00:10:03'),(7,'Arianna Helena Patisserie produção de bolos e doces Ltda','Beju&Magi','38093432000167','www.bejuemagi.com',1,'2025-10-15 00:09:33'),(8,'RM1 FOOD LTDA','MALICE PIZZARIA MEIER','60667575000194','https://storage.googleapis.com/prod-cardapio-web/uploads/company/logo/26935/4da1d48d2ae7c478696656159880752_ufwbkk.jpg',1,'2025-10-15 00:25:53'),(9,'Teste','teste','28372983','',1,'2025-10-15 02:26:05'),(10,'Uburguer Comércio de Alimentos Ltda','Uburguer','43959955000183','',1,'2025-10-15 03:15:39'),(11,'Sulamita Gastronomia','Sulamita Gastronomia','9999999999','',1,'2025-10-24 14:13:26');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_tickets`
--

DROP TABLE IF EXISTS `feedback_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sentiment_key` varchar(20) NOT NULL,
  `sentiment_score` tinyint(4) NOT NULL,
  `category` varchar(32) NOT NULL,
  `subject` varchar(160) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('aberto','em_andamento','concluido') NOT NULL DEFAULT 'aberto',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company_status` (`company_id`,`status`,`created_at`),
  KEY `idx_user_created` (`user_id`,`created_at`),
  CONSTRAINT `fk_fb_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fb_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_tickets`
--

LOCK TABLES `feedback_tickets` WRITE;
/*!40000 ALTER TABLE `feedback_tickets` DISABLE KEYS */;
INSERT INTO `feedback_tickets` VALUES (1,1,3,'estressado',1,'ocorrencia',NULL,'Assunto: Fui mal tratado\n\nO Zé me ofendeu. falou besteira pra todo mundo e depois veio me ofender no privado.','em_andamento','2025-10-07 23:39:06','2025-10-07 23:39:17'),(2,1,3,'estressado',1,'ocorrencia',NULL,'Assunto: Fulano roubou minha marmita\n\nnao eh a 1 e nem a seghunda vez que isso acontece! preciso que me ajudem','concluido','2025-10-09 13:23:50','2025-10-09 13:24:22'),(3,5,3,'bem',4,'infra_recursos',NULL,'Assunto: Adicionar cargo ASG\n\nGostaria que adicionasse o cargo de ASG para poder cadastrar corretamente os funcionários','aberto','2025-10-15 11:17:30','2025-10-15 11:17:30'),(4,5,3,'bem',4,'melhoria_processo',NULL,'Assunto: Poder editar o cadastro dos colaboradores\n\nPoder editar os colaboradores economiza tempo e deixa o programa preparado para mudanças ou intercorrências.','aberto','2025-10-15 11:19:05','2025-10-15 11:19:05');
/*!40000 ALTER TABLE `feedback_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_training`
--

DROP TABLE IF EXISTS `role_training`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_training` (
  `role_id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`training_id`),
  KEY `fk_rt_training` (`training_id`),
  CONSTRAINT `fk_rt_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rt_training` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_training`
--

LOCK TABLES `role_training` WRITE;
/*!40000 ALTER TABLE `role_training` DISABLE KEYS */;
INSERT INTO `role_training` VALUES (7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(7,2),(10,2),(12,2),(8,3),(11,3),(12,3),(9,4),(11,4),(12,4),(7,5),(10,5),(12,5),(8,6),(11,6),(12,6),(1,7),(2,7),(7,7),(8,7),(9,7),(10,7),(11,7),(12,7);
/*!40000 ALTER TABLE `role_training` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Analista'),(10,'Atendente de Delivery'),(11,'Auxiliar de Cozinha'),(9,'Chapeiro(a)'),(2,'Coordenador'),(8,'Cozinheiro(a)'),(7,'Garçom/Garçonete'),(12,'Gerente de Loja'),(3,'Instrutor');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_videos`
--

DROP TABLE IF EXISTS `training_videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `summary` text,
  `video_provider` enum('cloudflare','mux','vimeo','youtube','url') NOT NULL DEFAULT 'youtube',
  `video_ref` varchar(255) NOT NULL,
  `thumb_image` varchar(255) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT '0',
  `order_index` int(11) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_tv_order` (`training_id`,`order_index`),
  CONSTRAINT `fk_tv_training` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_videos`
--

LOCK TABLES `training_videos` WRITE;
/*!40000 ALTER TABLE `training_videos` DISABLE KEYS */;
INSERT INTO `training_videos` VALUES (1,1,'EP1 • Higiene Pessoal Essencial','Uniforme, EPI e lavagem correta de mãos.','youtube','https://www.youtube.com/watch?v=KZDY51vOn2g','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg',420,1,1),(2,1,'EP2 • Contaminação Cruzada','Separação de áreas, tábuas e fluxo.','youtube','https://www.youtube.com/watch?v=v2cR2cajXUA','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg',480,2,1),(3,1,'EP3 • Temperatura & Armazenamento','Zona de perigo e validade.','youtube','https://www.youtube.com/watch?v=Q3W0Xw_iG9s','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg',540,3,1),(4,2,'EP1 • Recepção & Primeira Impressão','Saudação, tempo de espera e acomodação.','youtube','https://www.youtube.com/watch?v=EQd-8mYk9jU','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg',360,1,1),(5,2,'EP2 • Cardápio e Sugestões','Storytelling dos pratos e bebidas.','youtube','https://www.youtube.com/watch?v=8c0VbOb8w2k','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg',420,2,1),(6,2,'EP3 • Upsell sem Forçar','Combos, sobremesas e cafés.','youtube','https://www.youtube.com/watch?v=9bZkp7q19f0','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg',300,3,1),(7,3,'EP1 • Mise en Place do PF','Pré-preparo, cortes, porcionamento.','youtube','https://www.youtube.com/watch?v=1APwq1df6Mw','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg',540,1,1),(8,3,'EP2 • Feijão, Arroz e Farofa','Textura, sabor, conservação.','youtube','https://www.youtube.com/watch?v=G1IbRujko-A','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg',600,2,1),(9,3,'EP3 • Feijoada Padronizada','Cortes, dessalgue, cocção e finalização.','youtube','https://www.youtube.com/watch?v=aqz-KE-bpKQ','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg',540,3,1),(10,4,'EP1 • Setup & Padrão de Chapa','Temperatura, organização e sequência.','youtube','https://www.youtube.com/watch?v=Zi_XLOBDo_Y','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg',360,1,1),(11,4,'EP2 • Fritadeira com Segurança','Troca de óleo, cesta, ponto e segurança.','youtube','https://www.youtube.com/watch?v=2Vv-BfVoq4g','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg',420,2,1),(12,5,'EP1 • Picking & Conferência','Checklist de itens e lacres.','youtube','https://www.youtube.com/watch?v=fJ9rUzIMcZQ','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg',300,1,1),(13,5,'EP2 • Embalagem Inteligente','Evitar vazamentos e manter temperatura/textura.','youtube','https://www.youtube.com/watch?v=3JZ_D3ELwOQ','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg',320,2,1),(14,6,'EP1 • Planejamento & PVPS','Organização de câmaras e validade.','youtube','https://www.youtube.com/watch?v=LsoLEjrDogU','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg',420,1,1),(15,6,'EP2 • Pré-preparo & Rendimento','Porcionamento, etiquetagem e controle de perdas.','youtube','https://www.youtube.com/watch?v=hT_nvWreIhg','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg',480,2,1),(16,7,'Limpar Chapa','Nessa aula vc aprenderá tudo','youtube','QOtNkBkj_UI','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg',60,1,1);
/*!40000 ALTER TABLE `training_videos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trainings`
--

DROP TABLE IF EXISTS `trainings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trainings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `objective` text NOT NULL,
  `description` text,
  `cover_image` varchar(255) DEFAULT NULL,
  `reward_image` varchar(255) DEFAULT NULL,
  `difficulty` enum('Iniciante','Intermediário','Avançado') DEFAULT 'Iniciante',
  `estimated_minutes` int(11) DEFAULT '0',
  `tags` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_trainings_company_title` (`company_id`,`title`(150)),
  CONSTRAINT `fk_train_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainings`
--

LOCK TABLES `trainings` WRITE;
/*!40000 ALTER TABLE `trainings` DISABLE KEYS */;
INSERT INTO `trainings` VALUES (1,1,'Boas Práticas de Higiene & Manipulação','Padronizar higiene pessoal, manipulação segura e sanitização de superfícies.','Conteúdo prático: EPI, lavagem de mãos, contaminação cruzada, temperatura segura e limpeza.','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg','https://static.vecteezy.com/system/resources/previews/027/291/500/non_2x/3d-rendered-medal-reward-rating-rank-verified-quality-badge-icon-png.png','Iniciante',45,'Higiene, Segurança, Qualidade',1,'2025-10-07 21:33:56'),(2,1,'Atendimento de Salão: Jeito Brasileiro','Elevar a experiência do cliente: acolhimento, timing e upsell respeitoso.','Recepção, apresentação do cardápio, sugestões (PFs, feijoada, moqueca) e objeções.','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg','https://thumbs.dreamstime.com/b/star-medal-d-icon-achievement-rewards-digital-trophy-symbol-games-competitions-white-background-star-medal-d-360430545.jpg','Intermediário',40,'Atendimento, Vendas, Experiência',1,'2025-10-07 21:33:56'),(3,1,'Cozinha Brasileira: Execução Consistente','Padronizar preparo de PF, feijoada, farofa, arroz/feijão e grelhados.','Fichas técnicas, rendimento, ponto e emplatamento rápido.','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg','https://peakeen.com/wp-content/uploads/2025/03/chef-medal5.webp','Intermediário',60,'Cozinha, Padronização, Qualidade',1,'2025-10-07 21:33:56'),(4,1,'Chapa & Fritadeira: Segurança e Produtividade','Operar com segurança, velocidade e padrão de cocção.','Setup da estação, ponto, troca de óleo, limpeza e checklists.','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg','https://s.alicdn.com/@sc04/kf/H0d011630b2f645aab849c2da2adeb6b0i.jpg','Iniciante',35,'Chapa, Fritadeira, Segurança',1,'2025-10-07 21:33:56'),(5,1,'Delivery & Embalagem: Padrão de Qualidade','Garantir que o pedido chegue quente, íntegro e apresentável.','Picking, conferência, selagem, rotas e NPS.','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg','https://s.alicdn.com/@sc04/kf/H253f389adca64421a131573659fa4738G.jpg','Intermediário',30,'Delivery, Embalagem, Experiência',1,'2025-10-07 21:33:56'),(6,1,'Mise en Place & Gestão de Estoque','Organizar produção e insumos para reduzir perdas e atrasos.','Planejamento do dia, pré-preparo, PVPS, inventário e comunicação cozinha-salão.','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg','https://s.alicdn.com/@sc04/kf/Hbf81d0160fd44ba58ff1f5d4a08adfdb2.jpg','Avançado',50,'Mise en Place, Estoque, Redução de Perdas',1,'2025-10-07 21:33:56'),(7,1,'Como limpar a chapa','Aprenda o processo correto para manter a chapa higienizada e limpa','Aprenda o processo correto para manter a chapa higienizada e limpaAprenda o processo correto para manter a chapa higienizada e limpa','https://guairaclean.com.br/wp-content/uploads/2018/10/kenny-luo-640783-unsplash-1080x675.jpg','https://www.trophiesplusmedals.co.uk/media/catalog/product/a/9/a902a_2.jpg?quality=80&bg-color=255,255,255&fit=bounds&height=650&width=572&canvas=572:650','Intermediário',45,'Higiene',1,'2025-10-07 21:48:52');
/*!40000 ALTER TABLE `trainings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_company`
--

DROP TABLE IF EXISTS `user_company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_company` (
  `user_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`company_id`),
  KEY `fk_uc_company` (`company_id`),
  CONSTRAINT `fk_uc_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_company`
--

LOCK TABLES `user_company` WRITE;
/*!40000 ALTER TABLE `user_company` DISABLE KEYS */;
INSERT INTO `user_company` VALUES (1,1),(3,1),(4,1),(6,1),(7,1),(34,1),(3,2),(4,2),(6,2),(7,2),(12,2),(3,5),(8,5),(9,5),(10,5),(11,5),(14,5),(17,5),(18,5),(19,5),(20,5),(21,5),(22,5),(23,5),(3,6),(3,7),(35,7),(3,8),(13,8),(3,9),(25,9),(3,10),(15,10),(16,10),(24,10),(3,11),(26,11),(27,11),(28,11),(29,11),(30,11),(31,11),(32,11),(33,11),(36,11);
/*!40000 ALTER TABLE `user_company` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_meta`
--

DROP TABLE IF EXISTS `user_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_meta` (
  `user_id` int(11) NOT NULL,
  `meta_key` varchar(64) NOT NULL,
  `meta_value` text,
  PRIMARY KEY (`user_id`,`meta_key`),
  CONSTRAINT `user_meta_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_meta`
--

LOCK TABLES `user_meta` WRITE;
/*!40000 ALTER TABLE `user_meta` DISABLE KEYS */;
INSERT INTO `user_meta` VALUES (4,'notes','Folguista');
/*!40000 ALTER TABLE `user_meta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_role`
--

DROP TABLE IF EXISTS `user_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_role` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `fk_ur_role` (`role_id`),
  CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_role`
--

LOCK TABLES `user_role` WRITE;
/*!40000 ALTER TABLE `user_role` DISABLE KEYS */;
INSERT INTO `user_role` VALUES (1,1),(3,1),(4,1),(6,1),(25,1),(26,1),(30,1),(1,2),(3,2),(4,2),(6,2),(26,2),(1,3),(3,3),(14,3),(26,3),(3,7),(4,7),(6,7),(7,7),(10,7),(11,7),(19,7),(26,7),(32,7),(33,7),(3,8),(4,8),(6,8),(9,8),(18,8),(26,8),(31,8),(3,9),(4,9),(6,9),(26,9),(34,9),(3,10),(4,10),(6,10),(13,10),(26,10),(32,10),(33,10),(3,11),(4,11),(6,11),(8,11),(16,11),(20,11),(21,11),(23,11),(24,11),(26,11),(27,11),(28,11),(3,12),(4,12),(6,12),(12,12),(15,12),(17,12),(22,12),(25,12),(26,12),(36,12);
/*!40000 ALTER TABLE `user_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_training_reward`
--

DROP TABLE IF EXISTS `user_training_reward`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_training_reward` (
  `user_id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `reward_image` varchar(255) NOT NULL,
  `awarded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`training_id`),
  KEY `fk_utr_training` (`training_id`),
  CONSTRAINT `fk_utr_training` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_utr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_training_reward`
--

LOCK TABLES `user_training_reward` WRITE;
/*!40000 ALTER TABLE `user_training_reward` DISABLE KEYS */;
INSERT INTO `user_training_reward` VALUES (3,1,'/assets/img/rewards/higiene_badge.png','2025-10-07 21:55:46'),(3,2,'/assets/img/rewards/salao_badge.png','2025-10-07 21:59:54'),(3,7,'https://static.vecteezy.com/system/resources/previews/027/291/500/non_2x/3d-rendered-medal-reward-rating-rank-verified-quality-badge-icon-png.png','2025-10-07 21:49:15');
/*!40000 ALTER TABLE `user_training_reward` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_video_progress`
--

DROP TABLE IF EXISTS `user_video_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_video_progress` (
  `user_id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `completed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`video_id`),
  KEY `fk_uvp_video` (`video_id`),
  CONSTRAINT `fk_uvp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uvp_video` FOREIGN KEY (`video_id`) REFERENCES `training_videos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_video_progress`
--

LOCK TABLES `user_video_progress` WRITE;
/*!40000 ALTER TABLE `user_video_progress` DISABLE KEYS */;
INSERT INTO `user_video_progress` VALUES (3,1,'2025-10-07 21:39:33'),(3,2,'2025-10-07 21:39:51'),(3,3,'2025-10-07 21:55:45'),(3,4,'2025-10-07 21:59:29'),(3,5,'2025-10-07 21:59:43'),(3,6,'2025-10-07 21:59:54'),(3,7,'2025-10-07 21:40:40'),(3,16,'2025-10-07 21:49:15');
/*!40000 ALTER TABLE `user_video_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `type` enum('Admin','Colaborador') NOT NULL DEFAULT 'Colaborador',
  `avatar_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `birthday` date DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin Master','admin@mindhub.local','$2y$10$FqwnhK9gP0aXUo5m0C9q/uQ9sB3Hq0nW7C3VtG6J1oVxjA1F7vFBS','Admin',NULL,'2025-10-07 20:19:38',NULL,NULL,1),(3,'Admin','admin@mindhub.com','$2y$10$056KuEfbaKuigBaKJMObGOta2ZKYzi5CoASAYF9B/e732hYT0HuSa','Admin',NULL,'2025-10-07 20:35:04',NULL,NULL,1),(4,'Rafael Gestor','rmelobarbosa@gmail.com','$2y$10$A/oc2gKDA//gVeiuF83sJu3rOjs0FT7Jk6Y1GUO6NIlhUTJgxdVIO','Colaborador','https://img.icons8.com/?size=1200&id=23347&format=png','2025-10-07 22:07:33','1989-02-27','21997868300',1),(6,'Rafael Barbosa','rafael@gmail.com','$2y$10$X.q9wxlCdvlQ4q9E3Df5tudmPYZojSMkgHsukC6GbwyE/ZF2D4KSy','Colaborador','https://img.icons8.com/?size=1200&id=23347&format=png','2025-10-07 22:08:16','1989-02-27','21997868300',1),(7,'Rafael Barbosa','teste@mindhub.com','$2y$10$/BVcHEGL6VjK1/z6lVujE.KhurfspVTdWaSHYf7JkmozQa.T5KLbW','Colaborador','https://img.icons8.com/?size=1200&id=23347&format=png','2025-10-08 03:12:24','1989-11-27','21997868300',1),(8,'ABIGAIL BRASIL','abigail@mindhub.com','$2y$10$B0SpwDO3WRaTtWx9skxfS.0ZeKnSrVMXCn7T1Bf1Tq6rSwmjMGhs2','Colaborador',NULL,'2025-10-14 21:11:49',NULL,NULL,1),(9,'Ailton de Jesus','Ailton@mindhub.com','$2y$10$sTfVFOR.CXGiIvsMbIKiFueipK3792wwTmA36E59ZrzNS2ZlmyQ2a','Colaborador',NULL,'2025-10-14 21:13:22',NULL,NULL,1),(10,'Alexandre Barbosa do Nascimento','Alexandre@mindhub.com','$2y$10$T93AeLD9FZOfmvPN.VhwT.2Tn37TBGk.UWRG6QNpmz7MTzZyM9pfm','Colaborador',NULL,'2025-10-14 21:14:06',NULL,NULL,1),(11,'Amanda Lemos da Silva','Amanda@mindhub.com','$2y$10$1w82nrLM5PzYwGM/8IhEGu.VHS8LKyXQFDItRUrw4WK2DJ8fSZWcC','Colaborador',NULL,'2025-10-14 21:15:25',NULL,NULL,1),(12,'Bruna Felix','bruna@espetobrasileiro.com.br','$2y$10$TQd38PcK/3WvgE0vwoGjU.f4KbY3bG8dm/QMOy9202/EVxjlI9R2G','Colaborador',NULL,'2025-10-15 03:04:54',NULL,NULL,1),(13,'Carol','carolssouza456@gmail.com','$2y$10$acLsGRMzuAXCBHEOHnM5/eyQ1ZgSlsRYmlnhweQY.91yL/W3wAYpK','Colaborador',NULL,'2025-10-15 03:46:27','1997-10-17','021972805646',1),(14,'Celina Lidia La Valle','Celina@mindhub.com','$2y$10$qMJ/zjrVbPWdDLRi404hjeoPGvNFrBAQomqt2g/yor7djehdLy2oK','Colaborador',NULL,'2025-10-15 04:57:06',NULL,NULL,1),(15,'Kelly Carvalho','kellycarsilva15@gmail.com','$2y$10$epvKJIMxp0TIWoSH75t/PuLzm25NPrXqf4TDcyZ17uJDBWCKM/yj.','Admin',NULL,'2025-10-15 06:21:21','1994-12-10','11951478947',1),(16,'Williane Silva','Wilianesilva455@gmail.com','$2y$10$Hfl9yunaSnN1N0EvyD8VROvWM5I0EBXs9Pt4Id3vZPHC26WTQiHKO','Colaborador',NULL,'2025-10-15 06:49:43',NULL,NULL,1),(17,'CLAUDIA MARIA SAYAO DUTRA','Claudia@mindhub.com','$2y$10$ZBLGqWq4p.t950WamqXP6OGvv2w9Cjjm7Edxne/52yWx3yIyBEHnW','Colaborador',NULL,'2025-10-15 14:09:39',NULL,NULL,1),(18,'Evandro Fernandes Do Nascimento','Evandro@mindhub.com','$2y$10$e80.NSKsVhI5hampHM4t1euJyg39.lSW0ZCO4Jg3pxqrjO7k/HvpS','Colaborador',NULL,'2025-10-15 14:10:34',NULL,NULL,1),(19,'JESSICA LEN REIFFE CARDOSO','Jessica@mindhub.com','$2y$10$6ZfQG3OSHoDKpgqgqPeD5eQkmlSU7fur60dIS.JxKcd6kOiouqLUW','Colaborador',NULL,'2025-10-15 14:12:33',NULL,NULL,1),(20,'JESSICA LINDALVA RAMOS FERREIRA','Jessical@mindhub.com','$2y$10$1QNAyGoD2..MoasXlN5Hd.OWZuKRMfLWz5StLBaliv/pZ768IkeP6','Colaborador',NULL,'2025-10-15 14:13:51',NULL,NULL,1),(21,'LAYSA REIFFE DIAS','Laysa@mindhub.com','$2y$10$fFGlKfV7u7q2PZ0axm.syO6KvAR54jgebYwL1QrHlS2rXVeF6i.Nq','Colaborador',NULL,'2025-10-15 14:14:29',NULL,NULL,1),(22,'MAURICIO NEVES DOS SANTOS','Mauricio@mindhub.com','$2y$10$QoQeleGH1CmjVI8wkeXgNOmcrWNguFexnJtSLt8NvLTyU5IGW8oTm','Colaborador',NULL,'2025-10-15 14:14:58',NULL,NULL,1),(23,'VIRGINIA DE MORAES COSTA','Virginia@mindhub.com','$2y$10$R9wa1kAfI4op0acDlE/3d.Xp0IocPHXJ1ugHeyXVNqruuGPh/YX8a','Colaborador',NULL,'2025-10-15 14:15:49',NULL,NULL,1),(24,'Williane da Silva','willianesilva455@gmail.com','$2y$10$W4bpEk6gu2IMjd.E3sN3ruRbdUOqlzZTh0Wr6nuJpQOjs4iKtHn..','Colaborador',NULL,'2025-10-15 23:58:31',NULL,NULL,1),(25,'Admin teste','admin@teste.com','$2y$10$3KYuiWAjrxNTNKoesove5.V0co06HN9/ieLubEenkBzEdSLcPTRfG','Admin',NULL,'2025-10-16 04:48:05','1989-02-27',NULL,1),(26,'Thais Vasconcelos','sulamitagastronomia@gmail.com','$2y$10$n8fBdlUMiNy4P8caPFvAEutR.rbAU/t7cjV/bOTrESuiGRzDWYWtG','Admin',NULL,'2025-10-24 17:14:46','1990-01-01','219999999999',1),(27,'Jorgiane','jo@sulamita.com.br','$2y$10$K7WPDeb7UgX8mo5sNBHDP.O4YHRnvFP8DEb4GRjSs5/Ef47dor3kW','Colaborador',NULL,'2025-11-04 04:57:10','2000-01-01','21977263406',1),(28,'Jane','jane@sulamita.com.br','$2y$10$VwzXOMFyOX6PhirUTBeYieeWmkyYy/QqhuzQv0Gi1j4I8d.ihQ7Sy','Colaborador',NULL,'2025-11-04 04:58:13','2000-01-01','21967000906',1),(29,'Marcia','marcia@sulamita.com.br','$2y$10$IkQh5ZiBQXQVqtEQULAA6ueEkDpGnMid055NLyvyYAzDV5Eo41K7K','Colaborador',NULL,'2025-11-04 04:58:47','2000-01-01','21991980338',1),(30,'Samara','samara@sulamita.com.br','$2y$10$2Z3UxHYomNTK6F5mdCOgM.dNEr.bMv87rjEVMIDiq/j7nzMGZyU4S','Colaborador',NULL,'2025-11-04 04:59:45','2000-01-01','21991473176',1),(31,'Marcela','marcela@sulamita.com.br','$2y$10$4FNruxH6/KU28rG6CWdleu2qjq/C2j2/vL/bZMmdiF6bNpjPnsHHC','Colaborador',NULL,'2025-11-04 05:01:13',NULL,'21981220387',1),(32,'Thais Felix','thais.felix@sulamita.com.br','$2y$10$e5ZPJCCd6ZJHnLMaCZTEJ.czbPuRTg2NS.4QhtW.OWc3c7AH0gVQu','Colaborador',NULL,'2025-11-04 05:02:05','2000-01-01','21964133986',1),(33,'Maylla','maylla@gmail.com','$2y$10$k2ztJUdVz6Crteow/gxcPuHk6GwmjJXQqEJC.4G.Gf76NoG0DhVwS','Colaborador',NULL,'2025-11-04 05:02:37','2000-01-01','21996143105',1),(34,'Eduardo','oeduardoluna@gmail.com','$2y$10$6cFYVWqKXwHZGsyzhHQ2UuK10AunCmhNIsU9jU.FheTv3b/Fsh9gK','Colaborador',NULL,'2025-12-30 00:42:57','2006-03-07','21972871523',1),(35,'Valdinho','email@teste','$2y$10$Z970KMyJDQdpErqZH03P.OjhXmqdfap3NK9KTx54jvJZF8da0ydNu','Admin',NULL,'2025-12-30 01:12:25',NULL,NULL,1),(36,'Gestor_Teste','gestor@email.com','$2y$10$Nw8vAy7mjS5X9gocVozLK.drtLsXOKyJCRvcpUvhsM6WYdoOelYpu','Colaborador',NULL,'2025-12-30 01:17:06','2014-03-14',NULL,1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-29 20:38:05
