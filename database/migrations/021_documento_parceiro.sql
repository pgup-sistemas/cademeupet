-- ═══════════════════════════════════════════════════════
-- Migration 021: Documento legal (CPF/CNPJ) do parceiro
-- Ate aqui o "perfil da empresa" nao tinha nenhuma identidade juridica
-- registrada -- so nome fantasia + conta pessoal de quem cadastrou.
-- ═══════════════════════════════════════════════════════

USE `cademeupet`;

ALTER TABLE `parceiro_inscricoes`
  ADD COLUMN IF NOT EXISTS `tipo_documento` enum('cpf','cnpj') NULL DEFAULT NULL AFTER `nome_fantasia`,
  ADD COLUMN IF NOT EXISTS `numero_documento` varchar(20) NULL DEFAULT NULL AFTER `tipo_documento`;

ALTER TABLE `parceiro_perfis`
  ADD COLUMN IF NOT EXISTS `tipo_documento` enum('cpf','cnpj') NULL DEFAULT NULL AFTER `nome_fantasia`,
  ADD COLUMN IF NOT EXISTS `numero_documento` varchar(20) NULL DEFAULT NULL AFTER `tipo_documento`;

ALTER TABLE `parceiro_perfis`
  ADD UNIQUE KEY IF NOT EXISTS `uq_documento` (`tipo_documento`, `numero_documento`);
