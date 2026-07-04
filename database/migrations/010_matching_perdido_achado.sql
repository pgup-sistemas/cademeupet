-- ═══════════════════════════════════════════════════════
-- Migration 010: Motor de match automático (perdido ↔ achado)
-- Tabelas: foto_embeddings, anuncio_matches
-- Começa 100% grátis (hash perceptual local via GD, provedor 'phash_local'),
-- com colunas já preparadas para um provedor pago futuro (vetor JSON).
-- Reaplicado em database/migrate_deploy.php (fonte de verdade do deploy)
-- ═══════════════════════════════════════════════════════

USE `cademeupet`;

CREATE TABLE IF NOT EXISTS `foto_embeddings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `foto_id` int(11) NOT NULL,
  `anuncio_id` int(11) NOT NULL,
  `provedor` varchar(30) NOT NULL DEFAULT 'phash_local',
  `status` enum('pendente','processado','falha') NOT NULL DEFAULT 'pendente',
  `hash_perceptual` varchar(64) DEFAULT NULL,
  `vetor` longtext DEFAULT NULL COMMENT 'Reservado para provedor pago futuro (embedding vetorial em JSON)',
  `tentativas` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `erro_mensagem` varchar(500) DEFAULT NULL,
  `processado_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_tentativas` (`status`,`tentativas`),
  KEY `idx_anuncio` (`anuncio_id`),
  CONSTRAINT `fk_embedding_foto` FOREIGN KEY (`foto_id`) REFERENCES `fotos_anuncios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_embedding_anuncio` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `anuncio_matches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `anuncio_perdido_id` int(11) NOT NULL,
  `anuncio_achado_id` int(11) NOT NULL,
  `score_total` decimal(5,2) NOT NULL,
  `score_visual` decimal(5,2) DEFAULT NULL,
  `score_geo` decimal(5,2) DEFAULT NULL,
  `score_atributos` decimal(5,2) DEFAULT NULL,
  `score_tempo` decimal(5,2) DEFAULT NULL,
  `distancia_km` decimal(8,2) DEFAULT NULL,
  `dias_diferenca` int(11) DEFAULT NULL,
  `status` enum('pendente','notificado','confirmado','rejeitado','expirado') NOT NULL DEFAULT 'pendente',
  `conversa_id` int(10) unsigned DEFAULT NULL,
  `confirmado_por_usuario_id` int(11) DEFAULT NULL,
  `notificado_em` datetime DEFAULT NULL,
  `resolvido_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_par` (`anuncio_perdido_id`,`anuncio_achado_id`),
  KEY `idx_status` (`status`),
  KEY `idx_perdido` (`anuncio_perdido_id`),
  KEY `idx_achado` (`anuncio_achado_id`),
  CONSTRAINT `fk_match_perdido` FOREIGN KEY (`anuncio_perdido_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_match_achado` FOREIGN KEY (`anuncio_achado_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_match_conversa` FOREIGN KEY (`conversa_id`) REFERENCES `conversas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `anuncios`
  ADD COLUMN IF NOT EXISTS `matching_processado_em` DATETIME NULL DEFAULT NULL AFTER `moderacao_status`;

ALTER TABLE `anuncios`
  ADD INDEX IF NOT EXISTS `idx_anuncios_matching` (`tipo`, `status`, `matching_processado_em`);

-- conversas.tipo precisa aceitar o novo valor 'match' (chat aberto ao confirmar um match)
ALTER TABLE `conversas`
  MODIFY COLUMN `tipo` enum('anuncio','petlove','triagem','match') NOT NULL DEFAULT 'anuncio';
