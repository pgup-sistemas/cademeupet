-- Fase 7: funcionalidades adicionais
-- 7.1 — Campos de reunião confirmada em anuncios
ALTER TABLE `anuncios`
    ADD COLUMN IF NOT EXISTS `resolvido_em`     DATETIME     NULL AFTER `data_expiracao`,
    ADD COLUMN IF NOT EXISTS `historia_reuniao` TEXT         NULL AFTER `resolvido_em`;

-- 7.2 — Adicionar filtros de raça e cor em alertas
ALTER TABLE `alertas`
    ADD COLUMN IF NOT EXISTS `raca` VARCHAR(100) NULL AFTER `especie`,
    ADD COLUMN IF NOT EXISTS `cor`  VARCHAR(50)  NULL AFTER `raca`;

-- 7.6 — Moderação básica em anuncios
ALTER TABLE `anuncios`
    ADD COLUMN IF NOT EXISTS `moderacao_status`  ENUM('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'aprovado' AFTER `historia_reuniao`,
    ADD COLUMN IF NOT EXISTS `moderacao_motivo`  VARCHAR(255) NULL AFTER `moderacao_status`,
    ADD INDEX IF NOT EXISTS `idx_moderacao` (`moderacao_status`);
