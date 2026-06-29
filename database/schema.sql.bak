-- ═══════════════════════════════════════════════
-- PETFINDER - ESTRUTURA DO BANCO DE DADOS
-- Versão: 1.0
-- Data: Dezembro 2025
-- ═══════════════════════════════════════════════

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "-04:00";

CREATE DATABASE IF NOT EXISTS `petfinder` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `petfinder`;

-- ═══════════════════════════════════════════════
-- TABELA: usuarios
-- ═══════════════════════════════════════════════
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `tipo_usuario` enum('comum','parceiro') NOT NULL DEFAULT 'comum',
  `notificacoes_email` tinyint(1) DEFAULT 1,
  `email_confirmado` tinyint(1) DEFAULT 0,
  `token_confirmacao` varchar(64) DEFAULT NULL,
  `token_recuperacao` varchar(64) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `tentativas_login` tinyint(4) DEFAULT 0,
  `bloqueado_ate` datetime DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acesso` timestamp NULL DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `is_admin` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_cidade` (`cidade`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_email_confirmado` (`email_confirmado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════
-- SISTEMA DE PARCEIROS (MVP)
-- ═══════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS `parceiro_inscricoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `categoria` enum('petshop','clinica','hotel','adestrador','outro') NOT NULL,
  `nome_fantasia` varchar(120) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `estado` varchar(2) NOT NULL,
  `mensagem` text DEFAULT NULL,
  `status` enum('pendente','aprovada','recusada') NOT NULL DEFAULT 'pendente',
  `aprovada_em` datetime DEFAULT NULL,
  `recusada_em` datetime DEFAULT NULL,
  `analisada_por` int(11) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_inscricao_usuario` (`usuario_id`),
  KEY `idx_status` (`status`),
  KEY `idx_cidade` (`cidade`),
  CONSTRAINT `fk_parceiro_inscricoes_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_parceiro_inscricoes_admin` FOREIGN KEY (`analisada_por`)
    REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `parceiro_perfis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `nome_fantasia` varchar(120) NOT NULL,
  `categoria` enum('petshop','clinica','hotel','adestrador','outro') NOT NULL,
  `descricao` text DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `email_contato` varchar(100) DEFAULT NULL,
  `site` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) NOT NULL,
  `estado` varchar(2) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `capa` varchar(255) DEFAULT NULL,
  `verificado` tinyint(1) DEFAULT 0,
  `publicado` tinyint(1) DEFAULT 0,
  `destaque` tinyint(1) DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_perfil_usuario` (`usuario_id`),
  UNIQUE KEY `unique_slug` (`slug`),
  KEY `idx_publicado` (`publicado`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_cidade` (`cidade`),
  CONSTRAINT `fk_parceiro_perfis_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `parceiro_assinaturas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `plano` enum('basico','destaque') NOT NULL DEFAULT 'basico',
  `periodicidade` enum('mensal','anual') NOT NULL DEFAULT 'mensal',
  `efi_plan_id` int(11) DEFAULT NULL,
  `efi_subscription_id` int(11) DEFAULT NULL,
  `valor_mensal` decimal(10,2) NOT NULL,
  `status` enum('pendente_pagamento','ativa','suspensa','cancelada') NOT NULL DEFAULT 'pendente_pagamento',
  `metodo_pagamento` enum('pix_manual','gateway') NOT NULL DEFAULT 'pix_manual',
  `pago_ate` date DEFAULT NULL,
  `proxima_cobranca` date DEFAULT NULL,
  `ultimo_pagamento_em` datetime DEFAULT NULL,
  `observacoes_admin` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assinatura_usuario` (`usuario_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_parceiro_assinaturas_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `parceiro_pagamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `plano` enum('basico','destaque') NOT NULL,
  `periodicidade` enum('mensal','anual') NOT NULL DEFAULT 'mensal',
  `gateway_tipo` enum('pix','cartao_avista','cartao_recorrente') NOT NULL DEFAULT 'pix',
  `valor` decimal(10,2) NOT NULL,
  `metodo` enum('pix_manual','gateway') NOT NULL DEFAULT 'pix_manual',
  `status` enum('pendente','aprovado','recusado') NOT NULL DEFAULT 'pendente',
  `referencia` varchar(120) DEFAULT NULL,
  `efi_charge_id` bigint(20) DEFAULT NULL,
  `efi_subscription_id` int(11) DEFAULT NULL,
  `payment_url` varchar(255) DEFAULT NULL,
  `comprovante_texto` text DEFAULT NULL,
  `aprovado_em` datetime DEFAULT NULL,
  `recusado_em` datetime DEFAULT NULL,
  `aprovado_por` int(11) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_parceiro_pagamentos_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_parceiro_pagamentos_admin` FOREIGN KEY (`aprovado_por`)
    REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════
-- TABELA: anuncios
-- ═══════════════════════════════════════════════
CREATE TABLE `anuncios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('perdido','encontrado','doacao') NOT NULL,
  `nome_pet` varchar(100) DEFAULT NULL,
  `especie` enum('cachorro','gato','ave','outro') NOT NULL,
  `raca` varchar(100) DEFAULT NULL,
  `cor` varchar(100) DEFAULT NULL,
  `tamanho` enum('pequeno','medio','grande') NOT NULL,
  `idade` int(11) DEFAULT NULL,
  `vacinas` text DEFAULT NULL,
  `castrado` tinyint(1) DEFAULT NULL,
  `necessita_termo_responsabilidade` tinyint(1) DEFAULT 0,
  `descricao` text DEFAULT NULL,
  `data_ocorrido` date NOT NULL,
  `endereco_completo` varchar(255) NOT NULL,
  `bairro` varchar(100) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `estado` varchar(2) NOT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `ponto_referencia` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `telefone_contato` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `email_contato` varchar(100) DEFAULT NULL,
  `recompensa` varchar(100) DEFAULT NULL,
  `status` enum('ativo','resolvido','inativo','bloqueado','expirado') DEFAULT 'ativo',
  `visualizacoes` int(11) DEFAULT 0,
  `data_publicacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `data_expiracao` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_especie` (`especie`),
  KEY `idx_cidade` (`cidade`),
  KEY `idx_status` (`status`),
  KEY `idx_data_pub` (`data_publicacao`),
  KEY `idx_localizacao` (`latitude`,`longitude`),
  KEY `idx_busca` (`tipo`,`especie`,`cidade`,`status`),
  CONSTRAINT `fk_anuncios_usuario` FOREIGN KEY (`usuario_id`) 
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════
-- TABELA: fotos_anuncios
-- ═══════════════════════════════════════════════
CREATE TABLE `fotos_anuncios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anuncio_id` int(11) NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `ordem` tinyint(4) DEFAULT 1,
  `data_upload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_anuncio` (`anuncio_id`),
  CONSTRAINT `fk_fotos_anuncio` FOREIGN KEY (`anuncio_id`) 
    REFERENCES `anuncios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════
-- TABELA: doacoes
-- ═══════════════════════════════════════════════
CREATE TABLE `doacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `tipo` enum('unica','mensal') DEFAULT 'unica',
  `metodo_pagamento` varchar(50) NOT NULL,
  `gateway` varchar(50) NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `payment_url` text DEFAULT NULL,
  `efi_charge_id` varchar(100) DEFAULT NULL,
  `efi_subscription_id` varchar(100) DEFAULT NULL,
  `efi_plan_id` int(11) DEFAULT NULL,
  `status` enum('pendente','aprovada','cancelada','estornada') DEFAULT 'pendente',
  `nome_doador` varchar(100) DEFAULT NULL,
  `email_doador` varchar(100) DEFAULT NULL,
  `cpf_doador` varchar(14) DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `exibir_mural` tinyint(1) DEFAULT 0,
  `data_doacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_pagamento_em` datetime DEFAULT NULL,
  `proxima_cobranca` date DEFAULT NULL,
  `cancelada_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_status` (`status`),
  KEY `idx_data` (`data_doacao`),
  KEY `idx_tipo` (`tipo`),
  CONSTRAINT `fk_doacoes_usuario` FOREIGN KEY (`usuario_id`) 
    REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════
-- TABELA: metas_financeiras
-- ═══════════════════════════════════════════════
CREATE TABLE `metas_financeiras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mes_referencia` date NOT NULL,
  `valor_meta` decimal(10,2) NOT NULL,
  `valor_arrecadado` decimal(10,2) DEFAULT 0.00,
  `custos_servidor` decimal(10,2) DEFAULT 0.00,
  `custos_manutencao` decimal(10,2) DEFAULT 0.00,
  `custos_outros` decimal(10,2) DEFAULT 0.00,
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mes` (`mes_referencia`),
  KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════
-- TABELA: favoritos
-- ═══════════════════════════════════════════════
CREATE TABLE `favoritos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `anuncio_id` int(11) NOT NULL,
  `data_favoritado` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_favorito` (`usuario_id`,`anuncio_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_anuncio` (`anuncio_id`),
  CONSTRAINT `fk_favoritos_usuario` FOREIGN KEY (`usuario_id`) 
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_favoritos_anuncio` FOREIGN KEY (`anuncio_id`) 
    REFERENCES `anuncios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════
-- TABELA: alertas
-- ═══════════════════════════════════════════════
CREATE TABLE `alertas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('perdido','encontrado','ambos') DEFAULT 'ambos',
  `especie` varchar(50) DEFAULT NULL,
  `cidade` varchar(100) NOT NULL,
  `estado` varchar(2) NOT NULL,
  `raio_km` int(11) DEFAULT 10,
  `ativo` tinyint(1) DEFAULT 1,
  `ultimo_envio` datetime DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_ativo` (`ativo`),
  CONSTRAINT `fk_alertas_usuario` FOREIGN KEY (`usuario_id`) 
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════
-- TABELA: denuncias
-- ═══════════════════════════════════════════════
CREATE TABLE `denuncias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anuncio_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `motivo` enum('inapropriado','spam','venda','golpe','outro') NOT NULL,
  `descricao` text DEFAULT NULL,
  `status` enum('pendente','analisada','procedente','improcedente') DEFAULT 'pendente',
  `data_denuncia` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `analisada_em` datetime DEFAULT NULL,
  `analisada_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_anuncio` (`anuncio_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_denuncias_anuncio` FOREIGN KEY (`anuncio_id`) 
    REFERENCES `anuncios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_denuncias_usuario` FOREIGN KEY (`usuario_id`) 
    REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════
-- TABELA: auditoria
-- ═══════════════════════════════════════════════
CREATE TABLE `auditoria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(100) NOT NULL,
  `tabela` varchar(50) NOT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `dados_antigos` text DEFAULT NULL,
  `dados_novos` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `data_acao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_acao` (`acao`),
  KEY `idx_data` (`data_acao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════
-- DADOS INICIAIS
-- ═══════════════════════════════════════════════

-- Usuário Admin (senha: Admin@123)
INSERT INTO `usuarios` 
  (`nome`, `email`, `telefone`, `senha`, `cidade`, `estado`, `email_confirmado`, `is_admin`, `ativo`) 
VALUES 
  ('Administrador', 'admin@petfinder.com', '00000000000',
   '$2y$12$Qr/WHCQl8WZKCYMXrsO8DOcVEkpnDgM409t7VodkxexXxwsWgjJLG',
   'Porto Velho', 'RO', 1, 1, 1);

-- Meta Financeira do Mês Atual
INSERT INTO `metas_financeiras` 
  (`mes_referencia`, `valor_meta`, `custos_servidor`, `custos_manutencao`, `descricao`) 
VALUES 
  (CURDATE(), 500.00, 150.00, 100.00, 
   'Meta mensal para manutenção do servidor e melhorias no sistema');

-- ═══════════════════════════════════════════════
-- VIEWS ÚTEIS
-- ═══════════════════════════════════════════════

-- View de estatísticas gerais
CREATE OR REPLACE VIEW `view_estatisticas` AS
SELECT 
  (SELECT COUNT(*) FROM usuarios WHERE ativo = 1) as usuarios_ativos,
  (SELECT COUNT(*) FROM anuncios WHERE status = 'ativo') as anuncios_ativos,
  (SELECT COUNT(*) FROM anuncios WHERE tipo = 'perdido' AND status = 'ativo') as perdidos_ativos,
  (SELECT COUNT(*) FROM anuncios WHERE tipo = 'encontrado' AND status = 'ativo') as encontrados_ativos,
  (SELECT COUNT(*) FROM anuncios WHERE tipo = 'doacao' AND status = 'ativo') as doacoes_ativas,
  (SELECT COUNT(*) FROM anuncios WHERE status = 'resolvido') as casos_resolvidos,
  (SELECT COALESCE(SUM(valor), 0) FROM doacoes WHERE status = 'aprovada') as total_doacoes,
  (SELECT COALESCE(SUM(valor), 0) FROM doacoes 
   WHERE status = 'aprovada' AND MONTH(data_doacao) = MONTH(CURDATE())) as doacoes_mes_atual;

-- ═══════════════════════════════════════════════
-- TRIGGERS
-- ═══════════════════════════════════════════════

-- Atualizar contagem de visualizações
DELIMITER //
CREATE TRIGGER `trg_before_insert_anuncio` 
BEFORE INSERT ON `anuncios`
FOR EACH ROW
BEGIN
  SET NEW.data_expiracao = DATE_ADD(NEW.data_publicacao, INTERVAL 180 DAY);
END//
DELIMITER ;

-- ═══════════════════════════════════════════════
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ═══════════════════════════════════════════════

-- Índice composto para busca rápida
ALTER TABLE `anuncios` 
  ADD FULLTEXT INDEX `idx_fulltext_busca` (`nome_pet`, `raca`, `cor`, `descricao`);

-- ═══════════════════════════════════════════════
-- FIM DO SCHEMA
-- ═══════════════════════════════════════════════