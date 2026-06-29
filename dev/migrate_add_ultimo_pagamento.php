<?php
/**
 * MIGRAÇÃO - Adicionar campo ultimo_pagamento_em à tabela doacoes
 * 
 * Este script adiciona o campo necessário para rastrear o último pagamento
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

// Conectar ao banco
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('❌ Erro de conexão: ' . $e->getMessage());
}

echo "========================================\n";
echo "MIGRAÇÃO - CAMPO ultimo_pagamento_em\n";
echo "========================================\n\n";

try {
    // Verificar se o campo já existe
    $query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'doacoes' AND COLUMN_NAME = 'ultimo_pagamento_em' AND TABLE_SCHEMA = DATABASE()";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Campo 'ultimo_pagamento_em' já existe\n";
    } else {
        // Adicionar o campo
        $sql = "ALTER TABLE doacoes ADD COLUMN ultimo_pagamento_em DATETIME NULL AFTER status";
        $pdo->exec($sql);
        echo "✅ Campo 'ultimo_pagamento_em' adicionado com sucesso!\n";
    }
    
    // Verificar índices
    echo "\n📋 Verificando índices...\n";
    
    $indexes = [
        'idx_efi_subscription_id' => "CREATE INDEX idx_efi_subscription_id ON doacoes(efi_subscription_id)",
        'idx_usuario_status' => "CREATE INDEX idx_usuario_status ON doacoes(usuario_id, status)",
        'idx_proxima_cobranca' => "CREATE INDEX idx_proxima_cobranca ON doacoes(proxima_cobranca)",
    ];
    
    foreach ($indexes as $name => $sql) {
        try {
            $checkQuery = "SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
                          WHERE INDEX_NAME = '$name' AND TABLE_NAME = 'doacoes' AND TABLE_SCHEMA = DATABASE()";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                $pdo->exec($sql);
                echo "✅ Índice '$name' criado\n";
            } else {
                echo "✅ Índice '$name' já existe\n";
            }
        } catch (Exception $e) {
            echo "⚠️  Índice '$name': " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n========================================\n";
    echo "✅ MIGRAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRO NA MIGRAÇÃO:\n";
    echo "   " . $e->getMessage() . "\n";
    exit(1);
}
