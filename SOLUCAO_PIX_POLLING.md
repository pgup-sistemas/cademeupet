# 🔧 Solução: Atualização Automática de Status PIX

## Problema Identificado

A página de pagamento PIX (`/doacao-pix?id=32`) **não atualiza** o status mesmo depois que o pagamento é confirmado pela EFI Bank. O usuário fica preso na tela do PIX indefinidamente.

**Causas Encontradas:**
1. ❌ A página verifica o status **apenas uma vez** no carregamento
2. ❌ O botão "Atualizar status" é apenas um **link estático**, não funciona
3. ❌ **Nenhum polling** automático - página nunca verifica se o pagamento foi confirmado
4. ❌ **Sem endpoint AJAX** para verificar status em tempo real

---

## Solução Implementada

### 1️⃣ Novo Endpoint: `/api/status-doacao.php`

**Arquivo criado:** `api/status-doacao.php`

Este endpoint AJAX permite que o JavaScript na página verifique o status da doação:

```
POST /api/status-doacao.php
Content-Type: application/json

{
  "id": 32,
  "txid": "00020126580014br.gov.bcb.brcode..."
}
```

**Resposta:**
```json
{
  "ok": true,
  "status": "processando|aprovada",
  "doacao_id": 32,
  "timestamp": "2026-01-10 15:30:00"
}
```

**O que faz:**
- ✅ Valida se a doação existe
- ✅ Valida se o TXID é correto
- ✅ Chama `PagamentoController->sincronizarStatusDoacaoPix()` para verificar com EFI
- ✅ Retorna status atual da doação
- ✅ Trata erros e retorna respostas apropriadas

---

### 2️⃣ JavaScript Automático: `views/doacao-pix.php`

**Arquivo modificado:** `views/doacao-pix.php`

Adicionado JavaScript com **polling automático**:

```javascript
// Auto-inicia polling quando página carrega
iniciarPolling() 
  ↓
// Verifica status a cada 5 segundos
atualizarStatusPix(autoPolling=true)
  ↓
// Se status = "aprovada" → Recarrega página
location.reload()
  ↓
// Mostra mensagem "Pagamento confirmado"
```

**Recursos:**
- 🔄 **Polling automático** - Verifica a cada 5 segundos
- ⏱️ **Timeout** - Para após 10 minutos (MAX_TENTATIVAS = 120)
- 🔘 **Botão manual** - Usuário pode clicar "Atualizar status" a qualquer momento
- 📊 **Loader visual** - Mostra spinner enquanto verifica
- 🔊 **Console logs** - Para debug via DevTools (F12)

---

## Como Funciona

### Fluxo Padrão

```
1. Usuário acessa /doacao-pix?id=32
2. Página carrega e verifica status UMA VEZ
3. Se não aprovado:
   - Inicia polling automático
   - A cada 5 segundos faz POST para /api/status-doacao.php
4. Webhook EFI é chamado (em paralelo):
   - EFI envia notificação de pagamento confirmado
   - Webhook atualiza banco de dados
5. Polling detecta a mudança:
   - Status muda para "aprovada"
   - Página faz auto-reload
   - Mostra "Pagamento confirmado!"
```

### Fallback - Se Webhook Falhar

Se por algum motivo o webhook não for chamado pela EFI:

```
1. Polling AJAX chama sincronizarStatusDoacaoPix()
2. Essa função faz GET direto na API EFI
3. Verifica o status real com PIX.API.EFIPAY.COM.BR
4. Atualiza banco de dados se status mudou
```

---

## Testes Implementados

### Script de Testes

**Arquivo:** `test-pix-polling.php`

Acesse: `http://seu-site.com/test-pix-polling.php`

Verifica:
- ✅ Se arquivo `/api/status-doacao.php` existe
- ✅ Se JavaScript está em `doacao-pix.php`
- ✅ Se controladores estão presentes
- ✅ Se webhook está configurado
- ✅ Se endpoint responde corretamente
- ✅ Lista doações pendentes para teste

---

## Como Testar Manualmente

### 1. Teste Local (Desenvolvimento)

```bash
# Terminal 1 - Verificar testes
curl http://localhost/petfinder/test-pix-polling.php

# Terminal 2 - Testar endpoint
curl -X POST http://localhost/petfinder/api/status-doacao.php \
  -H "Content-Type: application/json" \
  -d '{"id": 32, "txid": "sua-txid-aqui"}'
```

### 2. Teste no Navegador

```
1. Abra: https://seu-site.com/doacao-pix?id=32
2. Abra DevTools: F12
3. Vá à aba "Console"
4. Você verá logs:
   [PIX Polling] Iniciando polling automático...
   [PIX Polling] Tentativa 1/120
   [PIX Polling] Resposta: {ok: true, status: "processando"}
   [PIX Polling] Tentativa 2/120
   ... (a cada 5 segundos)
   [PIX Polling] Pagamento confirmado!
```

### 3. Teste com Webhook Real

1. Confirme que webhook está configurado em: `/api/efi-webhook.php`
2. Configure URL no painel EFI:
   ```
   Webhook URL: https://seu-site.com/api/efi-webhook.php
   Token: (use o configurado em config.php)
   ```
3. Faça um pagamento PIX real ou use sandbox EFI
4. Veja status atualizar automaticamente

---

## Checklist de Deploy

Antes de subir para produção:

- [ ] Arquivo `/api/status-doacao.php` criado
- [ ] JavaScript adicionado a `views/doacao-pix.php`
- [ ] Testar com `test-pix-polling.php`
- [ ] Webhook URL configurada no painel EFI
- [ ] Token de webhook em `config.php`
- [ ] HTTPS ativo (PIX exige HTTPS)
- [ ] Permissões corretas em `/api`

---

## Monitoramento

### Logs de Webhook

Verifique se webhook está sendo chamado:

```bash
tail -f includes/petfinder_error_log | grep "efi-webhook"
```

### Logs de Polling

No navegador, abra F12 → Console e procure por:
```
[PIX Polling]
[status-doacao]
```

### Dashboard Admin

Verifique doações pendentes vs aprovadas para monitorar conversão.

---

## Troubleshooting

### ❓ Polling não inicia
- Verifique se JavaScript está na página (`F12 → Sources`)
- Verifique se status inicial não é "aprovada"

### ❓ Endpoint retorna 404
- Verifique se `/api/status-doacao.php` existe
- Verifique permissões do arquivo (755)

### ❓ Status não atualiza mesmo com polling
- Webhook pode estar falhando
- Verifique token EFI em `config.php`
- Verifique URL webhook no painel EFI

### ❓ Página fica carregando infinitamente
- Timeout atingido (10 minutos)
- Webhook falhou e sincronização não funcionou
- Verificar logs em `/includes/petfinder_error_log`

---

## Arquivos Modificados/Criados

```
✨ NOVO:
- api/status-doacao.php              (Endpoint AJAX)
- test-pix-polling.php               (Script de testes)

📝 MODIFICADO:
- views/doacao-pix.php               (Adicionado JavaScript)

ℹ️ INFORMAÇÃO:
- SOLUCAO_PIX_POLLING.md            (Este arquivo)
```

---

## Referências

- [EFI Bank - Documentação PIX](https://developer.efipay.com.br/docs/pix)
- [Webhook - Notificações de Cobrança](https://developer.efipay.com.br/docs/pix-instant-notifications)
- [Status de Pagamento](https://developer.efipay.com.br/docs/pix-payment-status)

---

**Versão:** 1.0 | **Data:** 10/01/2026 | **Status:** ✅ Implementado e Testado
