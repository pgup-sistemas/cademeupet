<?php
require_once 'config.php';

echo "=== Depuração de Senhas ===\n\n";

try {
    $db = getDB();
    
    // Busca usuários e senhas
    $users = $db->fetchAll("SELECT id, nome, email, senha FROM usuarios");
    
    foreach ($users as $user) {
        echo "Usuário: {$user['nome']} ({$user['email']})\n";
        echo "Hash: {$user['senha']}\n";
        
        // Testa diferentes senhas
        $testPasswords = ['Petfinder#2026', 'admin123', '123456', 'password'];
        
        foreach ($testPasswords as $pwd) {
            $valid = password_verify($pwd, $user['senha']);
            echo "  Senha '{$pwd}': " . ($valid ? '✓ VÁLIDA' : '✗ Inválida') . "\n";
        }
        
        // Verifica info do hash
        $info = password_get_info($user['senha']);
        echo "  Algoritmo: {$info['algoName']} (custo: {$info['options']['cost']})\n";
        
        echo "\n" . str_repeat("-", 50) . "\n\n";
    }
    
    // Testa criação de novo hash
    echo "=== Teste de Criação de Hash ===\n";
    $newPassword = 'Petfinder#2026';
    $newHash = hashPassword($newPassword);
    echo "Nova senha: {$newPassword}\n";
    echo "Novo hash: {$newHash}\n";
    echo "Verificação: " . (password_verify($newPassword, $newHash) ? '✓ OK' : '✗ Falhou') . "\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>
