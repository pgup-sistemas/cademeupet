<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido. Use GET.'
    ]);
    exit;
}

// Rate limit: máximo 120 requisições por hora por IP
$ipKey = 'cep_' . md5(getClientIP());
if (isRateLimited($ipKey, 120, 3600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Limite de requisições excedido. Tente novamente mais tarde.']);
    exit;
}

$cepParam = $_GET['cep'] ?? '';
$cep = preg_replace('/\D/', '', $cepParam);

if (strlen($cep) !== 8) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'CEP inválido. Informe 8 dígitos.'
    ]);
    exit;
}

$viaCepUrl = "https://viacep.com.br/ws/{$cep}/json/";

try {
    $ch = curl_init($viaCepUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new RuntimeException('Erro ao consultar ViaCEP: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
        http_response_code(502);
        echo json_encode([
            'success' => false,
            'message' => 'Serviço de CEP indisponível no momento.'
        ]);
        exit;
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('Resposta inválida do ViaCEP.');
    }

    if (isset($data['erro']) && $data['erro'] === true) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'CEP não encontrado.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'logradouro' => $data['logradouro'] ?? '',
            'bairro' => $data['bairro'] ?? '',
            'cidade' => $data['localidade'] ?? '',
            'estado' => $data['uf'] ?? '',
            'complemento' => $data['complemento'] ?? '',
            'ddd' => $data['ddd'] ?? null,
            'cep' => $data['cep'] ?? $cep
        ]
    ]);
} catch (Throwable $e) {
    error_log('[API CEP] ' . $e->getMessage());
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'Não foi possível consultar o CEP agora. Tente novamente mais tarde.'
    ]);
}
