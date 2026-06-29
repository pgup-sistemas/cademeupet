<?php
require_once __DIR__ . '/../config.php';
$db = getDB();
$rows = $db->fetchAll('SELECT id, transaction_id, efi_charge_id, status, valor FROM doacoes WHERE id IN (17,26,27,31,42)');
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
