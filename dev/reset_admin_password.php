<?php
require_once 'config.php';

echo "=== Reset de Senha do Administrador ===\n\n";

try {
    $db = getDB();
    
    // Nova senha para o admin
    $newPassword = 'Petfinder#2026';
    $hashedPassword = hashPassword($newPassword);
    
    // Atualiza senha do admin (ID = 1)
    $result = $db->update(
        'usuarios',
        ['senha' => $hashedPassword],
        'id = ?',
        [1]
    );
    
    echo "✓ Senha do administrador atualizada com sucesso!\n";
    echo "Nova senha: {$newPassword}\n";
    echo "Hash gerado: {$hashedPassword}\n\n";
    
    // Confirma a atualização
    $admin = $db->fetchOne("SELECT nome, email FROM usuarios WHERE id = 1");
    echo "Dados do administrador:\n";
    echo "Nome: {$admin['nome']}\n";
    echo "Email: {$admin['email']}\n\n";
    
    // Testa o login
    echo "=== Teste de Login ===\n";
    $usuarioController = new UsuarioController();
    $result = $usuarioController->login($admin['email'], $newPassword);
    
    if (!empty($result['success'])) {
        echo "✓ Login testado com sucesso!\n";
        echo "Usuário: {$result['user']['nome']}\n";
        echo "Admin: " . ($result['user']['is_admin'] ? 'Sim' : 'Não') . "\n";
    } else {
        echo "✗ Falha no teste de login: " . ($result['error'] ?? 'Erro desconhecido') . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
}
?>
