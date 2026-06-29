<?php
// Script de teste CONTROLADO em produção: cria uma doação única (cartão à vista) de R$2, gera payment_url e grava logs.
// Uso: php production_create_doacao_cartao.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Doacao.php';
require_once __DIR__ . '/../controllers/PagamentoController.php';

// Ativar debug localmente para capturar detalhes em logs
putenv('DEBUG_DOACAO=1');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$valor = 2.00;
$payload = [
    'usuario_id' => null,
    'valor' => $valor,
    'tipo' => 'unica',
    'metodo_pagamento' => 'cartao_avista',
    'gateway' => 'efi',
    'nome_doador' => 'Teste Produção',
    'email_doador' => 'teste+producao@example.com',
    'cpf_doador' => null,
    'mensagem' => 'Teste de cobrança real - confirmar',
    'exibir_mural' => 0,
    'status' => 'pendente',
    'data_doacao' => date('Y-m-d H:i:s'),
];

$logPrefix = 'production_doacao_' . date('Ymd_His');
$logPath = BASE_PATH . '/logs/' . $logPrefix . '.log';
@mkdir(dirname($logPath), 0755, true);

file_put_contents($logPath, "Starting production donation test at " . date('c') . PHP_EOL, FILE_APPEND | LOCK_EX);

try {
    $doacaoModel = new Doacao();
    $doacaoId = $doacaoModel->create($payload);

    file_put_contents($logPath, "Created doacao id: $doacaoId\n", FILE_APPEND | LOCK_EX);

    $pag = new PagamentoController();
    $resp = $pag->criarLinkPagamentoDoacao((int)$doacaoId, (float)$valor, 'cartao_avista');

    file_put_contents($logPath, "API Response: " . json_encode($resp, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);

    $paymentUrl = (string)($resp['data']['payment_url'] ?? ($resp['payment_url'] ?? ''));
    $chargeId = (string)($resp['data']['charge']['id'] ?? ($resp['data']['charge_id'] ?? ''));

    // Atualizar doação com dados de gateway
    $update = ['payment_url' => $paymentUrl !== '' ? $paymentUrl : null, 'gateway' => 'efi'];
    if ($chargeId !== '') $update['efi_charge_id'] = $chargeId;
    $doacaoModel->update((int)$doacaoId, $update);

    file_put_contents($logPath, "Stored payment_url: $paymentUrl\n", FILE_APPEND | LOCK_EX);

    echo "SUCCESS\n";
    echo "Doacao ID: $doacaoId\n";
    echo "Payment URL: $paymentUrl\n";
    echo "Open in browser: /doacao-abrir-pagamento.php?id=$doacaoId\n";

    // Record a debug id if present in debug log
    // Check doacao_debug.log for last entry
    $debLog = BASE_PATH . '/logs/doacao_debug.log';
    if (file_exists($debLog)) {
        $lines = file($debLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $last = array_slice($lines, -1);
        $lastEntry = json_decode($last[0] ?? '{}', true);
        if (!empty($lastEntry['id'])) {
            echo "DebugID: " . ($lastEntry['id']) . "\n";
            file_put_contents($logPath, "DebugID: " . ($lastEntry['id']) . "\n", FILE_APPEND | LOCK_EX);
        }
    }

    file_put_contents($logPath, "Finished at " . date('c') . PHP_EOL, FILE_APPEND | LOCK_EX);
    exit(0);
} catch (Exception $e) {
    $msg = $e->getMessage();
    file_put_contents($logPath, "ERROR: " . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
    file_put_contents($logPath, "Trace: " . $e->getTraceAsString() . PHP_EOL, FILE_APPEND | LOCK_EX);
    echo "ERROR: " . $msg . "\n";
    exit(1);
}
