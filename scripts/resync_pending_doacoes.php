<?php
require_once __DIR__ . '/../config.php';

// Script CLI para reprocessar doações pendentes com TXID
// Uso: php scripts/resync_pending_doacoes.php [--limit=50] [--dry-run]

$opts = [];
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $opts['limit'] = (int)substr($arg, 8);
    }
    if ($arg === '--dry-run') {
        $opts['dry_run'] = true;
    }
}

$limit = $opts['limit'] ?? 100;
$dryRun = !empty($opts['dry_run']);

$logPath = BASE_PATH . '/logs/efi_resync.log';
@mkdir(dirname($logPath), 0755, true);

// Acquire a file lock to avoid overlapping runs
$lockFile = BASE_PATH . '/logs/resync.lock';
$lockFp = fopen($lockFile, 'c');
if ($lockFp === false) {
    echo "Unable to open lock file: $lockFile\n";
    exit(1);
}
if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
    echo "Another resync process is already running. Exiting.\n";
    exit(0);
}
// Write PID and timestamp into lock file for diagnostics
ftruncate($lockFp, 0);
fwrite($lockFp, json_encode(['pid' => getmypid(), 'time' => date('c')]) . PHP_EOL);

register_shutdown_function(function() use ($lockFp, $lockFile) {
    if (is_resource($lockFp)) {
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
    }
    // keep the lock file present for troubleshooting
});

function logEntry($entry) {
    global $logPath;
    file_put_contents($logPath, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

try {
    $db = getDB();

    $sql = 'SELECT id, transaction_id, valor, email_doador FROM doacoes WHERE status = "pendente" AND transaction_id IS NOT NULL AND transaction_id <> "" ORDER BY data_doacao ASC LIMIT ?';
    $rows = $db->fetchAll($sql, [$limit]);

    echo "Found " . count($rows) . " pending donations with TXID\n";

    $pag = new PagamentoController();

    $countProcessed = 0;

    foreach ($rows as $r) {
        $id = (int)$r['id'];
        $txid = (string)$r['transaction_id'];

        $msg = "Resync doacao_id={$id} txid={$txid}";
        echo $msg . "\n";

        $attempt = ['time' => date('c'), 'action' => 'resync_attempt', 'doacao_id' => $id, 'txid' => $txid];
        logEntry($attempt);

        if ($dryRun) continue;

        try {
            $atualizada = $pag->sincronizarStatusDoacaoPix($id, $txid);
            $status = (string)($atualizada['status'] ?? 'pendente');

            // Obter detalhe bruto (se possível)
            $detail = null;
            try {
                $detail = $pag->detalharCobrancaPix($txid);
            } catch (Throwable $t) {
                $detail = ['error' => $t->getMessage()];
            }

            $entry = ['time' => date('c'), 'action' => 'resync_result', 'doacao_id' => $id, 'txid' => $txid, 'status' => $status, 'detail' => $detail];
            logEntry($entry);

            echo " -> status={$status}\n";

            $countProcessed++;
        } catch (Throwable $e) {
            $err = ['time' => date('c'), 'action' => 'resync_error', 'doacao_id' => $id, 'txid' => $txid, 'error' => $e->getMessage()];
            logEntry($err);
            echo " -> error: " . $e->getMessage() . "\n";
        }

        // Pequena espera para evitar throttling
        sleep(1);
    }

    echo "Processed: $countProcessed\n";
    exit(0);

} catch (Throwable $e) {
    error_log('[resync_pending_doacoes] Fatal: ' . $e->getMessage());
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(2);
}
