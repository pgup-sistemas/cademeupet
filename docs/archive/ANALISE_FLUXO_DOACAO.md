# Análise Completa do Fluxo de Doação - PetFinder

## Problemas Identificados

### 1. **SDK EFI Simulada (CRÍTICO)**
   - **Arquivo**: `includes/efi.php`
   - **Problema**: Implementação simulada que retorna dados fictícios
   - **Impacto**: Nenhuma integração real com a API EFI, pagamentos não são processados
   - **Solução**: Usar SDK oficial `Efi\EfiPay` do vendor

### 2. **PagamentoController Chamando Métodos Inexistentes**
   - **Métodos chamados**:
     - `pixCreateImmediateCharge()` → SDK real usa esse
     - `pixGenerateQRCode()` → SDK real usa esse
     - `createOneStepLink()` → SDK real usa esse
     - `createPlan()` → SDK real usa esse
     - `createOneStepSubscriptionLink()` → SDK real usa esse
   - **Problema**: Alguns métodos existem, mas a classe Efi simulada não implementa todos
   - **Solução**: Usar `Efi\EfiPay` diretamente do vendor

### 3. **Credenciais EFI no .env não Carregadas**
   - **Arquivo**: `.env` tem valores vazios em `EFI_CLIENT_ID` e `EFI_CLIENT_SECRET`
   - **Problema**: Sem credenciais reais, API não funciona
   - **Solução**: Configurar credenciais corretas no .env

### 4. **Fluxo PIX**
   - **Arquivo**: `DoacaoController.php` linha 40-60
   - **Problema**: Depois de criar cobrança PIX, armazena apenas em SESSION, não persiste em DB corretamente
   - **Solução**: Garantir que transaction_id esteja sendo salvo corretamente

### 5. **Fluxo Cartão à Vista**
   - **Arquivo**: `DoacaoController.php` linha 61-80
   - **Problema**: Busca `payment_url` em estrutura de resposta errada
   - **Solução**: Verificar estrutura real da resposta do SDK

### 6. **Webhook PIX** 
   - **Arquivo**: `api/efi-webhook.php`
   - **Problema**: Busca `txid` em estrutura diferente do padrão EFI
   - **Solução**: Ajustar para padrão correto da API EFI

### 7. **Webhook de Cobrança (Billing)**
   - **Arquivo**: `api/efi-billing-notification.php`
   - **Problema**: Lógica de extração de dados pode não corresponder à resposta real do EFI
   - **Solução**: Revisar estrutura de resposta real

## Fluxo Esperado

### PIX
1. Usuário preenche formulário e seleciona PIX
2. `DoacaoController->criar()` registra doação com status = 'pendente'
3. Chama `PagamentoController->criarCobrancaPix()`
4. API EFI cria cobrança e retorna `txid` e QR Code
5. Usuário é redirecionado para tela de confirmação com QR Code
6. Após pagamento, webhook EFI envia notificação
7. `api/efi-webhook.php` processa e muda status para 'aprovada'

### Cartão à Vista
1. Usuário preenche formulário e seleciona Cartão
2. `DoacaoController->criar()` registra doação com status = 'pendente'
3. Chama `PagamentoController->criarLinkPagamentoDoacao()`
4. API EFI cria cobrança e retorna link de pagamento
5. Usuário é redirecionado para link do EFI
6. Após pagamento, webhook de billing envia notificação
7. `api/efi-billing-notification.php` processa e muda status para 'aprovada'

### Cartão Recorrente
1. Usuário deve estar logado
2. Similar ao cartão à vista, mas cria assinatura
3. Webhook processa pagamento mensal

## Ações Necessárias

1. ✅ Refatorar `includes/efi.php` para usar `Efi\EfiPay` real
2. ✅ Atualizar `PagamentoController.php` com estruturas corretas
3. ✅ Corrigir `api/efi-webhook.php` para padrão PIX
4. ✅ Corrigir `api/efi-billing-notification.php` para padrão de billing
5. ✅ Testar fluxo completo em sandbox
6. ✅ Validar credenciais .env

