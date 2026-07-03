<?php
/**
 * Cadê Meu Pet? - Envio de foto anexada à conversa
 * POST /api/conversa-imagem.php (multipart/form-data)
 * Campos: conversa_id, csrf_token, imagem (arquivo)
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido.']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Você precisa estar logado.']);
    exit;
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'erro' => 'Token inválido. Recarregue a página.']);
    exit;
}

$conversaId = (int)($_POST['conversa_id'] ?? 0);

if ($conversaId <= 0 || empty($_FILES['imagem'])) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erro' => 'Dados inválidos.']);
    exit;
}

$ctrl = new ConversaController();
echo json_encode($ctrl->enviarImagem($conversaId, getUserId(), $_FILES['imagem']));
