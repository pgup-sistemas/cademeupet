<?php
/**
 * GET /api/v1/anuncios — lista anúncios públicos moderados.
 * Filtros: tipo, especie, cidade, estado, lat+lng+raio_km, pagina, por_pagina.
 *
 * Nunca expõe dados pessoais do tutor (nome, telefone, e-mail, endereço
 * exato) — apenas os atributos do anúncio e localização aproximada.
 */
require_once __DIR__ . '/_bootstrap.php';
requireApiScope('anuncios_leitura');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiRespondError('method_not_allowed', 'Apenas GET é suportado neste recurso.', 405);
}

if (!empty($_GET['raio_km']) && (empty($_GET['lat']) || empty($_GET['lng']))) {
    apiRespondError('validation_error', 'Informe lat e lng ao usar raio_km.', 400);
}

$filtros = [
    'status' => 'ativo',
];
foreach (['tipo', 'especie', 'cidade', 'estado'] as $campo) {
    if (!empty($_GET[$campo])) {
        $filtros[$campo] = $_GET[$campo];
    }
}
if (!empty($_GET['lat']) && !empty($_GET['lng']) && !empty($_GET['raio_km'])) {
    $filtros['lat']  = (float)$_GET['lat'];
    $filtros['lng']  = (float)$_GET['lng'];
    $filtros['raio'] = (float)$_GET['raio_km'];
}

$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = min(50, max(1, (int)($_GET['por_pagina'] ?? 20)));
$offset = ($pagina - 1) * $porPagina;

$anuncioModel = new Anuncio();
$resultado = $anuncioModel->search($filtros, $porPagina, $offset);

$serializados = array_map(function (array $a): array {
    return [
        'id'             => (int)$a['id'],
        'tipo'           => $a['tipo'],
        'especie'        => $a['especie'],
        'raca'           => $a['raca'],
        'cor'            => $a['cor'],
        'tamanho'        => $a['tamanho'],
        'nome_pet'       => $a['nome_pet'],
        'descricao'      => $a['descricao'],
        'cidade'         => $a['cidade'],
        'estado'         => $a['estado'],
        // Latitude/longitude arredondadas (~1km) — não expõe endereço exato do tutor.
        'latitude'       => $a['latitude'] !== null ? round((float)$a['latitude'], 2) : null,
        'longitude'      => $a['longitude'] !== null ? round((float)$a['longitude'], 2) : null,
        'data_ocorrido'  => $a['data_ocorrido'],
        'data_publicacao' => $a['data_publicacao'],
        'foto_url'       => !empty($a['foto']) ? rtrim((string)BASE_URL, '/') . '/uploads/anuncios/' . $a['foto'] : null,
        'url'            => rtrim((string)BASE_URL, '/') . '/anuncio/' . (int)$a['id'],
    ];
}, $resultado['results'] ?? []);

apiRespondSuccess([
    'data' => $serializados,
    'meta' => [
        'pagina'     => $pagina,
        'por_pagina' => $porPagina,
        'total'      => (int)($resultado['total'] ?? 0),
    ],
]);
