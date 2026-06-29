<?php
require_once 'config.php';

echo "=== Teste do Sistema de Login ===\n\n";

// Testa com diferentes credenciais
$testCases = [
    ['email' => 'pageupsistemas@gmail.com', 'senha' => 'Petfinder#2026'],
    ['email' => 'admin@petfinder.com', 'senha' => 'admin123'],
    ['email' => 'test@example.com', 'senha' => 'test123']
];

foreach ($testCases as $i => $test) {
    echo "Teste " . ($i + 1) . ": {$test['email']}\n";
    echo str_repeat("-", 40) . "\n";
    
    try {
        $usuarioController = new UsuarioController();
        $result = $usuarioController->login($test['email'], $test['senha']);
        
        if (!empty($result['success'])) {
            echo "✓ Login bem-sucedido!\n";
            echo "  Usuário: {$result['user']['nome']}\n";
            echo "  Email: {$result['user']['email']}\n";
            echo "  Admin: " . ($result['user']['is_admin'] ? 'Sim' : 'Não') . "\n";
            echo "  Email confirmado: " . ($result['user']['email_confirmado'] ? 'Sim' : 'Não') . "\n";
        } else {
            echo "✗ Falha no login\n";
            echo "  Erro: " . ($result['error'] ?? 'Erro desconhecido') . "\n";
            if (!empty($result['need_confirmation'])) {
                echo "  Status: Email não confirmado\n";
            }
        }
    } catch (Exception $e) {
        echo "✗ Exceção: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Verifica usuários existentes no banco
echo "=== Usuários no Banco ===\n";
try {
    $db = getDB();
    $users = $db->fetchAll("SELECT id, nome, email, email_confirmado, ativo, is_admin FROM usuarios");
    
    foreach ($users as $user) {
        echo "ID: {$user['id']} | {$user['nome']} | {$user['email']}\n";
        echo "  Confirmado: " . ($user['email_confirmado'] ? 'Sim' : 'Não') . " | ";
        echo "Ativo: " . ($user['ativo'] ? 'Sim' : 'Não') . " | ";
        echo "Admin: " . ($user['is_admin'] ? 'Sim' : 'Não') . "\n\n";
    }
} catch (Exception $e) {
    echo "Erro ao listar usuários: " . $e->getMessage() . "\n";
}
?>
