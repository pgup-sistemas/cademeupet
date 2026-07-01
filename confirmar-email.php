<?php
require_once __DIR__ . '/config.php';

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    setFlashMessage('Link de confirmação inválido.', MSG_ERROR);
    redirect('/login/');
}

// Auth::confirmEmail() verifica TTL (token_confirmacao_expira) além da validade do token
$auth   = new Auth();
$result = $auth->confirmEmail($token);

if ($result['success']) {
    setFlashMessage('E-mail confirmado com sucesso! Você já pode fazer login.', MSG_SUCCESS);
} else {
    setFlashMessage($result['error'] ?? 'Link inválido ou expirado.', MSG_ERROR);
}

redirect('/login/');
