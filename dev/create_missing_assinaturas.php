<?php
require_once 'config.php';

echo "=== Criando Tabela parceiro_assinaturas Ausente ===\n\n";

try {
    $db = getDB();
    
    // SQL para criar a tabela parceiro_assinaturas
    $sql = "
        CREATE TABLE IF NOT EXISTS `parceiro_assinaturas` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `usuario_id` int(11) NOT NULL,
          `plano` enum('basico','destaque') NOT NULL DEFAULT 'basico',
          `periodicidade` enum('mensal','anual') NOT NULL DEFAULT 'mensal',
          `efi_plan_id` int(11) DEFAULT NULL,
          `efi_subscription_id` int(11) DEFAULT NULL,
          `valor_mensal` decimal(10,2) NOT NULL,
          `status` enum('pendente_pagamento','ativa','suspensa','cancelada') NOT NULL DEFAULT 'pendente_pagamento',
          `metodo_pagamento` enum('pix_manual','gateway') NOT NULL DEFAULT 'pix_manual',
          `pago_ate` date DEFAULT NULL,
          `proxima_cobranca` date DEFAULT NULL,
          `ultimo_pagamento_em` datetime DEFAULT NULL,
          `observacoes_admin` text DEFAULT NULL,
          `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_assinatura_usuario` (`usuario_id`),
          KEY `idx_status` (`status`),
          CONSTRAINT `fk_parceiro_assinaturas_usuario` FOREIGN KEY (`usuario_id`)
            REFERENCES `usuarios` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    echo "Criando tabela: parceiro_assinaturas...\n";
    
    try {
        $db->query($sql);
        echo "✓ Tabela parceiro_assinaturas criada com sucesso!\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "✓ Tabela parceiro_assinaturas já existe.\n";
        } else {
            echo "✗ Erro ao criar tabela parceiro_assinaturas: " . $e->getMessage() . "\n";
        }
    }
    
    // Verifica também a tabela metas_financeiras
    echo "\nVerificando tabela metas_financeiras...\n";
    $result = $db->fetchOne("SHOW TABLES LIKE 'metas_financeiras'");
    
    if (!$result) {
        echo "Criando tabela metas_financeiras...\n";
        $sqlMetas = "
            CREATE TABLE IF NOT EXISTS `metas_financeiras` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `mes_referencia` date NOT NULL,
              `valor_meta` decimal(10,2) NOT NULL,
              `valor_arrecadado` decimal(10,2) DEFAULT 0.00,
              `custos_servidor` decimal(10,2) DEFAULT 0.00,
              `custos_manutencao` decimal(10,2) DEFAULT 0.00,
              `custos_outros` decimal(10,2) DEFAULT 0.00,
              `descricao` text DEFAULT NULL,
              `ativo` tinyint(1) DEFAULT 1,
              `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_mes` (`mes_referencia`),
              KEY `idx_ativo` (`ativo`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        try {
            $db->query($sqlMetas);
            echo "✓ Tabela metas_financeiras criada com sucesso!\n";
            
            // Insere meta inicial
            $db->query("
                INSERT INTO metas_financeiras 
                (mes_referencia, valor_meta, custos_servidor, custos_manutencao, descricao) 
                VALUES 
                (CURDATE(), 500.00, 150.00, 100.00, 
                 'Meta mensal para manutenção do servidor e melhorias no sistema')
            ");
            echo "✓ Meta financeira inicial inserida!\n";
            
        } catch (Exception $e) {
            echo "✗ Erro ao criar tabela metas_financeiras: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✓ Tabela metas_financeiras já existe.\n";
    }
    
    echo "\n=== Verificação Final ===\n";
    $allTables = $db->fetchAll("SHOW TABLES LIKE 'parceiro%'");
    $allTables[] = $db->fetchOne("SHOW TABLES LIKE 'metas_financeiras'");
    
    foreach ($allTables as $table) {
        $tableName = array_values($table)[0];
        echo "✓ {$tableName}\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erro geral: " . $e->getMessage() . "\n";
}
?>
