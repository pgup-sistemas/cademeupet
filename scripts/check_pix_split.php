<?php
require_once __DIR__ . '/../config.php';

if ($argc < 2) {
    echo "Usage: php check_pix_split.php <txid>\n";
    exit(1);
}

$txid = $argv[1];
try {
    $pag = new PagamentoController();
    $detail = $pag->detalharCobrancaPix($txid);
    echo json_encode($detail, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(2);
}
