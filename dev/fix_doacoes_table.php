<?php
/**
 * Corrigir estrutura da tabela doacoes para compatibilidade
 * Execute este arquivo diretamente no servidor: https://cademeupet.pageup.net.br/fix_doacoes_table.php
 */

require_once 'config.php';

echo "=== Corrigindo Tabela doacoes ===\n\n";

try {
    $db = getDB();
    
    // Adicionar colunas ausentes
    $alteracoes = [
        "ALTER TABLE `doacoes` 
         MODIFY COLUMN `metodo_pagamento` enum('pix','cartao_avista','cartao_recorrente','manual') NOT NULL DEFAULT 'manual'",
        
        "ALTER TABLE `doacoes` 
         ADD COLUMN `efi_charge_id` varchar(100) DEFAULT NULL AFTER `transaction_id`",
        
        "ALTER TABLE `doacoes` 
         ADD COLUMN `efi_subscription_id` varchar(100) DEFAULT NULL AFTER `efi_charge_id`",
        
        "ALTER TABLE `doacoes` 
         ADD COLUMN `efi_plan_id` int(11) DEFAULT NULL AFTER `efi_subscription_id`",
        
        "ALTER TABLE `doacoes` 
         ADD COLUMN `data_aprovacao` datetime DEFAULT NULL AFTER `proxima_cobranca`",
        
        "ALTER TABLE `doacoes` 
         MODIFY COLUMN `status` enum('pendente','aprovado','recusado','cancelado') NOT NULL DEFAULT 'pendente'"
    ];
    
    foreach ($alteracoes as $sql) {
        try {
            echo "Executando: " . substr($sql, 0, 50) . "...\n";
            $db->query($sql);
            echo "✓ Sucesso\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "⚠ Coluna já existe\n";
            } else {
                echo "✗ Erro: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n=== Estrutura final ===\n";
    $structure = $db->fetchAll("DESCRIBE doacoes");
    foreach ($structure as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erro geral: " . $e->getMessage() . "\n";
}

echo "\n=== FIM ===\n";
?>

