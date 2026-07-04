-- ═══════════════════════════════════════════════════════
-- Migration 011: API pública para integração com sistemas externos
-- Tabelas: api_consumidores, api_keys, api_rate_limit_janelas,
--          api_requisicoes_log, api_ingestao_animais
-- Acesso por aprovação manual do admin (sem self-service).
-- Reaplicado em database/migrate_deploy.php (fonte de verdade do deploy)
-- ═══════════════════════════════════════════════════════

USE `cademeupet`;

CREATE TABLE IF NOT EXISTS `api_consumidores` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `email_contato` varchar(150) DEFAULT NULL,
  `descricao` varchar(500) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `consumidor_id` int(10) unsigned NOT NULL,
  `chave_hash` char(64) NOT NULL,
  `prefixo` varchar(12) NOT NULL,
  `escopos` set('anuncios_leitura','parceiros_leitura','ingestao_denuncias','ingestao_animais') NOT NULL DEFAULT 'anuncios_leitura,parceiros_leitura',
  `rate_limit_por_minuto` int(10) unsigned NOT NULL DEFAULT 60,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_uso_em` datetime DEFAULT NULL,
  `expira_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revogada_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hash` (`chave_hash`),
  KEY `idx_prefixo` (`prefixo`),
  CONSTRAINT `fk_apikey_consumidor` FOREIGN KEY (`consumidor_id`) REFERENCES `api_consumidores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_rate_limit_janelas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `api_key_id` int(10) unsigned NOT NULL,
  `janela_inicio` datetime NOT NULL,
  `contador` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_key_janela` (`api_key_id`,`janela_inicio`),
  CONSTRAINT `fk_ratelimit_apikey` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_requisicoes_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `api_key_id` int(10) unsigned DEFAULT NULL,
  `endpoint` varchar(150) NOT NULL,
  `metodo` varchar(10) NOT NULL,
  `status_http` smallint(5) unsigned NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `tempo_ms` int(10) unsigned DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_key_data` (`api_key_id`,`criado_em`),
  KEY `idx_data` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_ingestao_animais` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `api_key_id` int(10) unsigned NOT NULL,
  `payload_json` longtext NOT NULL,
  `status` enum('pendente_revisao','aprovado','rejeitado') NOT NULL DEFAULT 'pendente_revisao',
  `anuncio_id` int(11) DEFAULT NULL,
  `revisado_por` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_ingestao_apikey` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ingestao_anuncio` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feature flag para o endpoint de ingestão (desabilitado até haver consumidor real)
INSERT INTO `configuracoes` (`chave`, `valor`, `descricao`)
SELECT * FROM (SELECT 'api_ingestao_animais_ativa' AS chave, '0' AS valor, 'Habilita o endpoint POST /api/v1/ingestao/animais (ingestão de terceiros, ex. prefeitura/CCZ)' AS descricao) AS novo
WHERE NOT EXISTS (SELECT 1 FROM `configuracoes` WHERE `chave` = 'api_ingestao_animais_ativa');
