<?php
/**
 * Migrate: Add pix_qrcode column to doacoes table
 */

require_once __DIR__ . '/config.php';

echo "=== ADICIONANDO COLUNA pix_qrcode ===\n\n";

try {
    $db = getDB();
    
    // Verificar se coluna já existe
    $result = $db->fetchAll("SHOW COLUMNS FROM doacoes LIKE 'pix_qrcode'");
    
    if (!empty($result)) {
        echo "✓ Coluna 'pix_qrcode' já existe.\n";
    } else {
        echo "Adicionando coluna 'pix_qrcode'...\n";
        $db->query("ALTER TABLE doacoes ADD COLUMN pix_qrcode LONGTEXT COMMENT 'QR Code PIX em JSON (imagemQrcode, qrcode)'");
        echo "✓ Coluna 'pix_qrcode' adicionada com sucesso!\n";
    }
    
    // Verificar resultado
    $result = $db->fetchAll('DESCRIBE doacoes');
    
    $hasColumn = false;
    foreach ($result as $field) {
        if ($field['Field'] === 'pix_qrcode') {
            $hasColumn = true;
            echo "\nVerificação:\n";
            echo "  Campo: " . $field['Field'] . "\n";
            echo "  Tipo: " . $field['Type'] . "\n";
        }
    }
    
    if (!$hasColumn) {
        echo "\n✗ Erro: Coluna não foi criada corretamente.\n";
    }
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
}
?>
