<?php
/**
 * PetFinder - Conversor de Certificado P12 para PEM
 * 
 * IMPORTANTE: Certificado P12 precisa ser convertido para PEM antes de usar
 * 
 * Este script realiza a conversão usando as funções nativas do PHP (openssl_*)
 * 
 * Uso:
 *   1. Coloque o arquivo "producao-573055-petfinder.p12" na raiz do projeto
 *   2. Execute: php convert_certificate.php
 *   3. Verifique se "producao-573055-petfinder.pem" foi criado
 */

echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "PetFinder - Conversor de Certificado P12 para PEM\n";
echo "═══════════════════════════════════════════════════════════════════════════\n\n";

// ═══════════════════════════════════════════════════════════════════════════
// PASSO 1: Verificar se extensão OpenSSL está disponível
// ═══════════════════════════════════════════════════════════════════════════

echo "PASSO 1: Verificando extensão OpenSSL do PHP...\n";

if (!extension_loaded('openssl')) {
    die("❌ ERRO: Extensão OpenSSL do PHP não está habilitada!\n" .
        "Habilite em php.ini: extension=openssl\n");
}

echo "✓ Extensão OpenSSL do PHP disponível\n";

// ═══════════════════════════════════════════════════════════════════════════
// PASSO 2: Verificar se arquivo P12 existe
// ═══════════════════════════════════════════════════════════════════════════

$p12_file = __DIR__ . '/producao-573055-petfinder.p12';
$pem_file = __DIR__ . '/producao-573055-petfinder.pem';

if (!file_exists($p12_file)) {
    die("❌ ERRO: Arquivo '$p12_file' não encontrado!\n");
}

echo "✓ Arquivo P12 encontrado: " . basename($p12_file) . "\n";
echo "  Tamanho: " . number_format(filesize($p12_file)) . " bytes\n\n";

// ═══════════════════════════════════════════════════════════════════════════
// PASSO 3: Converter P12 para PEM usando funções PHP
// ═══════════════════════════════════════════════════════════════════════════

echo "PASSO 2: Convertendo P12 para PEM...\n";

$p12_data = file_get_contents($p12_file);
if ($p12_data === false) {
    die("❌ ERRO ao ler arquivo P12!\n");
}

// Array para armazenar certificados e chave
$certs = [];

// Decodificar PKCS12 usando função nativa do PHP
// Senha é vazia conforme EFI
$result = openssl_pkcs12_read($p12_data, $certs, '');

if ($result === false) {
    echo "❌ ERRO ao decodificar PKCS12!\n";
    echo "Tentando com valores de erro: " . openssl_error_string() . "\n";
    die();
}

echo "✓ Certificado P12 decodificado com sucesso\n\n";

// ═══════════════════════════════════════════════════════════════════════════
// PASSO 4: Extrair certificado e chave privada
// ═══════════════════════════════════════════════════════════════════════════

echo "PASSO 3: Extraindo certificado e chave privada...\n";

// Certificado
if (!isset($certs['cert']) || empty($certs['cert'])) {
    die("❌ ERRO: Certificado não encontrado no arquivo P12!\n");
}

$certificate = $certs['cert'];
echo "✓ Certificado extraído\n";

// Chave privada
$private_key = '';
if (isset($certs['pkey']) && !empty($certs['pkey'])) {
    $private_key = $certs['pkey'];
    echo "✓ Chave privada extraída\n";
} else {
    echo "⚠ Aviso: Chave privada não encontrada (pode estar protegida)\n";
}

// CA Certificate (se existir)
$ca_certs = '';
if (isset($certs['extracerts']) && is_array($certs['extracerts']) && count($certs['extracerts']) > 0) {
    $ca_certs = implode("\n", $certs['extracerts']);
    echo "✓ Certificados CA extraídos\n";
}

// ═══════════════════════════════════════════════════════════════════════════
// PASSO 5: Montar arquivo PEM
// ═══════════════════════════════════════════════════════════════════════════

echo "\nPASSO 4: Montando arquivo PEM...\n";

// Garantir que certificado está bem formatado
if (strpos($certificate, '-----BEGIN CERTIFICATE-----') === false) {
    $certificate = "-----BEGIN CERTIFICATE-----\n" . 
                   chunk_split(base64_encode($certificate), 64, "\n") . 
                   "-----END CERTIFICATE-----\n";
}

// Garantir que chave privada está bem formatada
$pem_content = $certificate;

if (!empty($private_key)) {
    if (strpos($private_key, '-----BEGIN') === false) {
        // Se é binário, codificar em base64
        if (strlen($private_key) > 0 && strpos($private_key, "-----") === false) {
            $private_key = "-----BEGIN PRIVATE KEY-----\n" . 
                          chunk_split(base64_encode($private_key), 64, "\n") . 
                          "-----END PRIVATE KEY-----\n";
        }
    }
    $pem_content .= $private_key;
}

// Adicionar CA certs se existirem
if (!empty($ca_certs)) {
    $pem_content .= $ca_certs;
}

// ═══════════════════════════════════════════════════════════════════════════
// PASSO 6: Salvar arquivo PEM
// ═══════════════════════════════════════════════════════════════════════════

echo "PASSO 5: Salvando arquivo PEM...\n";

if (file_put_contents($pem_file, $pem_content) === false) {
    die("❌ ERRO ao salvar arquivo PEM!\n");
}

echo "✓ Arquivo PEM salvo: " . basename($pem_file) . "\n";
echo "  Tamanho: " . number_format(strlen($pem_content)) . " bytes\n";
echo "  Caminho: $pem_file\n\n";

// ═══════════════════════════════════════════════════════════════════════════
// PASSO 7: Validar arquivo PEM
// ═══════════════════════════════════════════════════════════════════════════

echo "PASSO 6: Validando arquivo PEM...\n";

// Verificar estrutura PEM
if (strpos($pem_content, '-----BEGIN') === false || strpos($pem_content, '-----END') === false) {
    die("❌ ERRO: Arquivo PEM não parece ser válido (não tem estrutura PEM)!\n");
}

echo "✓ Estrutura PEM validada\n";

// Verificar seções
$sections = [];
if (strpos($pem_content, 'BEGIN CERTIFICATE') !== false) {
    $sections[] = "Certificado";
}
if (strpos($pem_content, 'BEGIN PRIVATE KEY') !== false || 
    strpos($pem_content, 'BEGIN RSA PRIVATE KEY') !== false) {
    $sections[] = "Chave Privada";
}

if (empty($sections)) {
    die("❌ ERRO: Arquivo PEM não contém certificado ou chave privada!\n");
}

echo "✓ Seções encontradas: " . implode(", ", $sections) . "\n";

// Tentar validar certificado em PHP
if (function_exists('openssl_x509_parse')) {
    $cert_info = openssl_x509_parse($certificate);
    if ($cert_info !== false) {
        echo "✓ Certificado validado em PHP\n";
        
        if (isset($cert_info['subject'])) {
            $subject = $cert_info['subject'];
            $cn = $subject['CN'] ?? 'Desconhecido';
            echo "  - Issuer/CN: $cn\n";
        }
        
        if (isset($cert_info['validFrom_time_t']) && isset($cert_info['validTo_time_t'])) {
            $valid_from = date('Y-m-d H:i:s', $cert_info['validFrom_time_t']);
            $valid_to = date('Y-m-d H:i:s', $cert_info['validTo_time_t']);
            echo "  - Válido de: $valid_from\n";
            echo "  - Válido até: $valid_to\n";
        }
    } else {
        echo "⚠ Aviso: Não foi possível validar certificado em PHP\n";
        echo "  (Isso é normal para alguns formatos de certificado)\n";
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SUCESSO!
// ═══════════════════════════════════════════════════════════════════════════

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "✓ CONVERSÃO CONCLUÍDA COM SUCESSO!\n";
echo "═══════════════════════════════════════════════════════════════════════════\n\n";

echo "Próximos passos:\n";
echo "1. Verifique se config.php está usando EFI_CERTIFICATE_PATH corretamente\n";
echo "2. Execute o teste OAuth: php test_oauth_authorization.php\n";
echo "3. Verifique se as credenciais estão corretas\n\n";

echo "Informações de segurança:\n";
echo "- O arquivo P12 contém a chave privada\n";
echo "- O arquivo PEM também contém a chave privada\n";
echo "- Ambos devem ser mantidos seguros no servidor\n";
echo "- NÃO envie estes arquivos por email ou versionamento público\n";
?>

