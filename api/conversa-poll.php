<?php
/**
 * Cadê Meu Pet? - Polling de novas mensagens de uma conversa
 * GET /api/conversa-poll.php?id=123&depois_de=45
 * Chamado periodicamente pela tela de mensagens enquanto a conversa está aberta.
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Não autenticado.']);
    exit;
}

$conversaId = (int)($_GET['id'] ?? 0);
$depoisDe = (int)($_GET['depois_de'] ?? 0);

if ($conversaId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Conversa inválida.']);
    exit;
}

// Rate limit leve: no máximo 1 chamada a cada 2s por conversa/sessão
$rateLimitKey = 'conv_poll_' . $conversaId;
$lastCall = $_SESSION[$rateLimitKey] ?? 0;
if ((time() - $lastCall) < 2) {
    echo json_encode(['ok' => true, 'mensagens' => []]);
    exit;
}
$_SESSION[$rateLimitKey] = time();

$ctrl = new ConversaController();
echo json_encode($ctrl->poll($conversaId, getUserId(), $depoisDe));
