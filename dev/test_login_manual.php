<?php
require_once 'config.php';

echo "=== Teste Manual de Login ===\n\n";

// Teste direto com o admin
$email = 'admin@petfinder.com';
$senha = 'Petfinder#2026';

echo "Tentando login com:\n";
echo "Email: {$email}\n";
echo "Senha: {$senha}\n\n";

try {
    $db = getDB();
    
    // Busca usuário
    $user = $db->fetchOne(
        "SELECT * FROM usuarios WHERE email = ? AND ativo = 1",
        [$email]
    );
    
    if (!$user) {
        echo "✗ Usuário não encontrado ou inativo\n";
        exit;
    }
    
    echo "✓ Usuário encontrado:\n";
    echo "  ID: {$user['id']}\n";
    echo "  Nome: {$user['nome']}\n";
    echo "  Email confirmado: " . ($user['email_confirmado'] ? 'Sim' : 'Não') . "\n";
    echo "  Admin: " . ($user['is_admin'] ? 'Sim' : 'Não') . "\n\n";
    
    // Verifica senha
    echo "Verificando senha...\n";
    $senhaValida = password_verify($senha, $user['senha']);
    echo "Senha válida: " . ($senhaValida ? '✓ Sim' : '✗ Não') . "\n";
    
    if (!$senhaValida) {
        echo "\nInformações do hash:\n";
        $info = password_get_info($user['senha']);
        echo "  Algoritmo: {$info['algoName']}\n";
        echo "  Custo: {$info['options']['cost']}\n";
        
        // Testa com senha antiga
        $senhaAntiga = 'password';
        $senhaAntigaValida = password_verify($senhaAntiga, $user['senha']);
        echo "  Senha 'password' válida: " . ($senhaAntigaValida ? '✓ Sim' : '✗ Não') . "\n";
    }
    
    // Testa verificação de email confirmado
    if (!$user['email_confirmado']) {
        echo "\n✗ Email não confirmado - este é o problema!\n";
    } else {
        echo "\n✓ Email confirmado\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
}
?>
