# Auditoria do Banco de Dados — Cadê Meu Pet?
> Auditado em: 2026-06-17 | Arquivo: database/schema.sql

## Resultado Geral: APROVADO COM OBSERVAÇÕES

---

## Chaves Primárias
**Status: OK**

Todas as tabelas têm PRIMARY KEY definida:
- `usuarios` → `id` AUTO_INCREMENT
- `parceiro_inscricoes` → `id` AUTO_INCREMENT
- `parceiro_perfis` → `id` AUTO_INCREMENT
- `parceiro_assinaturas` → `id` AUTO_INCREMENT
- `parceiro_pagamentos` → `id` AUTO_INCREMENT
- `anuncios` → `id` AUTO_INCREMENT
- `fotos_anuncios` → `id` AUTO_INCREMENT
- `doacoes` → `id` AUTO_INCREMENT
- `metas_financeiras` → `id` AUTO_INCREMENT
- `favoritos` → `id` AUTO_INCREMENT
- `alertas` → `id` AUTO_INCREMENT
- `denuncias` → `id` AUTO_INCREMENT
- `auditoria` → `id` AUTO_INCREMENT

---

## Foreign Keys com ON DELETE / ON UPDATE
**Status: OK**

Todas as FKs têm cláusula ON DELETE declarada:

| Tabela | FK | Referência | ON DELETE |
|---|---|---|---|
| parceiro_inscricoes | usuario_id | usuarios.id | CASCADE |
| parceiro_inscricoes | analisada_por | usuarios.id | SET NULL |
| parceiro_perfis | usuario_id | usuarios.id | CASCADE |
| parceiro_assinaturas | usuario_id | usuarios.id | CASCADE |
| parceiro_pagamentos | usuario_id | usuarios.id | CASCADE |
| parceiro_pagamentos | aprovado_por | usuarios.id | SET NULL |
| anuncios | usuario_id | usuarios.id | CASCADE |
| fotos_anuncios | anuncio_id | anuncios.id | CASCADE |
| doacoes | usuario_id | usuarios.id | SET NULL |
| favoritos | usuario_id | usuarios.id | CASCADE |
| favoritos | anuncio_id | anuncios.id | CASCADE |
| alertas | usuario_id | usuarios.id | CASCADE |
| denuncias | anuncio_id | anuncios.id | CASCADE |
| denuncias | usuario_id | usuarios.id | SET NULL |

**Observação:** nenhuma FK declara ON UPDATE. Para INT(11) com AUTO_INCREMENT o padrão RESTRICT é aceitável, mas recomenda-se adicionar ON UPDATE CASCADE para robustez futura.

---

## Índices para colunas de busca/JOIN frequentes
**Status: OK**

A tabela `anuncios` é a mais consultada e possui índices abrangentes:
- `idx_usuario` (usuario_id)
- `idx_tipo` (tipo)
- `idx_especie` (especie)
- `idx_cidade` (cidade)
- `idx_status` (status)
- `idx_data_pub` (data_publicacao)
- `idx_localizacao` (latitude, longitude)
- `idx_busca` composto: (tipo, especie, cidade, status)
- `FULLTEXT idx_fulltext_busca` (nome_pet, raca, cor, descricao)

---

## Charset e Collation
**Status: OK**

- Banco criado com `utf8mb4` / `utf8mb4_unicode_ci`
- Todas as tabelas usam `utf8mb4` / `utf8mb4_unicode_ci`
- Consistência total.

---

## Campos de data/hora
**Status: OK**

- `data_cadastro`, `data_publicacao`, `data_doacao`: TIMESTAMP com DEFAULT CURRENT_TIMESTAMP — correto.
- `data_atualizacao`: TIMESTAMP com ON UPDATE CURRENT_TIMESTAMP — correto.
- `bloqueado_ate`, `token_expira`, `aprovada_em`, `resolvido_em`: DATETIME — correto para datas com contexto de negócio.
- `data_expiracao`, `pago_ate`, `proxima_cobranca`: DATE — correto para datas sem hora.

---

## Observações e pendências (para Fase 2)

| Item | Situação |
|---|---|
| Nome do banco | Ainda `petfinder` — será renomeado para `cademeupet` na Fase 2 |
| Admin no schema | E-mail `admin@petfinder.com` — será atualizado na Fase 2 |
| Migration 003 | Pendente: tabelas `petlove_pets`, `petlove_fotos`, `petlove_interesses` (Fase 5) |
| Migration 004 | Já existe `latitude`/`longitude` na tabela `anuncios` — verificar campos faltantes |
| Migration 005 | Pendente: tabela `alertas_busca` (Fase 7) |
| Migration 006 | Parcialmente implementada: `status` e `resolvido_em` já existem em `anuncios`. Faltam: `historia_reuniao`, `slug` |
| `cancelamento_logs` | Migration 004_add_cancelamento_logs.sql existe mas não está no schema principal |
