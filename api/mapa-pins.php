<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('Access-Control-Allow-Origin: ' . BASE_URL);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido.']);
    exit;
}

try {
    $ctrl = new MapController();
    $pins = $ctrl->getPins();
    echo json_encode($pins, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('[mapa-pins] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno.']);
}
