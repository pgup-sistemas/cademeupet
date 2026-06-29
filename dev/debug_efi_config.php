<?php
/**
 * Debug EFI Configuration
 */

require_once __DIR__ . '/config.php';

echo "=== EFI CONFIGURATION DEBUG ===\n\n";

echo "1. Environment Settings:\n";
echo "   EFI_SANDBOX: " . (EFI_SANDBOX ? 'true (TESTE/SANDBOX)' : 'false (PRODUÇÃO)') . "\n";
echo "   EFI_BASE_URL: " . EFI_BASE_URL . "\n";
echo "\n";

echo "2. Credentials:\n";
echo "   EFI_CLIENT_ID: " . substr(EFI_CLIENT_ID, 0, 15) . "...\n";
echo "   EFI_CLIENT_SECRET: ****\n";
echo "   EFI_PIX_KEY: " . EFI_PIX_KEY . "\n";
echo "\n";

echo "3. Certificate:\n";
echo "   Path: " . EFI_CERTIFICATE_PATH . "\n";
echo "   Exists: " . (file_exists(EFI_CERTIFICATE_PATH) ? 'SIM' : 'NÃO') . "\n";
if (file_exists(EFI_CERTIFICATE_PATH)) {
    echo "   Size: " . filesize(EFI_CERTIFICATE_PATH) . " bytes\n";
}
echo "\n";

echo "4. Recommendations:\n";
if (EFI_SANDBOX) {
    echo "   ✓ Sistema em MODO TESTE (Sandbox)\n";
    echo "   ⚠ Para funcionar em sandbox, você precisa:\n";
    echo "      1. Credenciais EFI de TESTE (não de produção)\n";
    echo "      2. Certificado de TESTE\n";
    echo "      3. Usar credenciais testadas no https://dashboard-sandbox.efipay.com.br\n";
} else {
    echo "   ✓ Sistema em MODO PRODUÇÃO\n";
    echo "   ⚠ Para funcionar em produção, você precisa:\n";
    echo "      1. Credenciais EFI de PRODUÇÃO\n";
    echo "      2. Certificado de PRODUÇÃO (como o atual: producao-573055-petfinder.p12)\n";
    echo "      3. Usar credenciais autenticadas em https://dashboard.efipay.com.br\n";
}

echo "\n=== FIM DEBUG ===\n";
?>
