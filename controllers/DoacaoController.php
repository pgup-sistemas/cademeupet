<?php

/**
 * Cadê Meu Pet? - Controller de Doações
 * Gerencia o fluxo de doações, resumo para dashboards e histórico do usuário.
 */
class DoacaoController
{
    private $doacaoModel;

    public function __construct()
    {
        $this->doacaoModel = new Doacao();
    }

    public function criar(array $dados)
    {
        $dados = sanitize($dados);
        $erros = $this->validarDados($dados);

        if (!empty($erros)) {
            return ['success' => false, 'errors' => $erros];
        }

        $payload = $this->converterPayload($dados);

        try {
            $doacaoId = $this->doacaoModel->create($payload);

            $metodo = strtolower((string)($payload['metodo_pagamento'] ?? ''));
            if ($metodo === 'pix') {
                $pagamentoController = new PagamentoController();

                $doacaoParaPix = $payload;
                $doacaoParaPix['id'] = $doacaoId;

                // Preparar split se habilitado nas configs
                $split = null;
                if (defined('EFI_SPLIT_ENABLED') && EFI_SPLIT_ENABLED === true) {
                    $splitJson = (string)(defined('EFI_SPLIT_RULES_JSON') ? EFI_SPLIT_RULES_JSON : '');
                    if ($splitJson !== '') {
                        $decoded = json_decode($splitJson, true);
                        if (is_array($decoded)) {
                            $split = $decoded;
                        } else {
                            error_log('[DoacaoController] EFI_SPLIT_RULES_JSON inválido: ' . $splitJson);
                        }
                    }
                }

                $pix = $pagamentoController->criarCobrancaPix($doacaoParaPix, 'Doação Cadê Meu Pet? #' . $doacaoId, $split);

                $updateExtras = [
                    'gateway' => 'efi',
                    'transaction_id' => (string)$pix['txid'],
                    'pix_qrcode' => json_encode($pix['qrcode']),
                ];

                if (!empty($pix['split'])) {
                    $updateExtras['efi_split'] = json_encode($pix['split']);
                } elseif (!empty($split)) {
                    $updateExtras['efi_split'] = json_encode($split);
                }

                $this->doacaoModel->updateStatus(
                    (int)$doacaoId,
                    'pendente',
                    $updateExtras
                );

                if (!isset($_SESSION['pix_doacoes']) || !is_array($_SESSION['pix_doacoes'])) {
                    $_SESSION['pix_doacoes'] = [];
                }

                $_SESSION['pix_doacoes'][(int)$doacaoId] = [
                    'txid' => (string)$pix['txid'],
                    'qrcode' => $pix['qrcode'],
                    'split' => $pix['split'] ?? $split ?? null,
                ];

                return ['success' => true, 'id' => $doacaoId, 'redirect' => '/doacao-pix?id=' . $doacaoId];
            }

            if ($metodo === 'cartao_avista') {
                $pagamentoController = new PagamentoController();
                $resp = $pagamentoController->criarLinkPagamentoDoacao((int)$doacaoId, (float)($payload['valor'] ?? 0), 'cartao_avista');
                $paymentUrl = (string)($resp['data']['payment_url'] ?? ($resp['payment_url'] ?? ''));
                $chargeId = (string)($resp['data']['charge_id'] ?? ($resp['data']['charge']['id'] ?? ''));

                $this->doacaoModel->updateStatus(
                    (int)$doacaoId,
                    'pendente',
                    [
                        'gateway' => 'efi',
                        'payment_url' => $paymentUrl !== '' ? $paymentUrl : null,
                        'efi_charge_id' => $chargeId !== '' ? $chargeId : null,
                    ]
                );

                if ($paymentUrl !== '') {
                    // Redirecionar para rota local que abre o checkout em nova aba (target=_blank)
                    $redirectLocal = '/doacao-abrir-pagamento.php?id=' . $doacaoId;
                    return ['success' => true, 'id' => $doacaoId, 'redirect' => $redirectLocal];
                }

                throw new Exception('Não foi possível gerar o link de pagamento do cartão.');
            }

            if ($metodo === 'cartao_recorrente') {
                $usuarioId = (int)(getUserId() ?? 0);
                $pagamentoController = new PagamentoController();
                $resp = $pagamentoController->criarAssinaturaCartaoDoacao($usuarioId, (int)$doacaoId, (float)($payload['valor'] ?? 0));

                $paymentUrl = (string)($resp['data']['payment_url'] ?? '');
                $subscriptionId = (string)($resp['data']['subscription_id'] ?? '');
                $chargeId = (string)($resp['data']['charge']['id'] ?? ($resp['data']['charge_id'] ?? ''));
                $planId = (int)($resp['_petfinder_plan_id'] ?? 0);

                $this->doacaoModel->updateStatus(
                    (int)$doacaoId,
                    'pendente',
                    [
                        'gateway' => 'efi',
                        'payment_url' => $paymentUrl !== '' ? $paymentUrl : null,
                        'efi_subscription_id' => $subscriptionId !== '' ? $subscriptionId : null,
                        'efi_charge_id' => $chargeId !== '' ? $chargeId : null,
                        'efi_plan_id' => $planId > 0 ? $planId : null,
                        'proxima_cobranca' => date('Y-m-d', strtotime('+30 days')),
                    ]
                );

                if ($paymentUrl !== '') {
                    // Redirecionar para rota local que abre o checkout em nova aba (target=_blank)
                    $redirectLocal = '/doacao-abrir-pagamento.php?id=' . $doacaoId;
                    return ['success' => true, 'id' => $doacaoId, 'redirect' => $redirectLocal];
                }

                throw new Exception('Não foi possível gerar o link de pagamento do cartão recorrente.');
            }

            return ['success' => true, 'id' => $doacaoId];
        } catch (Exception $e) {
            $msg = $e->getMessage();
            error_log('[DoacaoController] Erro ao registrar doação: ' . $msg);

            // Geração de ID de debug para rastreamento
            try {
                $debugId = 'doacao_' . date('YmdHis') . '_' . substr(bin2hex(random_bytes(4)), 0, 8);
            } catch (Throwable $t) {
                $debugId = 'doacao_' . date('YmdHis') . '_' . substr(md5(uniqid('', true)), 0, 8);
            }

            // Preparar payload de debug (não incluir segredos sensíveis)
            $debugEntry = [
                'id' => $debugId,
                'time' => date('c'),
                'user_id' => getUserId(),
                'payload' => $payload ?? null,
                'exception' => [
                    'message' => $msg,
                    'trace' => $e->getTraceAsString()
                ],
                'server' => [
                    'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
                    'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ]
            ];

            // Salvar em log específico para doações (somente se DEBUG_DOACAO estiver ativo)
            $debugEnabled = (bool)envValue('DEBUG_DOACAO', false);
            $logPath = BASE_PATH . '/logs/doacao_debug.log';
            if ($debugEnabled) {
                @mkdir(dirname($logPath), 0755, true);
                file_put_contents($logPath, json_encode($debugEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
            }

            // Registrar no error_log sempre (apenas ID e mensagem curta)
            error_log('[DoacaoController][DEBUG] DebugID: ' . $debugId . ' - ' . $msg);

            // Mensagem padrão para o usuário (incluir código de debug para suporte)
            $userError = 'Não foi possível registrar a doação. Tente novamente. Código para suporte: ' . $debugId;

            // Se for um erro relacionado à integração EFI (SDK ausente, credenciais ou certificado),
            // retornar uma mensagem mais clara ao usuário e instruções para o administrador.
            if (
                stripos($msg, 'efi') !== false ||
                stripos($msg, 'sdk') !== false ||
                stripos($msg, 'certificado') !== false ||
                stripos($msg, 'composer') !== false ||
                stripos($msg, 'credenciais') !== false
            ) {
                $userError = 'Pagamento com cartão indisponível no momento. Por favor, selecione outro método ou tente novamente mais tarde. Código para suporte: ' . $debugId;

                // Log com instrução administrativa detalhada (somente para logs)
                error_log('[DoacaoController] AÇÃO SUGERIDA: Verificar instalação da SDK EFI (composer install), credenciais EFI (EFI_CLIENT_ID/Efi_CLIENT_SECRET) e o caminho do certificado (EFI_CERTIFICATE_PATH). DebugID: ' . $debugId);
            }

            return ['success' => false, 'errors' => [$userError], 'debug_id' => $debugId];
        }
    }

    public function listarHistorico(int $usuarioId, int $pagina = 1)
    {
        $pagina = max(1, $pagina);
        $limite = 20;
        $offset = ($pagina - 1) * $limite;

        return $this->doacaoModel->findByUser($usuarioId, $limite, $offset);
    }

    public function resumoDashboard()
    {
        return $this->doacaoModel->getDashboardSummary();
    }

    public function mural(int $limite = 20)
    {
        return $this->doacaoModel->getMural($limite);
    }

    public function metaAtual()
    {
        return $this->doacaoModel->getCurrentGoalProgress();
    }

    private function validarDados(array $dados): array
    {
        $erros = [];

        $minDonation = (float)envValue('MIN_DONATION_AMOUNT', MIN_DONATION_AMOUNT);
        
        // Obter valor (pode ser do radio ou do campo customizado)
        $valor = 0;
        if (!empty($dados['valor'])) {
            $valor = (float)$dados['valor'];
        } elseif (!empty($dados['valor_custom'])) {
            $valor = (float)$dados['valor_custom'];
        }
        
        if ($valor === 0 || $valor < $minDonation) {
            $erros[] = 'Valor da doação inválido. O mínimo é R$ ' . number_format($minDonation, 2, ',', '.');
        }

        if (empty($dados['metodo_pagamento'])) {
            $erros[] = 'Selecione um método de pagamento.';
        } else {
            $metodo = strtolower((string)$dados['metodo_pagamento']);
            $validMetodos = ['pix', 'cartao_avista', 'cartao_recorrente'];
            if (!in_array($metodo, $validMetodos, true)) {
                $erros[] = 'Método de pagamento inválido.';
            }

            if (!empty($dados['recorrente']) && $metodo !== 'cartao_recorrente') {
                $erros[] = 'Para doação mensal, use Cartão (mensal).';
            }

            if ($metodo === 'cartao_recorrente' && !isLoggedIn()) {
                $erros[] = 'Para doação mensal é necessário estar logado.';
            }
        }

        if (!empty($dados['email_doador']) && !isValidEmail($dados['email_doador'])) {
            $erros[] = 'Email informado é inválido.';
        }

        if (!empty($dados['cpf_doador']) && !$this->validarCPF($dados['cpf_doador'])) {
            $erros[] = 'CPF informado é inválido.';
        }

        if (!empty($dados['nome_doador']) && strlen($dados['nome_doador']) < 3) {
            $erros[] = 'Nome do doador deve conter pelo menos 3 caracteres.';
        }

        return $erros;
    }

    private function converterPayload(array $dados): array
    {
        $usuarioId = getUserId();
        
        // Obter valor (pode ser do radio ou do campo customizado)
        $valor = 0;
        if (!empty($dados['valor'])) {
            $valor = (float)$dados['valor'];
        } elseif (!empty($dados['valor_custom'])) {
            $valor = (float)$dados['valor_custom'];
        }

        $metodo = strtolower((string)($dados['metodo_pagamento'] ?? 'pix'));
        $isRecorrente = !empty($dados['recorrente']) || $metodo === 'cartao_recorrente';

        $payload = [
            'usuario_id' => $usuarioId ?: null,
            'valor' => $valor,
            'tipo' => $isRecorrente ? 'mensal' : 'unica',
            'metodo_pagamento' => $metodo,
            'gateway' => $dados['gateway'] ?? 'manual',
            'nome_doador' => $dados['nome_doador'] ?? null,
            'email_doador' => $dados['email_doador'] ?? null,
            'cpf_doador' => $dados['cpf_doador'] ?? null,
            'mensagem' => $dados['mensagem'] ?? null,
            'exibir_mural' => !empty($dados['exibir_mural']) ? 1 : 0,
            'status' => 'pendente',
            'data_doacao' => date('Y-m-d H:i:s')
        ];

        return $payload;
    }

    private function validarCPF(string $cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) != 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }
}

