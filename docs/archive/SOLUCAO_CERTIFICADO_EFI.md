# ⚠️ SOLUÇÃO: Erro no Certificado EFI

## Problema Encontrado
```
Certificado de homologação inválido para o ambiente escolhido [produção]
```

Isso significa que há uma incompatibilidade entre:
- O certificado (`producao-573055-petfinder.p12`)
- As credenciais EFI configuradas
- O ambiente (produção/sandbox)

---

## ✅ SOLUÇÃO 1: Configurar Certificado com Senha

Se o certificado `.p12` requer **senha**, você precisa:

### 1.1 Converter o certificado para PEM (se necessário)
```bash
openssl pkcs12 -in producao-573055-petfinder.p12 -out cert.pem -nodes -passin pass:SUASENHA
```

### 1.2 Atualizar config.php
```php
define('EFI_CERTIFICATE_PATH', __DIR__ . '/cert.pem');
```

### 1.3 Incluir a senha no config (se o SDK permitir)
```php
define('EFI_CERTIFICATE_PASSWORD', 'SUASENHA');
```

---

## ✅ SOLUÇÃO 2: Usar Sandbox (Para Testes)

Se você quer **testar sem produção**, use credenciais de SANDBOX:

### 2.1 Acessar Dashboard Sandbox
- URL: https://dashboard-sandbox.efipay.com.br
- Criar nova conta ou fazer login com teste

### 2.2 Obter Credenciais de Teste
1. Copiar `Client_Id_...` de SANDBOX
2. Copiar `Client_Secret_...` de SANDBOX
3. Preencher em config.php

### 2.3 Obter Certificado de Teste
1. Em dashboard sandbox, ir em "Segurança" → "Certificados"
2. Baixar certificado de teste (em PEM ou P12)
3. Salvar em: `/certificado-teste.pem`

### 2.4 Atualizar Config
```php
define('EFI_CLIENT_ID', 'Client_Id_SANDBOX_xxxxx');
define('EFI_CLIENT_SECRET', 'Client_Secret_SANDBOX_xxxxx');
define('EFI_CERTIFICATE_PATH', __DIR__ . '/certificado-teste.pem');
define('EFI_SANDBOX', true);
```

### 2.5 Testar
```bash
php test_doacao_form.php
```

---

## ✅ SOLUÇÃO 3: Verificar Credenciais de Produção

Se você tem **credenciais de produção genuínas**:

### 3.1 Validar Correspondência
1. Acessar: https://dashboard.efipay.com.br
2. Login com suas credenciais
3. Verificar se `Client_Id_...` e `Client_Secret_...` correspondem à conta
4. Ir em "Segurança" → "Certificados"
5. **Baixar o certificado CORRETO** para sua conta

### 3.2 Atualizar Certificado
```php
define('EFI_CERTIFICATE_PATH', __DIR__ . '/certificado-producao-correto.pem');
define('EFI_SANDBOX', false);
```

### 3.3 Testar com Valor Baixo
```bash
# Testar com R$ 2.00 (mínimo permitido)
# Ir para: https://petfinder.pageup.net.br/doar
# Selecionar PIX + R$ 2.00
```

---

## 📋 Checklist de Resolução

- [ ] Identificar se você tem credenciais de PRODUÇÃO ou SANDBOX
- [ ] Acessar o dashboard EFI correspondente
- [ ] Baixar o certificado CORRETO para sua conta
- [ ] Se P12, converter para PEM (se necessário)
- [ ] Atualizar caminhos em config.php
- [ ] Testar com `php test_doacao_form.php`
- [ ] Se OK, testar no navegador: https://petfinder.pageup.net.br/doar

---

## 🆘 Se Nada Funcionar

### Opção A: Usar EFI Sandbox Público (Testes)
```php
define('EFI_SANDBOX', true);
define('EFI_CERTIFICATE_PATH', __DIR__ . '/sandbox-cert.pem');
```
**Nota:** Sem credenciais de sandbox, o sistema usará um fallback simulado.

### Opção B: Verificar Logs
```bash
# Ver erros detalhados
tail -f /includes/petfinder_error_log
```

### Opção C: Contatar Suporte EFI
- Site: https://efipay.com.br
- Suporte: https://api.efipay.com.br/doc
- Email: contato@efipay.com.br

---

## 📞 Resumo das Ações Necessárias

1. **IMPORTANTE:** Determine qual ambiente você está usando (PRODUÇÃO ou SANDBOX)
2. **ACESSE** o dashboard correspondente
3. **OBTENHA** as credenciais e certificado CORRETOS para aquele ambiente
4. **CONFIGURE** em config.php
5. **TESTE** com `php test_doacao_form.php`

Assim que fizer isso, o sistema funcionará normalmente!

