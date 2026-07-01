<?php
/**
 * Cron: dispara e-mails de alertas de busca pendentes.
 *
 * Configurar no cPanel/cron host:
 *   0 */6 * * *  php /home/user/public_html/cademeupet/cron/disparar-alertas.php >> /dev/null 2>&1
 *
 * Executa a cada 6 horas. O ALERT_MIN_INTERVAL_SECONDS (3600 s) no controller
 * garante que o mesmo alerta não dispara mais de 1x por hora mesmo que o cron
 * rode com frequência maior.
 */

// Só pode rodar via CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Proibido.');
}

define('CRON_START', microtime(true));

require_once __DIR__ . '/../config.php';

$db = getDB();

// Buscar todos os alertas ativos cujo último envio foi há mais de ALERT_MIN_INTERVAL_SECONDS
// ou que nunca foram enviados
$intervalo = defined('ALERT_MIN_INTERVAL_SECONDS') ? (int)ALERT_MIN_INTERVAL_SECONDS : 3600;

$alertas = $db->fetchAll(
    "SELECT * FROM alertas
     WHERE ativo = 1
       AND (ultimo_envio IS NULL OR ultimo_envio < DATE_SUB(NOW(), INTERVAL ? SECOND))
     ORDER BY ultimo_envio ASC
     LIMIT 200",
    [$intervalo]
) ?: [];

if (empty($alertas)) {
    echo '[' . date('Y-m-d H:i:s') . '] Nenhum alerta elegível para disparo.' . PHP_EOL;
    exit(0);
}

$ctrl     = new AlertaController();
$enviados = 0;
$falhas   = 0;

foreach ($alertas as $alerta) {
    try {
        $ok = $ctrl->dispararResumo($alerta);
        if ($ok) {
            $enviados++;
        }
    } catch (Throwable $e) {
        $falhas++;
        error_log('[cron/disparar-alertas] Alerta #' . $alerta['id'] . ': ' . $e->getMessage());
    }
}

$elapsed = round(microtime(true) - CRON_START, 2);
echo '[' . date('Y-m-d H:i:s') . "] Alertas processados: {$enviados} enviados, {$falhas} falhas. ({$elapsed}s)" . PHP_EOL;
exit(0);
