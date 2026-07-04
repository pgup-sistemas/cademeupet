-- ═══════════════════════════════════════════════════════
-- Migration 009: Triagem e direcionamento veterinário emergencial
-- Tabelas: triagem_locais_publicos, triagem_solicitacoes,
--          triagem_arrecadacao_futura (reservada, sem uso no MVP)
-- Reaplicado em database/migrate_deploy.php (fonte de verdade do deploy)
-- ═══════════════════════════════════════════════════════

USE `cademeupet`;

-- Locais de atendimento público/institucional (ex.: clínica municipal)
CREATE TABLE IF NOT EXISTS `triagem_locais_publicos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `estado` char(2) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `horario_funcionamento` varchar(255) DEFAULT NULL,
  `como_funciona_fila` text DEFAULT NULL,
  `requisitos` varchar(500) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cidade_estado` (`cidade`,`estado`),
  KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solicitações de triagem feitas por tutores (login opcional)
CREATE TABLE IF NOT EXISTS `triagem_solicitacoes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `nome_contato` varchar(150) DEFAULT NULL,
  `telefone_contato` varchar(20) DEFAULT NULL,
  `especie` varchar(30) NOT NULL,
  `sintomas` text NOT NULL COMMENT 'JSON: respostas estruturadas do formulario',
  `nivel_urgencia` enum('baixa','moderada','alta','critica') NOT NULL,
  `renda_baixa_declarada` tinyint(1) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `direcionamento_sugerido` enum('publico','parceiro_privado','ambos','emergencia_imediata') NOT NULL,
  `triagem_locais_publicos_id` int(10) unsigned DEFAULT NULL,
  `parceiro_perfil_id` int(11) DEFAULT NULL,
  `conversa_id` int(10) unsigned DEFAULT NULL,
  `status` enum('orientado','em_contato','encerrado','abandonado') NOT NULL DEFAULT 'orientado',
  `disclaimer_aceito` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_parceiro_status` (`parceiro_perfil_id`,`status`),
  KEY `idx_urgencia_data` (`nivel_urgencia`,`criado_em`),
  CONSTRAINT `fk_triagem_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_triagem_local_publico` FOREIGN KEY (`triagem_locais_publicos_id`) REFERENCES `triagem_locais_publicos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_triagem_parceiro` FOREIGN KEY (`parceiro_perfil_id`) REFERENCES `parceiro_perfis` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_triagem_conversa` FOREIGN KEY (`conversa_id`) REFERENCES `conversas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reservada para fase futura de arrecadação vinculada a uma triagem.
-- Sem uso no MVP (nenhuma tela/lógica lê ou escreve aqui ainda).
CREATE TABLE IF NOT EXISTS `triagem_arrecadacao_futura` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `triagem_solicitacao_id` int(10) unsigned NOT NULL,
  `doacao_id` int(11) DEFAULT NULL,
  `valor_estimado_necessario` decimal(10,2) DEFAULT NULL,
  `status` enum('nao_iniciado','arrecadando','concluido') NOT NULL DEFAULT 'nao_iniciado',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_solicitacao` (`triagem_solicitacao_id`),
  CONSTRAINT `fk_arrecadacao_solicitacao` FOREIGN KEY (`triagem_solicitacao_id`) REFERENCES `triagem_solicitacoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_arrecadacao_doacao` FOREIGN KEY (`doacao_id`) REFERENCES `doacoes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- conversas.tipo precisa aceitar o novo valor 'triagem' (chat com clínica parceira)
ALTER TABLE `conversas`
  MODIFY COLUMN `tipo` enum('anuncio','petlove','triagem') NOT NULL DEFAULT 'anuncio';

-- Seed inicial: Clínica de Bem-Estar Animal Municipal (Porto Velho/RO)
INSERT INTO `triagem_locais_publicos`
  (`nome`, `cidade`, `estado`, `endereco`, `horario_funcionamento`, `como_funciona_fila`, `requisitos`, `ativo`)
SELECT * FROM (SELECT
  'Clínica de Bem-Estar Animal Municipal' AS nome,
  'Porto Velho' AS cidade,
  'RO' AS estado,
  'Av. Mamoré, nº 1120, Lagoinha (junto ao CCZ)' AS endereco,
  'Segunda a sexta-feira, das 8h às 17h' AS horario_funcionamento,
  'Distribuição de senhas por volta das 7h30 da manhã, em ordem de chegada. Quantidade diária limitada — recomendado chegar cedo.' AS como_funciona_fila,
  'Famílias de baixa renda (CadÚnico), protetores de animais e pets de rua' AS requisitos,
  1 AS ativo
) AS novo
WHERE NOT EXISTS (
  SELECT 1 FROM `triagem_locais_publicos`
  WHERE `nome` = 'Clínica de Bem-Estar Animal Municipal' AND `cidade` = 'Porto Velho'
);
