<?php
// Habilitar exibição temporária de erros para diagnóstico via web
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

$logFile = __DIR__ . '/logs/migrate_add_pix_split.log';
@mkdir(dirname($logFile), 0755, true);

set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logFile) {
    $msg = sprintf('[Migration ERROR] %s in %s on line %d', $errstr, $errfile, $errline);
    @file_put_contents($logFile, date('c') . ' ' . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
    // Convert to ErrorException to allow catch by exception handler
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

set_exception_handler(function($e) use ($logFile) {
    $msg = '[Migration EXCEPTION] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    @file_put_contents($logFile, date('c') . ' ' . $msg . PHP_EOL . $e->getTraceAsString() . PHP_EOL, FILE_APPEND | LOCK_EX);
    http_response_code(500);
    echo '<pre>' . htmlspecialchars($msg) . '</pre>';
    exit(1);
});

if (!file_exists(__DIR__ . '/config.php')) {
    $msg = 'Config file not found: ' . __DIR__ . '/config.php';
    @file_put_contents($logFile, date('c') . ' ' . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
    http_response_code(500);
    echo $msg;
    exit(1);
}

require_once __DIR__ . '/config.php';

@file_put_contents($logFile, date('c') . " Starting migration\n", FILE_APPEND | LOCK_EX);

echo "Migrating: add efi_split column and efi_transfers table\n";
$db = getDB();

try {
    // Adicionar coluna efi_split se não existir
    $col = $db->fetchOne("SHOW COLUMNS FROM doacoes LIKE 'efi_split'");
    if (empty($col)) {
        echo " - Adding column doacoes.efi_split (JSON)...\n";
        @file_put_contents($logFile, date('c') . " - Adding column doacoes.efi_split\n", FILE_APPEND | LOCK_EX);
        $db->query("ALTER TABLE doacoes ADD COLUMN efi_split JSON NULL AFTER pix_qrcode");
    } else {
        echo " - Column doacoes.efi_split already exists\n";
        @file_put_contents($logFile, date('c') . " - Column already exists\n", FILE_APPEND | LOCK_EX);
    }

    // Criar tabela efi_transfers
    $exists = $db->fetchOne("SHOW TABLES LIKE 'efi_transfers'");
    if (empty($exists)) {
        echo " - Creating table efi_transfers...\n";
        @file_put_contents($logFile, date('c') . " - Creating table efi_transfers\n", FILE_APPEND | LOCK_EX);
        $db->query(
            "CREATE TABLE efi_transfers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                txid VARCHAR(128) NOT NULL,
                payload JSON NOT NULL,
                status VARCHAR(64) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX(txid)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    } else {
        echo " - Table efi_transfers already exists\n";
        @file_put_contents($logFile, date('c') . " - Table already exists\n", FILE_APPEND | LOCK_EX);
    }

    echo "Migration finished.\n";
    @file_put_contents($logFile, date('c') . " Migration finished\n", FILE_APPEND | LOCK_EX);
} catch (Exception $e) {
    $msg = 'Migration failed: ' . $e->getMessage();
    @file_put_contents($logFile, date('c') . ' ' . $msg . PHP_EOL . $e->getTraceAsString() . PHP_EOL, FILE_APPEND | LOCK_EX);
    echo $msg;
    exit(1);
}
