<?php
require_once 'config.php';

echo "=== Debug da Página /doar ===\n\n";

// Simula sessão básica
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Administrador';
$_SESSION['user_email'] = 'admin@petfinder.com';
$_SESSION['is_admin'] = 1;
$_SESSION['logged_in'] = true;

// Testa página doar
echo "1. Testando arquivo views/doar.php\n";
echo str_repeat("-", 40) . "\n";

$doarFile = BASE_PATH . '/views/doar.php';

if (file_exists($doarFile)) {
    echo "✓ Arquivo existe: {$doarFile}\n";
    
    try {
        ob_start();
        include_once $doarFile;
        $output = ob_get_clean();
        
        if (empty($output)) {
            echo "✗ Página não gerou output (possível redirecionamento)\n";
        } else {
            echo "✓ Página gerou " . strlen($output) . " caracteres\n";
            
            // Verifica se há erros no output
            if (strpos($output, 'Fatal error') !== false || strpos($output, 'Parse error') !== false) {
                echo "✗ Erro PHP detectado no output\n";
            }
        }
        
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    } catch (Error $e) {
        echo "✗ Fatal Error: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
} else {
    echo "✗ Arquivo não encontrado: {$doarFile}\n";
}

echo "\n";

// Verifica models relacionados a doações
echo "2. Verificando Models de Doações\n";
echo str_repeat("-", 40) . "\n";

$donationModels = [
    'Doacao' => 'doacoes',
    'MetaFinanceira' => 'metas_financeiras'
];

foreach ($donationModels as $model => $table) {
    try {
        if (class_exists($model)) {
            echo "✓ Model {$model} existe\n";
            
            $instance = new $model();
            
            // Testa método findAll
            if (method_exists($instance, 'findAll')) {
                $result = $instance->findAll();
                echo "  ✓ findAll(): " . count($result) . " registros\n";
            }
            
        } else {
            echo "✗ Model {$model} não existe\n";
        }
        
        // Verifica tabela
        $db = getDB();
        $tableExists = $db->fetchOne("SHOW TABLES LIKE '{$table}'");
        if ($tableExists) {
            echo "  ✓ Tabela {$table} existe\n";
        } else {
            echo "  ✗ Tabela {$table} não existe\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Erro em {$model}: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Verifica funções relacionadas a doações
echo "3. Verificando Funções de Doações\n";
echo str_repeat("-", 40) . "\n";

$functions = [
    'generateCSRFToken',
    'validateCSRFToken',
    'sanitize',
    'formatMoney'
];

foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "✓ {$func} existe\n";
    } else {
        echo "✗ {$func} não existe\n";
    }
}

echo "\n";

// Verifica constantes de doação
echo "4. Verificando Constantes de Doação\n";
echo str_repeat("-", 40) . "\n";

$constants = [
    'MIN_DONATION_AMOUNT',
    'MERCADO_PAGO_PUBLIC_KEY',
    'MERCADO_PAGO_ACCESS_TOKEN',
    'DONATION_MODAL_TITLE',
    'DONATION_MODAL_TEXT'
];

foreach ($constants as $const) {
    if (defined($const)) {
        $value = constant($const);
        echo "✓ {$const}: " . (is_string($value) ? substr($value, 0, 30) . '...' : $value) . "\n";
    } else {
        echo "✗ {$const} não definida\n";
    }
}

echo "\n";

// Testa rota no .htaccess
echo "5. Verificando Rota no .htaccess\n";
echo str_repeat("-", 40) . "\n";

$htaccessFile = BASE_PATH . '/.htaccess';
if (file_exists($htaccessFile)) {
    $content = file_get_contents($htaccessFile);
    if (strpos($content, 'doar') !== false) {
        echo "✓ Rota /doar encontrada no .htaccess\n";
    } else {
        echo "✗ Rota /doar não encontrada no .htaccess\n";
    }
} else {
    echo "✗ Arquivo .htaccess não encontrado\n";
}

echo "\n=== Resumo ===\n";
echo "Verifique os erros marcados com ✗ acima.\n";
echo "Se tudo estiver ✓, o problema pode ser no servidor de produção.\n";
?>
