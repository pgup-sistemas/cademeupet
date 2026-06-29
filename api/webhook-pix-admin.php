<?php
/**
 * Cadê Meu Pet? - API Admin: Gerenciamento de Webhook PIX
 *
 * POST /api/webhook-pix-admin.php
 * { "csrf_token": "...", "acao": "registrar|consultar|remover" }
 *
 * Requer sessão de admin ativa.
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    // Tentar $_POST como fallback (form submit)
    $body = $_POST;
}

if (!validateCSRFToken($body['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'csrf_invalid']);
    exit;
}

$acao   = trim((string)($body['acao'] ?? ''));
$pixKey = (string)EFI_PIX_KEY;
$webhookUrl = (string)EFI_PIX_NOTIFICATION_URL;

if (!in_array($acao, ['registrar', 'consultar', 'remover'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'acao_invalida', 'acoes_validas' => ['registrar', 'consultar', 'remover']]);
    exit;
}

if ($pixKey === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'EFI_PIX_KEY não configurada no .env']);
    exit;
}

try {
    $pagamentoController = new PagamentoController();
    $efi = $pagamentoController->getApi();

    if ($acao === 'registrar') {
        if ($webhookUrl === '') {
            throw new Exception('EFI_PIX_NOTIFICATION_URL não configurada.');
        }

        // EFI exige que a URL seja HTTPS em produção.
        // Em sandbox pode ser HTTP, mas idealmente usar ngrok ou similar para testes locais.
        $result = $efi->pixConfigWebhook(
            ['chave' => $pixKey],
            ['webhookUrl' => $webhookUrl]
        );

        error_log('[webhook-pix-admin] Webhook registrado para chave ' . $pixKey . ' → ' . $webhookUrl);
        echo json_encode([
            'ok'          => true,
            'acao'        => 'registrar',
            'chave'       => $pixKey,
            'webhookUrl'  => $webhookUrl,
            'resposta_efi'=> $result,
        ]);
        exit;
    }

    if ($acao === 'consultar') {
        $result = $efi->pixDetailWebhook(['chave' => $pixKey]);
        echo json_encode([
            'ok'          => true,
            'acao'        => 'consultar',
            'chave'       => $pixKey,
            'resposta_efi'=> $result,
        ]);
        exit;
    }

    if ($acao === 'remover') {
        $result = $efi->pixDeleteWebhook(['chave' => $pixKey]);
        error_log('[webhook-pix-admin] Webhook removido para chave ' . $pixKey);
        echo json_encode([
            'ok'          => true,
            'acao'        => 'remover',
            'chave'       => $pixKey,
            'resposta_efi'=> $result,
        ]);
        exit;
    }

} catch (Exception $e) {
    error_log('[webhook-pix-admin] Erro: ' . $e->getMessage());
    http_response_code(502);
    echo json_encode([
        'ok'      => false,
        'error'   => $e->getMessage(),
        'dica'    => 'Em ambiente local (localhost) o EFI não consegue alcançar a URL do webhook. Use um túnel (ngrok/localtunnel) ou registre apenas em produção.',
    ]);
    exit;
}
