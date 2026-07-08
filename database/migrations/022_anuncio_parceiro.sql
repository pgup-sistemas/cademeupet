-- ═══════════════════════════════════════════════════════
-- Migration 022: Anúncio vinculado à empresa parceira
-- Ate aqui um parceiro aprovado publicava anuncio de doacao/perdido/
-- encontrado exatamente como qualquer usuario comum -- sem nenhum
-- vinculo com o perfil da empresa (parceiro_perfis).
-- ═══════════════════════════════════════════════════════

USE `cademeupet`;

ALTER TABLE `anuncios`
  ADD COLUMN IF NOT EXISTS `parceiro_perfil_id` int(11) NULL DEFAULT NULL AFTER `usuario_id`;

ALTER TABLE `anuncios`
  ADD KEY IF NOT EXISTS `idx_anuncio_parceiro` (`parceiro_perfil_id`);

ALTER TABLE `anuncios`
  ADD CONSTRAINT IF NOT EXISTS `fk_anuncio_parceiro` FOREIGN KEY (`parceiro_perfil_id`)
    REFERENCES `parceiro_perfis` (`id`) ON DELETE SET NULL;
