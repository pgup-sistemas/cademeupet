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

$conversaCtrl = new ConversaController();
$conversaExistente = isLoggedIn() && !$isOwner ? $conversaCtrl->buscarConversaDoUsuario('anuncio', $anuncio['id'], getUserId()) : null;
$totalConversasAnuncio = $isOwner ? $conversaCtrl->contarConversasDoItem('anuncio', $anuncio['id']) : 0;

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

$breadcrumbs = [
    ['label' => 'Início',       'url' => BASE_URL],
    ['label' => 'Buscar Pets',  'url' => BASE_URL . '/busca'],
    ['label' => $shareTitle],
];

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">

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
                    <h2 class="h4 fw-bold mb-1"><?php echo sanitize($anuncio['nome_pet'] ?: 'Pet ' . ucfirst($anuncio['especie'])); ?></h2>
                    <?php if (!empty($anuncio['parceiro_nome_fantasia'])): ?>
                        <p class="mb-2">
                            <a href="<?php echo BASE_URL; ?>/parceiro/<?php echo sanitize($anuncio['parceiro_slug']); ?>" class="text-decoration-none">
                                <i class="fa-solid fa-building me-1"></i>
                                <span class="fw-semibold"><?php echo sanitize($anuncio['parceiro_nome_fantasia']); ?></span>
                            </a>
                            <?php if (!empty($anuncio['parceiro_verificado'])): ?>
                                <span class="badge bg-success-subtle text-success ms-1"><i class="fa-solid fa-circle-check me-1"></i>Parceiro verificado</span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
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

                    <?php if (($anuncio['status'] ?? '') === STATUS_RESOLVIDO): ?>
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                            <i class="fa-solid fa-heart-circle-check fa-lg"></i>
                            <div>
                                <strong>Reunido!</strong> Este pet foi reencontrado.
                                <?php if (!empty($anuncio['resolvido_em'])): ?>
                                    <span class="text-muted small ms-1">em <?php echo date('d/m/Y', strtotime($anuncio['resolvido_em'])); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($anuncio['historia_reuniao'])): ?>
                                    <br><em class="small">"<?php echo sanitize($anuncio['historia_reuniao']); ?>"</em>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($isOwner && ($anuncio['status'] ?? '') !== STATUS_RESOLVIDO && ($anuncio['status'] ?? '') !== STATUS_INATIVO): ?>
                        <button type="button" class="btn btn-outline-success mb-4"
                                data-bs-toggle="modal" data-bs-target="#modalResolvidoDetalhe"
                                data-anuncio-id="<?php echo (int)$anuncio['id']; ?>"
                                data-nome-pet="<?php echo sanitize($anuncio['nome_pet'] ?: ucfirst($anuncio['especie'])); ?>">
                            <i class="fa-solid fa-heart-circle-check me-1"></i>Marcar como Reunido!
                        </button>
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

                    <?php if ($isOwner && $totalConversasAnuncio > 0): ?>
                        <a href="<?php echo BASE_URL; ?>/mensagens" class="btn btn-primary w-100 mb-3">
                            <i class="fa-solid fa-comments me-1"></i>Ver conversas sobre este anúncio
                        </a>
                    <?php elseif ($conversaExistente): ?>
                        <a href="<?php echo BASE_URL . '/mensagens?conversa=' . (int)$conversaExistente['id']; ?>" class="btn btn-primary w-100 mb-3">
                            <i class="fa-solid fa-comments me-1"></i>Continuar conversa
                        </a>
                    <?php elseif (isLoggedIn() && !$isOwner && ($anuncio['status'] ?? '') === STATUS_ATIVO): ?>
                        <button type="button" class="btn btn-primary w-100 mb-3" data-bs-toggle="modal" data-bs-target="#modalTenhoInformacoes">
                            <i class="fa-solid fa-comment-dots me-1"></i>Tenho informações sobre esse pet
                        </button>
                        <p class="text-muted small mb-3">
                            A conversa fica registrada na plataforma e o tutor recebe um aviso na hora.
                        </p>
                    <?php elseif (!isLoggedIn()): ?>
                        <a href="<?php echo BASE_URL . '/login?redirect=' . rawurlencode('/anuncio/' . (int)$anuncio['id'] . '/'); ?>" class="btn btn-primary w-100 mb-3">
                            <i class="fa-solid fa-right-to-bracket me-1"></i>Entrar para enviar mensagem
                        </a>
                    <?php endif; ?>

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
                        <?php if (!empty($anuncio['whatsapp'])): ?>
                            <div class="list-group-item px-0 d-flex align-items-center justify-content-between">
                                <div>
                                    <strong>WhatsApp</strong>
                                    <p class="mb-0 text-muted"><?php echo formatPhone($anuncio['whatsapp']); ?></p>
                                </div>
                                <a class="btn btn-success" href="https://wa.me/55<?php echo preg_replace('/[^0-9]/', '', $anuncio['whatsapp']); ?>" target="_blank"><i class="fa-brands fa-whatsapp"></i></a>
                            </div>
                        <?php endif; ?>
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

                    <?php if (isLoggedIn() && !$isOwner): ?>
                        <hr>
                        <div class="d-grid">
                            <button type="button" class="btn btn-outline-danger btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#modalDenuncia">
                                <i class="fa-solid fa-flag me-1"></i>Denunciar anúncio
                            </button>
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

<!-- Modal: Marcar como Reunido -->
<div class="modal fade" id="modalResolvidoDetalhe" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?php echo BASE_URL; ?>/marcar-resolvido.php">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="anuncio_id" id="rdAnuncioId" value="">
                <input type="hidden" name="return_to" value="<?php echo '/anuncio/' . (int)($anuncio['id'] ?? 0) . '/'; ?>">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-heart-circle-check text-success me-2"></i>Pet reencontrado!
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Que alegria! Conte brevemente como foi o reencontro de <strong id="rdNomePet"></strong>.</p>
                    <textarea class="form-control" name="historia_reuniao" rows="3" maxlength="500"
                              placeholder="Ex: Encontramos o Rex a dois quarteiroes de casa..."></textarea>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-heart-circle-check me-1"></i>Confirmar reencontro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
const mrd = document.getElementById('modalResolvidoDetalhe');
if (mrd) {
    mrd.addEventListener('show.bs.modal', function(e) {
        const btn = e.relatedTarget;
        document.getElementById('rdAnuncioId').value = btn.dataset.anuncioId || '';
        document.getElementById('rdNomePet').textContent = btn.dataset.nomePet || 'o pet';
    });
}
</script>

<?php if (isLoggedIn() && !$isOwner && ($anuncio['status'] ?? '') === STATUS_ATIVO): ?>
<!-- Modal: Tenho informações -->
<div class="modal fade" id="modalTenhoInformacoes" tabindex="-1" aria-labelledby="modalTenhoInformacoesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTenhoInformacoesLabel">
                    <i class="fa-solid fa-comment-dots text-primary me-2"></i>Tenho informações
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Conte ao tutor o que você viu ou sabe sobre este pet. A conversa abre dentro da plataforma.</p>
                <div id="tiAlerta"></div>
                <textarea class="form-control" id="tiMensagem" rows="4" maxlength="1000"
                          placeholder="Ex: Acho que vi esse pet no bairro X, perto da praça..."></textarea>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnEnviarTenhoInformacoes">
                    <i class="fa-solid fa-paper-plane me-1"></i>Enviar mensagem
                </button>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('btnEnviarTenhoInformacoes')?.addEventListener('click', async function () {
    const mensagem = document.getElementById('tiMensagem').value.trim();
    const alerta = document.getElementById('tiAlerta');
    alerta.innerHTML = '';

    if (!mensagem) {
        alerta.innerHTML = '<div class="alert alert-warning py-2">Escreva uma mensagem.</div>';
        return;
    }

    this.disabled = true;
    try {
        const resp = await fetch(<?php echo json_encode(rtrim((string)BASE_URL, '/') . '/api/conversas.php'); ?>, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                acao: 'abrir',
                tipo: 'anuncio',
                referencia_id: <?php echo (int)$anuncio['id']; ?>,
                mensagem: mensagem,
                csrf_token: <?php echo json_encode(generateCSRFToken()); ?>
            })
        });
        const data = await resp.json();
        if (data.ok) {
            window.location.href = <?php echo json_encode(rtrim((string)BASE_URL, '/') . '/mensagens'); ?> + '?conversa=' + data.conversa_id;
        } else {
            alerta.innerHTML = '<div class="alert alert-danger py-2">' + (data.erro || 'Erro ao enviar mensagem.') + '</div>';
            this.disabled = false;
        }
    } catch (e) {
        alerta.innerHTML = '<div class="alert alert-danger py-2">Erro de conexão. Tente novamente.</div>';
        this.disabled = false;
    }
});
</script>
<?php endif; ?>

<?php if (isLoggedIn() && !$isOwner): ?>
<!-- Modal: Denunciar Anúncio -->
<div class="modal fade" id="modalDenuncia" tabindex="-1" aria-labelledby="modalDenunciaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalDenunciaLabel">
                    <i class="fa-solid fa-flag text-danger me-2"></i>Denunciar anúncio
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Sua denúncia é anônima e será analisada pela equipe.</p>
                <div id="denunciaAlerta"></div>
                <div class="mb-3">
                    <label for="denunciaMotivo" class="form-label fw-semibold">Motivo <span class="text-danger">*</span></label>
                    <select class="form-select" id="denunciaMotivo" required>
                        <option value="">Selecione...</option>
                        <option value="inapropriado">Conteúdo inapropriado</option>
                        <option value="spam">Spam / duplicado</option>
                        <option value="venda">Anúncio de venda disfarçado</option>
                        <option value="golpe">Possível golpe ou fraude</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="denunciaDescricao" class="form-label fw-semibold">Detalhes adicionais</label>
                    <textarea class="form-control" id="denunciaDescricao" rows="3" maxlength="500"
                              placeholder="Descreva brevemente o problema..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnEnviarDenuncia">
                    <i class="fa-solid fa-flag me-1"></i>Enviar denúncia
                </button>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('btnEnviarDenuncia')?.addEventListener('click', async function () {
    const motivo    = document.getElementById('denunciaMotivo').value;
    const descricao = document.getElementById('denunciaDescricao').value;
    const alerta    = document.getElementById('denunciaAlerta');
    alerta.innerHTML = '';

    if (!motivo) {
        alerta.innerHTML = '<div class="alert alert-warning py-2">Selecione um motivo.</div>';
        return;
    }

    this.disabled = true;
    try {
        const fd = new FormData();
        fd.append('csrf_token', <?php echo json_encode(generateCSRFToken()); ?>);
        fd.append('anuncio_id', <?php echo (int)($anuncio['id'] ?? 0); ?>);
        fd.append('motivo', motivo);
        fd.append('descricao', descricao);

        const resp = await fetch(<?php echo json_encode(BASE_URL . '/denunciar-anuncio'); ?>, { method: 'POST', body: fd });
        const data = await resp.json();

        if (data.ok) {
            alerta.innerHTML = '<div class="alert alert-success py-2">Denúncia enviada! Obrigado por ajudar a manter a plataforma segura.</div>';
            document.getElementById('btnEnviarDenuncia').classList.add('d-none');
        } else {
            alerta.innerHTML = '<div class="alert alert-danger py-2">' + (data.erro || 'Erro ao enviar denúncia.') + '</div>';
            this.disabled = false;
        }
    } catch (e) {
        alerta.innerHTML = '<div class="alert alert-danger py-2">Erro de conexão. Tente novamente.</div>';
        this.disabled = false;
    }
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
