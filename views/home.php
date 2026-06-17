<?php
require_once __DIR__ . '/../config.php';

// Buscar últimos anúncios
$db = getDB();
$anunciosRecentes = $db->fetchAll("
    SELECT a.*, u.nome as autor_nome,
           (SELECT nome_arquivo FROM fotos_anuncios WHERE anuncio_id = a.id ORDER BY ordem LIMIT 1) as foto
    FROM anuncios a
    JOIN usuarios u ON a.usuario_id = u.id
    WHERE a.status = 'ativo'
    ORDER BY a.data_publicacao DESC
    LIMIT 8
");

// Estatísticas
$stats = $db->fetchOne("SELECT * FROM view_estatisticas");

include __DIR__ . '/../includes/header.php';
?>

<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="display-4 fw-bold mb-4 hero-title">
                    <i class="fa-solid fa-paw"></i> Encontre ou Publique um Pet
                </h1>
                <p class="lead mb-4">
                    Ajudamos a encontrar o dono de pets perdidos e a devolver cada animal ao seu lar.
                    Juntos, já promovemos <?php echo number_format($stats['casos_resolvidos'] ?? 0); ?> reencontros.
                </p>
                
                <!-- Busca Rápida -->
                <div class="search-box mb-4">
                    <form action="<?php echo BASE_URL; ?>/busca" method="GET">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text" id="quick-search-addon"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" 
                                   name="q" 
                                   class="form-control" 
                                   placeholder="Busque por raça, cor, bairro... Ex: labrador preto"
                                   id="quick-search"
                                   aria-label="Busca rápida"
                                   aria-describedby="quick-search-addon">
                            <button type="submit" class="btn btn-primary btn-search-hero">
                                Buscar
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Filtros Rápidos -->
                <div class="quick-filters d-flex flex-wrap gap-2">
                    <a href="<?php echo BASE_URL; ?>/busca?tipo=perdido" class="btn btn-outline-danger">
                        <i class="fa-solid fa-circle text-danger"></i> Perdidos
                    </a>
                    <a href="<?php echo BASE_URL; ?>/busca?tipo=encontrado" class="btn btn-outline-success">
                        <i class="fa-solid fa-circle text-success"></i> Encontrados
                    </a>
                    <a href="<?php echo BASE_URL; ?>/busca?tipo=doacao" class="btn btn-outline-primary">
                        <i class="fa-solid fa-circle text-primary"></i> Adoção
                    </a>
                    <a href="<?php echo BASE_URL; ?>/busca?especie=cachorro" class="btn btn-outline-secondary">
                        Cachorros
                    </a>
                    <a href="<?php echo BASE_URL; ?>/busca?especie=gato" class="btn btn-outline-secondary">
                        Gatos
                    </a>
                    <button onclick="buscarProximos()" class="btn btn-outline-primary">
                        <i class="fa-solid fa-location-dot"></i> Perto de Mim
                    </button>
                </div>
            </div>
            
            <div class="col-lg-5 text-center">
                <div class="cta-buttons">
                    <a href="<?php echo BASE_URL; ?>/novo-anuncio" class="btn btn-success btn-lg mb-3 w-100">
                        PUBLICAR ANÚNCIO
                    </a>
                    <p class="text-muted small">
                        É rápido, fácil e 100% gratuito!
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estatísticas -->
<div class="stats-bar">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['usuarios_ativos'] ?? 0); ?></div>
                    <div class="stat-label">Usuários Ativos</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['perdidos_ativos'] ?? 0); ?></div>
                    <div class="stat-label">Pets Perdidos</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['encontrados_ativos'] ?? 0); ?></div>
                    <div class="stat-label">Pets Encontrados</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['casos_resolvidos'] ?? 0); ?></div>
                    <div class="stat-label">Casos Resolvidos</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Anúncios Recentes -->
<div class="py-5">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="h3 fw-bold mb-1"><i class="fa-solid fa-bolt"></i> Publicados Hoje</h2>
                <p class="text-muted mb-0">Anúncios mais recentes na sua região</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/busca" class="btn btn-outline-primary d-none d-md-inline-flex">
                Ver todos →
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($anunciosRecentes as $anuncio): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card pet-card h-100">
                        <a href="<?php echo BASE_URL; ?>/anuncio/<?php echo (int)$anuncio['id']; ?>/" class="text-decoration-none text-dark">
                            <div class="position-relative">
                                <?php if ($anuncio['foto']): ?>
                                    <img class="card-img-top"
                                         src="<?php echo BASE_URL; ?>/uploads/anuncios/<?php echo sanitize($anuncio['foto']); ?>"
                                         alt="<?php echo sanitize($anuncio['nome_pet']); ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="card-img-top d-flex align-items-center justify-content-center bg-light text-muted"
                                         style="aspect-ratio:16/9;">
                                        <i class="fa-solid fa-camera fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="badge badge-tipo badge-<?php echo $anuncio['tipo']; ?>">
                                    <?php
                                        if ($anuncio['tipo'] === 'perdido') echo 'Perdido';
                                        elseif ($anuncio['tipo'] === 'doacao') echo 'Adoção';
                                        else echo 'Encontrado';
                                    ?>
                                </span>
                            </div>
                            <div class="card-body pb-2">
                                <h5 class="card-title fs-6 fw-semibold mb-1">
                                    <?php echo sanitize($anuncio['nome_pet'] ?: 'Pet ' . ucfirst($anuncio['especie'])); ?>
                                </h5>
                                <div class="mb-2">
                                    <span class="badge bg-secondary me-1"><?php echo ucfirst($anuncio['especie']); ?></span>
                                    <span class="badge bg-light text-dark"><?php echo ucfirst($anuncio['tamanho']); ?></span>
                                </div>
                                <p class="text-muted small mb-1">
                                    <i class="fa-solid fa-location-dot me-1"></i><?php echo sanitize($anuncio['bairro']); ?>, <?php echo sanitize($anuncio['cidade']); ?>
                                </p>
                                <p class="text-muted small mb-0">
                                    <i class="fa-regular fa-clock me-1"></i><?php echo timeAgo($anuncio['data_publicacao']); ?>
                                </p>
                            </div>
                        </a>
                        <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3 d-flex gap-2">
                            <a href="<?php echo BASE_URL; ?>/anuncio/<?php echo (int)$anuncio['id']; ?>/"
                               class="btn btn-sm btn-cmp-primary flex-grow-1">
                                Ver Detalhes
                            </a>
                            <?php if (isLoggedIn()): ?>
                                <button class="btn btn-sm btn-outline-danger btn-favoritar"
                                        data-id="<?php echo (int)$anuncio['id']; ?>"
                                        title="Favoritar">
                                    <i class="fa-solid fa-heart"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4 d-md-none">
            <a href="<?php echo BASE_URL; ?>/busca" class="btn btn-primary btn-lg">
                Ver Todos os Anúncios →
            </a>
        </div>
    </div>
</div>

<!-- Como Funciona -->
<div class="how-it-works-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="h3 fw-bold">Como Funciona?</h2>
            <p class="text-muted">É simples e rápido!</p>
        </div>
        
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="step-card">
                    <div class="step-icon"><i class="fa-solid fa-file-pen"></i></div>
                    <h4>1. Publique</h4>
                    <p>Cadastre o pet perdido ou encontrado com foto e localização</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="step-card">
                    <div class="step-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                    <h4>2. Busque</h4>
                    <p>Pessoas procuram por pets na sua região</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="step-card">
                    <div class="step-icon"><i class="fa-solid fa-heart text-danger"></i></div>
                    <h4>3. Reúna</h4>
                    <p>Conecte pets com suas famílias novamente!</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Parceiros CTA (discreto) -->
<div class="partners-cta-home py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="fw-bold mb-2"><i class="fa-solid fa-paw"></i> Serviços Pet na sua região</h3>
                <p class="mb-0">
                    Conheça empresas parceiras (pet shops, clínicas, hotéis, adestradores) e ajude a manter o Cadê Meu Pet? sustentável.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="<?php echo BASE_URL; ?>/parceiros" class="btn btn-light btn-lg">
                    Ver Parceiros
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Doações CTA -->
<div class="cta-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="fw-bold mb-3"><i class="fa-solid fa-heart me-2"></i>Ajude a Manter o Cadê Meu Pet? Gratuito</h2>
                <p class="mb-0 opacity-90">
                    Com sua doação, mantemos o sistema funcionando e ajudamos
                    mais pets a reencontrar suas famílias. Qualquer valor ajuda!
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-4 mt-md-0">
                <a href="<?php echo BASE_URL; ?>/doar" class="btn btn-light btn-lg fw-semibold" style="color:var(--cmp-secondary);">
                    <i class="fa-solid fa-heart me-2"></i>Doar Agora
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function buscarProximos() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            window.location.href = <?php echo json_encode(BASE_URL . '/busca'); ?> + `?lat=${lat}&lng=${lng}&raio=10`;
        }, function() {
            alert('Não foi possível obter sua localização. Verifique as permissões do navegador.');
        });
    } else {
        alert('Seu navegador não suporta geolocalização.');
    }
}

// Busca com sugestões
const searchInput = document.getElementById('quick-search');
if (searchInput) {
    let timeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            // Aqui você pode adicionar sugestões automáticas via AJAX
            console.log('Buscando:', this.value);
        }, 300);
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>