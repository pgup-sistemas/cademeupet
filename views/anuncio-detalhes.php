<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Detalhes do Anúncio - Cadê Meu Pet?';

$anuncioController = new AnuncioController();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('Anúncio não encontrado.', MSG_WARNING);
    redirect('/busca.php');
}

$anuncio = $anuncioController->getDetalhes($id);

if (!$anuncio) {
    setFlashMessage('Anúncio não encontrado ou removido.', MSG_WARNING);
    redirect('/busca.php');
}

$anuncioController->registrarVisualizacao($id);

$isOwner = isLoggedIn() && getUserId() == $anuncio['usuario_id'];
$favoritoController = new FavoritoController();
$isFavorited = $favoritoController->isFavorited($anuncio['id']);

$shareUrl = rtrim((string)BASE_URL, '/') . '/anuncio/' . (int)$anuncio['id'] . '/';
$shareTitle = ($anuncio['nome_pet'] ?: ('Pet ' . ucfirst($anuncio['especie'])));
$shareDescription = trim((string)($anuncio['descricao'] ?? ''));
if ($shareDescription === '') {
    $shareDescription = 'Veja este anúncio no Cadê Meu Pet?.';
}
$shareDescription = truncate($shareDescription, 140);

$firstPhoto = null;
if (!empty($anuncio['fotos'][0]['nome_arquivo'])) {
    $firstPhoto = rtrim((string)BASE_URL, '/') . '/uploads/anuncios/' . $anuncio['fotos'][0]['nome_arquivo'];
}

$metaOgTitle = $shareTitle;
$metaOgDescription = $shareDescription;
$metaOgUrl = $shareUrl;
$metaOgImage = $firstPhoto;

// Page-specific SEO overrides
$pageTitle = ($shareTitle ? $shareTitle . ' — ' . SITE_NAME : ($pageTitle ?? ('Detalhes do Anúncio - ' . SITE_NAME)));
$metaDescription = $shareDescription;
$canonical = $shareUrl;
$metaRobots = $metaRobots ?? 'index, follow';
$pageJsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $shareUrl
    ],
    'headline' => $shareTitle,
    'description' => $shareDescription,
    'image' => $firstPhoto ? [$firstPhoto] : [],
    'datePublished' => date('c', strtotime($anuncio['data_publicacao'] ?? date('Y-m-d H:i:s'))),
    'author' => [
        '@type' => 'Person',
        'name' => $anuncio['usuario_nome'] ?? 'Anunciante'
    ]
];

$includeMapAssets = true;

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Início</a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/busca.php">Busca</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo sanitize($anuncio['nome_pet'] ?: 'Pet ' . ucfirst($anuncio['especie'])); ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-7">
            <div id="carouselFotos" class="carousel slide shadow-sm rounded" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php if (!empty($anuncio['fotos'])): ?>
                        <?php foreach ($anuncio['fotos'] as $index => $foto): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <div class="carousel-blur-bg" style="background-image: url('<?php echo BASE_URL; ?>/uploads/anuncios/<?php echo sanitize($foto['nome_arquivo']); ?>');"></div>
                                <img src="<?php echo BASE_URL; ?>/uploads/anuncios/<?php echo sanitize($foto['nome_arquivo']); ?>" class="d-block w-100 rounded carousel-photo" alt="Foto do pet">
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="carousel-item active">
                            <div class="d-flex align-items-center justify-content-center bg-light" style="height: 360px;">
                                <div class="text-center text-muted">
                                    <i class="fa-solid fa-camera fa-3x"></i>
                                    <p class="mt-3 mb-0">Este anúncio não possui fotos.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($anuncio['fotos']) && count($anuncio['fotos']) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselFotos" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carouselFotos" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Próxima</span>
                    </button>
                <?php endif; ?>
            </div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body p-4">
                    <h2 class="h4 fw-bold mb-3"><?php echo sanitize($anuncio['nome_pet'] ?: 'Pet ' . ucfirst($anuncio['especie'])); ?></h2>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge bg-<?php echo $anuncio['tipo'] === 'perdido' ? 'danger' : ($anuncio['tipo'] === 'doacao' ? 'primary' : 'success'); ?>">
                            <?php echo $anuncio['tipo'] === 'perdido' ? '<i class="fa-solid fa-circle text-danger"></i> Perdido' : ($anuncio['tipo'] === 'doacao' ? '<i class="fa-solid fa-circle text-primary"></i> Adoção' : '<i class="fa-solid fa-circle text-success"></i> Encontrado'); ?>
                        </span>
                        <span class="badge bg-light text-dark"><i class="fa-solid fa-location-dot me-1"></i><?php echo sanitize($anuncio['bairro']); ?> - <?php echo sanitize($anuncio['cidade']); ?></span>
                        <span class="badge bg-light text-dark"><?php echo ucfirst($anuncio['especie']); ?></span>
                        <span class="badge bg-light text-dark"><?php echo ucfirst($anuncio['tamanho']); ?></span>
                    </div>

                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                        <p class="text-muted mb-0">
                            <i class="fa-regular fa-clock me-2"></i>Publicado <?php echo timeAgo($anuncio['data_publicacao']); ?> • Visualizações: <?php echo (int)$anuncio['visualizacoes']; ?>
                        </p>
                        <div>
                            <?php if (isLoggedIn()): ?>
                                <form method="POST" action="<?php echo BASE_URL; ?>/favorito_toggle.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                                    <input type="hidden" name="return_to" value="<?php echo '/anuncio/' . (int)$anuncio['id'] . '/'; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo $isFavorited ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                        <i class="fa-<?php echo $isFavorited ? 'solid' : 'regular'; ?> fa-heart me-1"></i>
                                        <?php echo $isFavorited ? 'Remover dos Favoritos' : 'Salvar nos Favoritos'; ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>/login/?redirect=/anuncio/<?php echo $anuncio['id']; ?>/" class="btn btn-sm btn-outline-danger">
                                    <i class="fa-regular fa-heart me-1"></i>Entre para favoritar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($isOwner && ($anuncio['status'] ?? '') !== STATUS_RESOLVIDO && ($anuncio['status'] ?? '') !== STATUS_INATIVO): ?>
                        <form method="POST" action="<?php echo BASE_URL; ?>/marcar-resolvido.php" class="mb-4" onsubmit="return confirm('Marcar este anúncio como resolvido?');">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="anuncio_id" value="<?php echo (int)$anuncio['id']; ?>">
                            <input type="hidden" name="return_to" value="<?php echo '/anuncio/' . (int)$anuncio['id'] . '/'; ?>">
                            <button type="submit" class="btn btn-outline-success">
                                <i class="fa-solid fa-circle-check me-1"></i> Marcar como resolvido
                            </button>
                        </form>
                    <?php endif; ?>

                    <h5 class="fw-bold">Descrição</h5>
                    <p class="mb-4"><?php echo nl2br(sanitize($anuncio['descricao'] ?? '')); ?></p>

                    <div class="row g-3">
                        <?php if (($anuncio['tipo'] ?? '') === 'doacao'): ?>
                            <?php if (!empty($anuncio['idade']) || $anuncio['idade'] === 0 || $anuncio['idade'] === '0'): ?>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-label">Idade</span>
                                        <span class="info-value"><?php echo (int)$anuncio['idade']; ?> ano(s)</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($anuncio['castrado']) || $anuncio['castrado'] === 0 || $anuncio['castrado'] === '0'): ?>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-label">Castrado</span>
                                        <span class="info-value"><?php echo ((string)$anuncio['castrado'] === '1') ? 'Sim' : 'Não'; ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($anuncio['necessita_termo_responsabilidade'])): ?>
                                <div class="col-12">
                                    <div class="info-box">
                                        <span class="info-label">Termo</span>
                                        <span class="info-value">Necessita termo de responsabilidade</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($anuncio['vacinas'])): ?>
                                <div class="col-12">
                                    <div class="info-box">
                                        <span class="info-label">Vacinas / Observações</span>
                                        <span class="info-value"><?php echo nl2br(sanitize($anuncio['vacinas'])); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (!empty($anuncio['raca'])): ?>
                            <div class="col-md-6">
                                <div class="info-box">
                                    <span class="info-label">Raça</span>
                                    <span class="info-value"><?php echo sanitize($anuncio['raca']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($anuncio['cor'])): ?>
                            <div class="col-md-6">
                                <div class="info-box">
                                    <span class="info-label">Cor</span>
                                    <span class="info-value"><?php echo sanitize($anuncio['cor']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-label">Data do ocorrido</span>
                                <span class="info-value"><?php echo formatDateBR($anuncio['data_ocorrido']); ?></span>
                            </div>
                        </div>
                        <?php if (!empty($anuncio['ponto_referencia'])): ?>
                            <div class="col-md-6">
                                <div class="info-box">
                                    <span class="info-label">Ponto de referência</span>
                                    <span class="info-value"><?php echo sanitize($anuncio['ponto_referencia']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($anuncio['recompensa'])): ?>
                            <div class="col-md-6">
                                <div class="info-box">
                                    <span class="info-label">Recompensa</span>
                                    <span class="info-value text-success fw-bold"><?php echo sanitize($anuncio['recompensa']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="sticky-top" style="top: 80px;">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Localização</h5>
                    <p class="mb-2"><i class="fa-solid fa-location-dot me-2"></i><?php echo sanitize($anuncio['endereco_completo']); ?></p>
                    <p class="text-muted mb-0">Bairro <?php echo sanitize($anuncio['bairro']); ?> • <?php echo sanitize($anuncio['cidade']); ?> - <?php echo sanitize($anuncio['estado']); ?></p>
                </div>
                <?php if (!empty($anuncio['latitude']) && !empty($anuncio['longitude'])): ?>
                    <div id="mapDetalhe"
                         class="cmp-map"
                         data-lat="<?php echo sanitize($anuncio['latitude']); ?>"
                         data-lng="<?php echo sanitize($anuncio['longitude']); ?>"
                         data-nome="<?php echo sanitize($anuncio['nome_pet'] ?: 'Pet'); ?>"
                         data-data="<?php echo sanitize(date('d/m/Y', strtotime($anuncio['data_publicacao']))); ?>"></div>
                    <div class="card-footer bg-transparent pt-2 pb-3 px-4">
                        <a href="https://maps.google.com/?q=<?php echo (float)$anuncio['latitude']; ?>,<?php echo (float)$anuncio['longitude']; ?>"
                           target="_blank" rel="noopener"
                           class="btn btn-sm btn-outline-secondary w-100">
                            <i class="fa-solid fa-map-location-dot me-1"></i> Abrir no Google Maps
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Entre em contato</h5>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a class="btn btn-outline-success btn-sm" href="https://wa.me/?text=<?php echo rawurlencode($shareTitle . ' - ' . $shareUrl); ?>" target="_blank" rel="noopener">
                            <i class="fa-brands fa-whatsapp me-1"></i> Compartilhar
                        </a>
                        <a class="btn btn-outline-primary btn-sm" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode($shareUrl); ?>" target="_blank" rel="noopener">
                            <i class="fa-brands fa-facebook me-1"></i> Facebook
                        </a>
                        <a class="btn btn-outline-dark btn-sm" href="https://twitter.com/intent/tweet?url=<?php echo rawurlencode($shareUrl); ?>&text=<?php echo rawurlencode($shareTitle); ?>" target="_blank" rel="noopener">
                            <i class="fa-brands fa-x-twitter me-1"></i> X
                        </a>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCopyLink">
                            <i class="fa-solid fa-link me-1"></i> Copiar link
                        </button>
                    </div>

                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 d-flex align-items-center justify-content-between">
                            <div>
                                <strong>WhatsApp</strong>
                                <p class="mb-0 text-muted"><?php echo formatPhone($anuncio['whatsapp']); ?></p>
                            </div>
                            <a class="btn btn-success" href="https://wa.me/55<?php echo preg_replace('/[^0-9]/', '', $anuncio['whatsapp']); ?>" target="_blank"><i class="fa-brands fa-whatsapp"></i></a>
                        </div>
                        <?php if (!empty($anuncio['telefone_contato'])): ?>
                            <div class="list-group-item px-0">
                                <strong>Telefone</strong>
                                <p class="mb-0 text-muted"><?php echo formatPhone($anuncio['telefone_contato']); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($anuncio['email_contato'])): ?>
                            <div class="list-group-item px-0">
                                <strong>Email</strong>
                                <?php
                                    $emailContato = trim((string)$anuncio['email_contato']);
                                    $emailContatoSafe = filter_var($emailContato, FILTER_VALIDATE_EMAIL) ? $emailContato : '';
                                    $mailtoSubject = $shareTitle . ' - Cadê Meu Pet?';
                                    $mailtoBody = "Olá! Vi este anúncio no Cadê Meu Pet? e gostaria de falar com você.\n\n" . $shareUrl;
                                    $mailtoHref = $emailContatoSafe
                                        ? ('mailto:' . $emailContatoSafe . '?subject=' . rawurlencode($mailtoSubject) . '&body=' . rawurlencode($mailtoBody))
                                        : '';
                                ?>
                                <p class="mb-0 text-muted" id="emailContatoText"><?php echo sanitize($emailContato); ?></p>
                                <?php if ($mailtoHref): ?>
                                    <a class="btn btn-outline-primary btn-sm mt-2" href="<?php echo $mailtoHref; ?>">Enviar email</a>
                                <?php else: ?>
                                    <div class="text-muted small mt-2">E-mail inválido para envio automático.</div>
                                <?php endif; ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="btnCopyEmail">
                                    Copiar e-mail
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($isOwner): ?>
                        <hr>
                        <div class="d-grid gap-2">
                            <a href="<?php echo BASE_URL; ?>/editar-anuncio/<?php echo $anuncio['id']; ?>/" class="btn btn-outline-primary"><i class="fa-solid fa-pencil me-1"></i>Editar anúncio</a>
                        </div>
                    <?php endif; ?>

                    <?php if (($anuncio['status'] ?? '') === STATUS_RESOLVIDO && ($isOwner || isAdmin())): ?>
                        <div class="mt-3">
                            <form method="POST" action="<?php echo BASE_URL; ?>/marcar-ativo.php" class="d-inline" onsubmit="return confirm('Reativar este anúncio?');">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="anuncio_id" value="<?php echo (int)$anuncio['id']; ?>">
                                <input type="hidden" name="return_to" value="<?php echo '/anuncio/' . (int)$anuncio['id'] . '/'; ?>">
                                <button type="submit" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left me-1"></i> Reativar</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            </div><!-- /.sticky-top -->
        </div>
    </div>
</div>

<style>

.carousel-item {
    background: #111;
    position: relative;
    overflow: hidden;
}

.carousel-blur-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    filter: blur(18px);
    transform: scale(1.12);
    opacity: 0.95;
    border-radius: inherit;
}

.carousel-photo {
    position: relative;
    z-index: 1;
}

.carousel-item img {
    height: 420px;
    width: 100%;
    object-fit: contain;
    object-position: center;
    background: transparent;
}

@media (max-width: 768px) {
    .carousel-item img {
        height: 300px;
    }
}

.info-box {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 12px 16px;
}

.info-label {
    display: block;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #6c757d;
    letter-spacing: 0.05em;
}

.info-value {
    font-weight: 600;
    color: #333;
}

.cmp-map {
    height: 280px;
    border-bottom-left-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
    overflow: hidden;
    border-top: 1px solid rgba(0,0,0,0.08);
}
</style>

<script>
document.getElementById('btnCopyLink')?.addEventListener('click', async function () {
    try {
        await navigator.clipboard.writeText(<?php echo json_encode($shareUrl); ?>);
        alert('Link copiado!');
    } catch (e) {
        prompt('Copie o link:', <?php echo json_encode($shareUrl); ?>);
    }
});

document.getElementById('btnCopyEmail')?.addEventListener('click', async function () {
    const email = document.getElementById('emailContatoText')?.textContent?.trim();
    if (!email) return;
    try {
        await navigator.clipboard.writeText(email);
        alert('E-mail copiado!');
    } catch (e) {
        prompt('Copie o e-mail:', email);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const mapEl = document.getElementById('mapDetalhe');
    if (!mapEl || !window.L) {
        return;
    }

    const lat  = Number(mapEl.dataset.lat);
    const lng  = Number(mapEl.dataset.lng);
    const nome = mapEl.dataset.nome || 'Pet';
    const data = mapEl.dataset.data || '';

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return;
    }

    const map = L.map('mapDetalhe', {
        scrollWheelZoom: false,
        dragging: true,
        tap: true
    }).setView([lat, lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const marker = L.marker([lat, lng]).addTo(map);
    marker.bindPopup(
        `<strong>${nome}</strong><br><small>${data}</small>`,
        { closeButton: false }
    ).openPopup();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
