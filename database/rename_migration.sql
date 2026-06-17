-- ═══════════════════════════════════════════════════════
-- MIGRATION: Renomear banco petfinder → cademeupet
-- IMPORTANTE: MySQL 8+ não suporta RENAME DATABASE.
-- Execute estas instruções manualmente no servidor.
-- ═══════════════════════════════════════════════════════

-- Passo 1: Criar novo banco
CREATE DATABASE IF NOT EXISTS `cademeupet`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Passo 2: Copiar cada tabela (executar em sequência)
-- Substitua 'petfinder' pelo nome real do banco de origem se diferente.

CREATE TABLE `cademeupet`.`usuarios`             LIKE `petfinder`.`usuarios`;
INSERT INTO  `cademeupet`.`usuarios`             SELECT * FROM `petfinder`.`usuarios`;

CREATE TABLE `cademeupet`.`parceiro_inscricoes`  LIKE `petfinder`.`parceiro_inscricoes`;
INSERT INTO  `cademeupet`.`parceiro_inscricoes`  SELECT * FROM `petfinder`.`parceiro_inscricoes`;

CREATE TABLE `cademeupet`.`parceiro_perfis`      LIKE `petfinder`.`parceiro_perfis`;
INSERT INTO  `cademeupet`.`parceiro_perfis`      SELECT * FROM `petfinder`.`parceiro_perfis`;

CREATE TABLE `cademeupet`.`parceiro_assinaturas` LIKE `petfinder`.`parceiro_assinaturas`;
INSERT INTO  `cademeupet`.`parceiro_assinaturas` SELECT * FROM `petfinder`.`parceiro_assinaturas`;

CREATE TABLE `cademeupet`.`parceiro_pagamentos`  LIKE `petfinder`.`parceiro_pagamentos`;
INSERT INTO  `cademeupet`.`parceiro_pagamentos`  SELECT * FROM `petfinder`.`parceiro_pagamentos`;

CREATE TABLE `cademeupet`.`anuncios`             LIKE `petfinder`.`anuncios`;
INSERT INTO  `cademeupet`.`anuncios`             SELECT * FROM `petfinder`.`anuncios`;

CREATE TABLE `cademeupet`.`fotos_anuncios`       LIKE `petfinder`.`fotos_anuncios`;
INSERT INTO  `cademeupet`.`fotos_anuncios`       SELECT * FROM `petfinder`.`fotos_anuncios`;

CREATE TABLE `cademeupet`.`doacoes`              LIKE `petfinder`.`doacoes`;
INSERT INTO  `cademeupet`.`doacoes`              SELECT * FROM `petfinder`.`doacoes`;

CREATE TABLE `cademeupet`.`metas_financeiras`    LIKE `petfinder`.`metas_financeiras`;
INSERT INTO  `cademeupet`.`metas_financeiras`    SELECT * FROM `petfinder`.`metas_financeiras`;

CREATE TABLE `cademeupet`.`favoritos`            LIKE `petfinder`.`favoritos`;
INSERT INTO  `cademeupet`.`favoritos`            SELECT * FROM `petfinder`.`favoritos`;

CREATE TABLE `cademeupet`.`alertas`              LIKE `petfinder`.`alertas`;
INSERT INTO  `cademeupet`.`alertas`              SELECT * FROM `petfinder`.`alertas`;

CREATE TABLE `cademeupet`.`denuncias`            LIKE `petfinder`.`denuncias`;
INSERT INTO  `cademeupet`.`denuncias`            SELECT * FROM `petfinder`.`denuncias`;

CREATE TABLE `cademeupet`.`auditoria`            LIKE `petfinder`.`auditoria`;
INSERT INTO  `cademeupet`.`auditoria`            SELECT * FROM `petfinder`.`auditoria`;

-- Passo 3: Atualizar e-mail do admin
UPDATE `cademeupet`.`usuarios`
SET email = 'admin@cademeupet.com.br'
WHERE email = 'admin@petfinder.com';

-- Passo 4: Verificar integridade
SELECT COUNT(*) AS usuarios    FROM `cademeupet`.`usuarios`;
SELECT COUNT(*) AS anuncios    FROM `cademeupet`.`anuncios`;
SELECT COUNT(*) AS doacoes     FROM `cademeupet`.`doacoes`;

-- Passo 5 (após verificação): Remover banco antigo
-- DROP DATABASE `petfinder`;
