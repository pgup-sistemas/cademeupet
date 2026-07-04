<?php
require_once __DIR__ . '/config.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('Falha na validação do formulário.', MSG_ERROR);
    redirect('/triagem');
}

$usuarioId = (int)getUserId();
$solicitacaoId = (int)($_POST['solicitacao_id'] ?? 0);

$controller = new TriagemController();
$resultado = $controller->abrirConversaComParceiro($solicitacaoId, $usuarioId);

if (!empty($resultado['success'])) {
    redirect('/mensagens');
}

setFlashMessage($resultado['error'] ?? 'Não foi possível abrir a conversa.', MSG_ERROR);
redirect('/triagem?resultado=' . $solicitacaoId);
