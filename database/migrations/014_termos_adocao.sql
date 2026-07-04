-- ═══════════════════════════════════════════════════════
-- Migration 014: Termo de Responsabilidade de Adoção/Doação
-- Ver docs/modulo-atendimento-veterinario-laudo.md (Fase 1)
-- Reaplicado em database/migrate_deploy.php (fonte de verdade do deploy)
-- ═══════════════════════════════════════════════════════

USE `cademeupet`;

CREATE TABLE IF NOT EXISTS `termos_adocao` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `anuncio_id` int(11) NOT NULL,
  `pet_id` int(10) unsigned DEFAULT NULL,
  `documento_id` int(10) unsigned NOT NULL,
  `doador_usuario_id` int(11) NOT NULL,
  `adotante_usuario_id` int(11) DEFAULT NULL,
  `adotante_nome_informado` varchar(150) DEFAULT NULL,
  `adotante_telefone_informado` varchar(20) DEFAULT NULL,
  `parceiro_testemunha_id` int(11) DEFAULT NULL,
  `status` enum('aguardando_adotante','assinado','recusado','expirado') NOT NULL DEFAULT 'aguardando_adotante',
  `expira_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_anuncio` (`anuncio_id`),
  KEY `idx_adotante` (`adotante_usuario_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_termo_anuncio` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_termo_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_termo_documento` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_termo_doador` FOREIGN KEY (`doador_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_termo_adotante` FOREIGN KEY (`adotante_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_termo_parceiro` FOREIGN KEY (`parceiro_testemunha_id`) REFERENCES `parceiro_perfis` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
