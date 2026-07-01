-- ═══════════════════════════════════════════════════════
-- Migration 008: Segurança de tokens + índices de performance
-- ═══════════════════════════════════════════════════════

-- Expiração do token de confirmação de e-mail (48 horas)
ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `token_confirmacao_expira` DATETIME NULL DEFAULT NULL
    AFTER `token_confirmacao`;

-- Índice para busca de moderação (admin-moderacao.php + queries de relatório)
ALTER TABLE `anuncios`
  ADD INDEX IF NOT EXISTS `idx_moderacao_status` (`moderacao_status`);

-- Índice para acelerar buscas de alertas elegíveis para disparo
ALTER TABLE `alertas`
  ADD INDEX IF NOT EXISTS `idx_ativo_ultimo_envio` (`ativo`, `ultimo_envio`);

-- Adicionar status 'pausada' em doacoes (assinaturas mensais) + coluna de timestamp
ALTER TABLE `doacoes`
  MODIFY COLUMN `status` enum('pendente','aprovada','cancelada','estornada','pausada')
    NOT NULL DEFAULT 'pendente';

ALTER TABLE `doacoes`
  ADD COLUMN IF NOT EXISTS `pausada_em` DATETIME NULL DEFAULT NULL
    AFTER `cancelada_em`;
