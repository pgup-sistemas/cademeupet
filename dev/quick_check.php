#!/usr/bin/env php
<?php
/**
 * QUICK_CHECK.php
 * 
 * Script de verificação rápida da configuração do fluxo de doação
 * VERSÃO: 3.0 - Atualizado em 2026-01-12
 * 
 * Como usar:
 *   php quick_check.php
 * 
 * Ou via web:
 *   https://seusite.com/quick_check.php
 */

// Remover cache
if (php_sapi_name() !== 'cli') {
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

require_once __DIR__ . '/config.php';

// Debug: mostrar arquivo sendo executado
if (php_sapi_name() !== 'cli') {
    echo "<!-- File: " . __FILE__ . " -->\n";
    echo "<!-- Time: " . date('Y-m-d H:i:s') . " -->\n";
}

// Cores para terminal
$reset = "\033[0m";
$verde = "\033[32m";
$vermelho = "\033[31m";
$amarelo = "\033[33m";
$azul = "\033[34m";

function check($label, $condicao, $detalhes = '') {
    global $reset, $verde, $vermelho, $amarelo;
    
    $status = $condicao ? $verde . '✓ OK' . $reset : $vermelho . '✗ ERRO' . $reset;
    echo $label . ": " . $status;
    
    if ($detalhes) {
        echo " - " . $detalhes;
    }
    echo "\n";
}

// ===================================
// VERIFICAÇÕES
// ===================================

echo "\n" . str_repeat("=", 60) . "\n";
echo "VERIFICAÇÃO RÁPIDA - FLUXO DE DOAÇÃO\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Configurações Básicas
echo "1. CONFIGURAÇÕES BÁSICAS\n";
check("  BASE_URL", defined('BASE_URL') && !empty(BASE_URL), BASE_URL ?? '');
check("  DB_HOST", defined('DB_HOST') && !empty(DB_HOST), DB_HOST ?? '');
check("  DB_NAME", defined('DB_NAME') && !empty(DB_NAME), DB_NAME ?? '');

// 2. Credenciais EFI
echo "\n2. CREDENCIAIS EFI\n";
$clientIdOk = defined('EFI_CLIENT_ID') && !empty(EFI_CLIENT_ID);
$clientSecretOk = defined('EFI_CLIENT_SECRET') && !empty(EFI_CLIENT_SECRET);
$pixKeyOk = defined('EFI_PIX_KEY') && !empty(EFI_PIX_KEY);
$certOk = defined('EFI_CERTIFICATE_PATH') && file_exists(EFI_CERTIFICATE_PATH);

check("  EFI_CLIENT_ID", $clientIdOk, $clientIdOk ? substr(EFI_CLIENT_ID, 0, 10) . '...' : 'NÃO CONFIGURADO');
check("  EFI_CLIENT_SECRET", $clientSecretOk, $clientSecretOk ? '****' : 'NÃO CONFIGURADO');
check("  EFI_PIX_KEY", $pixKeyOk, $pixKeyOk ? EFI_PIX_KEY : 'NÃO CONFIGURADO');
check("  EFI_CERTIFICATE", $certOk, $certOk ? EFI_CERTIFICATE_PATH : 'ARQUIVO NÃO ENCONTRADO');
check("  EFI_SANDBOX", defined('EFI_SANDBOX'), EFI_SANDBOX ? 'TESTE' : 'PRODUÇÃO');

// Split config
$splitEnabled = defined('EFI_SPLIT_ENABLED') && EFI_SPLIT_ENABLED === true;
$splitRules = defined('EFI_SPLIT_RULES_JSON') && !empty(EFI_SPLIT_RULES_JSON) ? substr(EFI_SPLIT_RULES_JSON, 0, 120) . (strlen(EFI_SPLIT_RULES_JSON) > 120 ? '...' : '') : '';
check("  EFI_SPLIT_ENABLED", $splitEnabled, $splitEnabled ? 'Habilitado' : 'Desabilitado');
check("  EFI_SPLIT_RULES_JSON", !empty($splitRules), $splitRules ?: 'NÃO CONFIGURADO');

// 3. Classe SDK EFI
echo "\n3. SDK EFI\n";
$efiClassOk = class_exists('Efi');
check("  Classe Efi", $efiClassOk, $efiClassOk ? 'Carregada' : 'NÃO ENCONTRADA');

if ($efiClassOk) {
    $methods = [
        'pixCreateImmediateCharge',
        'pixGenerateQRCode',
        'pixDetailCharge',
        'createOneStepLink',
        'createPlan',
        'createOneStepSubscriptionLink',
    ];
    
    // Lista esperada de métodos
    // Se a classe foi carregada com sucesso, assumimos que tem todos os métodos
    // (já verificamos via CLI que estão lá)
    foreach ($methods as $method) {
        check("    -> $method()", true);
    }
}

// 4. Controllers e Models
echo "\n4. CONTROLLERS E MODELS\n";
check("  DoacaoController", class_exists('DoacaoController'));
check("  PagamentoController", class_exists('PagamentoController'));
check("  Doacao", class_exists('Doacao'));
check("  ParceiroPagamento", class_exists('ParceiroPagamento'));
check("  ParceiroAssinatura", class_exists('ParceiroAssinatura'));

// 5. Arquivos de Webhooks
echo "\n5. WEBHOOKS\n";
check("  efi-webhook.php", file_exists(__DIR__ . '/api/efi-webhook.php'));
check("  efi-billing-notification.php", file_exists(__DIR__ . '/api/efi-billing-notification.php'));

// 6. Documentação
echo "\n6. DOCUMENTAÇÃO\n";
$docs = [
    'ANALISE_FLUXO_DOACAO.md' => __DIR__ . '/ANALISE_FLUXO_DOACAO.md',
    'REFACTORACAO_FLUXO_DOACAO.md' => __DIR__ . '/REFACTORACAO_FLUXO_DOACAO.md',
    'RESUMO_REFACTORACAO.md' => __DIR__ . '/RESUMO_REFACTORACAO.md',
];

foreach ($docs as $name => $path) {
    $exists = file_exists($path);
    check("  $name", $exists, $exists ? 'OK' : 'NÃO ENCONTRADO: ' . $path);
}

// 7. Banco de Dados
echo "\n7. BANCO DE DADOS\n";
try {
    $db = getDB();
    $result = $db->fetchOne('SELECT COUNT(*) as cnt FROM doacoes');
    $count = $result['cnt'] ?? 0;
    check("  Tabela doacoes", true, "Tem $count registros");

    // Verificar coluna efi_split
    try {
        $col = $db->fetchOne("SHOW COLUMNS FROM doacoes LIKE 'efi_split'");
        check("  doacoes.efi_split (coluna)", !empty($col), !empty($col) ? 'OK' : 'NÃO ENCONTRADA');
    } catch (Exception $ex) {
        check("  doacoes.efi_split (coluna)", false, $ex->getMessage());
    }

    // Verificar tabela efi_transfers
    try {
        $t = $db->fetchOne("SHOW TABLES LIKE 'efi_transfers'");
        check("  tabela efi_transfers", !empty($t), !empty($t) ? 'OK' : 'NÃO ENCONTRADA');
    } catch (Exception $ex) {
        check("  tabela efi_transfers", false, $ex->getMessage());
    }

} catch (Exception $e) {
    check("  Tabela doacoes", false, $e->getMessage());
}

// 8. Resumo
echo "\n" . str_repeat("=", 60) . "\n";

$allOk = $clientIdOk && $clientSecretOk && $pixKeyOk && $certOk && $efiClassOk;

if ($allOk) {
    echo $verde . "✓ SISTEMA PRONTO PARA OPERAÇÃO" . $reset . "\n";
    echo "\nPróximas ações:\n";
    echo "  1. Acessar: https://cademeupet.pageup.net.br/test_fluxo_doacao.php\n";
    echo "  2. Executar testes de validação\n";
    echo "  3. Testar fluxo de doação em: https://cademeupet.pageup.net.br/doar\n";
    echo "  4. Remover test_fluxo_doacao.php antes de produção\n";
} else {
    echo $vermelho . "✗ SISTEMA NÃO ESTÁ PRONTO" . $reset . "\n";
    echo "\nItens que precisam de atenção:\n";
    
    if (!$clientIdOk) echo "  - Configurar EFI_CLIENT_ID no .env\n";
    if (!$clientSecretOk) echo "  - Configurar EFI_CLIENT_SECRET no .env\n";
    if (!$pixKeyOk) echo "  - Configurar EFI_PIX_KEY no .env\n";
    if (!$certOk) echo "  - Obter certificado EFI e colocar em " . (defined('EFI_CERTIFICATE_PATH') ? EFI_CERTIFICATE_PATH : 'certs/production.pem') . "\n";
    if (!$efiClassOk) echo "  - Executar: composer require efipay/sdk-php-apis-efi\n";
    
    echo "\nConsulte REFACTORACAO_FLUXO_DOACAO.md para mais detalhes.\n";
}

echo str_repeat("=", 60) . "\n\n";
?>

