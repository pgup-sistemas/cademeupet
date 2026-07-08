<?php
require_once __DIR__ . '/../config.php';

$buscaController = new BuscaController();
$params = $_GET;
$resultado = $buscaController->listar($params);

$anuncios   = $resultado['anuncios'];
$filters    = $resultado['filters'];
$page       = $resultado['page'];
$total      = $resultado['total'] ?? count($anuncios);
$totalPages = $resultado['totalPages'] ?? 1;

$tituloPorTipo = [
    'perdido'    => 'Pets Perdidos',
    'encontrado' => 'Pets Encontrados',
    'doacao'     => 'Pets para Adoção',
];
$tituloBusca = $tituloPorTipo[$filters['tipo'] ?? ''] ?? 'Buscar Pets';
$pageTitle   = $tituloBusca . ' - Cadê Meu Pet?';

$filtrosAtivosCount = 0;
foreach (['q', 'tipo', 'especie', 'estado', 'cidade', 'bairro', 'tamanho', 'ordenacao', 'has_photo', 'raio'] as $chaveFiltro) {
    if (!empty($params[$chaveFiltro])) {
        $filtrosAtivosCount++;
    }
}

$includeMapAssets = true;

$mapPoints = [];
foreach ($anuncios as $anuncio) {
    if (!empty($anuncio['latitude']) && !empty($anuncio['longitude'])) {
        $mapPoints[] = [
            'id' => (int)$anuncio['id'],
            'tipo' => (string)($anuncio['tipo'] ?? ''),
            'especie' => (string)($anuncio['especie'] ?? ''),
            'nome' => (string)($anuncio['nome_pet'] ?: ('Pet ' . ucfirst((string)($anuncio['especie'] ?? '')))),
            'bairro' => (string)($anuncio['bairro'] ?? ''),
            'cidade' => (string)($anuncio['cidade'] ?? ''),
            'foto' => !empty($anuncio['foto']) ? (string)$anuncio['foto'] : null,
            'lat' => (float)$anuncio['latitude'],
            'lng' => (float)$anuncio['longitude'],
        ];
    }
}

// Seção de primeiro nível do menu principal (irmã do Início) — sem breadcrumb, igual à Home.
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <div class="offcanvas-lg offcanvas-start busca-filtros-offcanvas" tabindex="-1" id="offcanvasFiltros" aria-labelledby="offcanvasFiltrosLabel">
                <div class="offcanvas-header d-lg-none border-bottom">
                    <h5 class="offcanvas-title fw-bold" id="offcanvasFiltrosLabel"><i class="bi bi-funnel me-2"></i>Filtros</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#offcanvasFiltros" aria-label="Fechar"></button>
                </div>
                <div class="offcanvas-body d-block p-lg-0">
                    <div class="card shadow-sm border-0 sticky-lg-top busca-filtros-sticky">
                        <div class="card-body busca-filtros-body">
                            <h5 class="fw-bold mb-3 d-none d-lg-block"><i class="bi bi-funnel me-2"></i>Filtros Rápidos</h5>
                            <form method="GET" action="" id="filtrosBusca">
                                <div class="mb-2">
                                    <label for="busca-q" class="form-label">Palavra-chave</label>
                                    <input type="text" id="busca-q" name="q" class="form-control" placeholder="Ex: labrador preto" value="<?php echo sanitize($params['q'] ?? ''); ?>">
                                </div>

                                <div class="mb-2">
                                    <label for="busca-tipo" class="form-label">Tipo</label>
                                    <select id="busca-tipo" name="tipo" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="perdido" <?php echo (($filters['tipo'] ?? '') === 'perdido') ? 'selected' : ''; ?>>Perdidos</option>
                                        <option value="encontrado" <?php echo (($filters['tipo'] ?? '') === 'encontrado') ? 'selected' : ''; ?>>Encontrados</option>
                                        <option value="doacao" <?php echo (($filters['tipo'] ?? '') === 'doacao') ? 'selected' : ''; ?>>Adoção</option>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label for="busca-especie" class="form-label">Espécie</label>
                                    <select id="busca-especie" name="especie" class="form-select">
                                        <option value="">Todas</option>
                                        <option value="cachorro" <?php echo (($filters['especie'] ?? '') === 'cachorro') ? 'selected' : ''; ?>>Cachorro</option>
                                        <option value="gato" <?php echo (($filters['especie'] ?? '') === 'gato') ? 'selected' : ''; ?>>Gato</option>
                                        <option value="ave" <?php echo (($filters['especie'] ?? '') === 'ave') ? 'selected' : ''; ?>>Ave</option>
                                        <option value="outro" <?php echo (($filters['especie'] ?? '') === 'outro') ? 'selected' : ''; ?>>Outro</option>
                                    </select>
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-4">
                                        <label for="busca-estado" class="form-label">UF</label>
                                        <input type="text" id="busca-estado" name="estado" maxlength="2" class="form-control text-uppercase" value="<?php echo sanitize($params['estado'] ?? ''); ?>">
                                    </div>
                                    <div class="col-8">
                                        <label for="busca-cidade" class="form-label">Cidade</label>
                                        <input type="text" id="busca-cidade" name="cidade" class="form-control" value="<?php echo sanitize($params['cidade'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label for="busca-bairro" class="form-label">Bairro</label>
                                    <input type="text" id="busca-bairro" name="bairro" class="form-control" value="<?php echo sanitize($params['bairro'] ?? ''); ?>">
                                </div>

                                <div class="mb-2">
                                    <label for="busca-tamanho" class="form-label">Porte</label>
                                    <select id="busca-tamanho" name="tamanho" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="pequeno" <?php echo (($filters['tamanho'] ?? '') === 'pequeno') ? 'selected' : ''; ?>>Pequeno</option>
                                        <option value="medio" <?php echo (($filters['tamanho'] ?? '') === 'medio') ? 'selected' : ''; ?>>Médio</option>
                                        <option value="grande" <?php echo (($filters['tamanho'] ?? '') === 'grande') ? 'selected' : ''; ?>>Grande</option>
                                        <option value="gigante" <?php echo (($filters['tamanho'] ?? '') === 'gigante') ? 'selected' : ''; ?>>Gigante</option>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label for="busca-ordenacao" class="form-label">Ordenar por</label>
                                    <select id="busca-ordenacao" name="ordenacao" class="form-select">
                                        <option value="">Mais recentes</option>
                                        <option value="antigo" <?php echo (($filters['ordenacao'] ?? '') === 'antigo') ? 'selected' : ''; ?>>Mais antigos</option>
                                        <option value="popular" <?php echo (($filters['ordenacao'] ?? '') === 'popular') ? 'selected' : ''; ?>>Mais populares</option>
                                        <option value="proximo" <?php echo (($filters['ordenacao'] ?? '') === 'proximo') ? 'selected' : ''; ?>>Mais próximos</option>
                                    </select>
                                </div>

                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" value="1" id="comFoto" name="has_photo" <?php echo isset($filters['has_photo']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="comFoto">
                                        Apenas anúncios com foto
                                    </label>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label d-flex justify-content-between align-items-center">
                                        <span>Raio de busca</span>
                                        <button class="btn btn-link btn-sm p-0" type="button" onclick="usarMinhaPosicao()"><i class="bi bi-crosshair me-1"></i>Perto de mim</button>
                                    </label>
                                    <input type="hidden" name="lat" id="lat" value="<?php echo sanitize($params['lat'] ?? ''); ?>">
                                    <input type="hidden" name="lng" id="lng" value="<?php echo sanitize($params['lng'] ?? ''); ?>">
                                    <select name="raio" class="form-select">
                                        <option value="">Qualquer distância</option>
                                        <?php foreach ([5,10,20,50] as $raio): ?>
                                            <option value="<?php echo $raio; ?>" <?php echo (($filters['raio'] ?? '') == $raio) ? 'selected' : ''; ?>><?php echo $raio; ?> km</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="d-grid gap-2 mt-3">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Aplicar filtros</button>
                                    <a href="<?php echo BASE_URL; ?>/busca/" class="btn btn-outline-secondary">Limpar filtros</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                <div>
                    <h1 class="h3 fw-bold mb-0"><?php echo sanitize($tituloBusca); ?></h1>
                    <p class="text-muted mb-0">
                        <?php if ($total > 0): ?>
                            <strong><?php echo number_format($total); ?></strong> anúnci<?php echo $total === 1 ? 'o encontrado' : 'os encontrados'; ?><?php if ($totalPages > 1): ?> &mdash; página <?php echo $page; ?> de <?php echo $totalPages; ?><?php endif; ?>
                        <?php else: ?>
                            Nenhum anúncio encontrado
                        <?php endif; ?>
                    </p>
                </div>
                <button type="button" class="btn btn-outline-primary btn-filtro-icon d-lg-none flex-shrink-0" data-bs-toggle="offcanvas" data-bs-target="#offcanvasFiltros" aria-controls="offcanvasFiltros" aria-label="Abrir filtros">
                    <i class="bi bi-funnel"></i>
                    <?php if ($filtrosAtivosCount > 0): ?>
                        <span class="badge rounded-pill bg-primary btn-filtro-badge"><?php echo $filtrosAtivosCount; ?></span>
                    <?php endif; ?>
                </button>
            </div>

            <div class="mb-3">
                <div class="btn-group" role="group">
                    <a href="?<?php echo http_build_query(array_merge($params, ['ordenacao' => ''])); ?>" class="btn btn-outline-primary <?php echo empty($filters['ordenacao']) ? 'active' : ''; ?>">Recentes</a>
                    <a href="?<?php echo http_build_query(array_merge($params, ['ordenacao' => 'proximo'])); ?>" class="btn btn-outline-primary <?php echo (($filters['ordenacao'] ?? '') === 'proximo') ? 'active' : ''; ?>">Mais próximos</a>
                    <a href="?<?php echo http_build_query(array_merge($params, ['ordenacao' => 'popular'])); ?>" class="btn btn-outline-primary <?php echo (($filters['ordenacao'] ?? '') === 'popular') ? 'active' : ''; ?>">Populares</a>
                </div>
            </div>

            <ul class="nav nav-tabs mb-3" id="buscaTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-lista" data-bs-toggle="tab" data-bs-target="#pane-lista" type="button" role="tab" aria-controls="pane-lista" aria-selected="true">Lista</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-mapa" data-bs-toggle="tab" data-bs-target="#pane-mapa" type="button" role="tab" aria-controls="pane-mapa" aria-selected="false">Mapa</button>
                </li>
            </ul>

            <div class="tab-content" id="buscaTabsContent">
                <div class="tab-pane fade show active" id="pane-lista" role="tabpanel" aria-labelledby="tab-lista" tabindex="0">

                    <?php if (empty($anuncios)): ?>
                        <div class="alert alert-info">
                            <h5 class="fw-bold"><i class="bi bi-emoji-neutral me-2"></i>Nenhum anúncio encontrado</h5>
                            <p class="mb-2">Tente ajustar os filtros ou pesquisar por termos diferentes.</p>
                            <ul class="mb-0 small text-muted">
                                <li>Verifique se a grafia está correta</li>
                                <li>Experimente ampliar o raio de busca</li>
                                <li>Selecione "Todos" em espécie ou tipo</li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($anuncios as $anuncio): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card anuncio-card h-100" onclick="window.location='<?php echo BASE_URL; ?>/anuncio/<?php echo $anuncio['id']; ?>/'">
                                        <div class="position-relative">
                                            <?php if (!empty($anuncio['foto'])): ?>
                                                <img src="<?php echo BASE_URL; ?>/uploads/anuncios/<?php echo sanitize($anuncio['foto']); ?>" class="card-img-top" alt="Foto do pet">
                                            <?php else: ?>
                                                <div class="card-img-top d-flex align-items-center justify-content-center bg-light text-muted" style="height: 200px;">
                                                    <div class="text-center">
                                                        <i class="bi bi-camera" style="font-size: 2.5rem;"></i>
                                                        <p class="mb-0 mt-2">Sem foto</p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <span class="badge tipo-badge <?php echo $anuncio['tipo'] === 'perdido' ? 'bg-danger' : ($anuncio['tipo'] === 'doacao' ? 'bg-primary' : 'bg-success'); ?>">
                                                <?php echo $anuncio['tipo'] === 'perdido' ? ' Perdido' : ($anuncio['tipo'] === 'doacao' ? ' Adoção' : ' Encontrado'); ?>
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title fw-bold mb-2"><?php echo sanitize($anuncio['nome_pet'] ?: 'Pet ' . ucfirst($anuncio['especie'])); ?></h5>
                                            <div class="d-flex flex-wrap gap-2 mb-2">
                                                <span class="badge bg-light text-dark"><i class="bi bi-geo-alt me-1"></i><?php echo sanitize($anuncio['bairro']); ?></span>
                                                <span class="badge bg-light text-dark"><?php echo ucfirst($anuncio['especie']); ?></span>
                                                <span class="badge bg-light text-dark"><?php echo ucfirst($anuncio['tamanho']); ?></span>
                                            </div>
                                            <p class="text-muted small mb-2">
                                                <?php echo sanitize(truncate($anuncio['descricao'] ?? '', 80)); ?>
                                            </p>
                                        </div>
                                        <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center">
                                            <span class="text-muted small"><i class="bi bi-clock me-1"></i><?php echo timeAgo($anuncio['data_publicacao']); ?></span>
                                            <a href="<?php echo BASE_URL; ?>/anuncio/<?php echo $anuncio['id']; ?>/" class="btn btn-sm btn-outline-primary">Ver detalhes</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalPages > 1): ?>
                        <nav class="mt-4" aria-label="Paginação de resultados">
                            <ul class="pagination justify-content-center flex-wrap">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($params, ['page' => $page - 1])); ?>" tabindex="-1">Anterior</a>
                                </li>
                                <?php
                                $rangeStart = max(1, $page - 2);
                                $rangeEnd   = min($totalPages, $page + 2);
                                if ($rangeStart > 1): ?>
                                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($params, ['page' => 1])); ?>">1</a></li>
                                    <?php if ($rangeStart > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                                <?php endif; ?>
                                <?php for ($p = $rangeStart; $p <= $rangeEnd; $p++): ?>
                                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($params, ['page' => $p])); ?>"><?php echo $p; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <?php if ($rangeEnd < $totalPages): ?>
                                    <?php if ($rangeEnd < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($params, ['page' => $totalPages])); ?>"><?php echo $totalPages; ?></a></li>
                                <?php endif; ?>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($params, ['page' => $page + 1])); ?>">Próxima</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="pane-mapa" role="tabpanel" aria-labelledby="tab-mapa" tabindex="0">
                    <?php if (empty($mapPoints)): ?>
                        <div class="alert alert-info">
                            Nenhum anúncio com coordenadas para exibir no mapa nesta página.
                        </div>
                    <?php else: ?>
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div id="mapBusca" style="height: 520px; border-radius: 12px; overflow: hidden;"></div>
                                <div class="small text-muted mt-2">
                                    Dica: aplique o filtro de raio e use “Perto de mim” para ver anúncios próximos.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let __petfinderBuscaMapInitialized = false;

function initBuscaMap() {
    if (__petfinderBuscaMapInitialized) return;

    const mapEl = document.getElementById('mapBusca');
    if (!mapEl || !window.L) return;

    __petfinderBuscaMapInitialized = true;

    const points = <?php echo json_encode($mapPoints, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const filtroLat = Number(<?php echo json_encode($params['lat'] ?? ''); ?>);
    const filtroLng = Number(<?php echo json_encode($params['lng'] ?? ''); ?>);
    const filtroRaio = Number(<?php echo json_encode($filters['raio'] ?? ''); ?>);

    let centerLat = -10.9472;
    let centerLng = -61.9327;
    let zoom = 12;

    if (Number.isFinite(filtroLat) && Number.isFinite(filtroLng)) {
        centerLat = filtroLat;
        centerLng = filtroLng;
        zoom = 13;
    } else if (points.length) {
        centerLat = points[0].lat;
        centerLng = points[0].lng;
        zoom = 12;
    }

    const map = L.map('mapBusca', {
        center: [centerLat, centerLng],
        zoom,
        scrollWheelZoom: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const bounds = [];

    points.forEach(function (p) {
        const url = <?php echo json_encode(BASE_URL . '/anuncio/'); ?> + p.id + '/';
        const titulo = String(p.nome || 'Pet');
        const local = [p.bairro, p.cidade].filter(Boolean).join(' - ');
        const fotoHtml = p.foto
            ? '<div class="mb-2"><img src="<?php echo BASE_URL; ?>/uploads/anuncios/' + p.foto + '" style="width: 180px; max-width: 100%; height: 110px; object-fit: cover; border-radius: 10px;" /></div>'
            : '';

        const marker = L.marker([p.lat, p.lng]).addTo(map);
        marker.bindPopup(
            '<div style="max-width: 220px;">'
                + fotoHtml
                + '<div style="font-weight: 600; margin-bottom: 4px;">' + titulo + '</div>'
                + '<div style="font-size: 12px; color: #666; margin-bottom: 8px;">' + local + '</div>'
                + '<a href="' + url + '" class="btn btn-sm btn-primary">Ver anúncio</a>'
            + '</div>'
        );

        bounds.push([p.lat, p.lng]);
    });

    let circle = null;
    if (Number.isFinite(filtroLat) && Number.isFinite(filtroLng) && Number.isFinite(filtroRaio) && filtroRaio > 0) {
        circle = L.circle([filtroLat, filtroLng], {
            radius: filtroRaio * 1000,
            color: '#0d6efd',
            weight: 2,
            fillColor: '#0d6efd',
            fillOpacity: 0.08
        }).addTo(map);
    }

    if (bounds.length) {
        const leafletBounds = L.latLngBounds(bounds);
        if (circle) {
            leafletBounds.extend(circle.getBounds());
        }
        map.fitBounds(leafletBounds.pad(0.2));
    }

    setTimeout(function () {
        map.invalidateSize();
    }, 50);
}

document.getElementById('tab-mapa')?.addEventListener('shown.bs.tab', function () {
    initBuscaMap();
});

function usarMinhaPosicao() {
    if (!navigator.geolocation) {
        alert('Seu navegador não suporta geolocalização.');
        return;
    }

    navigator.geolocation.getCurrentPosition(function(position) {
        document.getElementById('lat').value = position.coords.latitude;
        document.getElementById('lng').value = position.coords.longitude;
        document.querySelector('select[name="raio"]').value = document.querySelector('select[name="raio"]').value || '10';
        document.getElementById('filtrosBusca').submit();
    }, function() {
        alert('Não foi possível obter sua localização.');
    });
}

function usarMinhaPosicaoHome(lat, lng) {
    document.getElementById('lat').value = lat;
    document.getElementById('lng').value = lng;
    document.getElementById('filtrosBusca').submit();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
