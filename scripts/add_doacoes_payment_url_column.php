<?php
// Adiciona coluna payment_url em `doacoes` se não existir
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

try {
    $db = getDB();
    $row = $db->fetchOne("SHOW COLUMNS FROM doacoes LIKE 'payment_url'");
    if (!empty($row)) {
        echo "Coluna 'payment_url' já existe.\n";
        exit(0);
    }

    $sql = "ALTER TABLE doacoes ADD COLUMN payment_url TEXT DEFAULT NULL";
    $db->query($sql);
    echo "Coluna 'payment_url' adicionada com sucesso.\n";
    exit(0);
} catch (Exception $e) {
    echo "Erro ao adicionar coluna: " . $e->getMessage() . "\n";
    exit(1);
}
