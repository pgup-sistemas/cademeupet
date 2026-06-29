<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Minhas Assinaturas - Cadê Meu Pet?';
$usuarioId = (int)getUserId();

$assinaturaCtrl = new AssinaturaController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Erro de validação. Recarregue a página.', 'danger');
    } else {
        $acao        = $_POST['acao'] ?? '';
        $assinaturaId = (int)($_POST['assinatura_id'] ?? 0);
        if ($assinaturaId > 0 && in_array($acao, ['cancelar', 'pausar', 'reativar'], true)) {
            $assinaturaCtrl->processarAcao($usuarioId, $assinaturaId, $acao);
        }
    }
    redirect('/assinaturas');
}

$assinaturas = $assinaturaCtrl->getAssinaturas($usuarioId);
$historico   = $assinaturaCtrl->getHistorico($usuarioId);

$totalAssinado = 0;
$totalMensal   = 0;
foreach ($assinaturas as $assinatura) {
    if (in_array($assinatura['status'], ['ativa', 'aprovada'], true)) {
        $v = (float)$assinatura['valor'];
        $totalAssinado += $v;
        $totalMensal   += $v;
    }
}

$breadcrumbs = [
    ['label' => 'Início',           'url' => BASE_URL],
    ['label' => 'Minhas Assinaturas'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <h1 class="h4 fw-bold mb-4">Minhas Assinaturas e Doações Recorrentes</h1>

    <?php $flash = getFlashMessage(); if ($flash): ?>
        <div class="alert alert-<?php echo sanitize($flash['type']); ?> alert-dismissible">
            <?php echo sanitize($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Estatísticas -->
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card text-white text-center py-3" style="background:linear-gradient(135deg,#667eea,#764ba2);">
                <div class="small opacity-75">Total Assinado</div>
                <div class="fw-bold fs-5">R$ <?php echo number_format($totalAssinado, 2, ',', '.'); ?></div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-white text-center py-3" style="background:linear-gradient(135deg,#f093fb,#f5576c);">
                <div class="small opacity-75">Assinaturas Ativas</div>
                <div class="fw-bold fs-5"><?php echo count(array_filter($assinaturas, fn($a) => $a['status'] === 'ativa')); ?></div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-white text-center py-3" style="background:linear-gradient(135deg,#4facfe,#00f2fe);">
                <div class="small opacity-75">Compromisso Mensal</div>
                <div class="fw-bold fs-5">R$ <?php echo number_format($totalMensal, 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>

    <!-- Assinaturas -->
    <h2 class="h5 fw-bold mb-3">Suas Assinaturas</h2>

    <?php if (empty($assinaturas)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-heart-circle-xmark fa-3x mb-3 d-block opacity-25"></i>
            <p>Você não tem assinaturas ativas no momento.</p>
            <a href="<?php echo BASE_URL; ?>/doar" class="btn btn-primary">Fazer uma doação</a>
        </div>
    <?php else: ?>
        <?php
        $statusLabel = [
            'ativa'     => 'Ativa',
            'pausada'   => 'Pausada',
            'cancelada' => 'Cancelada',
            'pendente'  => 'Pendente',
            'aprovada'  => 'Aprovada',
            'recusado'  => 'Recusado',
        ];
        $metodoLabel = [
            'pix'     => 'PIX',
            'cartao'  => 'Cartão',
            'gateway' => 'Cartão Recorrente',
            'boleto'  => 'Boleto',
        ];
        ?>
        <?php foreach ($assinaturas as $ass): ?>
            <div class="assinatura-card mb-3">
                <div class="assinatura-header">
                    <div class="assinatura-titulo">
                        <?php
                            $titulo = $ass['titulo'] ?? 'Doação Anônima';
                            echo sanitize(mb_strlen($titulo) > 50 ? mb_substr($titulo, 0, 50) . '...' : $titulo);
                        ?>
                    </div>
                    <span class="status-badge status-<?php echo sanitize($ass['status']); ?>">
                        <?php echo sanitize($statusLabel[$ass['status']] ?? $ass['status']); ?>
                    </span>
                </div>

                <div class="assinatura-info">
                    <div class="info-item">
                        <div class="info-label">Valor da Doação</div>
                        <div class="info-value valor-grande">R$ <?php echo number_format((float)$ass['valor'], 2, ',', '.'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Método de Pagamento</div>
                        <div class="info-value">
                            <?php
                                $m = $ass['metodo_pagamento'] ?? '';
                                echo sanitize($metodoLabel[$m] ?? ucfirst($m) ?: 'Não especificado');
                            ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Próxima Cobrança</div>
                        <div class="info-value">
                            <?php echo !empty($ass['proxima_cobranca'])
                                ? (new DateTime($ass['proxima_cobranca']))->format('d/m/Y')
                                : 'A definir'; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Último Pagamento</div>
                        <div class="info-value">
                            <?php echo !empty($ass['ultimo_pagamento_em'])
                                ? (new DateTime($ass['ultimo_pagamento_em']))->format('d/m/Y H:i')
                                : 'Pendente'; ?>
                        </div>
                    </div>
                </div>

                <div class="assinatura-acoes">
                    <?php if ($ass['status'] === 'ativa'): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="acao" value="pausar">
                            <input type="hidden" name="assinatura_id" value="<?php echo (int)$ass['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-warning"
                                    onclick="return confirm('Pausar esta assinatura?')">
                                <i class="fa-solid fa-pause me-1"></i>Pausar
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if ($ass['status'] === 'pausada'): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="acao" value="reativar">
                            <input type="hidden" name="assinatura_id" value="<?php echo (int)$ass['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-success">
                                <i class="fa-solid fa-circle-check me-1"></i>Reativar
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if ($ass['status'] !== 'cancelada'): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="acao" value="cancelar">
                            <input type="hidden" name="assinatura_id" value="<?php echo (int)$ass['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Cancelar esta assinatura? Esta ação não pode ser desfeita.')">
                                <i class="fa-solid fa-circle-xmark me-1"></i>Cancelar
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Histórico de Pagamentos -->
    <h2 class="h5 fw-bold mt-5 mb-3">Histórico de Pagamentos</h2>

    <?php if (empty($historico)): ?>
        <p class="text-muted text-center py-3">Nenhum histórico de pagamento disponível.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historico as $item): ?>
                        <tr>
                            <td class="small text-nowrap">
                                <?php echo (new DateTime($item['ultimo_pagamento_em'] ?? $item['criada_em']))->format('d/m/Y H:i'); ?>
                            </td>
                            <td class="small"><?php echo sanitize(mb_substr($item['titulo'] ?? 'Doação', 0, 40)); ?></td>
                            <td class="fw-bold small">R$ <?php echo number_format((float)$item['valor'], 2, ',', '.'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo sanitize($item['status']); ?>">
                                    <?php echo sanitize($item['status_label'] ?? $item['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/doacao/<?php echo (int)$item['id']; ?>/"
                                   class="btn btn-sm btn-outline-primary">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
