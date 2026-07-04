<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Minhas Triagens | Cadê Meu Pet?';
$usuarioId = (int)getUserId();
$controller = new TriagemController();
$solicitacoes = $controller->historicoDoUsuario($usuarioId);

$urgenciaLabel = [
    'critica'  => 'Crítica',
    'alta'     => 'Alta',
    'moderada' => 'Moderada',
    'baixa'    => 'Baixa',
];
$statusLabel = [
    'orientado'  => 'Orientado',
    'em_contato' => 'Em contato',
    'encerrado'  => 'Encerrado',
    'abandonado' => 'Abandonado',
];

$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL],
    ['label' => 'Minhas Triagens'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">Minhas triagens</h1>
        <a href="<?php echo BASE_URL; ?>/triagem" class="btn btn-primary btn-sm">Nova triagem</a>
    </div>

    <?php if (empty($solicitacoes)): ?>
        <div class="alert alert-info">Você ainda não fez nenhuma triagem.</div>
    <?php else: ?>
        <div class="list-group shadow-sm">
            <?php foreach ($solicitacoes as $s): ?>
                <a class="list-group-item list-group-item-action" href="<?php echo BASE_URL; ?>/triagem?resultado=<?php echo (int)$s['id']; ?>">
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="fw-semibold"><?php echo ucfirst(sanitize($s['especie'])); ?></span>
                            — Urgência: <?php echo $urgenciaLabel[$s['nivel_urgencia']] ?? $s['nivel_urgencia']; ?>
                            <?php if (!empty($s['parceiro_nome'])): ?>
                                · Clínica: <?php echo sanitize($s['parceiro_nome']); ?>
                            <?php elseif (!empty($s['local_publico_nome'])): ?>
                                · <?php echo sanitize($s['local_publico_nome']); ?>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-light text-dark"><?php echo $statusLabel[$s['status']] ?? $s['status']; ?></span>
                    </div>
                    <div class="small text-muted mt-1"><?php echo formatDateTimeBR($s['criado_em']); ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
