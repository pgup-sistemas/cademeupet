<?php
require_once __DIR__ . '/../config.php';

// Script de teste para criar uma cobrança PIX com split (apenas em sandbox/homologação)
if (!EFI_SANDBOX) {
    echo "AVISO: Este script deve ser usado somente em ambiente de homologação (EFI_SANDBOX=true).\n";
}

$sampleDoacao = [
    'id' => 999999,
    'valor' => 2.00,
    'nome_doador' => 'Teste Split',
    'cpf_doador' => '',
    'email_doador' => 'teste@example.com'
];

// Tentar obter regras de split do env (se configuradas) senão usar exemplo simples
$splitJson = envValue('EFI_SPLIT_RULES_JSON', '');
if (!empty($splitJson)) {
    $split = json_decode($splitJson, true);
} else {
    $split = [
        ['recipient_id' => '12345', 'percentage' => 50],
        ['recipient_id' => '67890', 'percentage' => 50],
    ];
}

$pag = new PagamentoController();
try {
    $resp = $pag->criarCobrancaPix($sampleDoacao, 'Doação teste split', $split);
    echo "Resposta da API (parcial):\n";
    echo json_encode([ 'txid' => $resp['txid'], 'split' => $resp['split'], 'charge_sample' => ($resp['charge'] ?? null) ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (Exception $e) {
    echo "Erro ao criar cobrança com split: " . $e->getMessage() . "\n";
}
