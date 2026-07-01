<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido.']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Você precisa estar logado para denunciar.']);
    exit;
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'erro' => 'Token inválido. Recarregue a página.']);
    exit;
}

$anuncioId = (int)($_POST['anuncio_id'] ?? 0);
$motivos   = ['inapropriado', 'spam', 'venda', 'golpe', 'outro'];
$motivo    = $_POST['motivo'] ?? '';
$descricao = trim((string)($_POST['descricao'] ?? ''));
$usuarioId = (int)getUserId();

if ($anuncioId <= 0 || !in_array($motivo, $motivos, true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erro' => 'Dados inválidos.']);
    exit;
}

$db = getDB();

// Verificar que o anúncio existe
$anuncio = $db->fetchOne('SELECT id, usuario_id FROM anuncios WHERE id = ? AND status = ?', [$anuncioId, STATUS_ATIVO]);
if (!$anuncio) {
    echo json_encode(['ok' => false, 'erro' => 'Anúncio não encontrado.']);
    exit;
}

// Não pode denunciar o próprio anúncio
if ((int)$anuncio['usuario_id'] === $usuarioId) {
    echo json_encode(['ok' => false, 'erro' => 'Você não pode denunciar seu próprio anúncio.']);
    exit;
}

// Verificar duplicidade (mesmo usuário, mesmo anúncio, pendente ou procedente)
$existente = $db->fetchOne(
    "SELECT id FROM denuncias WHERE anuncio_id = ? AND usuario_id = ? AND status IN ('pendente','procedente')",
    [$anuncioId, $usuarioId]
);
if ($existente) {
    echo json_encode(['ok' => false, 'erro' => 'Você já reportou este anúncio.']);
    exit;
}

$db->insert('denuncias', [
    'anuncio_id' => $anuncioId,
    'usuario_id' => $usuarioId,
    'motivo'     => $motivo,
    'descricao'  => $descricao ?: null,
    'status'     => 'pendente',
]);

echo json_encode(['ok' => true]);
