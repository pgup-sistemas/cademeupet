<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Doacao.php';

$doacaoModel = new Doacao();
$doacaoId = 34;

$doacao = $doacaoModel->findById($doacaoId);
if (empty($doacao)) {
    echo "Doação $doacaoId não encontrada. Nada a fazer.\n";
    exit(0);
}

try {
    // Apagar registro (DELETE) usando API do Database
    $db = getDB();
    $db->delete('doacoes', 'id = ?', [$doacaoId]);
    echo "Doação $doacaoId removida do banco.\n";
} catch (Exception $e) {
    echo "Erro ao remover doação: " . $e->getMessage() . "\n";
    exit(1);
}
