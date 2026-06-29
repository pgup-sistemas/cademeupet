# ⚡ GUIA RÁPIDO - COMEÇAR AGORA

## 5 Passos para Ativar o Fluxo de Doação

### 1️⃣ VERIFICAR O SISTEMA (2 minutos)
```bash
# Executar validação rápida
php quick_check.php

# Ou abrir no navegador
https://petfinder.pageup.net.br/quick_check.php
```

### 2️⃣ CONFIGURAR CREDENCIAIS (5 minutos)
Editar `.env` e preencher estes campos:

```env
# Credenciais da EFI (obter na conta https://dashboard.efipay.com.br)
EFI_CLIENT_ID=Client_Id_xxxxxxxxxxxxxxxxxxxxx
EFI_CLIENT_SECRET=Client_Secret_xxxxxxxxxxxxxxxxxxxx
EFI_PIX_KEY=sua_chave_pix@email.com

# Certificado (obter na conta EFI)
EFI_CERTIFICATE_PATH=/absolute/path/to/production.pem

# Modo teste (mudar para false depois)
EFI_SANDBOX=true

# Webhook token (gerar uma string aleatória)
EFI_WEBHOOK_TOKEN=um_token_secreto_aleatorio_bem_longo
```

### 3️⃣ INSTALAR SDK (1 minuto)
```bash
cd /caminho/para/petfinder
composer require efipay/sdk-php-apis-efi
```

### 4️⃣ OBTER CERTIFICADO (10 minutos)
1. Acessar: https://dashboard.efipay.com.br
2. Ir em Segurança/Certificados
3. Baixar certificado `production.pem`
4. Salvar em: `certs/production.pem`
5. Verificar permissões: `chmod 644 certs/production.pem`

### 5️⃣ TESTAR (5 minutos)
```
Abrir: https://petfinder.pageup.net.br/test_fluxo_doacao.php

Validar:
✓ Credenciais OK
✓ SDK carregada
✓ Controllers carregados
✓ Modelos carregados
```

---

## 🧪 TESTANDO O FLUXO

### Teste PIX em Sandbox
```
1. Abrir https://petfinder.pageup.net.br/doar
2. Preencher valores
3. Selecionar "PIX"
4. Clicar "Doar agora"
5. Escanear QR Code (em sandbox, não precisa pagar)
6. Validar status mudou para "aprovada" no banco
```

### Teste Cartão em Sandbox
```
1. Abrir https://petfinder.pageup.net.br/doar
2. Preencher valores
3. Selecionar "Cartão (à vista)"
4. Clicar "Doar agora"
5. Usar cartão de teste (número: 4111111111111111)
6. Validar status mudou para "aprovada"
```

### Teste Assinatura em Sandbox
```
1. Fazer login no site
2. Abrir https://petfinder.pageup.net.br/doar
3. Preencher valores
4. Selecionar "Cartão (mensal)"
5. Clicar "Doar agora"
6. Autorizar débito mensal
7. Validar assinatura criada
```

---

## ❌ ERROS COMUNS & SOLUÇÕES

### "SDK Efi\EfiPay não encontrada"
```bash
✓ Solução: composer require efipay/sdk-php-apis-efi
```

### "Credenciais EFI não configuradas"
```
✓ Solução: Preencher EFI_CLIENT_ID e EFI_CLIENT_SECRET em .env
```

### "Certificado EFI não encontrado"
```
✓ Solução: Baixar de https://dashboard.efipay.com.br
         e colocar em certs/production.pem
```

### "Webhook não recebe notificações"
```
✓ Solução: Configurar webhooks na conta EFI:
         PIX: https://seusite/api/efi-webhook.php?token=XXX
         Billing: https://seusite/api/efi-billing-notification.php?token=XXX
```

### "Doação fica pendente após pagamento"
```
✓ Solução: Ver logs em includes/petfinder_error_log
         Verificar se webhooks estão sendo recebidos
         Testar manualmente: php quick_check.php
```

---

## 📚 DOCUMENTAÇÃO COMPLETA

- **LEIA-ME.txt** (este arquivo) - Overview completo
- **quick_check.php** - Verificação rápida
- **test_fluxo_doacao.php** - Suite de testes
- **REFACTORACAO_FLUXO_DOACAO.md** - Guia detalhado (200+ linhas)
- **ANALISE_FLUXO_DOACAO.md** - Análise técnica
- **RESUMO_REFACTORACAO.md** - Sumário executivo

---

## 🎯 TIMELINE ESPERADO

| Ação | Tempo | Status |
|------|-------|--------|
| Verificação rápida | 2 min | ✅ Imediato |
| Configurar credenciais | 5 min | ✅ Hoje |
| Instalar SDK | 1 min | ✅ Hoje |
| Obter certificado | 10 min | ✅ Hoje |
| Testes sandbox | 30 min | ✅ Hoje/Amanhã |
| Testes produção | 30 min | ✅ Amanhã |
| **TOTAL** | **~80 min** | **Amanhã pronto!** |

---

## 🚀 FLUXO RESUMIDO

```
┌─────────────────────┐
│  Usuário acessa     │
│  /doar              │
└──────────┬──────────┘
           ↓
┌─────────────────────┐
│  Preenche form      │
│  Escolhe pagamento  │
└──────────┬──────────┘
           ↓
      ┌────┴────┐
      │          │
      ↓          ↓
    PIX      CARTÃO
      │          │
      ↓          ↓
   QR Code    Link Pag
      │          │
      ↓          ↓
   Webhook   Webhook
      │          │
      └────┬─────┘
           ↓
      Status UPDATE
      "aprovada"
      ✅ PRONTO!
```

---

## 🔒 SEGURANÇA

**REMOVA ANTES DE PRODUÇÃO:**
- ❌ `test_fluxo_doacao.php` - Teste (REMOVA!)
- ❌ `quick_check.php` - Verificação (REMOVA!)

**ATIVAR ANTES DE PRODUÇÃO:**
- ✅ `EFI_SANDBOX=false` em `.env`
- ✅ Usar credenciais reais de PRODUÇÃO
- ✅ Usar certificado de PRODUÇÃO
- ✅ Webhooks configurados na conta EFI

---

## 📞 SUPORTE RÁPIDO

| Problema | Arquivo | Seção |
|----------|---------|-------|
| Configuração | REFACTORACAO_FLUXO_DOACAO.md | 2. Configuração |
| Webhooks | REFACTORACAO_FLUXO_DOACAO.md | 3.1.1 |
| Erros | REFACTORACAO_FLUXO_DOACAO.md | 6. Problemas |
| Fluxo PIX | REFACTORACAO_FLUXO_DOACAO.md | 4.1 |
| Fluxo Cartão | REFACTORACAO_FLUXO_DOACAO.md | 4.2 |

---

## ✅ CHECKLIST EXPRESS

- [ ] `php quick_check.php` executado
- [ ] `.env` configurado com credenciais reais
- [ ] `composer require efipay/sdk-php-apis-efi` executado
- [ ] Certificado em `certs/production.pem`
- [ ] `test_fluxo_doacao.php` testado
- [ ] PIX testado
- [ ] Cartão testado
- [ ] Webhooks funcionando
- [ ] `test_fluxo_doacao.php` removido
- [ ] `quick_check.php` removido
- [ ] `EFI_SANDBOX=false` em `.env`
- [ ] 🎉 **PRONTO PARA PRODUÇÃO!**

---

## 🎉 RESUMO

✅ **Sistema pronto para ativar doações reais**

1. Configure credenciais
2. Instale SDK
3. Obtenha certificado
4. Teste em sandbox
5. Remova arquivos de teste
6. **ATIVO!**

---

**Tempo total estimado:** ~80 minutos até produção  
**Documentação:** Completa em REFACTORACAO_FLUXO_DOACAO.md  
**Suporte:** Ver seção de problemas comuns acima
