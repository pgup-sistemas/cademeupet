-- ═══════════════════════════════════════════════════════
-- Migration 019: Pedido de exames como documento assinável
-- Ver docs/modulo-atendimento-veterinario-laudo.md
-- Reaplicado em database/migrate_deploy.php (fonte de verdade do deploy)
-- ═══════════════════════════════════════════════════════

USE `cademeupet`;

ALTER TABLE `documentos`
  MODIFY COLUMN `tipo` enum('laudo','atestado','receituario','pedido_exame','termo_adocao','termo_responsabilidade') NOT NULL;
