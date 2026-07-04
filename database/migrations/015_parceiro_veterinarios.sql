-- ═══════════════════════════════════════════════════════
-- Migration 015: Veterinários habilitados por clínica parceira (CRMV)
-- Ver docs/modulo-atendimento-veterinario-laudo.md (Fase 2)
-- Reaplicado em database/migrate_deploy.php (fonte de verdade do deploy)
-- ═══════════════════════════════════════════════════════

USE `cademeupet`;

CREATE TABLE IF NOT EXISTS `parceiro_veterinarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parceiro_perfil_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome_completo` varchar(150) NOT NULL,
  `crmv_numero` varchar(20) NOT NULL,
  `crmv_uf` char(2) NOT NULL,
  `status` enum('pendente_validacao','aprovado','rejeitado','suspenso') NOT NULL DEFAULT 'pendente_validacao',
  `validado_por` int(11) DEFAULT NULL,
  `validado_em` datetime DEFAULT NULL,
  `motivo_rejeicao` varchar(500) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_crmv` (`crmv_numero`,`crmv_uf`),
  KEY `idx_status` (`status`),
  KEY `idx_parceiro` (`parceiro_perfil_id`),
  CONSTRAINT `fk_vet_parceiro` FOREIGN KEY (`parceiro_perfil_id`) REFERENCES `parceiro_perfis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vet_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vet_validado_por` FOREIGN KEY (`validado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
