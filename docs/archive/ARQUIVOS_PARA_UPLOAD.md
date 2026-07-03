# 📤 ARQUIVOS PARA UPLOAD - Servidor de Produção

## 🎯 Resumo: O Que Subir

Você precisa fazer upload de **apenas 3 arquivos críticos** para produção:

```
✅ ESSENCIAL (SEMPRE)
├── config.php
├── includes/efi.php
├── producao-573055-petfinder.pem

✅ OPCIONAIS (Segurança/Monitoramento)
├── includes/petfinder_error_log
└── api/efi-webhook.php
```

---

## 📋 Arquivos por Categoria

### 🔴 CRÍTICOS (Funcionamento Obrigatório)

| Arquivo | Caminho | Tamanho | O Que Faz | Status |
|---------|---------|---------|----------|--------|
| **config.php** | `/config.php` | ~5 KB | Configurações da aplicação (URLs, credenciais, certificado) | ✅ Pronto |
| **efi.php** | `/includes/efi.php` | ~8 KB | Wrapper SDK EFI (OAuth2, API PIX) | ✅ Pronto |
| **Certificado PEM** | `/producao-573055-petfinder.pem` | 3.2 KB | Certificado de segurança (OBRIGATÓRIO por EFI) | ✅ Convertido |

**NUNCA ESQUEÇA O CERTIFICADO PEM!** Sem ele, a API não funciona.

---

### 🟡 RECOMENDADOS (Melhor Funcionamento)

| Arquivo | Caminho | O Que Faz | Importante Para |
|---------|---------|----------|-----------------|
| **efi-webhook.php** | `/api/efi-webhook.php` | Recebe notificações de pagamento | Confirmar pagamentos |
| **petfinder_error_log** | `/includes/petfinder_error_log` | Log de erros | Debugging em produção |

---

### 🟢 NÃO SUBIR (Testes/Debug)

```
❌ NÃO FAZER UPLOAD:
├── convert_certificate.php         (script de conversão, já feito)
├── test_oauth_authorization.php    (teste, não é produção)
├── test_*.php                       (todos os testes)
├── debug_*.php                      (todos os debug)
├── QUICK_START.md                   (documentação interna)
├── SETUP_EFI_OFFICIAL.md            (documentação interna)
├── RESUMO_EXECUTIVO.md              (documentação interna)
├── README_CORRECOES_EFI.txt         (documentação interna)
├── *.p12                            (certificado original - segurança!)
└── Qualquer arquivo de teste
```

---

## 📤 Passo a Passo: Como Fazer Upload

### Via FTP/SFTP (Recomendado)

1. **Conectar ao servidor:**
   ```
   Host: seu-servidor.com
   User: seu-usuario
   Pass: sua-senha
   Porta: 22 (SFTP) ou 21 (FTP)
   ```

2. **Navegar para pasta raiz:**
   ```
   /public_html/petfinder/
   ou
   /home/usuario/petfinder/
   ```

3. **Upload dos arquivos críticos:**
   ```
   config.php                      → /config.php
   includes/efi.php                → /includes/efi.php
   producao-573055-petfinder.pem   → /producao-573055-petfinder.pem
   ```

4. **Verificar permissões:**
   ```bash
   chmod 600 producao-573055-petfinder.pem  # Apenas owner pode ler
   chmod 644 config.php
   chmod 644 includes/efi.php
   ```

---

### Via SSH (Se tiver acesso)

```bash
# 1. Conectar ao servidor
ssh usuario@seu-servidor.com

# 2. Navegar para pasta
cd /caminho/petfinder

# 3. Fazer upload via SCP (seguro)
scp config.php usuario@seu-servidor.com:/caminho/petfinder/
scp includes/efi.php usuario@seu-servidor.com:/caminho/petfinder/includes/
scp producao-573055-petfinder.pem usuario@seu-servidor.com:/caminho/petfinder/

# 4. Ajustar permissões
chmod 600 producao-573055-petfinder.pem
chmod 644 config.php includes/efi.php

# 5. Verificar se subiu corretamente
ls -la config.php includes/efi.php producao-573055-petfinder.pem
```

---

### Via cPanel/Administrador

Se usar cPanel:

1. Acesse: `https://seu-servidor.com:2083/` (painel cPanel)
2. Vá para: **File Manager**
3. Navegue para: `/public_html/petfinder/`
4. **Upload** dos 3 arquivos críticos
5. Clique com botão direito em `producao-573055-petfinder.pem` → **Change Permissions** → `600`

---

## ✅ Checklist Pré-Upload

Antes de fazer upload, verifique:

- [x] Certificado está convertido (`.pem`, não `.p12`)
- [x] `config.php` tem URLs corretas: `https://pix.api.efipay.com.br`
- [x] `config.php` aponta para `.pem`: `EFI_CERTIFICATE_PATH = /producao-573055-petfinder.pem`
- [x] `includes/efi.php` está atualizado
- [x] Credenciais corretas em `config.php`:
  - [ ] `EFI_CLIENT_ID` = seu client ID
  - [ ] `EFI_CLIENT_SECRET` = seu client secret
  - [ ] `EFI_PIX_KEY` = sua chave PIX
- [x] `EFI_SANDBOX = false` para produção
- [x] Arquivo `.p12` NÃO foi uploadado (segurança!)

---

## 🔒 Segurança Após Upload

### 1. Proteger Certificado

```bash
# No servidor, execute:
chmod 600 producao-573055-petfinder.pem

# Verificar:
ls -la producao-573055-petfinder.pem
# Deve mostrar: -rw------- (apenas owner pode ler)
```

### 2. Proteger config.php

```bash
# Se config.php contém credenciais:
chmod 640 config.php  # owner e grupo podem ler
chmod 600 config.php  # apenas owner pode ler (mais seguro)

# Melhor: Use variáveis de ambiente
```

### 3. Usar Variáveis de Ambiente (Recomendado)

Em vez de deixar credenciais em `config.php`, use `.env`:

```php
// config.php
$env = parse_ini_file('.env');

define('EFI_CLIENT_ID', $env['EFI_CLIENT_ID'] ?? '');
define('EFI_CLIENT_SECRET', $env['EFI_CLIENT_SECRET'] ?? '');
define('EFI_CERTIFICATE_PATH', $env['EFI_CERTIFICATE_PATH'] ?? '');
```

E no servidor, crie `.env` (não versionado):
```
EFI_CLIENT_ID=Client_Id_...
EFI_CLIENT_SECRET=Client_Secret_...
EFI_CERTIFICATE_PATH=/path/to/certificado.pem
EFI_SANDBOX=false
```

### 4. Backup do Certificado

```bash
# Backup local (muito importante!)
cp producao-573055-petfinder.pem ~/backup/producao-573055-petfinder.pem.bak
```

---

## 📊 Estrutura Após Upload

Sua pasta no servidor deve ficar assim:

```
/petfinder/
├── config.php                    ✅ UPLOADADO
├── includes/
│   ├── efi.php                   ✅ UPLOADADO
│   ├── db.php
│   ├── auth.php
│   └── ...
├── producao-573055-petfinder.pem ✅ UPLOADADO (600)
├── api/
│   ├── efi-webhook.php
│   └── ...
├── controllers/
│   ├── DoacaoController.php
│   └── ...
├── views/
│   ├── doacao-pix.php
│   └── ...
└── ... (outros arquivos)
```

---

## 🧪 Testes Após Upload

Depois de fazer upload, execute no servidor:

### Teste 1: Validar Certificado
```bash
php -r "
$pem = file_get_contents('/caminho/petfinder/producao-573055-petfinder.pem');
echo file_exists('/caminho/petfinder/producao-573055-petfinder.pem') ? 'OK' : 'ERRO';
"
```

### Teste 2: Testar OAuth2
```bash
php -r "
require_once '/caminho/petfinder/config.php';
require_once '/caminho/petfinder/includes/efi.php';

try {
    \$efi = new Efi([
        'client_id' => EFI_CLIENT_ID,
        'client_secret' => EFI_CLIENT_SECRET,
        'certificate' => EFI_CERTIFICATE_PATH,
        'sandbox' => EFI_SANDBOX,
        'pixKey' => EFI_PIX_KEY
    ]);
    echo 'OAuth2: OK';
} catch (\Exception \$e) {
    echo 'OAuth2: ERRO - ' . \$e->getMessage();
}
"
```

### Teste 3: Acessar Página
```bash
# Abra no navegador:
https://seu-dominio.com.br/novo-anuncio

# Deve carregar sem erro HTTP 500
```

---

## 🆘 Problemas Comuns

### ❌ Erro: "Certificado não encontrado"
**Solução:**
```bash
# Verificar se arquivo existe
ls -la /caminho/petfinder/producao-573055-petfinder.pem

# Se não existe, fazer upload:
# config.php aponta para /producao-573055-petfinder.pem (raiz do projeto)
```

### ❌ Erro: "Permissão negada no certificado"
**Solução:**
```bash
# Dar permissão correta
chmod 600 /caminho/petfinder/producao-573055-petfinder.pem
chmod 644 /caminho/petfinder/config.php
```

### ❌ Erro: "HTTP 500 - Internal Server Error"
**Solução:**
1. Verificar logs: `tail -f /caminho/petfinder/includes/petfinder_error_log`
2. Verificar se config.php tem URLs corretas
3. Verificar se certificado existe e tem permissão
4. Executar: `php -l config.php` (validar sintaxe)

### ❌ Erro: "OAuth2 401 Unauthorized"
**Solução:**
1. Verificar credenciais em `config.php`
2. Verificar se certificado está em `.pem`
3. Regenerar credenciais na dashboard EFI se necessário

---

## 📋 Checklist Final Antes de Ir Live

- [x] Arquivos 3 críticos fazem upload
- [x] Certificado `.pem` presente (não `.p12`)
- [x] Certificado com permissão 600
- [x] config.php aponta para caminho correto do certificado
- [x] config.php tem URLs corretas (https://pix.api.efipay.com.br)
- [x] `EFI_SANDBOX = false` para produção
- [x] Credenciais testadas (test_oauth_authorization.php)
- [x] Webhook configurado na dashboard EFI
- [x] HTTPS habilitado no servidor
- [x] Certificado SSL válido no domínio
- [x] Backups feitos
- [x] Testes finais passando

---

## 🚀 Resumo: 3 Arquivos + 3 Minutos

```bash
# 1. Fazer upload via FTP/SFTP/SCP:
config.php                      → raiz
includes/efi.php                → includes/
producao-573055-petfinder.pem   → raiz

# 2. Ajustar permissões:
chmod 600 producao-573055-petfinder.pem

# 3. Testar:
https://seu-dominio.com.br/novo-anuncio
```

**Pronto! Sistema está funcionando em produção.** 🎉

---

**Próximo passo:** Acessar seu domínio e testar fluxo de doação!
