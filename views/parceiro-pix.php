<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Pagamento via Pix - Parceiro | Cadê Meu Pet?';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$pagamentoModel = new ParceiroPagamento();
$pagamento = $id > 0 ? $pagamentoModel->findById($id) : null;

if (empty($pagamento)) {
    http_response_code(404);
    die('Pagamento não encontrado.');
}

$usuarioId = (int)(getUserId() ?? 0);
if ((int)($pagamento['usuario_id'] ?? 0) !== $usuarioId) {
    http_response_code(403);
    die('Acesso negado.');
}

$pix = null;
if (!empty($_SESSION['pix_parceiros'][$id]) && is_array($_SESSION['pix_parceiros'][$id])) {
    $pix = $_SESSION['pix_parceiros'][$id];
}

$txid = '';
if (!empty($pix['txid'])) {
    $txid = (string)$pix['txid'];
} elseif (!empty($pagamento['referencia'])) {
    $txid = (string)$pagamento['referencia'];
}

if (($pagamento['status'] ?? '') === 'pendente' && $txid !== '') {
    try {
        $pagamentoController = new PagamentoController();
        $atualizada = $pagamentoController->sincronizarStatusParceiroPix((int)$pagamento['id'], $txid);
        if (!empty($atualizada)) {
            $pagamento = $atualizada;
        }
    } catch (Exception $e) {
        error_log('[parceiro-pix] Falha ao sincronizar status Pix: ' . $e->getMessage());
    }
}

// Se a sessão expirou, tentar reconstruir o QR Code via API a partir do TXID
if (empty($pix) && $txid !== '') {
    try {
        $pagamentoController = new PagamentoController();
        $detail = $pagamentoController->detalharCobrancaPix($txid);

        $locId = null;
        if (is_array($detail)) {
            $locId = $detail['loc']['id'] ?? $detail['loc_id'] ?? null;
        }

        if (!empty($locId)) {
            $api = $pagamentoController->getApi();
            $responseQr = $api->pixGenerateQRCode(['id' => $locId]);

            $qrImageBase64 = $responseQr['imagemQrcode'] ?? $responseQr['imagem_qrcode'] ?? null;
            $qrText = $responseQr['qrcode'] ?? $responseQr['qrcodeText'] ?? $responseQr['qrCodeText'] ?? null;

            $qrImageDataUrl = '';
            if (!empty($qrImageBase64) && is_string($qrImageBase64)) {
                $img = trim($qrImageBase64);
                $qrImageDataUrl = str_starts_with($img, 'data:image') ? $img : ('data:image/png;base64,' . $img);
            }

            $pix = [
                'txid' => $txid,
                'qrcode' => [
                    'imagemQrcode' => $qrImageDataUrl,
                    'qrcode' => is_string($qrText) ? trim($qrText) : '',
                    'raw' => $responseQr,
                ],
            ];

            if (!isset($_SESSION['pix_parceiros']) || !is_array($_SESSION['pix_parceiros'])) {
                $_SESSION['pix_parceiros'] = [];
            }
            $_SESSION['pix_parceiros'][$id] = $pix;
        }
    } catch (Throwable $e) {
        error_log('[parceiro-pix] Falha ao reconstruir QR via API: ' . $e->getMessage());
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h4 fw-bold mb-3">Pagamento via Pix</h1>

                    <?php if (($pagamento['status'] ?? '') === 'aprovado'): ?>
                        <div class="alert alert-success">
                            Pagamento confirmado. Sua assinatura será ativada e seu perfil poderá ser publicado.
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-4">Escaneie o QR Code abaixo no seu app do banco, ou copie o código Pix “copia e cola”.</p>

                        <?php if (!empty($pix['qrcode']['imagemQrcode'])): ?>
                            <div class="text-center mb-4">
                                <img src="<?php echo sanitize($pix['qrcode']['imagemQrcode']); ?>" alt="QR Code Pix" style="max-width: 260px; width: 100%;" />
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Pix copia e cola</label>
                            <textarea class="form-control" rows="4" readonly><?php echo sanitize($pix['qrcode']['qrcode'] ?? ($pagamento['comprovante_texto'] ?? '')); ?></textarea>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-outline-primary" href="<?php echo url('/parceiro/pix?id=' . $id); ?>">Atualizar status</a>
                            <a class="btn btn-link" href="<?php echo url('/parceiro/painel'); ?>">Voltar</a>
                        </div>
                    <?php endif; ?>

                    <hr class="my-4" />

                    <div class="small text-muted">
                        <div><strong>ID do pagamento:</strong> <?php echo (int)$pagamento['id']; ?></div>
                        <div><strong>Status:</strong> <?php echo sanitize($pagamento['status'] ?? ''); ?></div>
                        <?php if (!empty($pagamento['plano'])): ?>
                            <div><strong>Plano:</strong> <?php echo sanitize($pagamento['plano']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($pagamento['valor'])): ?>
                            <div><strong>Valor:</strong> R$ <?php echo sanitize(number_format((float)$pagamento['valor'], 2, ',', '.')); ?></div>
                        <?php endif; ?>
                        <?php if ($txid !== ''): ?>
                            <div><strong>TXID:</strong> <?php echo sanitize($txid); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
