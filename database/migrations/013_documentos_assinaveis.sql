-- ═══════════════════════════════════════════════════════
-- Migration 013: Núcleo genérico de documento assinável
-- (reutilizado por laudo veterinário e termo de adoção nas próximas fases)
-- Ver docs/modulo-atendimento-veterinario-laudo.md
-- Reaplicado em database/migrate_deploy.php (fonte de verdade do deploy)
-- ═══════════════════════════════════════════════════════

USE `cademeupet`;

CREATE TABLE IF NOT EXISTS `documentos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipo` enum('laudo','atestado','receituario','termo_adocao','termo_responsabilidade') NOT NULL,
  `referencia_tipo` enum('atendimento','anuncio') NOT NULL,
  `referencia_id` int(10) unsigned NOT NULL,
  `conteudo_html` longtext NOT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `hash_conteudo` char(64) NOT NULL,
  `status` enum('rascunho','aguardando_assinaturas','assinado','revogado') NOT NULL DEFAULT 'rascunho',
  `codigo_verificacao` varchar(20) NOT NULL,
  `criado_por_usuario_id` int(11) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revogado_em` datetime DEFAULT NULL,
  `motivo_revogacao` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_codigo_verificacao` (`codigo_verificacao`),
  KEY `idx_referencia` (`referencia_tipo`,`referencia_id`),
  KEY `idx_tipo_status` (`tipo`,`status`),
  CONSTRAINT `fk_documento_criador` FOREIGN KEY (`criado_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `documento_assinaturas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `documento_id` int(10) unsigned NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `papel` enum('veterinario_autor','adotante_responsavel','doador','testemunha_parceiro') NOT NULL,
  `identificacao_extra` varchar(100) DEFAULT NULL COMMENT 'ex: CRMV 12345-RO no momento da assinatura',
  `hash_no_momento` char(64) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `assinado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_documento` (`documento_id`),
  CONSTRAINT `fk_assinatura_documento` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assinatura_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
