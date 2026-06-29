<?php
// test_efi.php - Script de diagnóstico rápido para verificar a disponibilidade da SDK Efí
// Suba este arquivo na raiz do projeto (onde está o config.php) e abra via navegador:
// https://seusite/test_efi.php
// REMOVA este arquivo após os testes.

require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Teste SDK Efí - PetFinder\n";
echo str_repeat('=', 40) . "\n";

// Informações básicas
if (defined('BASE_PATH')) {
    echo "BASE_PATH: " . BASE_PATH . "\n";
}
if (defined('BASE_URL')) {
    echo "BASE_URL: " . BASE_URL . "\n";
}

echo "\n-- Constantes EFI --\n";
echo "EFI_PIX_KEY definido? " . (defined('EFI_PIX_KEY') ? 'SIM' : 'NAO') . "\n";
echo "EFI_PIX_KEY valor: " . (defined('EFI_PIX_KEY') ? EFI_PIX_KEY : '---') . "\n";

echo "\n-- envValue() / .env --\n";
if (function_exists('envValue')) {
    echo "envValue() disponível: SIM\n";
    echo "env MIN_DONATION_AMOUNT: " . envValue('MIN_DONATION_AMOUNT', 'nao-informado') . "\n";
    echo "env EFI_PIX_KEY: " . envValue('EFI_PIX_KEY', '---') . "\n";
} else {
    echo "envValue() disponível: NAO\n";
}

echo "\n-- Verificação de classes --\n";
$checkClasses = [
    'Efi',
    'EFI',
    'EFI\\EFI',
    'Efi\\Efi',
    'Efi\\EfiPay',
];
foreach ($checkClasses as $c) {
    echo "class_exists('$c'): " . (class_exists($c) ? 'SIM' : 'NAO') . "\n";
}

// Tentar instanciar a classe Efi (compatível com PagamentoController)
try {
    // Normalizar alias se necessário
    if (!class_exists('Efi') && class_exists('EFI')) {
        class_alias('EFI', 'Efi');
    }

    if (class_exists('Efi')) {
        echo "\nTentando instanciar Efi...\n";
        $opts = [
            'client_id' => envValue('EFI_CLIENT_ID', defined('EFI_CLIENT_ID') ? EFI_CLIENT_ID : ''),
            'client_secret' => envValue('EFI_CLIENT_SECRET', defined('EFI_CLIENT_SECRET') ? EFI_CLIENT_SECRET : ''),
            'pixKey' => envValue('EFI_PIX_KEY', defined('EFI_PIX_KEY') ? EFI_PIX_KEY : ''),
            'certificate' => envValue('EFI_CERTIFICATE_PATH', defined('EFI_CERTIFICATE_PATH') ? EFI_CERTIFICATE_PATH : ''),
            'sandbox' => envValue('EFI_SANDBOX', defined('EFI_SANDBOX') ? EFI_SANDBOX : true),
            'base_url' => envValue('EFI_BASE_URL', defined('EFI_BASE_URL') ? EFI_BASE_URL : ''),
        ];

        $api = new Efi($opts);
        echo "Instanciado Efi com sucesso.\n";

        if (method_exists($api, 'pixCreateImmediateCharge')) {
            echo "Chamando pixCreateImmediateCharge de teste...\n";
            $body = [
                'valor' => ['original' => '1.00'],
                'chave' => $opts['pixKey'],
                'solicitacaoPagador' => 'Teste PetFinder'
            ];

            $resp = $api->pixCreateImmediateCharge([], $body);
            echo "Resposta pixCreateImmediateCharge: " . (is_array($resp) ? json_encode($resp) : (string)$resp) . "\n";

            if (method_exists($api, 'pixGenerateQRCode')) {
                $params = ['id' => $resp['loc']['id'] ?? ($resp['id'] ?? null)];
                $q = $api->pixGenerateQRCode($params);
                echo "Resposta pixGenerateQRCode: " . (is_array($q) ? json_encode($q) : (string)$q) . "\n";
            }
        } else {
            echo "Método pixCreateImmediateCharge não disponível na classe Efi.\n";
        }
    } else {
        echo "Classe Efi não encontrada — SDK não carregada.\n";
    }
} catch (Throwable $e) {
    echo "Exceção: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\nRemova este arquivo após a verificação.\n";

?>