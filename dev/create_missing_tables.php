<?php
require_once 'config.php';

echo "=== Criando Tabelas Ausentes ===\n\n";

try {
    $db = getDB();
    
    // Lista de tabelas para criar
    $tables = [
        'parceiro_inscricoes' => "
            CREATE TABLE IF NOT EXISTS `parceiro_inscricoes` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `usuario_id` int(11) NOT NULL,
              `categoria` enum('petshop','clinica','hotel','adestrador','outro') NOT NULL,
              `nome_fantasia` varchar(120) NOT NULL,
              `cidade` varchar(100) NOT NULL,
              `estado` varchar(2) NOT NULL,
              `mensagem` text DEFAULT NULL,
              `status` enum('pendente','aprovada','recusada') NOT NULL DEFAULT 'pendente',
              `aprovada_em` datetime DEFAULT NULL,
              `recusada_em` datetime DEFAULT NULL,
              `analisada_por` int(11) DEFAULT NULL,
              `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_inscricao_usuario` (`usuario_id`),
              KEY `idx_status` (`status`),
              KEY `idx_cidade` (`cidade`),
              CONSTRAINT `fk_parceiro_inscricoes_usuario` FOREIGN KEY (`usuario_id`)
                REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_parceiro_inscricoes_admin` FOREIGN KEY (`analisada_por`)
                REFERENCES `usuarios` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'parceiro_perfis' => "
            CREATE TABLE IF NOT EXISTS `parceiro_perfis` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `usuario_id` int(11) NOT NULL,
              `slug` varchar(160) NOT NULL,
              `nome_fantasia` varchar(120) NOT NULL,
              `categoria` enum('petshop','clinica','hotel','adestrador','outro') NOT NULL,
              `descricao` text DEFAULT NULL,
              `telefone` varchar(20) DEFAULT NULL,
              `whatsapp` varchar(20) DEFAULT NULL,
              `email_contato` varchar(100) DEFAULT NULL,
              `site` varchar(255) DEFAULT NULL,
              `instagram` varchar(255) DEFAULT NULL,
              `endereco` varchar(255) DEFAULT NULL,
              `bairro` varchar(100) DEFAULT NULL,
              `cidade` varchar(100) NOT NULL,
              `estado` varchar(2) NOT NULL,
              `logo` varchar(255) DEFAULT NULL,
              `capa` varchar(255) DEFAULT NULL,
              `verificado` tinyint(1) DEFAULT 0,
              `publicado` tinyint(1) DEFAULT 0,
              `destaque` tinyint(1) DEFAULT 0,
              `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_perfil_usuario` (`usuario_id`),
              UNIQUE KEY `unique_slug` (`slug`),
              KEY `idx_publicado` (`publicado`),
              KEY `idx_categoria` (`categoria`),
              KEY `idx_cidade` (`cidade`),
              CONSTRAINT `fk_parceiro_perfis_usuario` FOREIGN KEY (`usuario_id`)
                REFERENCES `usuarios` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'parceiro_pagamentos' => "
            CREATE TABLE IF NOT EXISTS `parceiro_pagamentos` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `usuario_id` int(11) NOT NULL,
              `plano` enum('basico','destaque') NOT NULL,
              `periodicidade` enum('mensal','anual') NOT NULL DEFAULT 'mensal',
              `gateway_tipo` enum('pix','cartao_avista','cartao_recorrente') NOT NULL DEFAULT 'pix',
              `valor` decimal(10,2) NOT NULL,
              `metodo` enum('pix_manual','gateway') NOT NULL DEFAULT 'pix_manual',
              `status` enum('pendente','aprovado','recusado') NOT NULL DEFAULT 'pendente',
              `referencia` varchar(120) DEFAULT NULL,
              `efi_charge_id` bigint(20) DEFAULT NULL,
              `efi_subscription_id` int(11) DEFAULT NULL,
              `payment_url` varchar(255) DEFAULT NULL,
              `comprovante_texto` text DEFAULT NULL,
              `aprovado_em` datetime DEFAULT NULL,
              `recusado_em` datetime DEFAULT NULL,
              `aprovado_por` int(11) DEFAULT NULL,
              `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_usuario` (`usuario_id`),
              KEY `idx_status` (`status`),
              CONSTRAINT `fk_parceiro_pagamentos_usuario` FOREIGN KEY (`usuario_id`)
                REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_parceiro_pagamentos_admin` FOREIGN KEY (`aprovado_por`)
                REFERENCES `usuarios` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];
    
    foreach ($tables as $tableName => $sql) {
        echo "Criando tabela: {$tableName}...\n";
        
        try {
            $db->query($sql);
            echo "✓ Tabela {$tableName} criada com sucesso!\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "✓ Tabela {$tableName} já existe.\n";
            } else {
                echo "✗ Erro ao criar tabela {$tableName}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n=== Verificando Tabelas Criadas ===\n";
    $allTables = $db->fetchAll("SHOW TABLES LIKE 'parceiro%'");
    foreach ($allTables as $table) {
        $tableName = array_values($table)[0];
        echo "✓ {$tableName}\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erro geral: " . $e->getMessage() . "\n";
}
?>
