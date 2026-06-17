-- ═══════════════════════════════════════════════
-- MIGRATION 003 — MÓDULO PET LOVE
-- Tabelas: petlove_pets, petlove_fotos, petlove_interesses
-- Data: Junho 2026
-- ═══════════════════════════════════════════════

USE `cademeupet`;

-- Perfis de pets para cruzamento/acasalamento
CREATE TABLE IF NOT EXISTS `petlove_pets` (
  `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`            INT(11) NOT NULL,
  `nome`                  VARCHAR(100) NOT NULL,
  `especie`               ENUM('cachorro','gato','outro') NOT NULL DEFAULT 'cachorro',
  `raca`                  VARCHAR(100) NOT NULL,
  `porte`                 ENUM('mini','pequeno','medio','grande','gigante') NOT NULL,
  `sexo`                  ENUM('macho','femea') NOT NULL,
  `idade_meses`           SMALLINT UNSIGNED NOT NULL,
  `cor`                   VARCHAR(50) NULL,
  `peso_kg`               DECIMAL(5,2) NULL,
  `tem_pedigree`          TINYINT(1) NOT NULL DEFAULT 0,
  `pedigree_num`          VARCHAR(60) NULL,
  `vacinado`              TINYINT(1) NOT NULL DEFAULT 0,
  `vermifugado`           TINYINT(1) NOT NULL DEFAULT 0,
  `castrado`              TINYINT(1) NOT NULL DEFAULT 0,
  `descricao`             TEXT NULL,
  `objetivo`              ENUM('cruzamento','pedigree','companhia') NOT NULL DEFAULT 'cruzamento',
  `latitude`              DECIMAL(10,8) NULL,
  `longitude`             DECIMAL(11,8) NULL,
  `cidade`                VARCHAR(100) NULL,
  `estado`                CHAR(2) NULL,
  `disponivel`            TINYINT(1) NOT NULL DEFAULT 1,
  `criacao_responsavel`   TINYINT(1) NOT NULL DEFAULT 0,
  `status`                ENUM('ativo','pausado','removido') NOT NULL DEFAULT 'ativo',
  `criado_em`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_especie_raca_porte` (`especie`, `raca`(50), `porte`),
  INDEX `idx_sexo` (`sexo`),
  INDEX `idx_status_disponivel` (`status`, `disponivel`),
  INDEX `idx_geo` (`latitude`, `longitude`),
  INDEX `idx_usuario` (`usuario_id`),
  CONSTRAINT `fk_petlove_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fotos dos pets do Pet Love
CREATE TABLE IF NOT EXISTS `petlove_fotos` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `petlove_id`  INT UNSIGNED NOT NULL,
  `caminho`     VARCHAR(255) NOT NULL,
  `principal`   TINYINT(1) NOT NULL DEFAULT 0,
  `ordem`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  INDEX `idx_petlove` (`petlove_id`),
  CONSTRAINT `fk_petlove_fotos` FOREIGN KEY (`petlove_id`)
    REFERENCES `petlove_pets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Manifestações de interesse entre tutores
CREATE TABLE IF NOT EXISTS `petlove_interesses` (
  `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `petlove_id`         INT UNSIGNED NOT NULL,
  `interessado_id`     INT(11) NOT NULL,
  `pet_interessado_id` INT UNSIGNED NULL,
  `mensagem`           TEXT NULL,
  `status`             ENUM('pendente','aceito','recusado') NOT NULL DEFAULT 'pendente',
  `criado_em`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_interesse` (`petlove_id`, `interessado_id`),
  INDEX `idx_interessado` (`interessado_id`),
  INDEX `idx_status` (`status`),
  CONSTRAINT `fk_interesse_petlove` FOREIGN KEY (`petlove_id`)
    REFERENCES `petlove_pets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_interesse_usuario` FOREIGN KEY (`interessado_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_interesse_pet` FOREIGN KEY (`pet_interessado_id`)
    REFERENCES `petlove_pets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
