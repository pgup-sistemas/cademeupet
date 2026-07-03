# Checklist de Deploy em Produção — Cadê Meu Pet?

> Domínio previsto: `cademeupet.pageup.net.br` (subdomínio de `pageup.net.br`).
> Se mudar para outro domínio, ajuste `BASE_URL` em `config.php` e todas as URLs deste checklist.

## 1. Domínio e servidor

- [ ] Subdomínio/domínio apontado para o servidor de hospedagem
- [ ] SSL/HTTPS ativo (Let's Encrypt ou da hospedagem) — `config.php` força cookies `secure` em produção, sem HTTPS o login quebra
- [ ] PHP ≥ 7.2 (testado em 8.2) com extensões: `pdo_mysql`, `curl`, `mbstring`, `openssl`, `gd`, `zip`
- [ ] Apache com `mod_rewrite` habilitado

## 2. Banco de dados (MySQL)

- [ ] Criar banco `cademeupet` + usuário/senha dedicados (não reaproveitar o antigo `petfinder`/`petfinder.mysql.dbaas.com.br`)
- [ ] Rodar nesta ordem:
  1. `database/schema.sql`
  2. `database/migrate_deploy.php`
  3. `database/initial_data_clean.sql`
- [ ] Trocar a senha do admin inicial:
  ```
  php -r "echo password_hash('SUA_SENHA_AQUI', PASSWORD_BCRYPT, ['cost'=>12]);"
  ```
  ```sql
  UPDATE usuarios SET senha='<hash>' WHERE email='admin@cademeupet.com.br';
  ```

## 3. Arquivo `.env` (nunca commitar; usar `.env.example` como base)

- [ ] `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- [ ] `EFI_CLIENT_ID`, `EFI_CLIENT_SECRET`, `EFI_PIX_KEY`, `EFI_WEBHOOK_TOKEN`, `EFI_BILLING_WEBHOOK_TOKEN`
- [ ] `EFI_CERTIFICATE_PATH` (setar explicitamente — não depender do default em `config.php`), `EFI_CERTIFICATE_PASSWORD`
- [ ] `RESEND_API_KEY`, `EMAIL_FROM`, `EMAIL_FROM_NAME`
- [ ] `GOOGLE_MAPS_API_KEY`
- [ ] `DEFAULT_ADMIN_EMAIL`

## 4. Efí Bank (PIX/pagamentos)

- [ ] Se reaproveitar CNPJ/conta: atualizar URLs de webhook no painel Efí para
  `https://cademeupet.pageup.net.br/api/efi-webhook.php` e `.../efi-billing-notification.php`
- [ ] Subir o certificado (`.p12`/`.pem`) via FileZilla para `secrets/` no servidor novo
- [ ] Confirmar `EFI_CERTIFICATE_PATH` no `.env` aponta para o arquivo certo (o nome de arquivo antigo era `producao-573055-petfinder.pem`; não depender mais desse nome)
- [ ] Se for CNPJ/conta diferente: criar aplicação nova na Efí do zero

## 5. Resend (e-mail transacional)

- [ ] Verificar domínio novo em resend.com (gera SPF/DKIM/DMARC)
- [ ] Adicionar os registros gerados no DNS do domínio novo
- [ ] Gerar API key nova → `RESEND_API_KEY`
- [ ] `EMAIL_FROM` deve ser um endereço `@<domínio-novo>` verificado no Resend

## 6. Google Maps API

- [ ] Adicionar o domínio novo nas restrições de referrer no Google Cloud Console

## 7. Estrutura de pastas e permissões (após subir via FileZilla)

- [ ] `uploads/` e `uploads/tmp/anuncios/` com permissão de escrita
- [ ] `cache/` com permissão de escrita
- [ ] `logs/` com permissão de escrita
- [ ] `secrets/` — confirmar que o `.htaccess` bloqueia acesso HTTP direto

## 8. Cron jobs

- [ ] `scripts/expire_ads.php` — expira anúncios vencidos (diário)
- [ ] `scripts/process_alerts.php` ou `cron/disparar-alertas.php` — alertas de pets compatíveis (a cada 6h)
- [ ] `scripts/resync_pending_doacoes.php` — resincroniza doações PIX pendentes com a Efí

## 9. Composer

- [ ] Rodar `composer install --no-dev` no servidor (ou subir `vendor/` inteiro via FileZilla)

## 10. O que NÃO subir para produção

- [ ] Pasta `dev/` inteira (scripts de debug com credenciais de teste hardcoded)
- [ ] Pasta `logs/` com dados reais de doações/acessos (subir vazia, só com `.htaccess`)
- [ ] `.env` local, `.git/`
- [ ] `docs/archive/` (documentação histórica do projeto antigo — opcional, não afeta funcionamento)

## 11. Testes antes de liberar

- [ ] `php -l` em todos os arquivos PHP sem erro de sintaxe
- [ ] `php tests/test_runner.php` — todos os testes passando
- [ ] Smoke test manual: home, busca, login, cadastro, doação PIX, painel admin, parceiros
- [ ] Testar 1 doação PIX real de valor baixo em produção (sandbox não reflete 100% o ambiente real da Efí)
- [ ] Testar recebimento de e-mail (cadastro, recuperação de senha, alerta)

---

**Nota de segurança:** o histórico do git ainda contém versões antigas de `dev/` e `logs/`
com credenciais de teste hardcoded (ex.: `admin@petfinder.com`, senhas de teste). Isso não afeta
produção, mas se o repositório for público ou compartilhado, considere reescrever o histórico
(`git filter-repo`) ou rotacionar qualquer credencial real que possa ter vazado.
