<?php

class PagamentoController
{
    public function getApi(): Efi
    {
        if (!class_exists(Efi::class)) {
            throw new Exception('SDK Efí simplificada não encontrada em includes/efi.php.');
        }

        $clientId     = (string)EFI_CLIENT_ID;
        $clientSecret = (string)EFI_CLIENT_SECRET;

        if ($clientId === '' || $clientSecret === '') {
            throw new Exception('Credenciais da Efí não configuradas (EFI_CLIENT_ID/EFI_CLIENT_SECRET).');
        }

        // EFI_CERTIFICATE_PATH já resolvido pelo config.php (secrets/ como default)
        $certificate = trim((string)EFI_CERTIFICATE_PATH);

        $options = [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'certificate'   => $certificate,
            'sandbox'       => (bool)EFI_SANDBOX,
            'base_url'      => (string)EFI_BASE_URL,
        ];

        return new Efi($options);
    }

    public function criarLinkPagamentoDoacao(int $doacaoId, float $valor, string $gatewayTipo)
    {
        $valorCentavos = (int)round($valor * 100);
        if ($valorCentavos < 1) {
            throw new Exception('Valor inválido para pagamento.');
        }

        $paymentMethod = 'all';
        if ($gatewayTipo === 'cartao_avista') {
            $paymentMethod = 'credit_card';
        } elseif ($gatewayTipo === 'pix') {
            $paymentMethod = 'pix';
        }

        $metadata = [
            'custom_id' => 'DOACAO_' . (int)$doacaoId,
        ];
        if ($this->shouldSendBillingNotificationUrl()) {
            $metadata['notification_url'] = $this->getBillingNotificationUrl();
        }

        $body = [
            'items' => [
                [
                    'name' => 'Doação Cadê Meu Pet?',
                    'amount' => 1,
                    'value' => $valorCentavos,
                ],
            ],
            'metadata' => $metadata,
            'settings' => [
                'payment_method' => $paymentMethod,
                'request_delivery_address' => false,
                'expire_at' => $this->getBillingExpireAt(),
            ],
        ];

        try {
            $api = $this->getApi();
            $response = $api->createOneStepLink([], $body);
            
            if (!is_array($response)) {
                throw new Exception('Resposta inválida da API EFI ao criar link de pagamento.');
            }

            return $response;
        } catch (Exception $e) {
            error_log('[PagamentoController] Erro ao criar link de pagamento (doação): ' . $e->getMessage());
            throw new Exception('Erro ao criar link de pagamento: ' . $e->getMessage());
        }
    }

    public function criarAssinaturaCartaoDoacao(int $usuarioId, int $doacaoId, float $valorMensal)
    {
        $usuarioId = (int)$usuarioId;
        if ($usuarioId <= 0) {
            throw new Exception('Para doação recorrente é necessário estar logado.');
        }

        $api = $this->getApi();
        $notificationUrl = $this->getBillingNotificationUrl();

        try {
            // Criar plano de assinatura
            $planResp = $api->createPlan([], [
                'name' => 'Doação Cadê Meu Pet? - Mensal',
                'interval' => 1,
                'repeats' => null,
            ]);
            
            $planId = (int)($planResp['data']['plan_id'] ?? ($planResp['plan_id'] ?? 0));
            if ($planId <= 0) {
                throw new Exception('Não foi possível criar o plano de assinatura de doação na Efí.');
            }

            $valorCentavos = (int)round($valorMensal * 100);
            if ($valorCentavos < 1) {
                throw new Exception('Valor inválido para assinatura.');
            }

            $metadata = [
                'custom_id' => 'DOACAO_ASSINATURA_' . (int)$doacaoId,
            ];
            if ($this->shouldSendBillingNotificationUrl()) {
                $metadata['notification_url'] = $notificationUrl;
            }

            $resp = $api->createOneStepSubscriptionLink(['id' => $planId], [
                'items' => [
                    [
                        'name' => 'Doação Cadê Meu Pet? (mensal)',
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

            $resp['_petfinder_plan_id'] = $planId;
            return $resp;
        } catch (Exception $e) {
            error_log('[PagamentoController] Erro ao criar assinatura (doação): ' . $e->getMessage());
            throw new Exception('Erro ao criar assinatura de doação: ' . $e->getMessage());
        }
    }

    private function getBillingNotificationUrl(): string
    {
        $token = (string)envValue('EFI_BILLING_WEBHOOK_TOKEN', (string)EFI_WEBHOOK_TOKEN);
        $url = rtrim((string)BASE_URL, '/') . '/api/efi-billing-notification.php';
        if ($token !== '') {
            $url .= '?token=' . urlencode($token);
        }
        return $url;
    }

    private function shouldSendBillingNotificationUrl(): bool
    {
        $baseUrl = strtolower((string)BASE_URL);

        if (str_contains($baseUrl, 'localhost') || str_contains($baseUrl, '127.0.0.1')) {
            return false;
        }

        return true;
    }

    private function getBillingExpireAt(): string
    {
        return date('Y-m-d', strtotime('+7 days'));
    }

    public function criarLinkPagamentoParceiro(int $pagamentoId, float $valor, string $gatewayTipo, string $periodicidade = 'mensal')
    {
        $valorCentavos = (int)round($valor * 100);
        if ($valorCentavos < 1) {
            throw new Exception('Valor inválido para pagamento.');
        }

        $paymentMethod = 'all';
        if ($gatewayTipo === 'pix') {
            $paymentMethod = 'pix';
        } elseif ($gatewayTipo === 'cartao_avista') {
            $paymentMethod = 'credit_card';
        }

        $metadata = [
            'custom_id' => 'PARCEIRO_PAGAMENTO_' . (int)$pagamentoId,
        ];
        if ($this->shouldSendBillingNotificationUrl()) {
            $metadata['notification_url'] = $this->getBillingNotificationUrl();
        }

        $body = [
            'items' => [
                [
                    'name'   => 'Assinatura Parceiro Cadê Meu Pet?',
                    'amount' => 1,
                    'value'  => $valorCentavos,
                ],
            ],
            'metadata' => $metadata,
            'settings' => [
                'payment_method'           => $paymentMethod,
                'request_delivery_address' => false,
                'expire_at'                => $this->getBillingExpireAt(),
            ],
        ];

        try {
            $api = $this->getApi();
            $response = $api->createOneStepLink([], $body);
            
            if (!is_array($response)) {
                throw new Exception('Resposta inválida da API EFI ao criar link de pagamento.');
            }

            return $response;
        } catch (Exception $e) {
            error_log('[PagamentoController] Erro ao criar link de pagamento (parceiro): ' . $e->getMessage());
            throw new Exception('Erro ao criar link de pagamento: ' . $e->getMessage());
        }
    }

    public function criarAssinaturaCartaoParceiro(int $usuarioId, int $pagamentoId, float $valorMensal, string $plano)
    {
        try {
            $assinaturaModel = new ParceiroAssinatura();
            $assinatura = $assinaturaModel->findByUserId($usuarioId);

            $api = $this->getApi();
            $notificationUrl = $this->getBillingNotificationUrl();

            $planId = (int)($assinatura['efi_plan_id'] ?? 0);
            if ($planId <= 0) {
                $planResp = $api->createPlan([], [
                    'name' => 'Parceiro Cadê Meu Pet? - ' . ($plano === 'destaque' ? 'Destaque' : 'Básico'),
                    'interval' => 1,
                    'repeats' => null,
                ]);
                $planId = (int)($planResp['data']['plan_id'] ?? ($planResp['plan_id'] ?? 0));
                if ($planId <= 0) {
                    throw new Exception('Não foi possível criar o plano de assinatura na Efí.');
                }
                $assinaturaModel->updateForUser($usuarioId, ['efi_plan_id' => $planId]);
            }

            $valorCentavos = (int)round($valorMensal * 100);
            $metadata = [
                'custom_id' => 'PARCEIRO_ASSINATURA_' . (int)$usuarioId,
            ];
            if ($this->shouldSendBillingNotificationUrl()) {
                $metadata['notification_url'] = $notificationUrl;
            }
            
            $resp = $api->createOneStepSubscriptionLink(['id' => $planId], [
                'items' => [
                    [
                        'name' => 'Assinatura Parceiro Cadê Meu Pet?',
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

            return $resp;
        } catch (Exception $e) {
            error_log('[PagamentoController] Erro ao criar assinatura (parceiro): ' . $e->getMessage());
            throw new Exception('Erro ao criar assinatura de parceiro: ' . $e->getMessage());
        }
    }

    public function criarCobrancaPix(array $doacao, string $descricao = 'Doação Cadê Meu Pet?', ?array $split = null)
    {
        $valor = number_format((float)($doacao['valor'] ?? 0), 2, '.', '');

        $infoAdicionais = [
            ['nome' => 'Aplicacao', 'valor' => 'Cadê Meu Pet?'],
            ['nome' => 'DoacaoID',  'valor' => (string)($doacao['id'] ?? '')],
        ];

        $extraBody = [];
        $cpf  = preg_replace('/[^0-9]/', '', (string)($doacao['cpf_doador'] ?? ''));
        $nome = trim((string)($doacao['nome_doador'] ?? ''));
        if ($cpf !== '' && strlen($cpf) === 11 && $nome !== '') {
            $extraBody['devedor'] = ['cpf' => $cpf, 'nome' => $nome];
        }

        return $this->emitirCobrancaPixInterna($valor, $descricao, $infoAdicionais, $split, $extraBody, 'doação');
    }

    public function detalharCobrancaPix(string $txid): array
    {
        $txid = trim($txid);
        if ($txid === '') {
            throw new Exception('TXID inválido.');
        }

        try {
            $api = $this->getApi();
            $response = $api->pixDetailCharge(['txid' => $txid]);
            
            if (!is_array($response)) {
                throw new Exception('Resposta inválida da API EFI ao detalhar cobrança PIX.');
            }

            return $response;
        } catch (Exception $e) {
            error_log('[PagamentoController] Erro ao detalhar cobrança PIX: ' . $e->getMessage());
            throw new Exception('Erro ao detalhar cobrança PIX: ' . $e->getMessage());
        }
    }

    public function sincronizarStatusDoacaoPix(int $doacaoId, string $txid)
    {
        $doacaoModel = new Doacao();
        $doacao = $doacaoModel->findById($doacaoId);
        if (empty($doacao)) {
            throw new Exception('Doação não encontrada.');
        }

        if (($doacao['status'] ?? '') === 'aprovada' || ($doacao['status'] ?? '') === 'aprovado') {
            return $doacao;
        }

        try {
            $detail = $this->detalharCobrancaPix($txid);

            // Algumas respostas da API podem manter status 'ATIVA', mas já trazer pagamentos em `pix`.
            // Se existir ao menos um item em pix, considerar como pagamento confirmado.
            $hasPixPayment = false;
            if (!empty($detail['pix']) && is_array($detail['pix'])) {
                foreach ($detail['pix'] as $pixItem) {
                    if (is_array($pixItem) && !empty($pixItem)) {
                        $hasPixPayment = true;
                        break;
                    }
                }
            }

            // Extrair possíveis campos de status (várias versões da API podem variar a estrutura)
            $statusCandidates = [];
            if (!empty($detail['status'])) $statusCandidates[] = (string)$detail['status'];
            if (!empty($detail['cob']['status'])) $statusCandidates[] = (string)$detail['cob']['status'];
            if (!empty($detail['charge']['status'])) $statusCandidates[] = (string)$detail['charge']['status'];
            if (!empty($detail['pix'][0]['status'])) $statusCandidates[] = (string)$detail['pix'][0]['status'];
            if (!empty($detail['situacao'])) $statusCandidates[] = (string)$detail['situacao'];

            $statusCobranca = strtoupper(trim(implode(' ', $statusCandidates)));
            error_log('[PagamentoController] sincronizarStatusDoacaoPix - TXID: ' . $txid . ' - status detectado: ' . $statusCobranca);

            // Considerar variações que significam pagamento confirmado
            if ($hasPixPayment || str_contains($statusCobranca, 'CONCLUIDA') || str_contains($statusCobranca, 'LIQUI') || str_contains($statusCobranca, 'PAGO') || str_contains($statusCobranca, 'RECEB')) {
                $doacaoModel->updateStatus((int)$doacaoId, 'aprovada', ['ultimo_pagamento_em' => date('Y-m-d H:i:s')]);
                $doacaoModel->updateGoalProgress((float)($doacao['valor'] ?? 0));

                // Enviar e-mail de agradecimento (se disponível)
                $email = (string)($doacao['email_doador'] ?? '');
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $subject = 'Obrigado pela sua doação - Cadê Meu Pet?';
                    $message = "<html><body><p>Olá,</p><p>Muito obrigado pela sua doação (ID: " . (int)$doacaoId . "). Seu pagamento foi confirmado via PIX.</p><p>Abraços,<br/>Equipe Cadê Meu Pet?</p></body></html>";
                    try {
                        sendEmail($email, $subject, $message);
                    } catch (Throwable $t) {
                        error_log('[PagamentoController] Falha ao enviar email de agradecimento: ' . $t->getMessage());
                    }
                }

            } elseif (str_starts_with($statusCobranca, 'REMOVIDA')) {
                $doacaoModel->updateStatus((int)$doacaoId, 'cancelada', ['cancelada_em' => date('Y-m-d H:i:s')]);
            }

            return $doacaoModel->findById($doacaoId);
        } catch (Exception $e) {
            error_log('[PagamentoController] Erro ao sincronizar doação PIX: ' . $e->getMessage());
            throw new Exception('Erro ao sincronizar doação: ' . $e->getMessage());
        }
    }

    public function criarCobrancaPixParceiro(int $pagamentoId, float $valor, string $descricao, ?array $split = null)
    {
        $valorFormatado = number_format((float)$valor, 2, '.', '');

        $infoAdicionais = [
            ['nome' => 'Tipo',       'valor' => 'Parceiro'],
            ['nome' => 'PagamentoID','valor' => (string)$pagamentoId],
        ];

        return $this->emitirCobrancaPixInterna($valorFormatado, $descricao, $infoAdicionais, $split, [], 'parceiro');
    }

    /**
     * Núcleo da emissão PIX — reutilizado por doação e parceiro.
     */
    private function emitirCobrancaPixInterna(
        string $valor,
        string $descricao,
        array  $infoAdicionais,
        ?array $split,
        array  $extraBody,
        string $contexto
    ): array {
        $pixKey = (string)(defined('EFI_PIX_KEY') ? EFI_PIX_KEY : '');
        if ($pixKey === '') {
            throw new Exception('Chave Pix não configurada (EFI_PIX_KEY).');
        }

        $body = array_merge([
            'calendario'       => ['expiracao' => 3600],
            'valor'            => ['original' => $valor],
            'chave'            => $pixKey,
            'solicitacaoPagador' => $descricao,
            'infoAdicionais'   => $infoAdicionais,
        ], $extraBody);

        if (defined('EFI_SPLIT_ENABLED') && EFI_SPLIT_ENABLED === true && !empty($split)) {
            $body['split'] = $split;
            error_log("[PagamentoController] split anexado ({$contexto}): " . json_encode($split));
        }

        try {
            $api        = $this->getApi();
            $responsePix = $api->pixCreateImmediateCharge([], $body);

            if (empty($responsePix['txid'])) {
                throw new Exception('Resposta inválida da Efí: txid não fornecido.');
            }

            $locId = $responsePix['loc']['id'] ?? $responsePix['loc_id'] ?? null;
            if (empty($locId)) {
                throw new Exception('Resposta inválida da Efí: location ID não fornecido.');
            }

            $responseQr     = $api->pixGenerateQRCode(['id' => $locId]);
            $qrImageBase64  = $responseQr['imagemQrcode'] ?? $responseQr['imagem_qrcode'] ?? null;
            $qrText         = $responseQr['qrcode'] ?? $responseQr['qrcodeText'] ?? $responseQr['qrCodeText'] ?? null;

            if (empty($qrImageBase64) && empty($qrText)) {
                throw new Exception('Resposta inválida da Efí ao gerar QR Code.');
            }

            $qrImageDataUrl = '';
            if (!empty($qrImageBase64) && is_string($qrImageBase64)) {
                $img = trim($qrImageBase64);
                $qrImageDataUrl = str_starts_with($img, 'data:image') ? $img : ('data:image/png;base64,' . $img);
            }

            return [
                'txid'   => $responsePix['txid'],
                'qrcode' => [
                    'imagemQrcode' => $qrImageDataUrl,
                    'qrcode'       => is_string($qrText) ? trim($qrText) : '',
                    'raw'          => $responseQr,
                ],
                'charge' => $responsePix,
                'split'  => $split ?? null,
            ];
        } catch (Exception $e) {
            error_log("[PagamentoController] Erro ao criar cobrança PIX ({$contexto}): " . $e->getMessage());
            throw new Exception('Erro ao criar cobrança PIX: ' . $e->getMessage());
        }
    }

    public function cancelarAssinaturaGateway(string $subscriptionId): bool
    {
        if (empty($subscriptionId)) {
            throw new Exception('ID da assinatura não fornecido.');
        }

        try {
            $api = $this->getApi();
            
            $params = ['id' => $subscriptionId];
            $response = $api->cancelSubscription($params);
            
            // Verificar se o cancelamento foi bem-sucedido
            if (isset($response['data'])) {
                $status = strtolower((string)($response['data']['status'] ?? ''));
                return in_array($status, ['canceled', 'cancelled', 'inactive', 'inativo']);
            }
            
            // Se não houver 'data', a API pode retornar apenas status top-level
            if (isset($response['status'])) {
                $status = strtolower((string)$response['status']);
                return in_array($status, ['canceled', 'cancelled', 'inactive', 'inativo']);
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('[PagamentoController] Erro ao cancelar assinatura: ' . $e->getMessage());
            
            // Se já estiver cancelada, considerar sucesso
            if (str_contains(strtolower($e->getMessage()), 'not found') || 
                str_contains(strtolower($e->getMessage()), 'cancelada')) {
                return true;
            }
            
            throw new Exception('Erro ao cancelar assinatura no gateway: ' . $e->getMessage());
        }
    }

    public function sincronizarStatusParceiroPix(int $pagamentoId, string $txid)
    {
        $txid = trim($txid);
        if ($txid === '') {
            throw new Exception('TXID inválido.');
        }

        $pagamentoModel = new ParceiroPagamento();
        $pagamento = $pagamentoModel->findById($pagamentoId);
        if (empty($pagamento)) {
            throw new Exception('Pagamento não encontrado.');
        }

        if (($pagamento['status'] ?? '') === 'aprovado') {
            return $pagamento;
        }

        try {
            $detail = $this->detalharCobrancaPix($txid);
            $statusCobranca = strtoupper((string)($detail['status'] ?? ''));

            if ($statusCobranca === 'CONCLUIDA') {
                $usuarioId = (int)($pagamento['usuario_id'] ?? 0);

                $pagamentoModel->update((int)$pagamentoId, [
                    'status' => 'aprovado',
                    'aprovado_em' => date('Y-m-d H:i:s'),
                ]);

                $assinaturaModel = new ParceiroAssinatura();
                $perfilModel = new ParceiroPerfil();

                $periodicidade = (string)($pagamento['periodicidade'] ?? 'mensal');
                $pagoAte = $periodicidade === 'anual'
                    ? date('Y-m-d', strtotime('+1 year'))
                    : date('Y-m-d', strtotime('+30 days'));
                $assinaturaModel->updateForUser($usuarioId, [
                    'status' => 'ativa',
                    'ultimo_pagamento_em' => date('Y-m-d H:i:s'),
                    'pago_ate' => $pagoAte,
                    'proxima_cobranca' => $pagoAte,
                    'metodo_pagamento' => 'gateway',
                ]);

                $perfilModel->publishForUser($usuarioId, true);
                $perfilModel->setHighlightForUser($usuarioId, ($pagamento['plano'] ?? '') === 'destaque');

                $adminEmail = (string)envValue('DEFAULT_ADMIN_EMAIL', 'admin@cademeupet.com.br');
                if ($adminEmail !== '') {
                    $subject = 'Pagamento PIX confirmado (Parceiro) - Cadê Meu Pet?';
                    $message = "<html><body style='font-family: Arial, sans-serif;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                            <h2 style='color:#00CC66;'>Pagamento confirmado</h2>
                            <p>Um pagamento de parceiro foi confirmado via PIX (Efí).</p>
                            <p><strong>Pagamento ID:</strong> " . (int)$pagamentoId . "</p>
                            <p><strong>TXID:</strong> " . sanitize($txid) . "</p>
                            <p><a href='" . BASE_URL . "/admin/parceiros?tab=pagamentos'>Abrir Admin Parceiros</a></p>
                        </div>
                    </body></html>";
                    sendEmail($adminEmail, $subject, $message);
                }
            }

            return $pagamentoModel->findById($pagamentoId);
        } catch (Exception $e) {
            error_log('[PagamentoController] Erro ao sincronizar pagamento parceiro PIX: ' . $e->getMessage());
            throw new Exception('Erro ao sincronizar pagamento: ' . $e->getMessage());
        }
    }
}
