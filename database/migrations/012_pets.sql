-- ═══════════════════════════════════════════════════════
-- Migration 012: Ficha permanente do pet (fundação do módulo de
-- Atendimento Veterinário / Laudo / Termo de Adoção)
-- Ver docs/modulo-atendimento-veterinario-laudo.md
-- Reaplicado em database/migrate_deploy.php (fonte de verdade do deploy)
-- ═══════════════════════════════════════════════════════

USE `cademeupet`;

CREATE TABLE IF NOT EXISTS `pets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tutor_usuario_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `especie` varchar(30) NOT NULL,
  `raca` varchar(100) DEFAULT NULL,
  `sexo` enum('macho','femea') DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `idade_aproximada_meses` smallint(5) unsigned DEFAULT NULL COMMENT 'usado quando nao se sabe a data exata',
  `cor` varchar(50) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `microchip_numero` varchar(50) DEFAULT NULL,
  `origem_anuncio_id` int(11) DEFAULT NULL COMMENT 'preenchido se o pet veio de um anuncio de doacao resolvido',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tutor` (`tutor_usuario_id`),
  CONSTRAINT `fk_pet_tutor` FOREIGN KEY (`tutor_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pet_origem_anuncio` FOREIGN KEY (`origem_anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
