<?php
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo 'ID de doação inválido.'; exit; }

$doacaoModel = new Doacao();
$doacao      = $doacaoModel->findById($id);
if (empty($doacao)) { http_response_code(404); echo 'Doação não encontrada.'; exit; }

$ownerId = (int)($doacao['usuario_id'] ?? 0);
if ($ownerId > 0 && (!isLoggedIn() || (int)getUserId() !== $ownerId)) {
    http_response_code(403); echo 'Acesso negado.'; exit;
}

$paymentUrl = trim((string)($doacao['payment_url'] ?? ''));
$lastError  = null;

if ($paymentUrl === '') {
    try {
        $pag = new PagamentoController();

        if (($doacao['tipo'] ?? '') === 'mensal') {
            $usuarioId = (int)($doacao['usuario_id'] ?? 0);
            if ($usuarioId <= 0 || !isLoggedIn() || (int)getUserId() !== $usuarioId) {
                throw new Exception('Para gerar link de assinatura é necessário estar logado como o dono da doação.');
            }

            $resp = $pag->criarAssinaturaCartaoDoacao($usuarioId, (int)$doacao['id'], (float)$doacao['valor']);

            $paymentUrl     = (string)($resp['data']['payment_url'] ?? ($resp['payment_url'] ?? ''));
            $subscriptionId = (string)($resp['data']['subscription_id'] ?? '');
            $planId         = (int)($resp['_petfinder_plan_id'] ?? 0);
            $chargeId       = (string)($resp['data']['charge']['id'] ?? ($resp['data']['charge_id'] ?? ''));

            $update = ['payment_url' => $paymentUrl !== '' ? $paymentUrl : null, 'gateway' => 'efi'];
            if ($subscriptionId !== '') $update['efi_subscription_id'] = $subscriptionId;
            if ($planId > 0)           $update['efi_plan_id']          = $planId;
            if ($chargeId !== '')      $update['efi_charge_id']        = $chargeId;
            $doacaoModel->update((int)$doacao['id'], $update);
        } else {
            $resp = $pag->criarLinkPagamentoDoacao((int)$doacao['id'], (float)$doacao['valor'], 'cartao_avista');
            $paymentUrl = (string)($resp['data']['payment_url'] ?? ($resp['payment_url'] ?? ''));
            if ($paymentUrl !== '') {
                $doacaoModel->update((int)$doacao['id'], ['payment_url' => $paymentUrl, 'gateway' => 'efi']);
            }
        }
    } catch (Exception $e) {
        $lastError = $e->getMessage();
        error_log('[doacao-abrir-pagamento] ID ' . (int)$doacao['id'] . ': ' . $lastError);
    }
}

$backUrl = BASE_URL . '/doar/';
require __DIR__ . '/includes/open-payment.php';
