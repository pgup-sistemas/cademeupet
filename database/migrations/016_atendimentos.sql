-- ═══════════════════════════════════════════════════════
-- Migration 016: Atendimentos veterinários presenciais
-- Ver docs/modulo-atendimento-veterinario-laudo.md (Fase 2)
-- Reaplicado em database/migrate_deploy.php (fonte de verdade do deploy)
-- ═══════════════════════════════════════════════════════

USE `cademeupet`;

CREATE TABLE IF NOT EXISTS `atendimentos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pet_id` int(10) unsigned NOT NULL,
  `parceiro_perfil_id` int(11) NOT NULL,
  `veterinario_id` int(10) unsigned NOT NULL,
  `triagem_solicitacao_id` int(10) unsigned DEFAULT NULL COMMENT 'preenchido se o atendimento veio de uma triagem anterior',
  `motivo_consulta` varchar(255) NOT NULL,
  `anamnese` text DEFAULT NULL,
  `peso_kg` decimal(5,2) DEFAULT NULL,
  `temperatura_c` decimal(4,1) DEFAULT NULL,
  `frequencia_cardiaca_bpm` smallint(5) unsigned DEFAULT NULL,
  `frequencia_respiratoria_mpm` smallint(5) unsigned DEFAULT NULL,
  `mucosas` varchar(100) DEFAULT NULL,
  `grau_hidratacao` varchar(50) DEFAULT NULL,
  `exame_fisico` text DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `conduta` text DEFAULT NULL,
  `vacinas_aplicadas` text DEFAULT NULL COMMENT 'JSON: [{nome, data, lote}]',
  `medicamentos_prescritos` text DEFAULT NULL,
  `proxima_consulta_recomendada` date DEFAULT NULL,
  `status` enum('em_andamento','finalizado','cancelado') NOT NULL DEFAULT 'em_andamento',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finalizado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pet` (`pet_id`),
  KEY `idx_parceiro` (`parceiro_perfil_id`),
  KEY `idx_veterinario` (`veterinario_id`),
  CONSTRAINT `fk_atendimento_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_atendimento_parceiro` FOREIGN KEY (`parceiro_perfil_id`) REFERENCES `parceiro_perfis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_atendimento_veterinario` FOREIGN KEY (`veterinario_id`) REFERENCES `parceiro_veterinarios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_atendimento_triagem` FOREIGN KEY (`triagem_solicitacao_id`) REFERENCES `triagem_solicitacoes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
