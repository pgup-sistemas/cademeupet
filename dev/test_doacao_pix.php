<?php
/**
 * Test doacao-pix page
 */

require_once __DIR__ . '/config.php';

// Simular um GET com ID
$_GET['id'] = 31;

echo "=== TESTE DOACAO-PIX ===\n\n";

try {
    // Incluir a view diretamente para testar
    require_once __DIR__ . '/views/doacao-pix.php';
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}
?>
