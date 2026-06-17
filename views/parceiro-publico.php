<?php
require_once __DIR__ . '/../config.php';

$slug = isset($_GET['slug']) ? (string)$_GET['slug'] : '';
$slug = trim($slug);

$perfilModel = new ParceiroPerfil();
$perfil = $slug !== '' ? $perfilModel->findBySlug($slug) : null;

if (!$perfil) {
    http_response_code(404);
    $pageTitle = 'Parceiro não encontrado | Cadê Meu Pet?';
    $breadcrumbs = [
        ['label' => 'Início',    'url' => BASE_URL],
        ['label' => 'Parceiros', 'url' => BASE_URL . '/parceiros'],
        ['label' => 'Não encontrado'],
    ];
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="container py-5">
        <div class="alert alert-warning">Parceiro não encontrado ou não publicado.</div>
        <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>/parceiros">Voltar para Parceiros</a>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$pageTitle = sanitize($perfil['nome_fantasia']) . ' | Parceiros Cadê Meu Pet?';
$metaOgTitle = $perfil['nome_fantasia'];
$metaOgDescription = $perfil['descricao'] ? truncate($perfil['descricao'], 160) : 'Serviços pet confiáveis na sua região.';
$metaOgUrl = BASE_URL . '/parceiro/' . $perfil['slug'];

$breadcrumbs = [
    ['label' => 'Início',    'url' => BASE_URL],
    ['label' => 'Parceiros', 'url' => BASE_URL . '/parceiros'],
    ['label' => sanitize($perfil['nome_fantasia'])],
];

include __DIR__ . '/../includes/header.php';
?>

<section class="partners-hero">
    <div class="container">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <div class="partners-hero-badge mb-3">
                    <i class="bi bi-briefcase me-2"></i>
                    <?php echo sanitize($perfil['categoria']); ?> • <?php echo sanitize($perfil['cidade']); ?> - <?php echo sanitize($perfil['estado']); ?>
                </div>
                <h1 class="display-6 fw-bold mb-2"><?php echo sanitize($perfil['nome_fantasia']); ?></h1>
                <p class="lead mb-0 text-muted">Serviços pet na sua região.</p>
            </div>
            <div class="col-lg-4">
                <div class="partners-hero-card">
                    <div class="fw-bold mb-2">Contato</div>
                    <div class="small text-muted">
                        <?php if (!empty($perfil['telefone'])): ?>
                            <div class="mb-1"><i class="bi bi-telephone"></i> <?php echo sanitize(formatPhone($perfil['telefone'])); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($perfil['whatsapp'])): ?>
                            <div class="mb-1"><i class="bi bi-whatsapp"></i> <?php echo sanitize(formatPhone($perfil['whatsapp'])); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($perfil['email_contato'])): ?>
                            <div class="mb-1"><i class="bi bi-envelope"></i> <?php echo sanitize($perfil['email_contato']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($perfil['site'])): ?>
                            <div class="mb-1"><i class="bi bi-globe"></i> <?php echo sanitize($perfil['site']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($perfil['instagram'])): ?>
                            <div class="mb-1"><i class="bi bi-instagram"></i> <?php echo sanitize($perfil['instagram']); ?></div>
                        <?php endif; ?>
                    </div>
                    <a class="btn btn-partners-primary w-100 mt-3" href="<?php echo BASE_URL; ?>/parceiros">Ver outros parceiros</a>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-2">Sobre</h2>
                    <div class="text-muted">
                        <?php echo nl2br(sanitize((string)($perfil['descricao'] ?? ''))); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-2">Endereço</h2>
                    <div class="text-muted">
                        <div><?php echo sanitize((string)($perfil['endereco'] ?? '')); ?></div>
                        <div><?php echo sanitize((string)($perfil['bairro'] ?? '')); ?></div>
                        <div><?php echo sanitize($perfil['cidade']); ?> - <?php echo sanitize($perfil['estado']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
