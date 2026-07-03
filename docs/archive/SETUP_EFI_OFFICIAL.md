# PetFinder - Guia de Configuração EFI Bank (Conforme Documentação Oficial)

## 📋 Resumo das Mudanças

Baseado na análise da **documentação oficial EFI** (https://dev.efipay.com.br/docs/api-pix/credenciais/), foram identificados os seguintes ajustes necessários:

### ✅ Problemas Corrigidos

1. **URL Base Incorreta**
   - ❌ Antes: `https://api.efipay.com.br/api/`
   - ✅ Agora: `https://pix.api.efipay.com.br` (Produção)
   - ✅ Agora: `https://pix-h.api.efipay.com.br` (Homologação)

2. **Formato de Certificado**
   - ❌ Antes: Usando `.p12` diretamente
   - ✅ Agora: Convertido para `.pem` (conforme documentação oficial)

3. **Autenticação OAuth2**
   - ✅ Implementado conforme padrão: HTTP Basic Auth + certificado

4. **Validação de Credenciais**
   - ✅ Criado teste automatizado de OAuth2

---

## 🚀 Passo a Passo para Implementar

### PASSO 1: Converter Certificado P12 para PEM

O EFI requer certificado em formato **PEM**. Se você tem um arquivo `.p12`, execute:

```bash
cd c:\xampp\htdocs\petfinder

# Opção 1: Usar script PHP (recomendado no Windows)
php convert_certificate.php

# Opção 2: Usar OpenSSL direto (se tiver instalado)
openssl pkcs12 -in producao-573055-petfinder.p12 -out producao-573055-petfinder.pem -nodes -password pass:""
```

**Resultado esperado:**
- Arquivo `producao-573055-petfinder.pem` criado na raiz do projeto
- Arquivo contém certificado + chave privada
- Tamanho deve ser similar ao arquivo P12

---

### PASSO 2: Verificar Arquivo config.php

O arquivo `config.php` foi atualizado para:

```php
// ✅ Agora aponta para .pem
define('EFI_CERTIFICATE_PATH', __DIR__ . '/producao-573055-petfinder.pem');

// ✅ URLs corretas conforme documentação
define('EFI_BASE_URL', EFI_SANDBOX === true 
    ? 'https://pix-h.api.efipay.com.br'      // Homologação (testes)
    : 'https://pix.api.efipay.com.br'         // Produção
);
```

---

### PASSO 3: Testar Autorização OAuth2

Execute o script de teste para verificar se a autorização funciona:

```bash
php test_oauth_authorization.php
```

**O que esperar:**
```
═══════════════════════════════════════════════════════════════════════════
✓ AUTORIZAÇÃO OAUTH2 CONCLUÍDA COM SUCESSO!
═══════════════════════════════════════════════════════════════════════════

Detalhes do Token:
- Tipo: Bearer
- Validade: 3600 segundos (1 horas)
- Token: eyJ0eXAiOiJKV1QiLCJhbGc...
- Escopos: cob.read cob.write pix.read pix.write webhook.read webhook.write
```

Se receber erro, verifique:
- [ ] Certificado `.pem` existe
- [ ] `EFI_CLIENT_ID` está correto
- [ ] `EFI_CLIENT_SECRET` está correto
- [ ] `EFI_SANDBOX` está configurado corretamente

---

### PASSO 4: Validar Credenciais na Dashboard EFI

Acesse sua conta EFI e verifique:

1. **Client_Id e Client_Secret:**
   - Vão em: Dashboard → Credenciais → API → OAuth2
   - Copie exatamente como está

2. **Certificado:**
   - Vão em: Dashboard → Certificados → Download
   - Baixar em formato `.p12`
   - Colocar na raiz do projeto
   - Converter para `.pem` usando script acima

3. **Escopos:**
   - Verifique se estão habilitados:
     - ✅ cob.read
     - ✅ cob.write
     - ✅ pix.read
     - ✅ pix.write
     - ✅ webhook.read
     - ✅ webhook.write

4. **Webhook:**
   - Registre a URL: `https://petfinder.pageup.net.br/api/efi-webhook.php`
   - Tipo: PIX
   - Eventos: cobranças

---

## 📚 Documentação Oficial EFI

Todos os ajustes foram baseados em:
- **Credenciais**: https://dev.efipay.com.br/docs/api-pix/credenciais/
- **API PIX**: https://dev.efipay.com.br/docs/api-pix/
- **Autenticação**: https://dev.efipay.com.br/docs/api-pix/autenticacao/

### Key Points da Documentação:

#### OAuth2 Flow
```
POST https://pix.api.efipay.com.br/oauth/token

Headers:
  Authorization: Basic base64(Client_Id:Client_Secret)
  Content-Type: application/json

Body:
{
  "grant_type": "client_credentials"
}

Response:
{
  "access_token": "...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "scope": "cob.read cob.write pix.read pix.write webhook.read webhook.write"
}
```

#### Certificado em Requisições
```
CURL_OPT:
  CURLOPT_SSLCERT => "/path/to/certificado.pem"
  CURLOPT_SSLCERTTYPE => "PEM"
  CURLOPT_SSLCERTPASSWD => ""  // Vazio conforme EFI
```

#### Endpoints Principais
- **Produção**: `https://pix.api.efipay.com.br`
- **Homologação**: `https://pix-h.api.efipay.com.br`

---

## Split de Pagamento (Opcional)

A Efipay suporta *split* (repasses) para algumas operações de cobrança. Para habilitar em PetFinder, configure as variáveis de ambiente:

- `EFI_SPLIT_ENABLED=true`
- `EFI_SPLIT_RULES_JSON` contendo JSON com as regras (ex.: `[{"recipient_id":"12345","percentage":50},{"recipient_id":"67890","percentage":50}]`)

Observações:
- Teste em homologação (EFI_SANDBOX=true) antes de ativar em produção.
- A estrutura exata de `recipient_id` e campos aceitos depende da sua conta Efipay; adapte o JSON conforme especificado no Dashboard/API.

**Atenção**: Execute a migração para criar a coluna e a tabela necessárias antes de usar split em produção:

```bash
php migrate_add_pix_split.php
```



---

## 🧪 Testes Recomendados

### Teste 1: OAuth2 (Já implementado)
```bash
php test_oauth_authorization.php
```

### Teste 2: Fluxo Completo PIX
1. Acesse: `/novo-anuncio` (para gerar doação de teste)
2. Selecione "PIX" como método
3. Insira valor (mínimo R$ 2,00)
4. Escaneie QR Code com qualquer app de pagamento
5. Verifique se status muda para "aprovado"

### Teste 3: Fluxo de Cartão
1. Acesse: `/novo-anuncio`
2. Selecione "Cartão de Crédito"
3. Complete checkout
4. Verifique se webhook retorna confirmação

---

## 🔒 Segurança

### ⚠️ IMPORTANTE

1. **Não versione o certificado**
   ```bash
   # Adicione ao .gitignore
   *.p12
   *.pem
   *.key
   ```

2. **Proteja as credenciais**
   - Não exponha Client_Id/Secret
   - Use .env em produção
   - Rotacionepicamente

3. **HTTPS obrigatório**
   - Todos os webhooks devem ser HTTPS
   - Certificado SSL válido para o domínio

4. **Validação de Webhook**
   - Verifique assinatura do webhook antes de processar
   - Token de segurança em headers

---

## ✅ Checklist Pré-Produção

- [ ] Certificado `.pem` convertido e presente na raiz
- [ ] `test_oauth_authorization.php` retorna sucesso
- [ ] Credenciais corretas em `config.php`
- [ ] `EFI_SANDBOX = false` para produção
- [ ] Webhook configurado na dashboard EFI
- [ ] Testes manuais de PIX funcionando
- [ ] Testes manuais de Cartão funcionando
- [ ] Logs de erro habilitados (`includes/petfinder_error_log`)
- [ ] Email de notificação configurado
- [ ] Certificados SSL/TLS do servidor válidos
- [ ] Firewall permite conexões com `pix.api.efipay.com.br`

---

## 📞 Suporte

Se encontrar problemas:

1. **Erro de certificado:**
   - Verifique se `.pem` foi gerado
   - Teste conversão com: `openssl pkcs12 -info -in arquivo.p12`

2. **Erro OAuth2 401:**
   - Verifique Client_Id e Client_Secret
   - Confirme em: Dashboard EFI → Credenciais

3. **Timeout na API:**
   - Verifique conectividade com EFI
   - Teste com: `ping pix.api.efipay.com.br`
   - Confira firewall/proxy do servidor

4. **Webhook não recebe:**
   - Verifique logs em: `includes/petfinder_error_log`
   - Confirme URL registrada na dashboard
   - Valide certificado SSL do servidor

---

**Última atualização:** 2026-01-12  
**Versão:** 2.0 - Conforme Documentação Oficial EFI
