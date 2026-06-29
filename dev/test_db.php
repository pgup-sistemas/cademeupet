<?php
require_once 'config.php';

try {
    echo "Testando conexão com o banco de dados...\n";
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conexão bem-sucedida!\n";
    
    // Testa consulta simples
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "✓ Total de usuários: " . $result['total'] . "\n";
    
    // Testa estrutura da tabela
    $stmt = $pdo->prepare("SHOW COLUMNS FROM usuarios");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    echo "\nEstrutura da tabela usuarios:\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
    
    // Verifica usuários ativos e confirmados
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1");
    $stmt->execute();
    $ativos = $stmt->fetch();
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE email_confirmado = 1");
    $stmt->execute();
    $confirmados = $stmt->fetch();
    echo "\n✓ Usuários ativos: " . $ativos['total'] . "\n";
    echo "✓ Usuários com email confirmado: " . $confirmados['total'] . "\n";
    
} catch (PDOException $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
}
?>