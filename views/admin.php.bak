<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$pageTitle = 'Painel Admin - PetFinder';

$doacaoModel = new Doacao();
$usuarioModel = new Usuario();

$db = getDB();
$stats = $db->fetchOne('SELECT * FROM view_estatisticas');

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Painel Admin</h1>
            <p class="text-muted mb-0">Gestão de usuários e financeiro.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="btn btn-outline-primary">Usuários</a>
            <a href="<?php echo BASE_URL; ?>/admin/parceiros" class="btn btn-outline-primary">Parceiros</a>
            <a href="<?php echo BASE_URL; ?>/admin/financeiro" class="btn btn-primary">Financeiro</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Usuários ativos</div>
                    <div class="h4 fw-bold mb-0"><?php echo number_format((int)($stats['usuarios_ativos'] ?? 0)); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Anúncios ativos</div>
                    <div class="h4 fw-bold mb-0"><?php echo number_format((int)($stats['anuncios_ativos'] ?? 0)); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total doações aprovadas</div>
                    <div class="h4 fw-bold mb-0"><?php echo formatMoney((float)($stats['total_doacoes'] ?? 0)); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Doações mês atual</div>
                    <div class="h4 fw-bold mb-0"><?php echo formatMoney((float)($stats['doacoes_mes_atual'] ?? 0)); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info mt-4 mb-0">
        Use o menu para gerenciar usuários e doações.
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
