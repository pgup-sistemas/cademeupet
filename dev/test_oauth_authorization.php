<?php
/**
 * PetFinder - Teste de Autorização OAuth2 com EFI
 * 
 * Conforme documentação oficial:
 * https://dev.efipay.com.br/docs/api-pix/credenciais/
 * 
 * Este script testa:
 * 1. Certificado PEM está acessível
 * 2. Credenciais (Client_Id e Client_Secret) estão válidas
 * 3. Autorização OAuth2 funciona
 * 4. Token de acesso é gerado corretamente
 * 
 * Uso: php test_oauth_authorization.php
 */

// ═══════════════════════════════════════════════════════════════════════════
// INICIALIZAÇÃO
// ═══════════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/config.php';

echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "PetFinder - Teste OAuth2 EFI\n";
echo "═══════════════════════════════════════════════════════════════════════════\n\n";

// ═══════════════════════════════════════════════════════════════════════════
// PASSO 1: Validar Configurações
// ═══════════════════════════════════════════════════════════════════════════

echo "PASSO 1: Validando configurações...\n";

$required_consts = [
    'EFI_CLIENT_ID',
    'EFI_CLIENT_SECRET',
    'EFI_CERTIFICATE_PATH',
    'EFI_SANDBOX',
];

foreach ($required_consts as $const) {
    if (!defined($const)) {
        die("❌ Constante não definida: $const\n");
    }
    echo "✓ $const definido\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════════════
// PASSO 2: Validar Certificado
// ═══════════════════════════════════════════════════════════════════════════

echo "PASSO 2: Validando certificado...\n";

$cert_path = EFI_CERTIFICATE_PATH;

if (!file_exists($cert_path)) {
    // Tentar com .pem
    $cert_path = str_replace('.p12', '.pem', EFI_CERTIFICATE_PATH);
    if (!file_exists($cert_path)) {
        die("❌ Certificado não encontrado em:\n  - " . EFI_CERTIFICATE_PATH . "\n  - " . $cert_path . "\n");
    }
}

echo "✓ Certificado encontrado: $cert_path\n";

$cert_size = filesize($cert_path);
echo "✓ Tamanho: " . number_format($cert_size) . " bytes\n";

// Validar que é PEM
$cert_content = file_get_contents($cert_path);
if (strpos($cert_content, '-----BEGIN') === false) {
    die("❌ Arquivo não parece ser PEM (não contém -----BEGIN)\n");
}

echo "✓ Formato PEM confirmado\n\n";

// ═══════════════════════════════════════════════════════════════════════════
// PASSO 3: Validar Credenciais
// ═══════════════════════════════════════════════════════════════════════════

echo "PASSO 3: Validando credenciais...\n";

$client_id = EFI_CLIENT_ID;
$client_secret = EFI_CLIENT_SECRET;

echo "✓ Client ID: " . substr($client_id, 0, 20) . "...\n";
echo "✓ Client Secret: " . substr($client_secret, 0, 20) . "...\n\n";

// ═══════════════════════════════════════════════════════════════════════════
// PASSO 4: Preparar Requisição OAuth2
// ═══════════════════════════════════════════════════════════════════════════

echo "PASSO 4: Preparando requisição OAuth2...\n";

// Determinar URL base
$base_url = EFI_BASE_URL;
$sandbox = defined('EFI_SANDBOX') ? EFI_SANDBOX : false;

if ($sandbox) {
    $oauth_url = 'https://pix-h.api.efipay.com.br/oauth/token';
    echo "✓ Modo: HOMOLOGAÇÃO (testes)\n";
} else {
    $oauth_url = 'https://pix.api.efipay.com.br/oauth/token';
    echo "✓ Modo: PRODUÇÃO\n";
}

echo "✓ URL OAuth: $oauth_url\n";

// Preparar Basic Auth
$auth_header = base64_encode($client_id . ':' . $client_secret);
echo "✓ Authorization Header preparado\n\n";

// ═══════════════════════════════════════════════════════════════════════════
// PASSO 5: Executar Requisição cURL
// ═══════════════════════════════════════════════════════════════════════════

echo "PASSO 5: Executando requisição cURL...\n\n";

$curl = curl_init();

curl_setopt_array($curl, [
    // URL e método
    CURLOPT_URL => $oauth_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    
    // Certificado SSL/TLS (OBRIGATÓRIO)
    CURLOPT_SSLCERT => $cert_path,
    CURLOPT_SSLCERTTYPE => 'PEM',
    CURLOPT_SSLCERTPASSWD => '', // Senha vazia conforme EFI
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    
    // Headers
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . $auth_header,
        'Content-Type: application/json',
    ],
    
    // Body com grant_type
    CURLOPT_POSTFIELDS => json_encode([
        'grant_type' => 'client_credentials'
    ]),
    
    // Timeout
    CURLOPT_TIMEOUT => 30,
]);

// Para debug (comentar em produção)
// curl_setopt($curl, CURLOPT_VERBOSE, true);

echo "Enviando...\n";

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curl_error = curl_error($curl);
$curl_errno = curl_errno($curl);

curl_close($curl);

echo "HTTP Status: $http_code\n";

// ═══════════════════════════════════════════════════════════════════════════
// PASSO 6: Processar Resposta
// ═══════════════════════════════════════════════════════════════════════════

echo "\nPASSO 6: Processando resposta...\n\n";

// Verificar erros cURL
if ($curl_errno !== 0) {
    echo "❌ Erro cURL #$curl_errno: $curl_error\n";
    echo "\nDica: Se for erro de certificado:\n";
    echo "1. Verifique se o arquivo PEM está correto\n";
    echo "2. Execute: php convert_certificate.php\n";
    echo "3. Verifique a senha do certificado\n";
    die();
}

// Tentar decodificar JSON
$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ Erro ao decodificar resposta:\n";
    echo json_last_error_msg() . "\n\n";
    echo "Resposta bruta:\n";
    echo $response . "\n";
    die();
}

// ═══════════════════════════════════════════════════════════════════════════
// PASSO 7: Validar Resposta de Sucesso
// ═══════════════════════════════════════════════════════════════════════════

echo "PASSO 7: Validando resposta...\n";

if ($http_code !== 200) {
    echo "❌ Erro na requisição (HTTP $http_code)\n\n";
    
    // Mostrar erro da EFI
    if (isset($data['errors'])) {
        echo "Erros retornados pela EFI:\n";
        foreach ((array)$data['errors'] as $error) {
            echo "- " . (is_array($error) ? json_encode($error) : $error) . "\n";
        }
    }
    
    if (isset($data['error_description'])) {
        echo "Descrição: " . $data['error_description'] . "\n";
    }
    
    echo "\nPossíveis causas:\n";
    echo "1. Credenciais inválidas (Client_Id/Client_Secret)\n";
    echo "2. Certificado expirado ou inválido\n";
    echo "3. Certificado não está em formato PEM\n";
    echo "4. Problema de conectividade com EFI\n";
    
    die();
}

// Verificar campos obrigatórios na resposta
$required_fields = ['access_token', 'token_type', 'expires_in'];
foreach ($required_fields as $field) {
    if (!isset($data[$field])) {
        die("❌ Campo obrigatório missing: $field\n");
    }
    echo "✓ Campo presente: $field\n";
}

// ═══════════════════════════════════════════════════════════════════════════
// SUCESSO!
// ═══════════════════════════════════════════════════════════════════════════

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "✓ AUTORIZAÇÃO OAUTH2 CONCLUÍDA COM SUCESSO!\n";
echo "═══════════════════════════════════════════════════════════════════════════\n\n";

echo "Detalhes do Token:\n";
echo "- Tipo: " . $data['token_type'] . "\n";
echo "- Validade: " . $data['expires_in'] . " segundos (" . floor($data['expires_in'] / 3600) . " horas)\n";
echo "- Token: " . substr($data['access_token'], 0, 50) . "...\n";

if (isset($data['scope'])) {
    echo "- Escopos: " . $data['scope'] . "\n";
}

echo "\nO token foi gerado com sucesso!\n";
echo "A integração EFI está pronta para usar.\n\n";

echo "Próximos passos:\n";
echo "1. Teste o fluxo completo de doação\n";
echo "2. Verifique se PIX e Cartão funcionam\n";
echo "3. Configure o webhook em: " . EFI_PIX_NOTIFICATION_URL . "\n";
?>
