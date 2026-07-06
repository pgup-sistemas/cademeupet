-- ═══════════════════════════════════════════════════════
-- Migration 018: Solicitação de exames no atendimento
-- Ver docs/modulo-atendimento-veterinario-laudo.md
-- Reaplicado em database/migrate_deploy.php (fonte de verdade do deploy)
-- ═══════════════════════════════════════════════════════

USE `cademeupet`;

ALTER TABLE `atendimentos`
  ADD COLUMN IF NOT EXISTS `exames_solicitados` text NULL DEFAULT NULL COMMENT 'JSON: [{nome, observacao}]' AFTER `medicamentos_prescritos`;
