<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Meus Favoritos - Cadê Meu Pet?';

$favoritoController = new FavoritoController();
$favoritos = $favoritoController->listarDoUsuario();

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
        <div>
            <h1 class="h3 fw-bold mb-2">Meus Favoritos</h1>
            <p class="text-muted mb-0">Seus anúncios salvos para acompanhar de perto.</p>
        </div>
        <a class="btn btn-outline-primary mt-3 mt-md-0" href="<?php echo BASE_URL; ?>/busca/">
            <i class="bi bi-search me-1"></i>Buscar novos anúncios
        </a>
    </div>

    <?php if (empty($favoritos)): ?>
        <div class="alert alert-info">
            Você ainda não favoritou nenhum anúncio. Explore a busca e salve os que deseja acompanhar!
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($favoritos as $favorito): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card anuncio-card h-100" onclick="window.location='<?php echo BASE_URL; ?>/anuncio/<?php echo $favorito['id']; ?>/'">
                        <div class="position-relative">
                            <?php if (!empty($favorito['foto'])): ?>
                                <img src="<?php echo BASE_URL; ?>/uploads/anuncios/<?php echo sanitize($favorito['foto']); ?>" class="card-img-top" alt="Foto do pet">
                            <?php else: ?>
                                <div class="card-img-top d-flex align-items-center justify-content-center bg-light text-muted" style="height: 200px;">
                                    <div class="text-center">
                                        <i class="bi bi-camera" style="font-size: 2.5rem;"></i>
                                        <p class="mb-0 mt-2">Sem foto</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <span class="badge tipo-badge <?php echo $favorito['tipo'] === 'perdido' ? 'bg-danger' : ($favorito['tipo'] === 'doacao' ? 'bg-primary' : 'bg-success'); ?>">
                                <?php echo $favorito['tipo'] === 'perdido' ? '🔴 Perdido' : ($favorito['tipo'] === 'doacao' ? '💙 Adoção' : '🟢 Encontrado'); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-2"><?php echo sanitize($favorito['nome_pet'] ?: 'Pet ' . ucfirst($favorito['especie'])); ?></h5>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge bg-light text-dark"><i class="bi bi-geo-alt me-1"></i><?php echo sanitize($favorito['bairro']); ?></span>
                                <span class="badge bg-light text-dark"><?php echo ucfirst($favorito['especie']); ?></span>
                                <span class="badge bg-light text-dark"><?php echo ucfirst($favorito['tamanho']); ?></span>
                            </div>
                            <p class="text-muted small mb-2">
                                Favoritado em <?php echo formatDateBR($favorito['data_favoritado']); ?>
                            </p>
                        </div>
                        <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center">
                            <span class="text-muted small"><i class="bi bi-clock me-1"></i><?php echo timeAgo($favorito['data_publicacao']); ?></span>
                            <form method="POST" action="<?php echo BASE_URL; ?>/favorito_toggle.php" onsubmit="event.stopPropagation();">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="anuncio_id" value="<?php echo $favorito['id']; ?>">
                                <input type="hidden" name="return_to" value="/favoritos.php">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-star-fill me-1"></i>Remover
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.anuncio-card {
    border: none;
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    transition: transform .2s ease, box-shadow .2s ease;
    cursor: pointer;
}

.anuncio-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
}

.anuncio-card img {
    height: 200px;
    object-fit: cover;
    object-position: top center;
}

.tipo-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 0.4rem 0.75rem;
    font-weight: 600;
    border-radius: 999px;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
