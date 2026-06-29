<?php
require_once 'config.php';

echo "=== Teste do PagamentoController ===\n\n";

// Testa se a classe existe
if (class_exists('PagamentoController')) {
    echo "✓ PagamentoController existe\n";
    
    try {
        $controller = new PagamentoController();
        echo "✓ Instância criada com sucesso\n";
        
        // Testa método criarCobrancaPix
        if (method_exists($controller, 'criarCobrancaPix')) {
            echo "Testando criarCobrancaPix()...\n";
            
            $dadosTeste = [
                'id' => 1,
                'valor' => 10.00,
                'nome_doador' => 'Teste Manual',
                'email_doador' => 'test@example.com'
            ];
            
            $resultado = $controller->criarCobrancaPix($dadosTeste, 'Doação Teste');
            
            if (is_array($resultado)) {
                echo "✓ criarCobrancaPix() funciona\n";
                echo "  Resultado: " . print_r($resultado, true) . "\n";
            } else {
                echo "✗ criarCobrancaPix() retornou tipo inválido: " . gettype($resultado) . "\n";
            }
            
        } else {
            echo "✗ Método criarCobrancaPix() não existe\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Exception no controller: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    } catch (Error $e) {
        echo "✗ Fatal Error no controller: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
} else {
    echo "✗ PagamentoController não existe\n";
}

echo "\n";

// Verifica constantes do EFI
echo "=== Verificando Constantes EFI ===\n";

$efiConstants = [
    'MERCADO_PAGO_PUBLIC_KEY',
    'MERCADO_PAGO_ACCESS_TOKEN'
];

foreach ($efiConstants as $const) {
    if (defined($const)) {
        $value = constant($const);
        echo "✓ {$const}: " . (strlen($value) > 10 ? substr($value, 0, 20) . '...' : $value) . "\n";
    } else {
        echo "✗ {$const} não definida\n";
    }
}

echo "\n";

// Verifica se há biblioteca EFI
echo "=== Verificando Biblioteca EFI ===\n";

$efiFiles = [
    'includes/efi.php',
    'includes/gerencianet.php',
    'vendor/autoload.php'
];

foreach ($efiFiles as $file) {
    if (file_exists(BASE_PATH . '/' . $file)) {
        echo "✓ {$file} existe\n";
    } else {
        echo "✗ {$file} não existe\n";
    }
}

echo "\n=== Resumo ===\n";
echo "Se houver erros marcados com ✗, eles podem estar causando o HTTP 500.\n";
echo "Verifique especialmente o PagamentoController e as dependências EFI.\n";
?>
