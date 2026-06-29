<?php
require_once 'config.php';

echo "=== Debug Específico das Páginas com Erro ===\n\n";

// Simula sessão admin
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Administrador';
$_SESSION['user_email'] = 'admin@petfinder.com';
$_SESSION['is_admin'] = 1;
$_SESSION['logged_in'] = true;

// Testa página admin/financeiro
echo "1. Testando /admin/financeiro\n";
echo str_repeat("-", 40) . "\n";

try {
    ob_start();
    include_once BASE_PATH . '/views/admin-financeiro.php';
    $output = ob_get_clean();
    
    if (empty($output)) {
        echo "✓ Página carregou sem output (pode ter redirecionado)\n";
    } else {
        echo "✓ Página gerou output: " . strlen($output) . " caracteres\n";
    }
    
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
} catch (Error $e) {
    echo "✗ Fatal Error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n";

// Testa página parceiro/painel
echo "2. Testando /parceiro/painel\n";
echo str_repeat("-", 40) . "\n";

try {
    ob_start();
    include_once BASE_PATH . '/views/parceiro-painel.php';
    $output = ob_get_clean();
    
    if (empty($output)) {
        echo "✓ Página carregou sem output (pode ter redirecionado)\n";
    } else {
        echo "✓ Página gerou output: " . strlen($output) . " caracteres\n";
    }
    
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
} catch (Error $e) {
    echo "✗ Fatal Error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n";

// Verifica models específicos que podem estar faltando
echo "3. Verificando Models Específicos\n";
echo str_repeat("-", 40) . "\n";

$specificModels = [
    'ParceiroAssinatura',
    'ParceiroPagamento',
    'MetaFinanceira'
];

foreach ($specificModels as $model) {
    try {
        if (class_exists($model)) {
            echo "✓ {$model} existe\n";
            $instance = new $model();
            
            // Testa métodos específicos
            if (method_exists($instance, 'countAll')) {
                $count = $instance->countAll();
                echo "  ✓ countAll(): {$count}\n";
            }
            
            if (method_exists($instance, 'findByUserId')) {
                $result = $instance->findByUserId(1);
                echo "  ✓ findByUserId(1): " . (empty($result) ? 'vazio' : 'encontrado') . "\n";
            }
            
        } else {
            echo "✗ {$model} não existe\n";
        }
    } catch (Exception $e) {
        echo "✗ Erro em {$model}: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Verifica funções auxiliares
echo "4. Verificando Funções Auxiliares\n";
echo str_repeat("-", 40) . "\n";

$functions = [
    'requireAdmin',
    'getUserId',
    'setFlashMessage',
    'validateCSRFToken'
];

foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "✓ {$func} existe\n";
    } else {
        echo "✗ {$func} não existe\n";
    }
}
?>
