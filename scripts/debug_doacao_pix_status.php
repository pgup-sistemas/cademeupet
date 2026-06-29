<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: text/plain; charset=utf-8');

$doacaoId = (int)($argv[1] ?? 0);
$txidArg = trim((string)($argv[2] ?? ''));

if ($doacaoId <= 0) {
    echo "Uso: php scripts/debug_doacao_pix_status.php <doacao_id> [txid]\n";
    exit(1);
}

$doacaoModel = new Doacao();
$doacao = $doacaoModel->findById($doacaoId);

if (empty($doacao)) {
    echo "Doação não encontrada: {$doacaoId}\n";
    exit(1);
}

$txid = $txidArg !== '' ? $txidArg : trim((string)($doacao['transaction_id'] ?? ''));

echo "=== DEBUG DOAÇÃO PIX ===\n";
echo "Doacao ID: {$doacaoId}\n";
echo "DB status: " . (string)($doacao['status'] ?? '') . "\n";
echo "DB txid: " . (string)($doacao['transaction_id'] ?? '') . "\n";
echo "Using txid: {$txid}\n\n";

if ($txid === '') {
    echo "TXID vazio.\n";
    exit(1);
}

$pc = new PagamentoController();

echo "--- 0) Schema do banco (doacoes.status) ---\n";
try {
    $db = getDB();
    $row = $db->fetchOne(
        'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT ' .
        'FROM INFORMATION_SCHEMA.COLUMNS ' .
        'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "doacoes" AND COLUMN_NAME = "status"'
    );
    if (is_array($row)) {
        echo "COLUMN_TYPE: " . (string)($row['COLUMN_TYPE'] ?? '') . "\n";
        echo "IS_NULLABLE: " . (string)($row['IS_NULLABLE'] ?? '') . "\n";
        echo "COLUMN_DEFAULT: " . (string)($row['COLUMN_DEFAULT'] ?? '') . "\n";
    } else {
        echo "Não foi possível ler schema do status.\n";
    }
} catch (Throwable $e) {
    echo "ERRO ao ler INFORMATION_SCHEMA: " . $e->getMessage() . "\n";
}

echo "\n";

echo "--- 1) Detalhar cobrança na Efí (pixDetailCharge) ---\n";
try {
    $detail = $pc->detalharCobrancaPix($txid);

    // Extrair alguns campos relevantes
    $status = $detail['status'] ?? ($detail['cob']['status'] ?? null);
    $pixCount = (is_array($detail['pix'] ?? null)) ? count($detail['pix']) : 0;

    echo "status(raw): " . (is_scalar($status) ? (string)$status : json_encode($status)) . "\n";
    echo "pix_count: {$pixCount}\n";

    if ($pixCount > 0) {
        echo "pix[0] keys: " . implode(',', array_keys((array)$detail['pix'][0])) . "\n";
        echo "pix[0] sample: " . substr(json_encode($detail['pix'][0], JSON_UNESCAPED_UNICODE), 0, 800) . "\n";
    }

    echo "detail keys: " . implode(',', array_keys($detail)) . "\n";
} catch (Throwable $e) {
    echo "ERRO no pixDetailCharge: " . $e->getMessage() . "\n";
}

echo "\n--- 2) Sincronizar status (sincronizarStatusDoacaoPix) ---\n";
try {
    $updated = $pc->sincronizarStatusDoacaoPix($doacaoId, $txid);
    if (!is_array($updated)) {
        echo "RETORNO inesperado (não é array): " . gettype($updated) . "\n";
        echo "value: " . substr(@json_encode($updated, JSON_UNESCAPED_UNICODE), 0, 800) . "\n";
    } else {
        echo "after sync status: " . (string)($updated['status'] ?? '') . "\n";
        echo "ultimo_pagamento_em: " . (string)($updated['ultimo_pagamento_em'] ?? '') . "\n";
        echo "raw updated: " . substr(json_encode($updated, JSON_UNESCAPED_UNICODE), 0, 1200) . "\n";
    }
} catch (Throwable $e) {
    echo "ERRO na sincronização: " . $e->getMessage() . "\n";
}

echo "\n--- 3) Ler doação novamente do DB ---\n";
$doacao2 = $doacaoModel->findById($doacaoId);
if (!is_array($doacao2)) {
    echo "db fetch retornou vazio/inesperado: " . gettype($doacao2) . "\n";
    exit(0);
}

echo "db status now: " . (string)($doacao2['status'] ?? '') . "\n";
echo "db ultimo_pagamento_em: " . (string)($doacao2['ultimo_pagamento_em'] ?? '') . "\n";
echo "db raw: " . substr(json_encode($doacao2, JSON_UNESCAPED_UNICODE), 0, 1200) . "\n";
