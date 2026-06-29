<?php
/**
 * Check existing donations
 */

require_once __DIR__ . '/config.php';

echo "=== DOACOES NO BANCO ===\n\n";

try {
    $db = getDB();
    $doacoes = $db->fetchAll('SELECT id, usuario_id, valor, status, metodo_pagamento FROM doacoes ORDER BY id DESC LIMIT 10');
    
    echo "Total de doações: " . count($doacoes) . "\n\n";
    
    foreach ($doacoes as $doacao) {
        echo "ID: {$doacao['id']}\n";
        echo "  Valor: R$ " . number_format($doacao['valor'], 2, ',', '.') . "\n";
        echo "  Status: " . $doacao['status'] . "\n";
        echo "  Método: " . $doacao['metodo_pagamento'] . "\n";
        echo "  Usuário ID: " . ($doacao['usuario_id'] ?? 'Não logado') . "\n";
        echo "\n";
    }
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
?>
