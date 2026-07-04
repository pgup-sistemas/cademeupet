<?php
/**
 * Cadê Meu Pet? - Bootstrap de autenticação/rate-limit da API pública.
 * Incluído no topo de todo endpoint em api/v1/*.php.
 *
 * Acesso por API key (header X-Api-Key), aprovada manualmente pelo admin
 * (sem self-service). Nunca autentica por sessão — consumidor externo não
 * tem sessão PHP.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/api_helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: X-Api-Key, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

define('API_REQUEST_START', microtime(true));

$GLOBALS['__api_key_id'] = null;
$GLOBALS['__api_endpoint'] = $_SERVER['REQUEST_URI'] ?? '';

$apiKeyModel = new ApiKey();
$apiRateLimitModel = new ApiRateLimit();

$headerKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($headerKey === '') {
    apiRespondError('unauthorized', 'Informe a API key no header X-Api-Key.', 401);
}

$chaveRow = $apiKeyModel->buscarPorChavePlaintext($headerKey);
if (!$chaveRow || !$apiKeyModel->valida($chaveRow)) {
    apiRespondError('unauthorized', 'API key inválida, revogada ou expirada.', 401);
}

$GLOBALS['__api_key_id'] = (int)$chaveRow['id'];
$GLOBALS['__api_key_row'] = $chaveRow;

if (!$apiRateLimitModel->verificarERegistrar((int)$chaveRow['id'], (int)$chaveRow['rate_limit_por_minuto'])) {
    apiRespondError('rate_limited', 'Limite de requisições excedido para esta API key.', 429, ['Retry-After' => '30']);
}

$apiKeyModel->registrarUso((int)$chaveRow['id']);

/** Verifica se a API key autenticada tem o escopo exigido; encerra com 403 se não tiver. */
function requireApiScope(string $escopo): void
{
    $apiKeyModel = new ApiKey();
    if (!$apiKeyModel->temEscopo($GLOBALS['__api_key_row'], $escopo)) {
        apiRespondError('forbidden', "Esta API key não tem o escopo '$escopo'.", 403);
    }
}
