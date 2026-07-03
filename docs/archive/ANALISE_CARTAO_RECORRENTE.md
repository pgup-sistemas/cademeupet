# 📋 Análise e Implementação: Cobrança Recorrente com Cartão de Crédito

## 1. Estado Atual do Projeto

### ✅ O que já existe

#### Estrutura de Banco de Dados
```sql
-- Tabela doacoes com suporte a recorrência
- metodo_pagamento: 'cartao_recorrente'
- efi_subscription_id: ID da assinatura no EFI
- efi_plan_id: ID do plano no EFI
- proxima_cobranca: Data da próxima cobrança

-- Tabela parceiro_assinaturas
- efi_subscription_id: ID da assinatura
- efi_plan_id: ID do plano
- status: ativa|cancelada|pausada
- pago_ate: Até quando está pago
- proxima_cobranca: Próxima cobrança
```

#### Controllers Existentes
```php
PagamentoController:
- criarAssinaturaCartaoDoacao() ✅
- criarAssinaturaCartaoParceiro() ✅
- getApi() ✅
- getBillingNotificationUrl() ✅

DoacaoController:
- Cria doação com tipo 'cartao_recorrente' ✅

CancelamentoController:
- cancelarAssinaturaGateway() ✅
```

#### Views Existentes
```php
/views/doacao-pix.php - Fluxo para PIX (já implementado)
/views/parceiro-pagamento.php - Página de pagamento parceiros
/views/doar.php - Formulário de doações com opção recorrente
```

#### Webhooks
```php
/api/efi-billing-notification.php - Processa notificações de pagamento
/api/efi-webhook.php - Processa notificações de PIX
```

---

## 2. Conformidade com Documentação EFI

### 📖 Fonte: https://dev.efipay.com.br/docs/api-cobrancas/credenciais

### Fluxo Esperado pela EFI

```
1. AUTENTICAÇÃO OAuth2
   ├─ HTTP Basic Auth (Client_ID:Client_Secret)
   ├─ Certificado PEM (obrigatório)
   ├─ Endpoint: POST /oauth/token
   └─ Response: Bearer Token + expires_in

2. CRIAR PLANO
   ├─ POST /plans
   ├─ Body: { name, interval, repeats }
   └─ Response: plan_id

3. CRIAR ASSINATURA
   ├─ POST /subscriptions
   ├─ Body: { plan_id, customer, charge, metadata }
   └─ Response: subscription_id

4. COBRAR
   ├─ POST /subscriptions/{id}/charges
   ├─ Ou automaticamente no ciclo do plano
   └─ Response: charge_id

5. WEBHOOK
   ├─ EFI envia eventos (charge.paid, subscription.activated, etc)
   ├─ Aplicação processa e atualiza BD
   └─ Response: HTTP 200 OK
```

### ✅ O que está implementado corretamente

1. **Autenticação OAuth2** ✅
   - Usando HTTP Basic Auth
   - Certificado PEM configurado
   - Token sendo gerado

2. **Criação de Plano** ✅
   - Método `createPlan()` sendo chamado
   - name, interval, repeats sendo passados

3. **Criação de Assinatura** ✅
   - Endpoint `/subscriptions` sendo usado
   - Metadados sendo enviados
   - Payment method = 'credit_card'

4. **Webhooks** ✅
   - `/api/efi-billing-notification.php` existe
   - Processando notificações

### ⚠️ Lacunas Identificadas

1. **Validação de Cartão**
   - Não há validação se cartão foi salvo corretamente
   - Sem verificação de status da assinatura

2. **Retry de Cobranças Falhadas**
   - Sem tratamento automático de falha
   - Sem notificação ao usuário

3. **Gerenciamento de Cartões Salvos**
   - Sem interface para o usuário ver/trocar cartão
   - Sem armazenamento de token do cartão

4. **Painel de Controle de Assinaturas**
   - Usuário não consegue ver suas assinaturas ativas
   - Não consegue pausar/cancelar de forma simples

5. **Documentação de Eventos Webhook**
   - Não está claro quais eventos EFI está enviando
   - Sem logging detalhado de eventos

---

## 3. Plano de Implementação

### Fase 1: Validação e Testes ✅ (Próximo)
```
1. Testar criação de plano
2. Testar criação de assinatura
3. Testar primeira cobrança
4. Validar webhook
5. Testar cancelamento
```

### Fase 2: Melhorias na Funcionalidade
```
1. Adicionar validação de cartão
2. Implementar retry de cobranças
3. Criar painel de assinaturas
4. Adicionar suporte a cartões salvos
5. Melhorar logging e debug
```

### Fase 3: Segurança e Conformidade
```
1. PCI-DSS compliance
2. Tokenização de cartão
3. Encriptação de dados sensíveis
4. Testes de segurança
```

---

## 4. Endpoints da API EFI Necessários

### Autenticação
```
POST https://pix.api.efipay.com.br/oauth/token
Authorization: Basic <base64(client_id:client_secret)>
Body: { "grant_type": "client_credentials" }
```

### Planos
```
POST /plans
Body: {
  "name": string,
  "interval": number (1-12),
  "repeats": null (infinito) ou number
}
```

### Assinaturas
```
POST /subscriptions
Body: {
  "customer": {
    "name": string,
    "email": string,
    "cpf": string,
    "phone": string
  },
  "items": [
    {
      "name": string,
      "amount": number,
      "value": number (em centavos)
    }
  ],
  "metadata": {
    "custom_id": string,
    "notification_url": string
  }
}

GET /subscriptions/{id}
GET /subscriptions/{id}/charges
POST /subscriptions/{id}/charges
DELETE /subscriptions/{id}
```

### Webhooks
```
Eventos esperados:
- charge.created
- charge.updated
- charge.completed
- subscription.created
- subscription.updated
- subscription.activated
- subscription.canceled
- subscription.paused
```

---

## 5. Estrutura de Tabelas Necessárias

### Tabelas Existentes que Precisam de Ajustes

```sql
-- doacoes (já tem)
ALTER TABLE doacoes ADD COLUMN IF NOT EXISTS:
- cartao_token (varchar(255)) - Token do cartão salvo
- cartao_final (varchar(4)) - Últimos 4 dígitos
- cartao_bandeira (varchar(20)) - Visa, Mastercard, etc
- ultimo_pagamento_em (datetime) - Data do último pagamento
- proxima_tentativa (datetime) - Próxima tentativa se falha

-- parceiro_assinaturas (já tem)
- Verificar se tem todos os campos acima

-- Pode ser criada: assinaturas_eventos (log de eventos)
CREATE TABLE assinaturas_eventos (
  id int AUTO_INCREMENT PRIMARY KEY,
  assinatura_id int,
  tipo_evento varchar(50),
  dados json,
  criado_em timestamp
);
```

---

## 6. Próximos Passos Imediatos

### HOJE:
1. ✅ Analisar código (DONE)
2. ⏳ Criar testes de integração com EFI
3. ⏳ Validar fluxo completo de assinatura
4. ⏳ Testar webhook de notificação

### SEMANA QUE VEM:
1. Melhorar tratamento de erros
2. Adicionar painel de assinaturas
3. Implementar retry automático
4. Documentar para usuários finais

---

## 7. Riscos e Considerações

### 🔴 Riscos Críticos
1. **Cobrança duplicada** - Sem verificação de idempotência
2. **Cartão inválido** - Sem validação prévia
3. **Falha de webhook** - Sem retry se EFI não conseguir enviar

### 🟡 Riscos Médios
1. **Expiração de cartão** - Sem renovação automática
2. **Mudança de cartão** - Sem interface para usuário trocar
3. **Cancelamento não processado** - Se webhook falhar

### 🟢 Riscos Baixos
1. **Performance** - Será OK com otimizações simples
2. **Escalabilidade** - Estrutura aguenta bem

---

## 8. Conformidade Legal

### ✅ Verificar
- [ ] Termos de uso EFI
- [ ] PCI-DSS compliance
- [ ] Lei de Proteção de Dados (LGPD)
- [ ] Autorização para débito recorrente
- [ ] Cancelamento em 1 clique

---

**Status Geral:** 70% implementado, 30% para melhorias

Vamos começar pela **Fase 1: Validação e Testes**?
