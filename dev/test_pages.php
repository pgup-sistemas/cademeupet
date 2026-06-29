<?php
require_once 'config.php';

echo "=== Teste de Páginas com Problemas ===\n\n";

// URLs que estavam dando erro 500
$testUrls = [
    '/admin/parceiros',
    '/admin/financeiro', 
    '/parceiro/painel',
    '/parceiros/inscricao'
];

foreach ($testUrls as $url) {
    echo "Testando: {$url}\n";
    echo str_repeat("-", 40) . "\n";
    
    try {
        // Simula a requisição para testar se os arquivos existem
        $viewFile = null;
        
        switch ($url) {
            case '/admin/parceiros':
                $viewFile = BASE_PATH . '/views/admin-parceiros.php';
                break;
            case '/admin/financeiro':
                $viewFile = BASE_PATH . '/views/admin-financeiro.php';
                break;
            case '/parceiro/painel':
                $viewFile = BASE_PATH . '/views/parceiro-painel.php';
                break;
            case '/parceiros/inscricao':
                $viewFile = BASE_PATH . '/views/parceiros-inscricao.php';
                break;
        }
        
        if ($viewFile && file_exists($viewFile)) {
            echo "✓ Arquivo existe: {$viewFile}\n";
            
            // Tenta incluir o arquivo para testar erros PHP
            ob_start();
            $error = false;
            try {
                include_once $viewFile;
            } catch (Exception $e) {
                echo "✗ Erro PHP: " . $e->getMessage() . "\n";
                $error = true;
            } catch (Error $e) {
                echo "✗ Erro Fatal: " . $e->getMessage() . "\n";
                $error = true;
            }
            ob_end_clean();
            
            if (!$error) {
                echo "✓ Arquivo carregado sem erros PHP\n";
            }
        } else {
            echo "✗ Arquivo não encontrado: " . ($viewFile ?? 'Não mapeado') . "\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Erro ao testar: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Verifica se as tabelas necessárias existem
echo "=== Verificação de Tabelas Necessárias ===\n";
try {
    $db = getDB();
    
    $requiredTables = [
        'parceiro_inscricoes',
        'parceiro_perfis', 
        'parceiro_pagamentos'
    ];
    
    foreach ($requiredTables as $table) {
        $result = $db->fetchOne("SHOW TABLES LIKE '{$table}'");
        if ($result) {
            echo "✓ Tabela {$table} existe\n";
        } else {
            echo "✗ Tabela {$table} não existe\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Erro ao verificar tabelas: " . $e->getMessage() . "\n";
}

echo "\n=== Teste de Constantes ===\n";
echo "TIPO_DOACAO: " . (defined('TIPO_DOACAO') ? TIPO_DOACAO : 'NÃO DEFINIDA') . "\n";
echo "TIPO_PERDIDO: " . (defined('TIPO_PERDIDO') ? TIPO_PERDIDO : 'NÃO DEFINIDA') . "\n";
echo "TIPO_ENCONTRADO: " . (defined('TIPO_ENCONTRADO') ? TIPO_ENCONTRADO : 'NÃO DEFINIDA') . "\n";
?>
