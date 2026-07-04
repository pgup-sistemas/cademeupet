<?php
/**
 * Cadê Meu Pet? - Helpers de resposta/log para a API pública (/api/v1).
 */

function apiRespondError(string $code, string $message, int $httpStatus, array $extraHeaders = []): void
{
    http_response_code($httpStatus);
    foreach ($extraHeaders as $nome => $valor) {
        header("$nome: $valor");
    }
    apiLogRequest($httpStatus);
    echo json_encode(['error' => ['code' => $code, 'message' => $message]], JSON_UNESCAPED_UNICODE);
    exit;
}

function apiRespondSuccess(array $data, int $httpStatus = 200): void
{
    http_response_code($httpStatus);
    apiLogRequest($httpStatus);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function apiLogRequest(int $status): void
{
    if (!function_exists('getDB')) {
        return;
    }
    try {
        $db = getDB();
        $tempoMs = defined('API_REQUEST_START') ? (int)round((microtime(true) - API_REQUEST_START) * 1000) : null;
        $db->insert('api_requisicoes_log', [
            'api_key_id'  => $GLOBALS['__api_key_id'] ?? null,
            'endpoint'    => $GLOBALS['__api_endpoint'] ?? ($_SERVER['REQUEST_URI'] ?? ''),
            'metodo'      => $_SERVER['REQUEST_METHOD'] ?? '',
            'status_http' => $status,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
            'tempo_ms'    => $tempoMs,
        ]);
    } catch (Throwable $e) {
        error_log('[api_helpers] Falha ao registrar log de requisição: ' . $e->getMessage());
    }
}
