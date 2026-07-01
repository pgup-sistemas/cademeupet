<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// ========================
// ALLOWLIST DE IP (mesma lista do efi-webhook.php)
// ========================
$efiAllowedIps = [
    '34.193.116.68', '34.201.82.218', '52.71.157.255',
    '52.72.250.233', '52.201.120.24', '100.28.11.138', '18.215.141.45',
    '54.167.39.240', '52.2.242.99',
];
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $remoteIp = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
}
if (!IS_LOCAL && !in_array($remoteIp, $efiAllowedIps, true)) {
    http_response_code(403);
    error_log('[efi-billing-notification] IP bloqueado: ' . $remoteIp);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

// ========================
// VALIDAR TOKEN (apenas header — nunca via GET para evitar exposição em logs)
// ========================
$tokenAuth = '';
if (isset($_SERVER['HTTP_X_WEBHOOK_TOKEN'])) {
    $tokenAuth = (string)$_SERVER['HTTP_X_WEBHOOK_TOKEN'];
} elseif (isset($_SERVER['HTTP_X_EFI_WEBHOOK_TOKEN'])) {
    $tokenAuth = (string)$_SERVER['HTTP_X_EFI_WEBHOOK_TOKEN'];
}

$expectedToken = (string)envValue('EFI_BILLING_WEBHOOK_TOKEN', (string)EFI_WEBHOOK_TOKEN);
if ($expectedToken !== '' && !hash_equals($expectedToken, $tokenAuth)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

// ========================
// OBTER NOTIFICATION TOKEN
// ========================
$notificationToken = '';

if (isset($_POST['notification'])) {
    $notificationToken = (string)$_POST['notification'];
} else {
    $raw = file_get_contents('php://input');
    $data = json_decode((string)$raw, true);
    if (is_array($data) && isset($data['notification'])) {
        $notificationToken = (string)$data['notification'];
    }
}

$notificationToken = trim($notificationToken);
if (empty($notificationToken)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_notification_token']);
    exit;
}

// ========================
// PROCESSAR NOTIFICAÇÃO
// ========================
try {
    $pagamentoController = new PagamentoController();
    $efi = $pagamentoController->getApi();

    // Obter detalhes da notificação
    $chargeNotification = $efi->getNotification(['token' => $notificationToken], []);

    $dataList = $chargeNotification['data'] ?? [];

    if (!is_array($dataList) || empty($dataList)) {
        http_response_code(200);
        echo json_encode(['ok' => true, 'status' => 'no_data']);
        exit;
    }

    // Pegar o último evento
    $ultimo = $dataList[count($dataList) - 1];
    $statusAtual = strtoupper((string)($ultimo['status']['current'] ?? ''));

    // Extrair IDs da transação
    $identifiers = (array)($ultimo['identifiers'] ?? []);
    $chargeId = $identifiers['charge_id'] ?? null;
    $subscriptionId = $identifiers['subscription_id'] ?? null;

    error_log('[efi-billing-notification] Status: ' . $statusAtual . ', ChargeId: ' . ($chargeId ?? 'N/A') . ', SubscriptionId: ' . ($subscriptionId ?? 'N/A'));

    // ========================
    // PROCURAR PAGAMENTO/DOAÇÃO
    // ========================
    $parceiroPagamentoModel = new ParceiroPagamento();
    $doacaoModel = new Doacao();

    $pagamento = null;
    $doacao = null;

    // Procurar pagamento de parceiro
    if (!empty($chargeId)) {
        $pagamento = $parceiroPagamentoModel->findByEfiChargeId((string)$chargeId);
    }
    if (!$pagamento && !empty($subscriptionId)) {
        $pagamento = $parceiroPagamentoModel->findLastBySubscriptionId((string)$subscriptionId);
    }

    // Procurar doação
    if (!$pagamento && !empty($chargeId)) {
        $doacao = $doacaoModel->findByEfiChargeId((string)$chargeId);
    }
    if (!$pagamento && !$doacao && !empty($subscriptionId)) {
        $doacao = $doacaoModel->findLastBySubscriptionId((string)$subscriptionId);
    }

    if (empty($pagamento) && empty($doacao)) {
        http_response_code(404);
        error_log('[efi-billing-notification] Nenhuma transação encontrada para ChargeId: ' . ($chargeId ?? 'N/A'));
        echo json_encode(['ok' => false, 'error' => 'transaction_not_found']);
        exit;
    }

    // ========================
    // MAPEAR STATUS
    // ========================
    $novoStatus = null;
    $statusEventType = strtoupper((string)($ultimo['status']['type'] ?? ''));

    if (in_array($statusAtual, ['PAID', 'SETTLED'], true)) {
        $novoStatus = 'aprovado';
    } elseif (in_array($statusAtual, ['UNPAID', 'CANCELED', 'CANCELLED', 'REFUSED', 'REJECTED'], true)) {
        $novoStatus = 'recusado';
    }

    error_log('[efi-billing-notification] Event Type: ' . $statusEventType . ', Status Type: ' . ($ultimo['status']['type'] ?? 'N/A'));

    // Verificar se é webhook de assinatura
    $isSubscriptionEvent = !empty($subscriptionId) && (
        strpos($statusEventType, 'SUBSCRIPTION') !== false ||
        strpos($statusEventType, 'BILL') !== false ||
        !empty($subscriptionId)
    );

    if ($novoStatus && !empty($pagamento)) {
        $update = [];
        if (!empty($chargeId)) {
            $update['efi_charge_id'] = (string)$chargeId;
        }
        if (!empty($subscriptionId)) {
            $update['efi_subscription_id'] = (string)$subscriptionId;
        }

        if (($pagamento['status'] ?? '') !== $novoStatus) {
            $update['status'] = $novoStatus;
            if ($novoStatus === 'aprovado') {
                $update['aprovado_em'] = date('Y-m-d H:i:s');
            } elseif ($novoStatus === 'recusado') {
                $update['recusado_em'] = date('Y-m-d H:i:s');
            }
        }

        if (!empty($update)) {
            $parceiroPagamentoModel->update((int)$pagamento['id'], $update);
            error_log('[efi-billing-notification] Pagamento parceiro atualizado: ' . json_encode($update));
        }

        // Se aprovado, atualizar assinatura
        if ($novoStatus === 'aprovado') {
            $assinaturaModel = new ParceiroAssinatura();
            $perfilModel = new ParceiroPerfil();

            $usuarioId = (int)($pagamento['usuario_id'] ?? 0);
            $periodicidade = (string)($pagamento['periodicidade'] ?? 'mensal');
            $pagoAte = $periodicidade === 'anual'
                ? date('Y-m-d', strtotime('+1 year'))
                : date('Y-m-d', strtotime('+30 days'));

            $assinaturaUpdate = [
                'status' => 'ativa',
                'ultimo_pagamento_em' => date('Y-m-d H:i:s'),
                'pago_ate' => $pagoAte,
                'proxima_cobranca' => $pagoAte,
                'metodo_pagamento' => 'gateway',
            ];
            if (!empty($subscriptionId)) {
                $assinaturaUpdate['efi_subscription_id'] = (int)$subscriptionId;
            }
            $assinaturaModel->updateForUser($usuarioId, $assinaturaUpdate);

            $perfilModel->publishForUser($usuarioId, true);
            $perfilModel->setHighlightForUser($usuarioId, ($pagamento['plano'] ?? '') === 'destaque');
        }
    }

    // ========================
    // ATUALIZAR DOAÇÃO
    // ========================
    if ($novoStatus && !empty($doacao)) {
        $update = [];
        if (!empty($chargeId)) {
            $update['efi_charge_id'] = (string)$chargeId;
        }
        if (!empty($subscriptionId)) {
            $update['efi_subscription_id'] = (string)$subscriptionId;
        }

        if ($novoStatus === 'aprovado') {
            if (($doacao['status'] ?? '') !== 'aprovada') {
                $update['status'] = 'aprovada';
                $update['ultimo_pagamento_em'] = date('Y-m-d H:i:s');
            }
        } elseif ($novoStatus === 'recusado') {
            if (($doacao['status'] ?? '') !== 'cancelada') {
                $update['status'] = 'cancelada';
                $update['cancelada_em'] = date('Y-m-d H:i:s');
            }
        }

        if (!empty($update)) {
            $doacaoModel->update((int)$doacao['id'], $update);
            error_log('[efi-billing-notification] Doação atualizada: ' . json_encode($update));
        }

        // Se aprovada, atualizar meta
        if (($update['status'] ?? '') === 'aprovada') {
            $doacaoAtual = $doacaoModel->findById((int)$doacao['id']);
            if (!empty($doacaoAtual)) {
                $doacaoModel->updateGoalProgress((float)($doacaoAtual['valor'] ?? 0));
            }
        }
    }

    // ========================
    // RESPOSTA SUCESSO
    // ========================
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'status' => $statusAtual,
        'mapped_status' => $novoStatus,
    ]);
    exit;

} catch (Exception $e) {
    error_log('[efi-billing-notification] Exceção ao processar notificação: ' . $e->getMessage());
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'processing_error']);
    exit;
}
