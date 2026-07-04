-- ═══════════════════════════════════════════════════════
-- Migration 017: Laudo veterinário assinado (vínculo fino entre
-- atendimento e o núcleo genérico de documento assinável)
-- Ver docs/modulo-atendimento-veterinario-laudo.md (Fase 3)
-- Reaplicado em database/migrate_deploy.php (fonte de verdade do deploy)
-- ═══════════════════════════════════════════════════════

USE `cademeupet`;

CREATE TABLE IF NOT EXISTS `laudos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `atendimento_id` int(10) unsigned NOT NULL,
  `documento_id` int(10) unsigned NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_atendimento_documento` (`atendimento_id`,`documento_id`),
  KEY `idx_atendimento` (`atendimento_id`),
  CONSTRAINT `fk_laudo_atendimento` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_laudo_documento` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Retificação: um documento novo pode referenciar o anterior que corrigiu
-- (documentos assinados são imutáveis — correção sempre gera um novo registro).
ALTER TABLE `documentos`
  ADD COLUMN IF NOT EXISTS `retifica_documento_id` int(10) unsigned NULL DEFAULT NULL AFTER `criado_por_usuario_id`;

ALTER TABLE `documentos`
  ADD CONSTRAINT `fk_documento_retifica` FOREIGN KEY (`retifica_documento_id`) REFERENCES `documentos` (`id`) ON DELETE SET NULL;
