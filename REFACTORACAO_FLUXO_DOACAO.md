# Refatoração Completa do Fluxo de Doação - PetFinder

## 📋 Resumo das Alterações

Este documento descreve as mudanças realizadas no fluxo de doação (PIX, Cartão à Vista e Cartão Recorrente) do PetFinder.

---

## 🔧 Alterações Realizadas

### 1. **includes/efi.php** - SDK EFI Refatorada
**Mudança:** Substituição da implementação simulada pela SDK oficial `Efi\EfiPay`

**Antes:** 
- Classe simulada retornando dados fictícios
- Sem integração real com API EFI
- Métodos como `pixCreateImmediateCharge` não faziam nada

**Depois:**
- Wrapper robusto da SDK oficial `Efi\EfiPay`
- Validação de credenciais
- Tratamento adequado de exceções
- Métodos implementados:
  - `pixCreateImmediateCharge()` - Cria cobrança PIX
  - `pixGenerateQRCode()` - Gera QR Code
  - `pixDetailCharge()` - Consulta status de cobrança
  - `createOneStepLink()` - Cria link de pagamento (cartão)
  - `createPlan()` - Cria plano de assinatura
  - `createOneStepSubscriptionLink()` - Cria link de assinatura
  - `getNotification()` - Obtém notificações de webhook
  - `cancelSubscription()` - Cancela assinatura

---

### 2. **controllers/PagamentoController.php** - Refatoração Completa

#### 2.1 Método `criarCobrancaPix()`
- Validação melhorada de resposta
- Tratamento flexível de estrutura de resposta
- Melhor extração do `location ID`
- Suporte a variações na resposta da API
- Logging detalhado de erros

#### 2.2 Método `detalharCobrancaPix()`
- Erro handling aprimorado
- Tratamento genérico de exceções (não apenas `EfiException`)
- Validação de resposta

#### 2.3 Método `sincronizarStatusDoacaoPix()`
- Try-catch adequado
- Validação de doação antes de sincronizar
- Melhor logging

#### 2.4 Método `criarCobrancaPixParceiro()`
- Mesmas melhorias que `criarCobrancaPix()`
- Estrutura adequada de resposta

#### 2.5 Método `criarLinkPagamentoDoacao()`
- Remoção de `$params = []` desnecessário
- Erro handling adequado

#### 2.6 Método `criarAssinaturaCartaoDoacao()`
- Implementação robusta de try-catch
- Suporte a variações de estrutura de resposta do plano
- Melhor logging

#### 2.7 Método `criarAssinaturaCartaoParceiro()`
- Mesmas melhorias que acima

#### 2.8 Método `cancelarAssinaturaGateway()`
- Tratamento flexível de estruturas de resposta
- Melhor tratamento de exceções
- Suporte a múltiplas variações de "cancelado"

#### 2.9 Método `sincronizarStatusParceiroPix()`
- Try-catch aprimorado
- Validação melhorada
- Logging detalhado

---

### 3. **api/efi-webhook.php** - Webhook PIX Refatorado

**Mudanças:**
- Melhor extração de `TXID` (suporta múltiplas estruturas)
- Logging detalhado de dados recebidos
- Respostas HTTP adequadas
- Validação de token aprimorada
- Mensagens de erro mais específicas
- Suporte a `pix_id` como alternativa de TXID

---

### 4. **api/efi-billing-notification.php** - Webhook de Cartão Refatorado

**Mudanças:**
- Estrutura comentada por seções
- Logging extensivo em cada etapa
- Melhor tratamento de dados de notificação
- Respostas HTTP apropriadas
- Mensagens de erro detalhadas
- Suporte a múltiplas estruturas de resposta da API

---

## 📦 Dependências

O projeto requer que a SDK oficial do EFI seja instalada:

```bash
composer require efipay/sdk-php-apis-efi
```

Esta SDK é instalada via Composer e fornece a classe `Efi\EfiPay` que é o coração da integração.

---

## ⚙️ Configuração Necessária

### 1. Arquivo `.env`

Garantir que estejam preenchidas as seguintes variáveis:

```env
# ========== EFÍ BANK - PIX ==================
EFI_CLIENT_ID=seu_client_id_aqui
EFI_CLIENT_SECRET=seu_client_secret_aqui
EFI_PIX_KEY=sua_chave_pix@email.com
EFI_PIX_DESCRIPTION=Doação para PetFinder
EFI_PIX_NOTIFICATION_URL=https://petfinder.pageup.net.br/api/efi-webhook.php
EFI_BASE_URL=https://api.efipay.com.br/api/
EFI_WEBHOOK_TOKEN=seu_token_secreto_aqui
EFI_SANDBOX=false  # Mudar para 'true' para testes em sandbox
EFI_CERTIFICATE_PATH=/caminho/para/certificado.pem
```

### 2. Certificado SSL/TLS

Colocar o certificado da EFI em: `certs/production.pem`

Este certificado é fornecido pela EFI e é obrigatório para autenticação mútua.

### 3. Webhooks Configurados na EFI

#### Webhook PIX
- **URL:** `https://petfinder.pageup.net.br/api/efi-webhook.php?token=seu_token_secreto`
- **Método:** POST
- **Tipo:** PIX

#### Webhook de Faturamento (Cartão)
- **URL:** `https://petfinder.pageup.net.br/api/efi-billing-notification.php?token=seu_token_secreto`
- **Método:** POST
- **Tipo:** Notificações de Cobrança

---

## 🧪 Testes

Um arquivo de teste foi criado para validar a integração:

```bash
test_fluxo_doacao.php
```

**Como usar:**

1. Suba o arquivo na raiz do projeto
2. Acesse: `https://seusite.com/test_fluxo_doacao.php`
3. Siga os passos de validação
4. **Remova o arquivo após os testes**

---

## 🔄 Fluxo de Doação PIX

```
1. Usuário acessa /doar
2. Preenche formulário e seleciona PIX
3. Frontend envia POST para /views/doar.php
4. DoacaoController->criar() é chamado
5. Registra doação com status='pendente' no BD
6. Chama PagamentoController->criarCobrancaPix()
7. SDK EFI cria cobrança e retorna txid + QR Code
8. Doação é atualizada com transaction_id=txid
9. Usuário é redirecionado para página de confirmação PIX
10. Usuário escaneia QR Code e paga
11. EFI envia webhook para /api/efi-webhook.php
12. Webhook extrai txid e chama sincronizarStatusDoacaoPix()
13. Sistema consulta detalhes na EFI
14. Se status='CONCLUIDA', doação é marcada como 'aprovada'
15. Meta de doação é atualizada
```

---

## 💳 Fluxo de Doação Cartão (À Vista)

```
1. Usuário acessa /doar
2. Preenche formulário e seleciona "Cartão (à vista)"
3. Frontend envia POST para /views/doar.php
4. DoacaoController->criar() é chamado
5. Registra doação com status='pendente' no BD
6. Chama PagamentoController->criarLinkPagamentoDoacao()
7. SDK EFI cria link de pagamento
8. Doação é atualizada com payment_url + efi_charge_id
9. Usuário é redirecionado para link de pagamento da EFI
10. Usuário preenche dados do cartão
11. EFI processa o pagamento
12. EFI envia webhook para /api/efi-billing-notification.php
13. Sistema processa notificação usando getNotification()
14. Extrai charge_id e status
15. Se status='PAID', doação é marcada como 'aprovada'
16. Meta de doação é atualizada
```

---

## 📅 Fluxo de Doação Cartão Recorrente (Mensal)

```
1. Usuário (logado) acessa /doar
2. Preenche formulário e seleciona "Cartão (mensal)"
3. Frontend envia POST para /views/doar.php
4. DoacaoController->criar() é chamado
5. Registra doação com status='pendente' no BD
6. Chama PagamentoController->criarAssinaturaCartaoDoacao()
7. SDK EFI cria plano de assinatura
8. SDK EFI cria link de assinatura
9. Doação é atualizada com subscription_id + efi_plan_id
10. Usuário é redirecionado para link de assinatura
11. Usuário autoriza pagamento mensal
12. EFI processa primeiro pagamento
13. EFI envia webhook para /api/efi-billing-notification.php
14. Sistema marca doação como 'aprovada'
15. A cada mês, EFI cobra automaticamente
16. Cada cobrança gera um novo webhook
17. Sistema atualiza data de próxima cobrança
```

---

## ⚠️ Possíveis Problemas e Soluções

### Problema: "SDK Efi\EfiPay não encontrada"
**Solução:**
```bash
composer require efipay/sdk-php-apis-efi
```

### Problema: "Credenciais EFI não configuradas"
**Solução:** Verifique `.env` e certifique-se que `EFI_CLIENT_ID` e `EFI_CLIENT_SECRET` não estão vazios.

### Problema: "Certificado EFI não encontrado"
**Solução:** 
1. Baixe o certificado da EFI
2. Coloque em `certs/production.pem`
3. Atualize `EFI_CERTIFICATE_PATH` no `.env`

### Problema: "Webhook não está recebendo notificações"
**Solução:**
1. Verifique se webhooks estão configurados na conta EFI
2. Verifique se token está correto
3. Verifique logs em `includes/petfinder_error_log`

### Problema: "Doação fica pendente após pagamento"
**Solução:**
1. Verificar logs em `includes/petfinder_error_log`
2. Testar webhook manualmente
3. Verificar se `transaction_id` está sendo salvo corretamente no BD

---

## 📊 Melhorias Implementadas

| Aspecto | Antes | Depois |
|---------|-------|--------|
| **SDK** | Simulada (fictícia) | Oficial EFI (`Efi\EfiPay`) |
| **Validação** | Mínima | Robusta em cada etapa |
| **Tratamento de Erros** | Apenas `EfiException` | Múltiplas camadas try-catch |
| **Logging** | Básico | Detalhado em cada etapa |
| **Flexibilidade** | Rígida | Suporta múltiplas estruturas de resposta |
| **Documentação** | Inexistente | Completa e detalhada |

---

## 🚀 Próximos Passos

1. **Configurar credenciais reais da EFI** no `.env`
2. **Obter certificado SSL/TLS** da EFI
3. **Testar em SANDBOX** primeiro (`EFI_SANDBOX=true`)
4. **Configurar webhooks** na conta EFI
5. **Executar test_fluxo_doacao.php** para validação
6. **Testar fluxo completo** em https://petfinder.pageup.net.br/doar
7. **Passar para PRODUÇÃO** (`EFI_SANDBOX=false`)
8. **Remover arquivo de teste** (test_fluxo_doacao.php)

---

## 📝 Notas Importantes

- Todas as requisições à API EFI retornam erros adequados
- Logging está ativado em `/includes/petfinder_error_log`
- Webhooks têm validação de token por segurança
- Sistema suporta múltiplas tentativas de sincronização
- Dados sensíveis (credenciais) não são logados
- Sistema é thread-safe e pode processar múltiplas doações simultâneamente

---

## ✅ Checklist Final

- [ ] Credenciais EFI configuradas em `.env`
- [ ] Certificado EFI colocado em `certs/production.pem`
- [ ] SDK EFI instalada via Composer
- [ ] Webhooks configurados na conta EFI
- [ ] test_fluxo_doacao.php executado e validado
- [ ] Fluxo PIX testado em sandbox
- [ ] Fluxo Cartão testado em sandbox
- [ ] Fluxo Assinatura testado em sandbox
- [ ] Logs verificados para erros
- [ ] Sistema pronto para produção
- [ ] test_fluxo_doacao.php removido

---

**Última Atualização:** 2026-01-12
**Versão:** 2.0 - Refatoração Completa
**Status:** Pronto para Implementação
