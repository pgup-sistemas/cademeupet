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

$latParam = $_GET['lat'] ?? null;
$lngParam = $_GET['lng'] ?? null;

if ($latParam === null || $lngParam === null) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Informe latitude e longitude.'
    ]);
    exit;
}

$lat = filter_var($latParam, FILTER_VALIDATE_FLOAT);
$lng = filter_var($lngParam, FILTER_VALIDATE_FLOAT);

if ($lat === false || $lng === false) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Latitude ou longitude inválidas.'
    ]);
    exit;
}

function petfinder_extractUfFromNominatim(array $address): string
{
    $iso = $address['ISO3166-2-lvl4'] ?? $address['ISO3166-2-lvl3'] ?? $address['ISO3166-2-lvl6'] ?? null;
    if (is_string($iso) && preg_match('/^BR\-([A-Z]{2})$/', $iso, $matches)) {
        return $matches[1];
    }

    $stateName = (string)($address['state'] ?? '');
    $stateName = mb_strtolower(trim($stateName));

    $map = [
        'acre' => 'AC',
        'alagoas' => 'AL',
        'amapá' => 'AP',
        'amapa' => 'AP',
        'amazonas' => 'AM',
        'bahia' => 'BA',
        'ceará' => 'CE',
        'ceara' => 'CE',
        'distrito federal' => 'DF',
        'espírito santo' => 'ES',
        'espirito santo' => 'ES',
        'goiás' => 'GO',
        'goias' => 'GO',
        'maranhão' => 'MA',
        'maranhao' => 'MA',
        'mato grosso' => 'MT',
        'mato grosso do sul' => 'MS',
        'minas gerais' => 'MG',
        'pará' => 'PA',
        'para' => 'PA',
        'paraíba' => 'PB',
        'paraiba' => 'PB',
        'paraná' => 'PR',
        'parana' => 'PR',
        'pernambuco' => 'PE',
        'piauí' => 'PI',
        'piaui' => 'PI',
        'rio de janeiro' => 'RJ',
        'rio grande do norte' => 'RN',
        'rio grande do sul' => 'RS',
        'rondônia' => 'RO',
        'rondonia' => 'RO',
        'roraima' => 'RR',
        'santa catarina' => 'SC',
        'são paulo' => 'SP',
        'sao paulo' => 'SP',
        'sergipe' => 'SE',
        'tocantins' => 'TO',
    ];

    return $map[$stateName] ?? '';
}

function petfinder_reverseGeocodeGoogle(float $lat, float $lng): array
{
    $query = http_build_query([
        'latlng' => sprintf('%.8f,%.8f', $lat, $lng),
        'key' => GOOGLE_MAPS_API_KEY,
        'language' => 'pt-BR'
    ]);

    $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . $query;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new RuntimeException('Erro ao consultar Google Geocoding: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
        throw new RuntimeException('Serviço Google Geocoding indisponível (HTTP ' . $httpCode . ').');
    }

    $payload = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('Resposta inválida do Google Maps.');
    }

    if (($payload['status'] ?? '') !== 'OK' || empty($payload['results'])) {
        throw new RuntimeException('Endereço não encontrado no Google Maps.');
    }

    $result = $payload['results'][0];
    $components = $result['address_components'] ?? [];

    $addressData = [
        'logradouro' => '',
        'bairro' => '',
        'cidade' => '',
        'estado' => '',
        'cep' => '',
        'pais' => '',
        'latitude' => $lat,
        'longitude' => $lng
    ];

    foreach ($components as $component) {
        if (in_array('route', $component['types'], true)) {
            $addressData['logradouro'] = $component['long_name'];
        }
        if (in_array('sublocality', $component['types'], true) || in_array('administrative_area_level_3', $component['types'], true)) {
            $addressData['bairro'] = $component['long_name'];
        }
        if (in_array('administrative_area_level_2', $component['types'], true) || in_array('locality', $component['types'], true)) {
            $addressData['cidade'] = $component['long_name'];
        }
        if (in_array('administrative_area_level_1', $component['types'], true)) {
            $addressData['estado'] = $component['short_name'];
        }
        if (in_array('postal_code', $component['types'], true)) {
            $addressData['cep'] = $component['long_name'];
        }
        if (in_array('country', $component['types'], true)) {
            $addressData['pais'] = $component['long_name'];
        }
    }

    return $addressData;
}

function petfinder_reverseGeocodeNominatim(float $lat, float $lng): array
{
    $query = http_build_query([
        'format' => 'jsonv2',
        'lat' => sprintf('%.8f', $lat),
        'lon' => sprintf('%.8f', $lng),
        'addressdetails' => 1,
        'accept-language' => 'pt-BR'
    ]);

    $url = 'https://nominatim.openstreetmap.org/reverse?' . $query;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: CadeMeuPet/1.0 (+https://cademeupet.pageup.net.br)'
        ],
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new RuntimeException('Erro ao consultar Nominatim: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
        throw new RuntimeException('Serviço Nominatim indisponível (HTTP ' . $httpCode . ').');
    }

    $payload = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('Resposta inválida do Nominatim.');
    }

    $address = $payload['address'] ?? [];

    $logradouro = (string)($address['road'] ?? $address['pedestrian'] ?? $address['footway'] ?? '');
    $bairro = (string)($address['neighbourhood'] ?? $address['suburb'] ?? $address['quarter'] ?? '');
    $cidade = (string)($address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? $address['county'] ?? '');
    $estado = petfinder_extractUfFromNominatim($address);
    $cep = (string)($address['postcode'] ?? '');
    $pais = (string)($address['country'] ?? '');

    return [
        'logradouro' => $logradouro,
        'bairro' => $bairro,
        'cidade' => $cidade,
        'estado' => $estado,
        'cep' => $cep,
        'pais' => $pais,
        'latitude' => $lat,
        'longitude' => $lng
    ];
}

try {
    $useGoogle = !empty(GOOGLE_MAPS_API_KEY) && GOOGLE_MAPS_API_KEY !== 'your-google-maps-api-key';

    if ($useGoogle) {
        $addressData = petfinder_reverseGeocodeGoogle($lat, $lng);
        echo json_encode([
            'success' => true,
            'data' => $addressData,
            'provider' => 'google'
        ]);
        exit;
    }

    $addressData = petfinder_reverseGeocodeNominatim($lat, $lng);
    echo json_encode([
        'success' => true,
        'data' => $addressData,
        'provider' => 'nominatim'
    ]);
} catch (Throwable $e) {
    error_log('[API Geocode] ' . $e->getMessage());
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'Não foi possível obter o endereço. Tente novamente mais tarde.'
    ]);
}
