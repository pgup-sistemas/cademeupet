<?php
// CLI script to inspect last entries of logs/doacao_debug.log
// Usage: php debug_doacao_errors.php [N]

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$limit = isset($argv[1]) ? (int)$argv[1] : 20;
$logPath = BASE_PATH . '/logs/doacao_debug.log';

if (!file_exists($logPath)) {
    echo "Log file not found: $logPath\n";
    exit(1);
}

$lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$total = count($lines);
$start = max(0, $total - $limit);

for ($i = $start; $i < $total; $i++) {
    $line = $lines[$i];
    $entry = json_decode($line, true);
    if (!$entry) continue;

    echo "------------------------------------------------------------\n";
    echo "ID: " . ($entry['id'] ?? '') . "\n";
    echo "Time: " . ($entry['time'] ?? '') . "\n";
    echo "User ID: " . ($entry['user_id'] ?? '') . "\n";
    echo "Message: " . ($entry['exception']['message'] ?? '') . "\n";
    echo "Remote: " . (($entry['server']['REMOTE_ADDR'] ?? '') . ' ' . ($entry['server']['REQUEST_URI'] ?? '')) . "\n";

    if (!empty($entry['payload'])) {
        echo "Payload keys: " . implode(', ', array_keys($entry['payload'])) . "\n";
    }

    $trace = $entry['exception']['trace'] ?? '';
    $firstTraceLine = explode("\n", $trace)[0] ?? '';
    echo "Trace (first line): " . $firstTraceLine . "\n";
}

echo "\nDisplayed " . min($limit, $total) . " of $total entries.\n";

return 0;
