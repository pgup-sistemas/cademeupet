<?php
/**
 * POST /api/v1/ingestao/animais — ingestão de dados de terceiros
 * (ex.: prefeitura/CCZ enviando animais recolhidos).
 *
 * NUNCA publica automaticamente: todo registro entra como
 * 'pendente_revisao' e só vira anúncio depois de um moderador humano
 * aprovar manualmente. Desabilitado por padrão via feature flag em
 * `configuracoes.api_ingestao_animais_ativa` até haver um consumidor real.
 */
require_once __DIR__ . '/../_bootstrap.php';
requireApiScope('ingestao_animais');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiRespondError('method_not_allowed', 'Apenas POST é suportado neste recurso.', 405);
}

if (getConfig('api_ingestao_animais_ativa', '0') !== '1') {
    apiRespondError('feature_disabled', 'Este endpoint ainda não está habilitado para consumidores externos.', 503);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    apiRespondError('validation_error', 'Corpo da requisição deve ser um JSON válido.', 400);
}

$camposObrigatorios = ['especie', 'cidade', 'estado'];
foreach ($camposObrigatorios as $campo) {
    if (empty($payload[$campo])) {
        apiRespondError('validation_error', "Campo obrigatório ausente: {$campo}.", 400);
    }
}

$db = getDB();
$id = $db->insert('api_ingestao_animais', [
    'api_key_id'   => $GLOBALS['__api_key_id'],
    'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
    'status'       => 'pendente_revisao',
]);

apiRespondSuccess([
    'data' => [
        'id'     => (int)$id,
        'status' => 'pendente_revisao',
    ],
    'message' => 'Recebido. Um moderador humano vai revisar antes de qualquer publicação.',
], 201);
