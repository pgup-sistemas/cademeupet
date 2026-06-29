<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Payment Tests (Efi) ===\n";
echo "Time: " . date('c') . "\n\n";

$pg = new PagamentoController();

$tests = [];

$tests['doacao_cartao_avista'] = function () use ($pg) {
    return $pg->criarLinkPagamentoDoacao(999999, 10.00, 'cartao_avista');
};

$tests['doacao_cartao_recorrente'] = function () use ($pg) {
    // usuarioId precisa ser válido para recorrente
    return $pg->criarAssinaturaCartaoDoacao(1, 999999, 10.00);
};

$tests['parceiro_cartao_avista'] = function () use ($pg) {
    return $pg->criarLinkPagamentoParceiro(999999, 10.00, 'cartao_avista');
};

foreach ($tests as $name => $fn) {
    echo "--- TEST: {$name} ---\n";
    try {
        $resp = $fn();
        echo "OK\n";

        $paymentUrl = '';
        if (is_array($resp)) {
            $paymentUrl = (string)($resp['data']['payment_url'] ?? ($resp['payment_url'] ?? ''));
        }

        if ($paymentUrl !== '') {
            echo "payment_url: {$paymentUrl}\n";
        } else {
            echo "payment_url: (vazio)\n";
        }

        // imprimir resumo enxuto
        if (is_array($resp)) {
            $data = $resp['data'] ?? $resp;
            if (is_array($data)) {
                $chargeId = (string)($data['charge_id'] ?? ($data['charge']['id'] ?? ''));
                $subscriptionId = (string)($data['subscription_id'] ?? '');
                if ($chargeId !== '') echo "charge_id: {$chargeId}\n";
                if ($subscriptionId !== '') echo "subscription_id: {$subscriptionId}\n";
            }
        }

    } catch (Throwable $e) {
        echo "FAIL: " . $e->getMessage() . "\n";
        echo "Trace: " . $e->getTraceAsString() . "\n";
    }
    echo "\n";
}
