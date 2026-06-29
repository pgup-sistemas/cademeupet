<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Doacao.php';

$doacaoModel = new Doacao();
$doacao = $doacaoModel->findById(34);
if (empty($doacao)) {
    echo "Doação não encontrada\n";
    exit(1);
}

echo json_encode($doacao, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
