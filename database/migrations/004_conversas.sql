-- ═══════════════════════════════════════════════
-- MIGRATION 004 — CHAT INTERNO (CONVERSAS)
-- Tabelas: conversas, conversa_mensagens, depoimentos
-- Reaplicado em database/migrate_deploy.php (fonte de verdade do deploy)
-- ═══════════════════════════════════════════════

USE `cademeupet`;

-- Uma conversa por par (anúncio ou pet do Pet Love) x usuário interessado
CREATE TABLE IF NOT EXISTS `conversas` (
  `id`                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tipo`                    ENUM('anuncio','petlove') NOT NULL DEFAULT 'anuncio',
  `referencia_id`           INT UNSIGNED NOT NULL,
  `usuario_dono_id`         INT(11) NOT NULL,
  `usuario_interessado_id`  INT(11) NOT NULL,
  `status`                  ENUM('aberta','resolvida','arquivada') NOT NULL DEFAULT 'aberta',
  `criado_em`               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultima_mensagem_em`      DATETIME NULL,
  UNIQUE KEY `uniq_conversa` (`tipo`, `referencia_id`, `usuario_interessado_id`),
  INDEX `idx_dono` (`usuario_dono_id`),
  INDEX `idx_interessado` (`usuario_interessado_id`),
  INDEX `idx_referencia` (`tipo`, `referencia_id`),
  CONSTRAINT `fk_conversa_dono` FOREIGN KEY (`usuario_dono_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conversa_interessado` FOREIGN KEY (`usuario_interessado_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mensagens de cada conversa (texto, foto anexada ou localização compartilhada)
CREATE TABLE IF NOT EXISTS `conversa_mensagens` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `conversa_id`  INT UNSIGNED NOT NULL,
  `remetente_id` INT(11) NOT NULL,
  `mensagem`     TEXT NOT NULL,
  `tipo`         ENUM('texto','imagem','localizacao') NOT NULL DEFAULT 'texto',
  `arquivo`      VARCHAR(255) NULL,
  `latitude`     DECIMAL(10,8) NULL,
  `longitude`    DECIMAL(11,8) NULL,
  `lida`         TINYINT(1) NOT NULL DEFAULT 0,
  `criado_em`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_conversa_data` (`conversa_id`, `criado_em`),
  INDEX `idx_remetente` (`remetente_id`),
  CONSTRAINT `fk_mensagem_conversa` FOREIGN KEY (`conversa_id`)
    REFERENCES `conversas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mensagem_remetente` FOREIGN KEY (`remetente_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Depoimentos de reencontro/adoção — moderados antes de aparecerem em público
CREATE TABLE IF NOT EXISTS `depoimentos` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `conversa_id` INT UNSIGNED NULL,
  `usuario_id`  INT(11) NOT NULL,
  `anuncio_id`  INT(11) NULL,
  `texto`       TEXT NOT NULL,
  `aprovado`    TINYINT(1) NOT NULL DEFAULT 0,
  `criado_em`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_aprovado` (`aprovado`),
  INDEX `idx_anuncio` (`anuncio_id`),
  CONSTRAINT `fk_depoimento_usuario` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_depoimento_conversa` FOREIGN KEY (`conversa_id`)
    REFERENCES `conversas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
