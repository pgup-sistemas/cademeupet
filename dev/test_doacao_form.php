<?php
/**
 * Test de Doação - Simular envio de formulário
 */

require_once __DIR__ . '/config.php';

// Simular um POST
$_POST = [
    'csrf_token' => 'test_token',
    'valor' => '10',
    'metodo_pagamento' => 'pix',
    'nome_doador' => 'Teste Doador',
    'email_doador' => 'teste@example.com',
    'exibir_mural' => '1'
];

// Override CSRF validation para teste
if (!function_exists('validateCSRFToken')) {
    function validateCSRFToken($token) {
        return true;
    }
}

echo "Testando fluxo de doação...\n";
echo "================================\n";
echo "POST Data:\n";
print_r($_POST);
echo "\n";

try {
    $controller = new DoacaoController();
    $result = $controller->criar($_POST);
    
    echo "Resultado:\n";
    print_r($result);
    
    if (!empty($result['success'])) {
        echo "\n✓ SUCESSO! Doação foi criada.\n";
        if (!empty($result['redirect'])) {
            echo "Redirecionando para: " . $result['redirect'] . "\n";
        }
    } else {
        echo "\n✗ ERRO! Problemas encontrados:\n";
        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $erro) {
                echo "  - " . $erro . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "\n✗ EXCEÇÃO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
