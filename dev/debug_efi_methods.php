<?php
/**
 * DEBUG - Verificar métodos da classe EFI
 */

require_once __DIR__ . '/config.php';

echo "<pre>\n";
echo "=== DEBUG EFI METHODS ===\n\n";

// 1. Verificar se classe existe
echo "1. Classe 'Efi' existe? " . (class_exists('Efi') ? 'SIM' : 'NÃO') . "\n";

// 2. Verificar arquivo
$efiFile = __DIR__ . '/includes/efi.php';
echo "2. Arquivo includes/efi.php existe? " . (file_exists($efiFile) ? 'SIM' : 'NÃO') . "\n";
echo "   Caminho: $efiFile\n";

// 3. Verificar se pode fazer Reflection
if (class_exists('Efi')) {
    try {
        $reflection = new ReflectionClass('Efi');
        echo "\n3. ReflectionClass criada com sucesso\n";
        
        // 4. Listar TODOS os métodos
        $methods = $reflection->getMethods();
        echo "\n4. Total de métodos: " . count($methods) . "\n";
        echo "   Métodos encontrados:\n";
        
        foreach ($methods as $method) {
            echo "      - " . $method->getName() . " (linha " . $method->getStartLine() . ")\n";
        }
        
        // 5. Verificar métodos específicos
        echo "\n5. Verificação de métodos específicos:\n";
        $targetMethods = [
            'pixCreateImmediateCharge',
            'pixGenerateQRCode',
            'pixDetailCharge',
            'createOneStepLink',
            'createPlan',
            'createOneStepSubscriptionLink',
        ];
        
        foreach ($targetMethods as $method) {
            $exists = $reflection->hasMethod($method);
            echo "      - $method: " . ($exists ? 'SIM' : 'NÃO') . "\n";
        }
        
    } catch (Exception $e) {
        echo "ERRO ao fazer Reflection: " . $e->getMessage() . "\n";
        echo "Arquivo: " . $e->getFile() . "\n";
        echo "Linha: " . $e->getLine() . "\n";
    }
} else {
    echo "\nERRO: Classe 'Efi' não foi carregada!\n";
    
    // Tentar incluir manualmente
    echo "\nTentando incluir o arquivo manualmente...\n";
    try {
        require_once __DIR__ . '/includes/efi.php';
        echo "Arquivo incluído com sucesso\n";
        echo "Classe 'Efi' existe agora? " . (class_exists('Efi') ? 'SIM' : 'NÃO') . "\n";
    } catch (Exception $e) {
        echo "ERRO ao incluir: " . $e->getMessage() . "\n";
    }
}

echo "\n=== FIM DEBUG ===\n";
echo "</pre>";
?>
