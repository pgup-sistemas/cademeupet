<?php
require_once 'config.php';

echo "=== Teste de Login com Debug Completo ===\n\n";

// Inicia sessão manualmente
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$email = 'admin@petfinder.com';
$senha = 'Petfinder#2026';

echo "1. Testando isLoggedIn() antes do login:\n";
echo "   isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "\n";
echo "   Sessão atual: " . print_r($_SESSION, true) . "\n\n";

echo "2. Processando login...\n";

try {
    $usuarioController = new UsuarioController();
    
    echo "   Controller criado\n";
    
    $result = $usuarioController->login($email, $senha);
    
    echo "   Login processado\n";
    echo "   Resultado: " . print_r($result, true) . "\n\n";
    
    if (!empty($result['success'])) {
        echo "3. Login bem-sucedido!\n";
        echo "   isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "\n";
        echo "   Sessão após login: " . print_r($_SESSION, true) . "\n";
    } else {
        echo "3. Falha no login\n";
        echo "   Erro: " . ($result['error'] ?? 'Sem erro definido') . "\n";
        if (!empty($result['need_confirmation'])) {
            echo "   Precisa confirmar email\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Exceção: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n4. Verificação final da sessão:\n";
echo "   session_status(): " . session_status() . "\n";
echo "   session_id(): " . session_id() . "\n";
echo "   \$_SESSION: " . print_r($_SESSION, true) . "\n";
?>
