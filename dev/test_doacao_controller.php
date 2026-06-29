<?php
require_once 'config.php';

echo "=== Teste do DoacaoController ===\n\n";

// Testa se a classe existe
if (class_exists('DoacaoController')) {
    echo "✓ DoacaoController existe\n";
    
    try {
        $controller = new DoacaoController();
        echo "✓ Instância criada com sucesso\n";
        
        // Testa método resumoDashboard
        if (method_exists($controller, 'resumoDashboard')) {
            echo "Testando resumoDashboard()...\n";
            $resumo = $controller->resumoDashboard();
            echo "✓ resumoDashboard() funciona\n";
            echo "  Resumo: " . print_r($resumo, true) . "\n";
        } else {
            echo "✗ Método resumoDashboard() não existe\n";
        }
        
        // Testa método metaAtual
        if (method_exists($controller, 'metaAtual')) {
            echo "Testando metaAtual()...\n";
            $meta = $controller->metaAtual();
            echo "✓ metaAtual() funciona\n";
            echo "  Meta: " . print_r($meta, true) . "\n";
        } else {
            echo "✗ Método metaAtual() não existe\n";
        }
        
        // Testa método mural
        if (method_exists($controller, 'mural')) {
            echo "Testando mural()...\n";
            $mural = $controller->mural(6);
            echo "✓ mural() funciona\n";
            echo "  Mural: " . print_r($mural, true) . "\n";
        } else {
            echo "✗ Método mural() não existe\n";
        }
        
        // Testa método criar
        if (method_exists($controller, 'criar')) {
            echo "Testando criar()...\n";
            
            $dadosTeste = [
                'valor' => '10.00',
                'tipo' => 'unica',
                'nome_doador' => 'Teste Manual',
                'email_doador' => 'test@example.com',
                'exibir_mural' => '1',
                'mensagem' => 'Doação de teste'
            ];
            
            $resultado = $controller->criar($dadosTeste);
            echo "✓ criar() funciona\n";
            echo "  Resultado: " . print_r($resultado, true) . "\n";
        } else {
            echo "✗ Método criar() não existe\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Exception no controller: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "  Trace: " . $e->getTraceAsString() . "\n";
    } catch (Error $e) {
        echo "✗ Fatal Error no controller: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "  Trace: " . $e->getTraceAsString() . "\n";
    }
    
} else {
    echo "✗ DoacaoController não existe\n";
}

echo "\n";

// Verifica dependências
echo "=== Verificando Dependências ===\n";

$dependencies = [
    'Doacao' => 'models/Doacao.php',
    'MetaFinanceira' => 'models/MetaFinanceira.php'
];

foreach ($dependencies as $class => $file) {
    if (file_exists(BASE_PATH . '/' . $file)) {
        echo "✓ {$file} existe\n";
        
        if (class_exists($class)) {
            echo "✓ Classe {$class} carregada\n";
        } else {
            echo "✗ Classe {$class} não carregada\n";
        }
    } else {
        echo "✗ {$file} não existe\n";
    }
}

echo "\n=== Teste Completo ===\n";
?>
