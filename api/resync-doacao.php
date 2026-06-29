<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// Endpoint para reprocessar/sincronizar uma doação (por id ou txid)
// Protegido por token (RESYNC_TOKEN) ou pelo token de webhook EFI (EFI_WEBHOOK_TOKEN)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json']);
    exit;
}

$token = '';
if (!empty($body['token'])) {
    $token = (string)$body['token'];
} elseif (isset($_SERVER['HTTP_X_RESYNC_TOKEN'])) {
    $token = (string)$_SERVER['HTTP_X_RESYNC_TOKEN'];
}

$expected = (string)envValue('RESYNC_TOKEN', (string)EFI_WEBHOOK_TOKEN);
if ($expected !== '' && !hash_equals($expected, $token)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$txid = isset($body['txid']) ? trim((string)$body['txid']) : '';
$doacaoId = isset($body['id']) ? (int)$body['id'] : 0;

if ($txid === '' && $doacaoId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_id_or_txid']);
    exit;
}

try {
    $doacaoModel = new Doacao();
    $pagamentoController = new PagamentoController();

    if ($txid !== '') {
        $doacao = $doacaoModel->findByTransactionId($txid);
        if (empty($doacao)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'doacao_not_found', 'txid' => $txid]);
            exit;
        }
        $doacaoId = (int)$doacao['id'];
    } else {
        $doacao = $doacaoModel->findById($doacaoId);
        if (empty($doacao)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'doacao_not_found', 'id' => $doacaoId]);
            exit;
        }
        $txid = (string)($doacao['transaction_id'] ?? '');
        if ($txid === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'missing_txid_on_doacao', 'id' => $doacaoId]);
            exit;
        }
    }

    // Registrar tentativa de reprocessamento
    try {
        $logPath = BASE_PATH . '/logs/efi_resync.log';
        @mkdir(dirname($logPath), 0755, true);
        $entry = ['time' => date('c'), 'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null, 'action' => 'resync_attempt', 'doacao_id' => $doacaoId, 'txid' => $txid];
        file_put_contents($logPath, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    } catch (Throwable $t) {
        error_log('[resync-doacao] Falha ao gravar log: ' . $t->getMessage());
    }

    $atualizada = $pagamentoController->sincronizarStatusDoacaoPix($doacaoId, $txid);

    $novoStatus = (string)($atualizada['status'] ?? ($atualizada['status'] ?? 'pendente'));

    // Buscar detalhe bruto da cobrança (útil para diagnóstico)
    $detail = null;
    try {
        $detail = $pagamentoController->detalharCobrancaPix($txid);
    } catch (Throwable $t) {
        error_log('[resync-doacao] Falha ao obter detalhe da cobrança: ' . $t->getMessage());
    }

    // Log resultado
    try {
        $entry = ['time' => date('c'), 'action' => 'resync_result', 'doacao_id' => $doacaoId, 'txid' => $txid, 'status' => $novoStatus, 'detail' => $detail !== null ? $detail : 'error_fetching_detail'];
        file_put_contents($logPath, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    } catch (Throwable $t) {
        error_log('[resync-doacao] Falha ao gravar log resultado: ' . $t->getMessage());
    }

    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'doacao_id' => $doacaoId,
        'txid' => $txid,
        'status' => $novoStatus,
        'detail' => $detail,
        'data' => $atualizada
    ]);
    exit;

} catch (Exception $e) {
    error_log('[resync-doacao] Erro: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'internal_error', 'message' => $e->getMessage()]);
    exit;
}
