<?php
// CLI script para expirar anúncios vencidos

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Este script deve ser executado somente via linha de comando." . PHP_EOL);
}

require_once __DIR__ . '/../config.php';

set_time_limit(0);

echo '[' . date('Y-m-d H:i:s') . "] Iniciando expiração de anúncios..." . PHP_EOL;

$anuncioModel = new Anuncio();

try {
    $stmt = $anuncioModel->expireOldAds();
    $rows = $stmt->rowCount();
    echo "Anúncios expirados: {$rows}" . PHP_EOL;
} catch (Throwable $e) {
    error_log('[expire_ads] ' . $e->getMessage());
    echo "Erro: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo '[' . date('Y-m-d H:i:s') . "] Concluído." . PHP_EOL;
exit(0);
