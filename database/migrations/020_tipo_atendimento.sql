-- ═══════════════════════════════════════════════════════
-- Migration 020: Tipo de atendimento (consulta/vacinação/exame/retorno)
-- Cada tipo tem seu proprio criterio minimo de finalizacao, evitando
-- forcar o formato de "consulta completa" pra procedimentos rapidos
-- como vacinacao isolada.
-- Ver docs/modulo-atendimento-veterinario-laudo.md
-- Reaplicado em database/migrate_deploy.php (fonte de verdade do deploy)
-- ═══════════════════════════════════════════════════════

USE `cademeupet`;

ALTER TABLE `atendimentos`
  ADD COLUMN IF NOT EXISTS `tipo_atendimento` enum('consulta','vacinacao','exame','retorno') NOT NULL DEFAULT 'consulta' AFTER `motivo_consulta`;
