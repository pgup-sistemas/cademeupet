<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Termos de Adoção | Cadê Meu Pet?';
$usuarioId = (int)getUserId();
$controller = new TermoAdocaoController();

$comoDoador = $controller->comoDoador($usuarioId);
$comoAdotante = $controller->comoAdotante($usuarioId);

$statusLabel = [
    'aguardando_adotante' => 'Aguardando assinatura',
    'assinado' => 'Assinado',
    'recusado' => 'Recusado',
    'expirado' => 'Expirado',
];
$statusBadge = [
    'aguardando_adotante' => 'warning text-dark',
    'assinado' => 'success',
    'recusado' => 'secondary',
    'expirado' => 'secondary',
];

$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL],
    ['label' => 'Termos de Adoção'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <h1 class="h3 fw-bold mb-4">Termos de Adoção</h1>

    <h2 class="h5 fw-bold mb-2">Como adotante</h2>
    <?php if (empty($comoAdotante)): ?>
        <div class="alert alert-info">Nenhum termo endereçado a você ainda.</div>
    <?php else: ?>
        <div class="list-group mb-4 shadow-sm">
            <?php foreach ($comoAdotante as $t): ?>
                <a class="list-group-item list-group-item-action" href="<?php echo BASE_URL; ?>/termo-adocao?id=<?php echo (int)$t['id']; ?>">
                    <div class="d-flex justify-content-between">
                        <span><?php echo sanitize($t['nome_pet'] ?: ucfirst($t['especie'])); ?></span>
                        <span class="badge bg-<?php echo $statusBadge[$t['status']] ?? 'secondary'; ?>"><?php echo $statusLabel[$t['status']] ?? $t['status']; ?></span>
                    </div>
                    <div class="small text-muted"><?php echo formatDateTimeBR($t['criado_em']); ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2 class="h5 fw-bold mb-2">Como doador</h2>
    <?php if (empty($comoDoador)): ?>
        <div class="alert alert-info">Você ainda não iniciou nenhum termo.</div>
    <?php else: ?>
        <div class="list-group shadow-sm">
            <?php foreach ($comoDoador as $t): ?>
                <a class="list-group-item list-group-item-action" href="<?php echo BASE_URL; ?>/termo-adocao?id=<?php echo (int)$t['id']; ?>">
                    <div class="d-flex justify-content-between">
                        <span><?php echo sanitize($t['nome_pet'] ?: ucfirst($t['especie'])); ?></span>
                        <span class="badge bg-<?php echo $statusBadge[$t['status']] ?? 'secondary'; ?>"><?php echo $statusLabel[$t['status']] ?? $t['status']; ?></span>
                    </div>
                    <div class="small text-muted"><?php echo formatDateTimeBR($t['criado_em']); ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
