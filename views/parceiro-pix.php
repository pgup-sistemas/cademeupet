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
                'txid'   => $txid,
                'qrcode' => [
                    'imagemQrcode' => $qrImageDataUrl,
                    'qrcode'       => is_string($qrText) ? trim($qrText) : '',
                    'raw'          => $responseQr,
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

$status        = (string)($pagamento['status'] ?? 'pendente');
$plano         = (string)($pagamento['plano'] ?? 'basico');
$periodicidade = (string)($pagamento['periodicidade'] ?? 'mensal');
$valor         = (float)($pagamento['valor'] ?? 0);
$dataCriacao   = (string)($pagamento['data_criacao'] ?? '');
$aprovadoEm    = (string)($pagamento['aprovado_em'] ?? '');
$efiChargeId   = (string)($pagamento['efi_charge_id'] ?? '');
$pixCode       = (string)($pix['qrcode']['qrcode'] ?? ($pagamento['comprovante_texto'] ?? ''));
$qrImage       = (string)($pix['qrcode']['imagemQrcode'] ?? '');

$statusMap = [
    'pendente'  => ['label' => 'Aguardando pagamento', 'badge' => 'warning',   'icon' => 'bi-hourglass-split'],
    'aprovado'  => ['label' => 'Pagamento confirmado',  'badge' => 'success',   'icon' => 'bi-check-circle-fill'],
    'recusado'  => ['label' => 'Pagamento recusado',    'badge' => 'danger',    'icon' => 'bi-x-circle-fill'],
    'cancelado' => ['label' => 'Cancelado',             'badge' => 'secondary', 'icon' => 'bi-slash-circle'],
];
$statusInfo = $statusMap[$status] ?? ['label' => ucfirst($status), 'badge' => 'secondary', 'icon' => 'bi-info-circle'];

$planoLabel         = $plano === 'destaque' ? 'Destaque' : 'Básico';
$periodicidadeLabel = $periodicidade === 'anual' ? 'Anual' : 'Mensal';

$breadcrumbs = [
    ['label' => 'Início',    'url' => BASE_URL],
    ['label' => 'Parceiros', 'url' => BASE_URL . '/parceiros'],
    ['label' => 'Pagar via PIX'],
];
include __DIR__ . '/../includes/header.php';
?>

<style>
.pix-qr-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: 2px solid #dee2e6;
    border-radius: .75rem;
    padding: 1rem;
}
.pix-detail-table td { padding: .35rem .5rem; vertical-align: middle; }
.pix-detail-table td:first-child { color: #6c757d; white-space: nowrap; width: 45%; }
.copy-field { font-family: monospace; font-size: .78rem; line-height: 1.5; resize: none; }
</style>

<div class="container py-4" style="max-width: 700px;">

    <?php /* ── Cabeçalho ── */ ?>
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-2">Pagamento via Pix</h1>
            <span class="badge bg-<?php echo $statusInfo['badge']; ?> d-inline-flex align-items-center gap-1 px-3 py-2 fs-6">
                <i class="bi <?php echo $statusInfo['icon']; ?>"></i>
                <?php echo $statusInfo['label']; ?>
            </span>
        </div>
        <a class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
           href="<?php echo url('/parceiro/painel'); ?>">
            <i class="bi bi-arrow-left"></i> Painel
        </a>
    </div>

    <?php if ($status === 'aprovado'): ?>

    <?php /* ── Estado: confirmado ── */ ?>
    <div class="card border-0 shadow-sm mb-4" style="border-left: 4px solid #198754 !important;">
        <div class="card-body p-4 text-center">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
            <h2 class="h5 fw-bold mt-3 mb-1">Pagamento confirmado!</h2>
            <p class="text-muted mb-3">
                Sua assinatura foi ativada. O perfil de parceiro já pode ser publicado na plataforma.
            </p>
            <?php if (!empty($aprovadoEm)): ?>
                <div class="text-muted small mb-3">
                    <i class="bi bi-calendar-check me-1"></i>
                    Confirmado em <?php echo formatDateBR($aprovadoEm); ?>
                </div>
            <?php endif; ?>
            <a class="btn btn-success d-inline-flex align-items-center gap-2"
               href="<?php echo url('/parceiro/painel'); ?>">
                <i class="bi bi-house-door"></i> Ir para o painel
            </a>
        </div>
    </div>

    <?php else: ?>

    <?php /* ── Resumo do pagamento ── */ ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body px-4 py-3">
            <div class="row g-0 text-center divide-x">
                <div class="col-4 py-2 border-end">
                    <div class="text-muted small mb-1">Valor</div>
                    <div class="fw-bold fs-5 text-success">
                        R$ <?php echo number_format($valor, 2, ',', '.'); ?>
                    </div>
                </div>
                <div class="col-4 py-2 border-end">
                    <div class="text-muted small mb-1">Plano</div>
                    <div class="fw-semibold"><?php echo sanitize($planoLabel); ?></div>
                </div>
                <div class="col-4 py-2">
                    <div class="text-muted small mb-1">Período</div>
                    <div class="fw-semibold"><?php echo sanitize($periodicidadeLabel); ?></div>
                </div>
            </div>
            <?php if ($periodicidade === 'anual' && $valor > 0): ?>
            <div class="border-top mt-1 pt-2 text-center text-muted small">
                Equivale a
                <strong>R$ <?php echo number_format($valor / 12, 2, ',', '.'); ?>/mês</strong>
                — cobrança única à vista via Pix
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php /* ── Instruções ── */ ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body px-4 py-3">
            <h2 class="h6 fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-info-circle text-primary"></i> Como pagar
            </h2>
            <ol class="mb-0 ps-3 text-muted" style="line-height: 2.1;">
                <li>Abra o aplicativo do seu banco e selecione <strong>Pix</strong></li>
                <li>Escaneie o QR Code <em>ou</em> cole o código copia e cola</li>
                <li>Confirme o valor de <strong class="text-success">R$ <?php echo number_format($valor, 2, ',', '.'); ?></strong></li>
                <li>O pagamento é confirmado automaticamente em segundos</li>
            </ol>
        </div>
    </div>

    <?php /* ── QR Code ── */ ?>
    <?php if (!empty($qrImage) || !empty($pixCode)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body px-4 py-4">

            <?php if (!empty($qrImage)): ?>
            <div class="text-center mb-4">
                <div class="pix-qr-wrap">
                    <img src="<?php echo sanitize($qrImage); ?>"
                         alt="QR Code Pix"
                         style="width: 220px; height: 220px; display: block;" />
                </div>
                <p class="text-muted small mt-2 mb-0">
                    <i class="bi bi-camera me-1"></i>Aponte a câmera do app do banco
                </p>
            </div>
            <?php endif; ?>

            <?php if (!empty($pixCode)): ?>
            <div>
                <label class="form-label fw-semibold d-flex align-items-center gap-1 mb-2">
                    <i class="bi bi-clipboard2-pulse text-primary"></i> Pix copia e cola
                </label>
                <textarea class="form-control copy-field"
                          id="pix-code"
                          rows="4"
                          readonly><?php echo sanitize($pixCode); ?></textarea>
                <div class="d-flex align-items-center gap-2 mt-2">
                    <button type="button"
                            class="btn btn-primary d-inline-flex align-items-center gap-2"
                            id="btn-copy"
                            onclick="copiarPix()">
                        <i class="bi bi-clipboard" id="btn-copy-icon"></i>
                        <span id="btn-copy-label">Copiar código</span>
                    </button>
                    <span class="text-success small d-none d-inline-flex align-items-center gap-1"
                          id="copy-feedback">
                        <i class="bi bi-check2-circle"></i> Copiado com sucesso!
                    </span>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill"></i>
        QR Code indisponível.
        <a href="<?php echo url('/parceiro/pix?id=' . $id); ?>" class="ms-1">Recarregar</a>
    </div>
    <?php endif; ?>

    <?php /* ── Ações ── */ ?>
    <div class="d-flex flex-wrap gap-2 mb-2">
        <a class="btn btn-outline-primary d-inline-flex align-items-center gap-2"
           href="<?php echo url('/parceiro/pix?id=' . $id); ?>"
           id="btn-atualizar">
            <i class="bi bi-arrow-clockwise"></i> Verificar pagamento
        </a>
        <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2"
           href="<?php echo url('/parceiro/pagamento'); ?>">
            <i class="bi bi-arrow-repeat"></i> Gerar novo Pix
        </a>
    </div>
    <div class="text-muted small mb-4" id="auto-refresh-msg">
        <i class="bi bi-clock me-1"></i>
        Verificação automática em <strong><span id="countdown">20</span>s</strong>
    </div>

    <?php endif; /* fim status !== aprovado */ ?>

    <?php /* ── Detalhes técnicos (colapsável) ── */ ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 px-4 py-3">
            <button class="btn btn-link text-decoration-none p-0 fw-semibold text-body d-inline-flex align-items-center gap-2"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#detalhes-pagamento"
                    aria-expanded="false">
                <i class="bi bi-receipt text-muted"></i>
                Detalhes do pagamento
                <i class="bi bi-chevron-down text-muted" style="font-size: .75rem;"></i>
            </button>
        </div>
        <div class="collapse" id="detalhes-pagamento">
            <div class="card-body pt-0 px-4 pb-4">
                <table class="table table-sm table-borderless pix-detail-table mb-0">
                    <tbody>
                        <tr>
                            <td>ID do pagamento</td>
                            <td><code>#<?php echo (int)$pagamento['id']; ?></code></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                <span class="badge bg-<?php echo $statusInfo['badge']; ?> d-inline-flex align-items-center gap-1">
                                    <i class="bi <?php echo $statusInfo['icon']; ?>"></i>
                                    <?php echo $statusInfo['label']; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Plano</td>
                            <td><?php echo sanitize($planoLabel); ?> &mdash; <?php echo sanitize($periodicidadeLabel); ?></td>
                        </tr>
                        <tr>
                            <td>Valor</td>
                            <td class="fw-semibold text-success">
                                R$ <?php echo number_format($valor, 2, ',', '.'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Método</td>
                            <td class="d-flex align-items-center gap-1">
                                <i class="bi bi-qr-code text-muted"></i> Pix
                            </td>
                        </tr>
                        <?php if (!empty($dataCriacao)): ?>
                        <tr>
                            <td>Gerado em</td>
                            <td><?php echo formatDateBR($dataCriacao); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($aprovadoEm)): ?>
                        <tr>
                            <td>Confirmado em</td>
                            <td><?php echo formatDateBR($aprovadoEm); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($txid !== ''): ?>
                        <tr>
                            <td>TXID Pix</td>
                            <td>
                                <code class="text-break" style="font-size:.78rem;"><?php echo sanitize($txid); ?></code>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($efiChargeId !== ''): ?>
                        <tr>
                            <td>ID Cobrança (EFI)</td>
                            <td><code><?php echo sanitize($efiChargeId); ?></code></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td>Titular</td>
                            <td><?php echo sanitize((string)($pagamento['usuario_nome'] ?? '')); ?></td>
                        </tr>
                        <tr>
                            <td>E-mail</td>
                            <td class="text-break"><?php echo sanitize((string)($pagamento['email'] ?? '')); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    window.copiarPix = function () {
        var el      = document.getElementById('pix-code');
        var feedback = document.getElementById('copy-feedback');
        var btn     = document.getElementById('btn-copy');
        var icon    = document.getElementById('btn-copy-icon');
        var lbl     = document.getElementById('btn-copy-label');
        if (!el) return;

        var done = function () {
            if (feedback) feedback.classList.remove('d-none');
            if (icon)     { icon.className = 'bi bi-clipboard-check'; }
            if (lbl)      lbl.textContent = 'Copiado!';
            if (btn)      btn.classList.replace('btn-primary', 'btn-success');
            setTimeout(function () {
                if (feedback) feedback.classList.add('d-none');
                if (icon)     icon.className = 'bi bi-clipboard';
                if (lbl)      lbl.textContent = 'Copiar código';
                if (btn)      btn.classList.replace('btn-success', 'btn-primary');
            }, 3000);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(el.value).then(done).catch(function () {
                el.select(); el.setSelectionRange(0, 99999);
                try { document.execCommand('copy'); done(); } catch (e) {}
            });
        } else {
            el.select(); el.setSelectionRange(0, 99999);
            try { document.execCommand('copy'); done(); } catch (e) {}
        }
    };

    <?php if ($status === 'pendente'): ?>
    var n = 20;
    var el = document.getElementById('countdown');
    var t  = setInterval(function () {
        n--;
        if (el) el.textContent = n;
        if (n <= 0) {
            clearInterval(t);
            window.location.href = '<?php echo url('/parceiro/pix?id=' . $id); ?>';
        }
    }, 1000);

    var btnA = document.getElementById('btn-atualizar');
    if (btnA) btnA.addEventListener('click', function () { clearInterval(t); });
    <?php else: ?>
    var msg = document.getElementById('auto-refresh-msg');
    if (msg) msg.remove();
    <?php endif; ?>
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
