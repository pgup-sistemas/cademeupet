# Auditoria de Segurança — Cadê Meu Pet?
> Auditado em: 2026-06-17

## Resultado Geral: APROVADO COM RESSALVAS

---

## SQL Injection
**Status: OK (código de produção)**

- Todos os controllers e models de produção usam PDO com prepared statements.
- `$pdo->query()` sem parâmetros só ocorre em scripts de migração/manutenção (`create_missing_*.php`, `migrate_*.php`, `fix_*.php`), que operam apenas com DDL (ALTER TABLE, CREATE TABLE) — sem input de usuário.
- Arquivos de risco baixo (scripts administrativos, não acessíveis pelo público):
  - `create_missing_assinaturas.php`, `create_missing_tables.php`, `create_production_tables.php`
  - `database/migrate_cancelamentos.php`, `fix_doacoes_table.php`, `migrate_add_pix_*.php`

**Ação recomendada:** mover esses scripts para `database/migrations/` e restringir acesso via `.htaccess`.

---

## XSS (Cross-Site Scripting)
**Status: OK (proteção adequada)**

- Projeto utiliza função `sanitize()` (wrapper de `htmlspecialchars(ENT_QUOTES, UTF-8)`) em todas as saídas de dados textuais de usuário nas views.
- Campos como `nome_pet`, `descricao`, `raca`, `cidade` passam por `sanitize()` antes de serem exibidos.
- Casos de `echo $var['id']` sem sanitização são seguros pois `id` é inteiro gerado pelo banco.
- Casos de `echo $var['status']` sem sanitização são seguros pois `status` é ENUM limitado do banco.

**Pendência menor:** alguns `echo $var['id']` em campos hidden poderiam usar `(int)$var['id']` para maior clareza semântica, mas não representam risco real.

---

## CSRF
**Status: OK**

- Token CSRF implementado em todos os formulários críticos via `generateCSRFToken()` e `validateCSRFToken()`.
- Verificado em: admin-cancelamentos, admin-financeiro, admin-parceiros, admin-usuarios, alertas.
- Formulários de login e cadastro também possuem proteção CSRF (verificado em includes/auth.php).

---

## Upload de Arquivos
**Status: OK**

- `includes/functions.php` usa `finfo_open(FILEINFO_MIME_TYPE)` para validação real de tipo MIME.
- Validação de extensão permitida via `ALLOWED_EXTENSIONS` (`jpg`, `jpeg`, `png`, `webp`).
- Limite de tamanho via `MAX_FILE_SIZE` (2MB).
- `MAX_PHOTOS_PER_AD` limita quantidade de fotos por anúncio (2).

---

## Senhas
**Status: OK**

- `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])` usado para hash (custo 12 — adequado).
- `password_verify()` usado para verificação.
- Nenhum uso de `md5()` ou `sha1()` para senhas em código de produção.

---

## Sessões
**Status: OK**

- `session_regenerate_id(true)` chamado em `includes/auth.php:153` após login bem-sucedido.

---

## Variáveis de Ambiente / Credenciais
**Status: ATENÇÃO**

- `config.php` **NÃO está no `.gitignore`** — risco de exposição de credenciais no repositório.
- Credenciais de banco de dados e EFI (gateway de pagamento) estão em `config.php` hardcoded.
- `.env` está no `.gitignore` mas o projeto não usa `.env` — usa `config.php` diretamente.

**Ação obrigatória antes do deploy:** adicionar `config.php` ao `.gitignore`.

**Nota sobre renomeação (Fase 2):** `config.php` ainda contém:
- `DB_NAME = 'petfinder'`
- `DB_USER = 'petfinder'`
- `SITE_NAME = 'PetFinder'`
- `BASE_URL = 'https://petfinder.pageup.net.br'`
- `admin@petfinder.com` como e-mail do admin no schema

Esses valores serão atualizados na Fase 2.

---

## Tabela Resumo

| Item | Status | Observação |
|---|---|---|
| SQL Injection | OK | Prepared statements em todo código de produção |
| XSS | OK | `sanitize()` (htmlspecialchars) aplicado consistentemente |
| CSRF | OK | Tokens em todos os formulários críticos |
| Upload | OK | Validação MIME real + extensão + tamanho |
| Senhas | OK | bcrypt com custo 12 |
| Sessões | OK | `session_regenerate_id(true)` após login |
| Credenciais | ATENÇÃO | `config.php` não está no `.gitignore` |
