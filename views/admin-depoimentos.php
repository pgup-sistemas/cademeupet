<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$pageTitle = 'Admin - Depoimentos - Cadê Meu Pet?';
$adminCtrl = new AdminController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Erro de validação. Recarregue a página.', MSG_ERROR);
        redirect('/admin/depoimentos');
    }
    $depoimentoId = (int)($_POST['depoimento_id'] ?? 0);
    $adminCtrl->processarDepoimento($depoimentoId, $_POST['acao'] ?? '');
    redirect('/admin/depoimentos');
}

$filtro = $_GET['filtro'] ?? 'pendente';
['depoimentos' => $depoimentos, 'contagens' => $contagens] = $adminCtrl->listarDepoimentos($filtro);

$breadcrumbs = [
    ['label' => 'Início',    'url' => BASE_URL],
    ['label' => 'Admin',     'url' => BASE_URL . '/admin'],
    ['label' => 'Depoimentos'],
];
$suppressBreadcrumbBar = true;
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">

    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

    <div class="admin-main py-4 px-4">

    <?php include __DIR__ . '/../includes/admin-breadcrumb.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0"><i class="fa-solid fa-quote-left me-2"></i>Depoimentos</h1>
        <a href="<?php echo BASE_URL; ?>/admin" class="btn btn-outline-secondary btn-sm d-none d-lg-inline-flex">
            <i class="fa-solid fa-arrow-left me-1"></i>Voltar ao Admin
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-auto">
            <a href="?filtro=pendente" class="text-decoration-none">
                <div class="card border-0 shadow-sm <?php echo $filtro === 'pendente' ? 'border-warning border-2' : ''; ?>" style="min-width:130px;">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold fs-4 text-warning"><?php echo (int)($contagens['pendentes'] ?? 0); ?></div>
                        <div class="small text-muted">Pendentes</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-auto">
            <a href="?filtro=aprovado" class="text-decoration-none">
                <div class="card border-0 shadow-sm <?php echo $filtro === 'aprovado' ? 'border-success border-2' : ''; ?>" style="min-width:130px;">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold fs-4 text-success"><?php echo (int)($contagens['aprovados'] ?? 0); ?></div>
                        <div class="small text-muted">Aprovados (públicos)</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <?php $flash = getFlashMessage(); if ($flash): ?>
        <div class="alert alert-<?php echo sanitize($flash['type']); ?> alert-dismissible">
            <?php echo sanitize($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($depoimentos)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-check-circle fa-3x mb-3 d-block opacity-25"></i>
            <p>Nenhum depoimento <?php echo $filtro; ?>.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($depoimentos as $d): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <p class="fst-italic mb-3">"<?php echo sanitize($d['texto']); ?>"</p>
                            <p class="small text-muted mb-1">
                                <i class="fa-solid fa-user me-1"></i><?php echo sanitize($d['usuario_nome']); ?>
                                &nbsp;·&nbsp;<?php echo timeAgo($d['criado_em']); ?>
                            </p>
                            <?php if (!empty($d['nome_pet'])): ?>
                                <p class="small text-muted mb-0">
                                    <i class="fa-solid fa-paw me-1"></i>sobre <?php echo sanitize($d['nome_pet']); ?>
                                    <?php if ($d['anuncio_id']): ?>
                                        (<a href="<?php echo BASE_URL; ?>/anuncio/<?php echo (int)$d['anuncio_id']; ?>/" target="_blank">ver anúncio</a>)
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <?php if ($filtro === 'pendente'): ?>
                            <div class="card-footer bg-transparent border-0 pb-3 px-3">
                                <div class="d-flex gap-2">
                                    <form method="POST" class="flex-grow-1">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="depoimento_id" value="<?php echo (int)$d['id']; ?>">
                                        <input type="hidden" name="acao" value="aprovar">
                                        <button type="submit" class="btn btn-success btn-sm w-100">
                                            <i class="fa-solid fa-check me-1"></i>Aprovar
                                        </button>
                                    </form>
                                    <form method="POST" class="flex-grow-1" onsubmit="return confirm('Rejeitar e remover este depoimento?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="depoimento_id" value="<?php echo (int)$d['id']; ?>">
                                        <input type="hidden" name="acao" value="rejeitar">
                                        <button type="submit" class="btn btn-danger btn-sm w-100">
                                            <i class="fa-solid fa-xmark me-1"></i>Rejeitar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    </div><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
