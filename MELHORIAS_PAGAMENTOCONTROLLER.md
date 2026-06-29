/**
 * MELHORIAS PARA PAGAMENTOCONTROLLER - VALIDAÇÃO E TRATAMENTO DE ERROS
 * 
 * Este arquivo contém as melhorias sugeridas para o PagamentoController
 * para melhorar a validação e o tratamento de erros nas assinaturas
 */

// ==========================================
// MÉTODO MELHORADO: criarAssinaturaCartaoDoacao()
// ==========================================

public function criarAssinaturaCartaoDoacaoMelhorado(int $usuarioId, int $doacaoId, float $valorMensal)
{
    // ========================
    // VALIDAÇÕES INICIAIS
    // ========================
    $usuarioId = (int)$usuarioId;
    if ($usuarioId <= 0) {
        throw new Exception('Para doação recorrente é necessário estar logado.');
    }

    $doacaoId = (int)$doacaoId;
    if ($doacaoId <= 0) {
        throw new Exception('ID de doação inválido.');
    }

    $valorMensal = (float)$valorMensal;
    if ($valorMensal < 0.50) {
        throw new Exception('Valor mínimo para assinatura é R$ 0,50.');
    }

    if ($valorMensal > 100000) {
        throw new Exception('Valor máximo para assinatura é R$ 100.000,00.');
    }

    // Verificar se a doação existe e pertence ao usuário
    $doacaoModel = new Doacao();
    $doacao = $doacaoModel->findById($doacaoId);
    if (empty($doacao)) {
        throw new Exception('Doação não encontrada.');
    }

    if ((int)$doacao['usuario_id'] !== $usuarioId) {
        throw new Exception('Acesso negado: essa doação não pertence a você.');
    }

    // Se já tem assinatura ativa, não pode criar outra
    if (!empty($doacao['efi_subscription_id']) && !empty($doacao['efi_subscription_id']) !== '0') {
        if (in_array($doacao['status'], ['ativa', 'aprovada', 'pausada'])) {
            throw new Exception('Esta doação já possui uma assinatura ativa. Cancele a anterior antes de criar uma nova.');
        }
    }

    // ========================
    // OBTER API
    // ========================
    try {
        $api = $this->getApi();
    } catch (Exception $e) {
        error_log('[PagamentoController] Erro ao obter API EFI: ' . $e->getMessage());
        throw new Exception('Erro ao conectar com o gateway de pagamento: ' . $e->getMessage());
    }

    $notificationUrl = $this->getBillingNotificationUrl();

    try {
        // ========================
        // CRIAR PLANO
        // ========================
        error_log('[PagamentoController] Iniciando criação de plano para usuário ' . $usuarioId . ', doação ' . $doacaoId);

        $planResp = $api->createPlan([], [
            'name' => 'Doação PetFinder - Mensal - Usuário ' . $usuarioId,
            'interval' => 1,
            'repeats' => null, // null = infinitas repetições
        ]);
        
        if (!is_array($planResp)) {
            throw new Exception('Resposta inválida da API ao criar plano.');
        }

        $planId = (int)($planResp['data']['plan_id'] ?? ($planResp['plan_id'] ?? 0));
        if ($planId <= 0) {
            error_log('[PagamentoController] Resposta do createPlan: ' . json_encode($planResp));
            throw new Exception('Não foi possível criar o plano de assinatura na EFI. Resposta inválida.');
        }

        error_log('[PagamentoController] Plano criado: ' . $planId);

        // ========================
        // CONVERTER VALOR
        // ========================
        $valorCentavos = (int)round($valorMensal * 100);
        if ($valorCentavos < 50) {
            throw new Exception('Valor inválido: mínimo de R$ 0,50.');
        }

        // ========================
        // PREPARAR METADATA
        // ========================
        $metadata = [
            'custom_id' => 'DOACAO_ASSINATURA_' . $doacaoId . '_' . time(),
            'usuario_id' => $usuarioId,
            'doacao_id' => $doacaoId,
        ];
        
        if ($this->shouldSendBillingNotificationUrl()) {
            $metadata['notification_url'] = $notificationUrl;
        }

        // ========================
        // CRIAR LINK DE ASSINATURA
        // ========================
        error_log('[PagamentoController] Criando link de assinatura para plano ' . $planId);

        $resp = $api->createOneStepSubscriptionLink(['id' => $planId], [
            'items' => [
                [
                    'name' => 'Doação PetFinder (mensal) - R$ ' . number_format($valorMensal, 2, ',', '.'),
                    'amount' => 1,
                    'value' => $valorCentavos,
                ],
            ],
            'metadata' => $metadata,
            'settings' => [
                'payment_method' => 'credit_card',
                'request_delivery_address' => false,
                'expire_at' => $this->getBillingExpireAt(),
            ],
        ]);

        if (!is_array($resp)) {
            throw new Exception('Resposta inválida da API ao criar link de assinatura.');
        }

        // ========================
        // VALIDAR RESPOSTA
        // ========================
        $url = $resp['url'] ?? $resp['_links']['payment'][0]['href'] ?? null;
        if (empty($url)) {
            error_log('[PagamentoController] Resposta do createOneStepSubscriptionLink: ' . json_encode($resp));
            throw new Exception('Link de assinatura não foi gerado corretamente.');
        }

        // ========================
        // ATUALIZAR BANCO DE DADOS
        // ========================
        $doacaoModel->update($doacaoId, [
            'efi_plan_id' => $planId,
            'metodo_pagamento' => 'cartao_recorrente',
            'status' => 'pendente',
            'criada_em' => date('Y-m-d H:i:s'),
        ]);

        error_log('[PagamentoController] Doação atualizada: plan_id=' . $planId . ', doacao_id=' . $doacaoId);

        // ========================
        // RESPOSTA COM SEGURANÇA
        // ========================
        return [
            'url' => $url,
            'plano_id' => $planId,
            'doacao_id' => $doacaoId,
            'valor' => $valorMensal,
            'status' => 'pendente_pagamento'
        ];

    } catch (Exception $e) {
        $mensagem = $e->getMessage();
        error_log('[PagamentoController] Erro ao criar assinatura (doação ' . $doacaoId . '): ' . $mensagem);
        
        // Se criou o plano mas falhou depois, tentar limpar
        if (!empty($planId)) {
            error_log('[PagamentoController] Plano ' . $planId . ' foi criado mas a assinatura falhou. Necessário limpeza manual.');
        }
        
        throw new Exception('Erro ao criar assinatura de doação: ' . $mensagem);
    }
}


// ==========================================
// MÉTODO NOVO: Validar Cartão Antes
// ==========================================

public function validarCartaoAntesAssinatura(string $nomeCartao, string $numeroCartao, string $validadeMes, string $validadeAno, string $cvv): bool
{
    // ========================
    // VALIDAÇÕES BÁSICAS
    // ========================
    
    // Validar nome
    if (strlen(trim($nomeCartao)) < 3) {
        throw new Exception('Nome do titular inválido.');
    }
    
    // Validar número usando Luhn Algorithm
    $numeroCartao = preg_replace('/\D/', '', $numeroCartao);
    if (!$this->validarLuhn($numeroCartao)) {
        throw new Exception('Número do cartão inválido.');
    }
    
    // Validar validade
    $validadeMes = (int)$validadeMes;
    $validadeAno = (int)$validadeAno;
    
    if ($validadeMes < 1 || $validadeMes > 12) {
        throw new Exception('Mês de validade inválido.');
    }
    
    $anoAtual = (int)date('Y');
    if ($validadeAno < $anoAtual || ($validadeAno === $anoAtual && $validadeMes < (int)date('m'))) {
        throw new Exception('Cartão expirado.');
    }
    
    // Validar CVV
    $cvv = preg_replace('/\D/', '', $cvv);
    if (strlen($cvv) < 3 || strlen($cvv) > 4) {
        throw new Exception('CVV inválido.');
    }
    
    error_log('[PagamentoController] Validação de cartão bem-sucedida para ' . substr($numeroCartao, -4));
    
    return true;
}

/**
 * Validar número de cartão usando Algoritmo de Luhn
 */
private function validarLuhn(string $numero): bool
{
    $numero = preg_replace('/\D/', '', $numero);
    
    // Deve ter entre 13 e 19 dígitos
    if (strlen($numero) < 13 || strlen($numero) > 19) {
        return false;
    }
    
    $soma = 0;
    $dobrar = false;
    
    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $digito = (int)$numero[$i];
        
        if ($dobrar) {
            $digito *= 2;
            if ($digito > 9) {
                $digito -= 9;
            }
        }
        
        $soma += $digito;
        $dobrar = !$dobrar;
    }
    
    return ($soma % 10) === 0;
}


// ==========================================
// MÉTODO NOVO: Buscar Status da Assinatura
// ==========================================

public function sincronizarStatusAssinatura(string $efiSubscriptionId): array
{
    if (empty($efiSubscriptionId)) {
        throw new Exception('ID de assinatura EFI não fornecido.');
    }

    try {
        $api = $this->getApi();
        
        // Usar o método correto da API EFI para obter detalhes da assinatura
        $response = $api->getSubscription(['id' => $efiSubscriptionId], []);
        
        if (!is_array($response)) {
            throw new Exception('Resposta inválida da API ao buscar assinatura.');
        }

        error_log('[PagamentoController] Status da assinatura ' . $efiSubscriptionId . ': ' . json_encode($response));

        return [
            'efi_subscription_id' => $efiSubscriptionId,
            'status' => $response['status'] ?? 'unknown',
            'proxima_cobranca' => $response['next_billing_date'] ?? null,
            'ultimo_pagamento' => $response['last_billing_date'] ?? null,
        ];

    } catch (Exception $e) {
        error_log('[PagamentoController] Erro ao sincronizar status de assinatura: ' . $e->getMessage());
        throw new Exception('Erro ao sincronizar status: ' . $e->getMessage());
    }
}


// ==========================================
// MÉTODO NOVO: Cancelar Assinatura na EFI
// ==========================================

public function cancelarAssinaturaEfi(string $efiSubscriptionId): bool
{
    if (empty($efiSubscriptionId)) {
        throw new Exception('ID de assinatura EFI não fornecido.');
    }

    try {
        $api = $this->getApi();
        
        // Usar o método da API EFI para cancelar assinatura
        $response = $api->cancelSubscription(['id' => $efiSubscriptionId], []);
        
        error_log('[PagamentoController] Assinatura ' . $efiSubscriptionId . ' cancelada na EFI. Resposta: ' . json_encode($response));
        
        return true;

    } catch (Exception $e) {
        error_log('[PagamentoController] Erro ao cancelar assinatura na EFI: ' . $e->getMessage());
        // Não lançar exceção pois o cancelamento local é mais importante
        return false;
    }
}
