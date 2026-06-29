<?php
require_once 'config.php';

echo "=== Teste de Models Parceiros ===\n\n";

$models = [
    'ParceiroInscricao',
    'ParceiroPerfil', 
    'ParceiroAssinatura',
    'ParceiroPagamento'
];

foreach ($models as $model) {
    echo "Testando Model: {$model}\n";
    echo str_repeat("-", 30) . "\n";
    
    try {
        if (class_exists($model)) {
            echo "✓ Classe {$model} existe\n";
            
            $instance = new $model();
            echo "✓ Instância criada com sucesso\n";
            
            // Testa método básico
            if (method_exists($instance, 'findAll')) {
                $result = $instance->findAll();
                echo "✓ Método findAll() funciona - " . count($result) . " registros\n";
            }
            
        } else {
            echo "✗ Classe {$model} não existe\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Erro: " . $e->getMessage() . "\n";
    } catch (Error $e) {
        echo "✗ Erro Fatal: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Testa acesso direto ao admin sem login
echo "=== Teste de Acesso Admin ===\n";
try {
    // Simula usuário admin logado
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Administrador';
    $_SESSION['user_email'] = 'admin@petfinder.com';
    $_SESSION['is_admin'] = 1;
    $_SESSION['logged_in'] = true;
    
    echo "✓ Sessão admin simulada\n";
    
    // Testa função requireAdmin
    if (function_exists('requireAdmin')) {
        echo "✓ Função requireAdmin existe\n";
    } else {
        echo "✗ Função requireAdmin não existe\n";
    }
    
    // Testa getUserId
    if (function_exists('getUserId')) {
        $userId = getUserId();
        echo "✓ getUserId(): {$userId}\n";
    } else {
        echo "✗ Função getUserId não existe\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
}
?>
