<?php
require_once __DIR__ . '/../config.php';

$includeMapAssets = true;
$includeMapCluster = true;

$homeCtrl = new HomeController();
['anunciosRecentes' => $anunciosRecentes, 'stats' => $stats, 'depoimentos' => $depoimentos] = $homeCtrl->getHomeData();

// Impacto combinado: reencontros + adoções concluídas (anúncios resolvidos)
// + matches confirmados no Pet Love.
$totalConexoes = (int)($stats['casos_resolvidos'] ?? 0) + (int)($stats['petlove_matches'] ?? 0);

include __DIR__ . '/../includes/header.php';
?>

<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="display-4 fw-bold mb-4 hero-title">
                    <i class="fa-solid fa-paw"></i> Encontre, Adote ou Conecte um Pet
                </h1>
                <p class="lead mb-4">
                    Do reencontro de pets perdidos à adoção responsável e ao Pet Love para cruzamento,
                    além de parceiros de confiança para cuidar do seu animal — o Cadê Meu Pet? conecta
                    tutores em todo o Brasil. Juntos, já promovemos <?php echo number_format($totalConexoes); ?> conexões entre pets e famílias.
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
                <div class="cta-buttons mt-4 mt-lg-0">
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

<?php if (!empty($depoimentos)): ?>
<!-- Depoimentos -->
<div class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="h3 fw-bold mb-2"><i class="fa-solid fa-heart text-danger me-2"></i>Histórias reais de reencontro</h2>
            <p class="text-muted">Depoimentos de tutores que usaram o Cadê Meu Pet? para reunir a família</p>
        </div>
        <div class="row g-4">
            <?php foreach ($depoimentos as $dep): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <i class="fa-solid fa-quote-left text-primary opacity-25 fa-2x mb-2"></i>
                            <p class="fst-italic flex-grow-1">"<?php echo sanitize(truncate($dep['texto'], 220)); ?>"</p>
                            <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top">
                                <?php if (!empty($dep['foto'])): ?>
                                    <img src="<?php echo BASE_URL . '/uploads/anuncios/' . sanitize($dep['foto']); ?>" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;" alt="">
                                <?php else: ?>
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                        <i class="fa-solid fa-paw"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <strong class="d-block small"><?php echo sanitize($dep['usuario_nome']); ?></strong>
                                    <span class="text-muted" style="font-size:.78rem;">
                                        <?php echo $dep['nome_pet'] ? 'sobre ' . sanitize($dep['nome_pet']) : 'Cadê Meu Pet?'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

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

<!-- Mapa Geral -->
<div class="py-5 bg-white border-top border-bottom">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h2 class="h3 fw-bold mb-1"><i class="fa-solid fa-map-location-dot"></i> Mapa de Ocorrências</h2>
                <p class="text-muted mb-0">Pets perdidos e encontrados na sua região</p>
            </div>
            <div class="d-none d-md-flex gap-3 align-items-center small">
                <span><span class="badge" style="background:#e74c3c;">&nbsp;</span> Perdido (<span id="legendaCountPerdido">0</span>)</span>
                <span><span class="badge" style="background:#27ae60;">&nbsp;</span> Encontrado (<span id="legendaCountEncontrado">0</span>)</span>
                <span><span class="badge" style="background:#3498db;">&nbsp;</span> Adoção (<span id="legendaCountDoacao">0</span>)</span>
            </div>
        </div>
        <div id="mapaGeral" style="height:420px;border-radius:16px;overflow:hidden;border:1px solid rgba(0,0,0,.08);"></div>
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
                    <h4>Publique</h4>
                    <p>Cadastre o pet perdido ou encontrado com foto e localização</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="step-card">
                    <div class="step-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                    <h4>Busque</h4>
                    <p>Pessoas procuram por pets na sua região</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="step-card">
                    <div class="step-icon"><i class="fa-solid fa-heart"></i></div>
                    <h4>Reúna</h4>
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

<!-- Pet Love CTA -->
<div class="py-5" style="background:linear-gradient(135deg,#fff0f5,#ffe8f5);">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-md-7">
                <h2 class="fw-bold mb-2">
                    <i class="fa-solid fa-heart me-2" style="color:#e5499a;"></i>Encontre um par para o seu pet
                </h2>
                <p class="mb-0 text-muted">
                    No <strong>Pet Love</strong> você encontra pets compatíveis para cruzamento — filtrados por espécie, raça, porte e proximidade.
                    Criação responsável e com acompanhamento veterinário.
                </p>
            </div>
            <div class="col-md-5 text-md-end">
                <a href="<?php echo BASE_URL; ?>/petlove" class="btn btn-lg fw-semibold me-2"
                   style="background:#e5499a;color:#fff;border-color:#e5499a;">
                    <i class="fa-solid fa-heart me-2"></i>Explorar Pet Love
                </a>
                <a href="<?php echo BASE_URL; ?>/petlove/novo" class="btn btn-outline-secondary btn-lg">
                    <i class="fa-solid fa-plus me-1"></i>Cadastrar pet
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

<link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/vendors/leaflet.markercluster/MarkerCluster.css">
<link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/vendors/leaflet.markercluster/MarkerCluster.Default.css">
<script>
// Mapa geral carregado após todos os scripts (Leaflet incluído via footer)
window.addEventListener('load', function () {
    const mapEl = document.getElementById('mapaGeral');
    if (!mapEl || typeof L === 'undefined') return;

    function initMapaGeral() {
        const map = L.map('mapaGeral', {
            scrollWheelZoom: false,
            center: [-10.9472, -61.9327],
            zoom: 5
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        const corTipo = { perdido: '#e74c3c', encontrado: '#27ae60', doacao: '#3498db' };

        function criarIcone(tipo) {
            const cor = corTipo[tipo] || '#888';
            return L.divIcon({
                className: '',
                html: `<div style="
                    width:16px;height:16px;border-radius:50%;
                    background:${cor};border:2px solid #fff;
                    box-shadow:0 1px 3px rgba(0,0,0,.4);"></div>`,
                iconSize: [16, 16],
                iconAnchor: [8, 8],
                popupAnchor: [0, -10]
            });
        }

        const grupo = (typeof L.markerClusterGroup === 'function')
            ? L.markerClusterGroup({ maxClusterRadius: 60 })
            : L.layerGroup();

        const bounds = [];

        fetch(<?php echo json_encode(BASE_URL . '/api/mapa-pins.php'); ?>)
            .then(r => r.json())
            .then(pins => {
                if (!Array.isArray(pins) || pins.length === 0) return;
                const tipoLabel = { perdido: 'Perdido', encontrado: 'Encontrado', doacao: 'Adoção' };

                const contagem = { perdido: 0, encontrado: 0, doacao: 0 };
                pins.forEach(pin => { if (contagem[pin.tipo] !== undefined) contagem[pin.tipo]++; });
                const elPerdido = document.getElementById('legendaCountPerdido');
                const elEncontrado = document.getElementById('legendaCountEncontrado');
                const elDoacao = document.getElementById('legendaCountDoacao');
                if (elPerdido) elPerdido.textContent = contagem.perdido;
                if (elEncontrado) elEncontrado.textContent = contagem.encontrado;
                if (elDoacao) elDoacao.textContent = contagem.doacao;

                pins.forEach(pin => {
                    const marker = L.marker([pin.lat, pin.lng], { icon: criarIcone(pin.tipo) });
                    const foto = pin.foto_thumb
                        ? `<img src="${pin.foto_thumb}" style="width:72px;height:72px;object-fit:cover;border-radius:8px;float:left;margin-right:8px;" loading="lazy">`
                        : '';
                    marker.bindPopup(`
                        <div style="min-width:180px;overflow:hidden;">
                            ${foto}
                            <strong>${pin.nome_pet}</strong><br>
                            <span style="background:${corTipo[pin.tipo]||'#888'};color:#fff;font-size:.75rem;padding:1px 6px;border-radius:4px;">${tipoLabel[pin.tipo] || ''}</span>
                            <br><small>${pin.bairro ? pin.bairro + ' · ' : ''}${pin.cidade}</small><br>
                            <a href="${pin.url}" style="font-size:.82rem;">Ver anúncio &rarr;</a>
                        </div>
                    `, { maxWidth: 240 });
                    grupo.addLayer(marker);
                    bounds.push([pin.lat, pin.lng]);
                });

                map.addLayer(grupo);

                if (bounds.length > 0) {
                    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 12 });
                }
            })
            .catch(() => {});
    }

    initMapaGeral();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>