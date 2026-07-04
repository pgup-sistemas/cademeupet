<?php
/**
 * GET /api/v1/parceiros — lista parceiros publicados (petshops/clínicas/ONGs).
 * Filtros: categoria, cidade, pagina, por_pagina.
 */
require_once __DIR__ . '/_bootstrap.php';
requireApiScope('parceiros_leitura');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiRespondError('method_not_allowed', 'Apenas GET é suportado neste recurso.', 405);
}

$cidade = $_GET['cidade'] ?? null;
$categoria = $_GET['categoria'] ?? null;

if ($categoria !== null && !in_array($categoria, ['petshop', 'clinica', 'hotel', 'adestrador', 'outro'], true)) {
    apiRespondError('validation_error', 'Categoria inválida.', 400);
}

$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = min(50, max(1, (int)($_GET['por_pagina'] ?? 20)));
$offset = ($pagina - 1) * $porPagina;

$parceiroModel = new ParceiroPerfil();
$rows = $parceiroModel->listPublic($cidade, $categoria, $porPagina, $offset);
$total = $parceiroModel->countPublic($cidade, $categoria);

$serializados = array_map(function (array $p): array {
    return [
        'id'            => (int)$p['id'],
        'nome_fantasia' => $p['nome_fantasia'],
        'categoria'     => $p['categoria'],
        'descricao'     => $p['descricao'],
        'cidade'        => $p['cidade'],
        'estado'        => $p['estado'],
        'bairro'        => $p['bairro'],
        'verificado'    => (bool)$p['verificado'],
        'destaque'      => (bool)$p['destaque'],
        'site'          => $p['site'],
        'instagram'     => $p['instagram'],
        'url'           => rtrim((string)BASE_URL, '/') . '/parceiro/' . $p['slug'],
        // Telefone/whatsapp/e-mail não são expostos publicamente pela API —
        // o contato passa pela página pública do parceiro (link acima).
    ];
}, $rows);

apiRespondSuccess([
    'data' => $serializados,
    'meta' => [
        'pagina'     => $pagina,
        'por_pagina' => $porPagina,
        'total'      => $total,
    ],
]);
