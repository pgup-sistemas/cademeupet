<?php
/**
 * Verificar se tabela doacoes existe
 */

require_once 'config.php';

echo "=== Verificando Tabela doacoes ===\n\n";

try {
    $db = getDB();
    
    // Verificar se tabela existe
    $result = $db->fetchAll("SHOW TABLES LIKE 'doacoes'");
    
    if (count($result) > 0) {
        echo "✓ Tabela 'doacoes' existe\n";
        
        // Verificar estrutura
        $structure = $db->fetchAll("DESCRIBE doacoes");
        echo "\nEstrutura da tabela:\n";
        foreach ($structure as $column) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
    } else {
        echo "✗ Tabela 'doacoes' NÃO existe\n";
        
        // Criar tabela
        echo "\nCriando tabela 'doacoes'...\n";
        $createSQL = "
            CREATE TABLE `doacoes` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `usuario_id` int(11) DEFAULT NULL,
              `valor` decimal(10,2) NOT NULL,
              `tipo` enum('unica','mensal') NOT NULL DEFAULT 'unica',
              `metodo_pagamento` enum('pix','cartao_avista','cartao_recorrente','manual') NOT NULL DEFAULT 'manual',
              `gateway` varchar(50) DEFAULT 'manual',
              `nome_doador` varchar(120) DEFAULT NULL,
              `email_doador` varchar(120) DEFAULT NULL,
              `cpf_doador` varchar(11) DEFAULT NULL,
              `mensagem` text DEFAULT NULL,
              `exibir_mural` tinyint(1) DEFAULT 0,
              `status` enum('pendente','aprovado','recusado','cancelado') NOT NULL DEFAULT 'pendente',
              `transaction_id` varchar(100) DEFAULT NULL,
              `payment_url` text DEFAULT NULL,
              `efi_charge_id` varchar(100) DEFAULT NULL,
              `efi_subscription_id` varchar(100) DEFAULT NULL,
              `efi_plan_id` int(11) DEFAULT NULL,
              `proxima_cobranca` date DEFAULT NULL,
              `data_doacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `data_aprovacao` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_usuario` (`usuario_id`),
              KEY `idx_status` (`status`),
              KEY `idx_data_doacao` (`data_doacao`),
              CONSTRAINT `fk_doacoes_usuario` FOREIGN KEY (`usuario_id`)
                REFERENCES `usuarios` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->execute($createSQL);
        echo "✓ Tabela 'doacoes' criada com sucesso!\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
}

echo "\n=== FIM ===\n";
?>
