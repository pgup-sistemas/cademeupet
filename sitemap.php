<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=UTF-8');
$pdo = getDB();

$pages = [
    ['loc' => rtrim(BASE_URL, '/') . '/', 'priority' => '1.0', 'changefreq' => 'daily'],
    ['loc' => rtrim(BASE_URL, '/') . '/busca', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['loc' => rtrim(BASE_URL, '/') . '/parceiros', 'priority' => '0.8', 'changefreq' => 'weekly'],
    ['loc' => rtrim(BASE_URL, '/') . '/ajuda', 'priority' => '0.6', 'changefreq' => 'monthly'],
];

// Fetch public anuncios (not inativo, not bloqueado)
$rows = $pdo->fetchAll(
    "SELECT id, data_publicacao FROM anuncios WHERE status NOT IN (?, ?) ORDER BY data_publicacao DESC LIMIT 1000",
    [STATUS_INATIVO, STATUS_BLOQUEADO]
);

foreach ($rows as $r) {
    $pages[] = [
        'loc' => rtrim(BASE_URL, '/') . '/anuncio/' . (int)$r['id'] . '/',
        'lastmod' => date('Y-m-d', strtotime($r['data_publicacao'])),
        'priority' => '0.7',
        'changefreq' => 'monthly'
    ];
}

$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
foreach ($pages as $p) {
    $url = $xml->addChild('url');
    $url->addChild('loc', htmlspecialchars($p['loc'], ENT_XML1 | ENT_COMPAT, 'UTF-8'));
    if (!empty($p['lastmod'])) $url->addChild('lastmod', $p['lastmod']);
    $url->addChild('changefreq', $p['changefreq']);
    $url->addChild('priority', $p['priority']);
}

echo $xml->asXML();
