<?php
require_once __DIR__ . '/../config.php';

// Forçar JSON por padrão em erros; success response for Efí is plain '200'
header('Content-Type: application/json; charset=utf-8');

// Garantir que o diretório de logs exista
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$webhookLog = $logDir . '/efi_webhook.log';

// ========================
// VERIFICAÇÃO DE URL (GET)
// EFI envia GET para confirmar que a URL existe antes de registrar o webhook.
// Deve responder 200 com corpo vazio ou qualquer conteúdo.
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $entry = ['ts' => date('c'), 'event' => 'webhook_verification_get', 'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null];
    @file_put_contents($webhookLog, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo '200';
    exit;
}

// ========================
// VALIDAR IP DE ORIGEM (allowlist Efí Bank)
// IPs oficiais: https://sejaefi.com.br/central-de-ajuda/pix/webhook-pix
// ========================
$efiAllowedIps = [
    // Produção
    '34.193.116.68', '34.201.82.218', '52.71.157.255',
    '52.72.250.233', '52.201.120.24', '100.28.11.138', '18.215.141.45',
    // Homologação (sandbox)
    '54.167.39.240', '52.2.242.99',
];

$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
// Suporte a proxy reverso confiável (apenas se o servidor estiver atrás de proxy)
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $remoteIp = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
}

// Em sandbox local ignorar o allowlist; em produção, validar sempre
if (!IS_LOCAL && !in_array($remoteIp, $efiAllowedIps, true)) {
    http_response_code(403);
    $entry = ['ts' => date('c'), 'event' => 'ip_blocked', 'remote_addr' => $remoteIp];
    @file_put_contents($webhookLog, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

// ========================
// VALIDAR TOKEN
// ========================
// Aceitar apenas POST (GET já tratado acima)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$token = '';

if (isset($_SERVER['HTTP_X_WEBHOOK_TOKEN'])) {
    $token = (string)$_SERVER['HTTP_X_WEBHOOK_TOKEN'];
} elseif (isset($_SERVER['HTTP_X_EFI_WEBHOOK_TOKEN'])) {
    $token = (string)$_SERVER['HTTP_X_EFI_WEBHOOK_TOKEN'];
} elseif (isset($_GET['token'])) {
    $token = (string)$_GET['token'];
}

$expectedToken = (string)EFI_WEBHOOK_TOKEN;
// Se houver token configurado, exigir que seja fornecido (skip-mTLS safe-guard)
$tokenOk = true;
if ($expectedToken !== '') {
    if (!hash_equals($expectedToken, $token)) {
        $tokenOk = false;
    }
}

if ($expectedToken !== '' && !$tokenOk) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    // Persistir tentativa não autorizada
    $entry = [
        'ts' => date('c'),
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'event' => 'unauthorized',
        'headers' => function_exists('getallheaders') ? getallheaders() : [],
    ];
    @file_put_contents($webhookLog, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    exit;
}

// ========================
// OBTER DADOS DO WEBHOOK
// ========================
$raw = file_get_contents('php://input');
$data = json_decode((string)$raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json']);
    // Log do payload inválido
    $entry = [
        'ts' => date('c'),
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'event' => 'invalid_json',
        'raw' => substr($raw, 0, 4000),
        'headers' => function_exists('getallheaders') ? getallheaders() : [],
    ];
    @file_put_contents($webhookLog, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    exit;
}

// Log persistente do webhook (payload reduzido)
$entry = [
    'ts' => date('c'),
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
    'event' => 'received',
    'headers' => function_exists('getallheaders') ? getallheaders() : [],
    'payload' => $data,
];
@file_put_contents($webhookLog, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);

// ========================
// EXTRAIR TXID
// ========================
$txid = '';

// Padrão EFI: pode vir em diferentes estruturas
if (!empty($data['pix'][0]['txid'])) {
    $txid = (string)$data['pix'][0]['txid'];
} elseif (!empty($data['txid'])) {
    $txid = (string)$data['txid'];
} elseif (!empty($data['cob']['txid'])) {
    $txid = (string)$data['cob']['txid'];
} elseif (!empty($data['pix_id'])) {
    $txid = (string)$data['pix_id'];
}

if (empty($txid)) {
    http_response_code(400);
    error_log('[efi-webhook] TXID não encontrado nos dados');
    echo json_encode(['ok' => false, 'error' => 'missing_txid']);
    exit;
}

$txid = trim($txid);
error_log('[efi-webhook] TXID extraído: ' . $txid);
@file_put_contents($webhookLog, json_encode(['ts' => date('c'), 'event' => 'txid_extracted', 'txid' => $txid]) . PHP_EOL, FILE_APPEND | LOCK_EX);

// ========================
// DETECTAR E PERSISTIR REPASSES/TRANSFERENCIAS (SPLIT)
// ========================
$transfersFound = [];
$possibleKeys = ['transfers','transfer','transferencias','repasse','repasses','transferencia'];
$findTransfers = function($arr) use (&$findTransfers, $possibleKeys, &$transfersFound) {
    if (!is_array($arr)) return;
    foreach ($arr as $k => $v) {
        if (in_array(strtolower($k), $possibleKeys, true) && !empty($v)) {
            $transfersFound[] = $v;
        }
        if (is_array($v)) {
            $findTransfers($v);
        }
    }
};
$findTransfers($data);

if (!empty($transfersFound)) {
    require_once BASE_PATH . '/models/EfiTransfer.php';
    $efiTransferModel = new EfiTransfer();
    foreach ($transfersFound as $tf) {
        try {
            $status = is_array($tf) && isset($tf['status']) ? (string)$tf['status'] : null;
            $efiTransferModel->create($txid, is_array($tf) ? $tf : ['transfer' => $tf], $status);
            @file_put_contents($webhookLog, json_encode(['ts' => date('c'), 'event' => 'transfer_saved', 'txid' => $txid, 'transfer' => is_array($tf) ? $tf : $tf], JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            error_log('[efi-webhook] Falha ao salvar repasse: ' . $e->getMessage());
        }
    }
}

// ========================
// PROCESSAR PAGAMENTO
// ========================
$doacaoModel = new Doacao();
$pagamentoController = new PagamentoController();

try {
    // Procurar doação com este TXID
    $doacao = $doacaoModel->findByTransactionId($txid);
    
    if (!empty($doacao)) {
        if (($doacao['status'] ?? '') === 'aprovada') {
            // Retornar confirmação simples conforme documentação Efí: corpo plain text "200"
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(200);
            echo '200';
            exit;
        }

        // Sincronizar status com a API EFI
        $atualizada = $pagamentoController->sincronizarStatusDoacaoPix((int)$doacao['id'], $txid);

        // Log resultado de processamento
        @file_put_contents($webhookLog, json_encode(['ts' => date('c'), 'event' => 'processed_doacao', 'doacao_id' => $doacao['id'], 'result' => $atualizada]) . PHP_EOL, FILE_APPEND | LOCK_EX);

        // Retornar confirmação simples conforme documentação Efí: corpo plain text "200"
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(200);
        echo '200';
        exit;
    }

    // Procurar pagamento de parceiro com este TXID
    $parceiroPagamentoModel = new ParceiroPagamento();
    $pagamentoParceiro = $parceiroPagamentoModel->findByReferencia($txid);
    
    if (!empty($pagamentoParceiro)) {
        if (($pagamentoParceiro['status'] ?? '') === 'aprovado') {
            @file_put_contents($webhookLog, json_encode(['ts' => date('c'), 'event' => 'already_approved_parceiro', 'pagamento_id' => $pagamentoParceiro['id']]) . PHP_EOL, FILE_APPEND | LOCK_EX);
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(200);
            echo '200';
            exit;
        }

        // Sincronizar status com a API EFI
        $atualizada = $pagamentoController->sincronizarStatusParceiroPix((int)$pagamentoParceiro['id'], $txid);
        @file_put_contents($webhookLog, json_encode(['ts' => date('c'), 'event' => 'processed_parceiro', 'pagamento_id' => $pagamentoParceiro['id'], 'result' => $atualizada]) . PHP_EOL, FILE_APPEND | LOCK_EX);
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(200);
        echo '200';
        exit;
    }

    // Nenhuma doação ou pagamento encontrado
    http_response_code(404);
    error_log('[efi-webhook] Nenhuma transação encontrada para TXID: ' . $txid);
    echo json_encode([
        'ok' => false,
        'error' => 'transaction_not_found',
        'txid' => $txid
    ]);
    exit;

} catch (Exception $e) {
    error_log('[efi-webhook] Exceção ao processar webhook: ' . $e->getMessage());
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'error' => 'processing_error',
        'message' => $e->getMessage()
    ]);
    exit;
}

