<?php
require_once __DIR__ . '/config.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo 'ID de pagamento inválido.'; exit; }

$pagamentoModel = new ParceiroPagamento();
$pagamento      = $pagamentoModel->findById($id);
if (empty($pagamento)) { http_response_code(404); echo 'Pagamento não encontrado.'; exit; }

$ownerId = (int)($pagamento['usuario_id'] ?? 0);
if ($ownerId <= 0 || (int)getUserId() !== $ownerId) {
    http_response_code(403); echo 'Acesso negado.'; exit;
}

$paymentUrl = trim((string)($pagamento['payment_url'] ?? ''));
$lastError  = $paymentUrl === '' ? 'Link de pagamento não encontrado. Refaça o processo de pagamento.' : null;
$backUrl    = BASE_URL . '/parceiro/pagamento/';

require __DIR__ . '/includes/open-payment.php';
