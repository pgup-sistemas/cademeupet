<?php
/**
 * Debug específico para POST /doar
 * Simula o envio do formulário de doação
 */

require_once 'config.php';

echo "=== DEBUG POST /doar ===\n\n";

// 1. Verificar método e dados POST
echo "1. Simulando POST para /doar...\n";
$_POST = [
    'valor' => '50.00',
    'nome_doador' => 'Teste Usuario',
    'email_doador' => 'teste@email.com',
    'mensagem' => 'Doação de teste',
    'metodo_pagamento' => 'pix',
    'exibir_mural' => '1'
];

echo "POST data: " . print_r($_POST, true) . "\n";

// 2. Verificar controllers
echo "2. Verificando DoacaoController...\n";
require_once 'includes/efi.php'; // Incluir SDK EFI primeiro
if (file_exists('controllers/DoacaoController.php')) {
    require_once 'controllers/DoacaoController.php';
    if (class_exists('DoacaoController')) {
        echo "✓ DoacaoController existe\n";
        
        try {
            $controller = new DoacaoController();
            echo "✓ DoacaoController instanciado\n";
            
            // Verificar método criar
            if (method_exists($controller, 'criar')) {
                echo "✓ Método criar() existe\n";
                
                // Tentar executar (pode dar erro esperado)
                try {
                    ob_start();
                    $result = $controller->criar($_POST);
                    $output = ob_get_clean();
                    echo "✓ Método criar() executado: " . substr($output, 0, 100) . "...\n";
                    echo "Resultado: " . print_r($result, true) . "\n";
                } catch (Exception $e) {
                    echo "✗ Erro no método criar(): " . $e->getMessage() . "\n";
                    echo "Stack trace: " . $e->getTraceAsString() . "\n";
                }
            } else {
                echo "✗ Método criar() não existe\n";
            }
        } catch (Exception $e) {
            echo "✗ Erro ao instanciar DoacaoController: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✗ Classe DoacaoController não existe\n";
    }
} else {
    echo "✗ Arquivo controllers/DoacaoController.php não existe\n";
}

// 3. Verificar PagamentoController
echo "\n3. Verificando PagamentoController...\n";
if (file_exists('controllers/PagamentoController.php')) {
    require_once 'controllers/PagamentoController.php';
    require_once 'includes/efi.php'; // Incluir SDK EFI
    if (class_exists('PagamentoController')) {
        echo "✓ PagamentoController existe\n";
        
        try {
            $controller = new PagamentoController();
            echo "✓ PagamentoController instanciado\n";
            
            // Verificar método criarCobrancaPix
            if (method_exists($controller, 'criarCobrancaPix')) {
                echo "✓ Método criarCobrancaPix() existe\n";
            } else {
                echo "✗ Método criarCobrancaPix() não existe\n";
            }
        } catch (Exception $e) {
            echo "✗ Erro ao instanciar PagamentoController: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✗ Classe PagamentoController não existe\n";
    }
} else {
    echo "✗ Arquivo controllers/PagamentoController.php não existe\n";
}

// 4. Verificar constantes EFI
echo "\n4. Verificando constantes EFI...\n";
$efi_constants = [
    'EFI_PIX_KEY',
    'EFI_CLIENT_ID', 
    'EFI_CLIENT_SECRET',
    'EFI_CERTIFICATE_PATH',
    'EFI_PIX_DESCRIPTION',
    'EFI_PIX_NOTIFICATION_URL',
    'EFI_BASE_URL',
    'EFI_WEBHOOK_TOKEN'
];

foreach ($efi_constants as $constant) {
    if (defined($constant)) {
        echo "✓ $constant definida\n";
    } else {
        echo "✗ $constant NÃO definida\n";
    }
}

// 5. Verificar arquivo EFI
echo "\n5. Verificando includes/efi.php...\n";
if (file_exists('includes/efi.php')) {
    echo "✓ Arquivo includes/efi.php existe\n";
    require_once 'includes/efi.php';
    
    if (class_exists('Efi')) {
        echo "✓ Classe Efi existe\n";
    } else {
        echo "✗ Classe Efi não existe\n";
    }
} else {
    echo "✗ Arquivo includes/efi.php NÃO existe\n";
}

// 6. Verificar tabela metas_financeiras
echo "\n6. Verificando tabela metas_financeiras...\n";
try {
    $db = getDB();
    $result = $db->fetchAll("SHOW TABLES LIKE 'metas_financeiras'");
    if (count($result) > 0) {
        echo "✓ Tabela metas_financeiras existe\n";
    } else {
        echo "✗ Tabela metas_financeiras NÃO existe\n";
    }
} catch (Exception $e) {
    echo "✗ Erro ao verificar tabela metas_financeiras: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO DEBUG ===\n";
?>
