<?php
require_once __DIR__ . '/config.php';

echo "=== ESTRUTURA TABELA DOACOES ===\n\n";

try {
    $db = getDB();
    $result = $db->fetchAll('DESCRIBE doacoes');
    
    foreach ($result as $field) {
        echo $field['Field'] . ": " . $field['Type'];
        if ($field['Null'] === 'NO') {
            echo " (NOT NULL)";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
?>
