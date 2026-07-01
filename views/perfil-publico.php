<?php
require_once __DIR__ . '/../config.php';

$usuarioId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($usuarioId <= 0) {
    http_response_code(404);
    include __DIR__ . '/../includes/header.php';
    echo '<div class="container py-5"><div class="alert alert-warning">Usuário não encontrado.</div></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$db = getDB();

$usuario = $db->fetchOne(
    "SELECT id, nome, cidade, estado, data_cadastro FROM usuarios WHERE id = ? AND ativo = 1",
    [$usuarioId]
);

if (!$usuario) {
    http_response_code(404);
    $pageTitle = 'Usuário não encontrado | Cadê Meu Pet?';
    $breadcrumbs = [
        ['label' => 'Início', 'url' => BASE_URL],
        ['label' => 'Usuário não encontrado'],
    ];
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="container py-5">
        <div class="alert alert-warning">Usuário não encontrado ou inativo.</div>
        <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>">Voltar ao Início</a>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$anuncioModel = new Anuncio();
$resultado    = $anuncioModel->search(['usuario_id' => $usuarioId, 'status' => STATUS_ATIVO], 20, 0);
$anuncios     = $resultado['results'] ?? [];
$totalAnuncios = $resultado['total'] ?? 0;

$pageTitle   = sanitize($usuario['nome']) . ' | Cadê Meu Pet?';
$breadcrumbs = [
    ['label' => 'Início',  'url' => BASE_URL],
    ['label' => sanitize($usuario['nome'])],
];

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <!-- Coluna de perfil -->
        <div class="col-lg-3">
            <div class="card shadow-sm border-0 text-center p-4">
                <div class="mb-3">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                         style="width:80px;height:80px;font-size:2rem;">
                        <?php echo mb_strtoupper(mb_substr($usuario['nome'], 0, 1, 'UTF-8'), 'UTF-8'); ?>
                    </div>
                </div>
                <h5 class="fw-bold mb-1"><?php echo sanitize($usuario['nome']); ?></h5>
                <?php if (!empty($usuario['cidade'])): ?>
                    <div class="text-muted small mb-1">
                        <i class="bi bi-geo-alt me-1"></i>
                        <?php echo sanitize($usuario['cidade']); ?>
                        <?php if (!empty($usuario['estado'])): ?>
                            &mdash; <?php echo sanitize($usuario['estado']); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="text-muted small">
                    <i class="bi bi-calendar3 me-1"></i>
                    Membro desde <?php echo date('M/Y', strtotime($usuario['data_cadastro'])); ?>
                </div>
                <hr>
                <div class="text-muted small">
                    <strong><?php echo $totalAnuncios; ?></strong> anúncio<?php echo $totalAnuncios !== 1 ? 's' : ''; ?> ativo<?php echo $totalAnuncios !== 1 ? 's' : ''; ?>
                </div>
            </div>
        </div>

        <!-- Anúncios ativos do usuário -->
        <div class="col-lg-9">
            <h4 class="fw-bold mb-4">Anúncios de <?php echo sanitize($usuario['nome']); ?></h4>

            <?php if (empty($anuncios)): ?>
                <div class="alert alert-info">Este usuário não possui anúncios ativos no momento.</div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($anuncios as $anuncio): ?>
                        <div class="col-sm-6 col-xl-4">
                            <a href="<?php echo BASE_URL; ?>/anuncio/<?php echo (int)$anuncio['id']; ?>/" class="text-decoration-none">
                                <div class="card h-100 shadow-sm border-0 anuncio-card">
                                    <?php if (!empty($anuncio['foto'])): ?>
                                        <img src="<?php echo BASE_URL; ?>/uploads/anuncios/<?php echo sanitize($anuncio['foto']); ?>"
                                             class="card-img-top" alt="Foto do pet"
                                             style="height:180px;object-fit:cover;">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center bg-light" style="height:180px;">
                                            <i class="bi bi-camera text-muted" style="font-size:2rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <span class="badge bg-<?php echo $anuncio['tipo'] === 'perdido' ? 'danger' : ($anuncio['tipo'] === 'doacao' ? 'primary' : 'success'); ?> mb-2">
                                            <?php echo $anuncio['tipo'] === 'perdido' ? 'Perdido' : ($anuncio['tipo'] === 'doacao' ? 'Adoção' : 'Encontrado'); ?>
                                        </span>
                                        <h6 class="card-title mb-1 fw-bold text-dark">
                                            <?php echo sanitize($anuncio['nome_pet'] ?: ('Pet ' . ucfirst($anuncio['especie']))); ?>
                                        </h6>
                                        <div class="text-muted small">
                                            <i class="bi bi-geo-alt me-1"></i>
                                            <?php echo sanitize($anuncio['cidade']); ?> &mdash; <?php echo sanitize($anuncio['estado']); ?>
                                        </div>
                                        <div class="text-muted small mt-1">
                                            <?php echo formatDateBR($anuncio['data_publicacao'] ?? ''); ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
