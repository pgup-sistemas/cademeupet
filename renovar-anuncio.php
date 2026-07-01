<?php
require_once __DIR__ . '/config.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit();
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('Erro de validação. Recarregue a página e tente novamente.', MSG_ERROR);
    redirect('/meus-anuncios');
}

$anuncioId = isset($_POST['anuncio_id']) ? (int)$_POST['anuncio_id'] : 0;
if ($anuncioId <= 0) {
    setFlashMessage('Anúncio inválido.', MSG_ERROR);
    redirect('/meus-anuncios');
}

$controller = new AnuncioController();
$result = $controller->renovar($anuncioId, (int)getUserId());

if (!empty($result['success'])) {
    setFlashMessage('Anúncio renovado com sucesso! Ele voltará a aparecer nas buscas.', MSG_SUCCESS);
    redirect('/anuncio/' . $anuncioId . '/');
} else {
    setFlashMessage($result['error'] ?? 'Não foi possível renovar o anúncio.', MSG_ERROR);
    redirect('/meus-anuncios?status=expirado');
}
