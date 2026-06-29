# EFI OAuth2 - Diagrama de Autenticação

## Fluxo de Autorização (Conforme Documentação Oficial)

```
┌─────────────────────────────────────────────────────────────────────┐
│                     APLICAÇÃO PETFINDER                             │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  1️⃣  PREPARAR CREDENCIAIS                                            │
│  ┌──────────────────────────────────────────────────────────┐       │
│  │ Client_Id: Client_Id_eb634fb28bc3cf...                 │       │
│  │ Client_Secret: Client_Secret_10e743b7...               │       │
│  │ Certificado: producao-573055-petfinder.pem             │       │
│  │ Grant Type: client_credentials                          │       │
│  └──────────────────────────────────────────────────────────┘       │
│                           ↓                                          │
│  2️⃣  CODIFICAR CREDENCIAIS EM BASE64                                │
│  ┌──────────────────────────────────────────────────────────┐       │
│  │ Basic Auth = base64(Client_Id:Client_Secret)            │       │
│  │ Result: Q2xpZW50X0lkX...:Q2xpZW50X1NlY3JldF8u...       │       │
│  └──────────────────────────────────────────────────────────┘       │
│                           ↓                                          │
│  3️⃣  PREPARAR REQUISIÇÃO HTTP                                       │
│  ┌──────────────────────────────────────────────────────────┐       │
│  │ POST /oauth/token                                        │       │
│  │                                                          │       │
│  │ Headers:                                                │       │
│  │   Authorization: Basic Q2xpZW50X0lkX...:..             │       │
│  │   Content-Type: application/json                        │       │
│  │                                                          │       │
│  │ Body:                                                   │       │
│  │ {                                                       │       │
│  │   "grant_type": "client_credentials"                   │       │
│  │ }                                                       │       │
│  │                                                          │       │
│  │ SSL/TLS Options: (OBRIGATÓRIO)                         │       │
│  │   CURLOPT_SSLCERT: /path/to/certificado.pem           │       │
│  │   CURLOPT_SSLCERTPASSWD: ""                            │       │
│  └──────────────────────────────────────────────────────────┘       │
│                           ↓                                          │
└─────────────────────────────────────────────────────────────────────┘
                            │
                            │ HTTP POST
                            │ (com certificado)
                            ↓
┌─────────────────────────────────────────────────────────────────────┐
│                      SERVIDOR EFI (PRODUÇÃO)                        │
│              https://pix.api.efipay.com.br/oauth/token              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  4️⃣  VALIDAR CREDENCIAIS NO SERVIDOR EFI                            │
│  ┌──────────────────────────────────────────────────────────┐       │
│  │ ✓ Validar Client_Id e Client_Secret                     │       │
│  │ ✓ Validar certificado                                    │       │
│  │ ✓ Verificar escopos habilitados                          │       │
│  │ ✓ Gerar token de acesso                                 │       │
│  └──────────────────────────────────────────────────────────┘       │
│                           ↓                                          │
│  5️⃣  ENVIAR RESPOSTA COM TOKEN                                      │
│  ┌──────────────────────────────────────────────────────────┐       │
│  │ HTTP 200 OK                                              │       │
│  │                                                          │       │
│  │ {                                                       │       │
│  │   "access_token": "eyJ0eXAiOiJKV1Q...",               │       │
│  │   "token_type": "Bearer",                               │       │
│  │   "expires_in": 3600,                                   │       │
│  │   "scope": "cob.read cob.write pix.read pix.write"     │       │
│  │ }                                                       │       │
│  └──────────────────────────────────────────────────────────┘       │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
                            │
                            │ JSON Response
                            │
                            ↓
┌─────────────────────────────────────────────────────────────────────┐
│                     APLICAÇÃO PETFINDER                             │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  6️⃣  ARMAZENAR TOKEN EM CACHE/MEMÓRIA                               │
│  ┌──────────────────────────────────────────────────────────┐       │
│  │ access_token: eyJ0eXAiOiJKV1Q...                        │       │
│  │ expires_in: 3600 segundos (1 hora)                       │       │
│  │ token_type: Bearer                                        │       │
│  └──────────────────────────────────────────────────────────┘       │
│                           ↓                                          │
│  7️⃣  USAR TOKEN EM REQUISIÇÕES SUBSEQUENTES                         │
│  ┌──────────────────────────────────────────────────────────┐       │
│  │ GET /cob (listar cobranças)                             │       │
│  │ POST /cob (criar cobrança)                              │       │
│  │ GET /pix (operações PIX)                                │       │
│  │ ...                                                      │       │
│  │                                                          │       │
│  │ Headers:                                                │       │
│  │   Authorization: Bearer eyJ0eXAiOiJKV1Q...             │       │
│  │   (com certificado em todas as requisições)             │       │
│  └──────────────────────────────────────────────────────────┘       │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Endpoints por Ambiente

### 🏗️ Desenvolvimento (Homologação)
```
OAuth2 Token:     https://pix-h.api.efipay.com.br/oauth/token
Cobranças PIX:    https://pix-h.api.efipay.com.br/cob
QR Code:          https://pix-h.api.efipay.com.br/qrcode
```

### 🚀 Produção
```
OAuth2 Token:     https://pix.api.efipay.com.br/oauth/token
Cobranças PIX:    https://pix.api.efipay.com.br/cob
QR Code:          https://pix.api.efipay.com.br/qrcode
```

---

## Exemplo de Código (PHP)

### Preparar Requisição OAuth2

```php
<?php
// config.php
define('EFI_CLIENT_ID', 'Client_Id_...');
define('EFI_CLIENT_SECRET', 'Client_Secret_...');
define('EFI_CERTIFICATE_PATH', '/path/to/certificado.pem');
define('EFI_SANDBOX', false); // true = homologação, false = produção

// test_oauth_authorization.php
require_once __DIR__ . '/config.php';

// Determinar URL base
$base_url = EFI_SANDBOX 
    ? 'https://pix-h.api.efipay.com.br'
    : 'https://pix.api.efipay.com.br';

$oauth_url = $base_url . '/oauth/token';

// Preparar Basic Auth (Header)
$auth_header = base64_encode(EFI_CLIENT_ID . ':' . EFI_CLIENT_SECRET);

// Usar cURL com certificado
$curl = curl_init();

curl_setopt_array($curl, [
    // URL
    CURLOPT_URL => $oauth_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    
    // Certificado SSL (OBRIGATÓRIO conforme EFI)
    CURLOPT_SSLCERT => EFI_CERTIFICATE_PATH,
    CURLOPT_SSLCERTTYPE => 'PEM',
    CURLOPT_SSLCERTPASSWD => '', // Vazio
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    
    // Headers
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . $auth_header,
        'Content-Type: application/json',
    ],
    
    // Body
    CURLOPT_POSTFIELDS => json_encode([
        'grant_type' => 'client_credentials'
    ]),
    
    // Timeout
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// Processar resposta
if ($http_code === 200) {
    $data = json_decode($response, true);
    
    $access_token = $data['access_token'];      // JWT token
    $token_type = $data['token_type'];          // "Bearer"
    $expires_in = $data['expires_in'];          // 3600 segundos
    $scope = $data['scope'] ?? '';              // Escopos habilitados
    
    echo "✓ Token gerado com sucesso!\n";
    echo "Válido por: $expires_in segundos\n";
    echo "Escopos: $scope\n";
} else {
    echo "❌ Erro: HTTP $http_code\n";
    echo $response;
}
?>
```

---

## Verificação de Credenciais

### Checklist para Dashboard EFI

1. **Credenciais OAuth2**
   - [ ] Client_Id encontrado
   - [ ] Client_Secret copiado exatamente
   - [ ] Não tem espaços extras
   - [ ] Não foi compartilhado publicamente

2. **Certificado**
   - [ ] P12 baixado da dashboard
   - [ ] Convertido para PEM com comando correto
   - [ ] Não expirou
   - [ ] Está na raiz do projeto

3. **Escopos Habilitados**
   - [ ] cob.read ✓
   - [ ] cob.write ✓
   - [ ] pix.read ✓
   - [ ] pix.write ✓
   - [ ] webhook.read ✓
   - [ ] webhook.write ✓

4. **Webhook**
   - [ ] URL: `https://petfinder.pageup.net.br/api/efi-webhook.php`
   - [ ] Certificado SSL válido no servidor
   - [ ] Teste de entrega bem-sucedido

---

## Troubleshooting

### Erro 401 (Unauthorized)
```
Possíveis causas:
1. Client_Id ou Client_Secret incorretos
2. Credenciais com espaços extras
3. Certificado inválido
4. Certificado expirou
```

**Solução:**
- Verifique credenciais na dashboard
- Regenere se necessário
- Reconverta certificado

### Erro de SSL/Certificado
```
SSL certificate problem: unable to get local issuer certificate
CURLOPT_SSLCERT_FAILED
```

**Solução:**
- Verifique se arquivo `.pem` existe
- Reconverta com comando correto
- Valide com: `openssl x509 -in file.pem -text -noout`

### Timeout
```
Operation timed out after 30000 milliseconds
```

**Solução:**
- Verifique conectividade: `ping pix.api.efipay.com.br`
- Aumentar timeout cURL
- Verificar firewall/proxy

---

## Mais Informações

- **Documentação Oficial**: https://dev.efipay.com.br/docs/api-pix/
- **API Reference**: https://dev.efipay.com.br/docs/api-pix/api-reference/
- **Webhooks**: https://dev.efipay.com.br/docs/api-pix/webhooks/
- **SDK PHP**: https://github.com/efipay/sdk-php-apis-efi

---

**Última atualização:** 2026-01-12  
**Baseado em:** Documentação Oficial EFI - https://dev.efipay.com.br/docs/api-pix/credenciais/
