<?php
/**
 * Cadê Meu Pet? - Contagem de mensagens não lidas (badge do menu)
 * GET /api/conversa-nao-lidas.php
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'total' => 0]);
    exit;
}

$ctrl = new ConversaController();
echo json_encode(['ok' => true, 'total' => $ctrl->contarNaoLidas(getUserId())]);
