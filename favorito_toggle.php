<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('Falha de validação. Tente novamente.', MSG_ERROR);
    redirect('/login.php');
}

requireLogin();

$anuncioId = isset($_POST['anuncio_id']) ? (int)$_POST['anuncio_id'] : 0;
$returnTo = $_POST['return_to'] ?? '/';
if (!is_string($returnTo) || $returnTo === '' || strpos($returnTo, '://') !== false) {
    $returnTo = '/';
}

if ($anuncioId <= 0) {
    setFlashMessage('Anúncio inválido.', MSG_ERROR);
    redirect($returnTo);
}

$favoritoController = new FavoritoController();
$result = $favoritoController->toggle($anuncioId);

if (!empty($result['favorited'])) {
    setFlashMessage('Anúncio salvo nos seus favoritos!', MSG_SUCCESS);
} else {
    setFlashMessage('Anúncio removido dos favoritos.', MSG_INFO);
}

redirect($returnTo);
