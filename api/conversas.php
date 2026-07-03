<?php
/**
 * Cadê Meu Pet? - API de Conversas (chat interno)
 *
 * POST /api/conversas.php
 * Body (JSON ou form): { acao: 'abrir'|'enviar'|'ler'|'localizacao', ... }
 *
 *  acao=abrir       { tipo, referencia_id, mensagem }   -> { ok, conversa_id }
 *  acao=enviar      { conversa_id, mensagem }            -> { ok }
 *  acao=ler         { conversa_id }                       -> { ok, conversa, mensagens }
 *  acao=localizacao { conversa_id, latitude, longitude }  -> { ok }
 *
 * Envio de foto é em /api/conversa-imagem.php (multipart/form-data).
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido.']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Você precisa estar logado.']);
    exit;
}

$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
$input = is_array($json) ? $json : $_POST;

if (!validateCSRFToken($input['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'erro' => 'Token inválido. Recarregue a página.']);
    exit;
}

$acao = $input['acao'] ?? '';
$usuarioId = getUserId();
$ctrl = new ConversaController();

switch ($acao) {
    case 'abrir':
        $tipo = (string)($input['tipo'] ?? 'anuncio');
        $referenciaId = (int)($input['referencia_id'] ?? 0);
        $mensagem = (string)($input['mensagem'] ?? '');
        echo json_encode($ctrl->abrir($tipo, $referenciaId, $mensagem));
        break;

    case 'enviar':
        $conversaId = (int)($input['conversa_id'] ?? 0);
        $mensagem = (string)($input['mensagem'] ?? '');
        echo json_encode($ctrl->enviarMensagem($conversaId, $usuarioId, $mensagem));
        break;

    case 'ler':
        $conversaId = (int)($input['conversa_id'] ?? 0);
        echo json_encode($ctrl->listarMensagens($conversaId, $usuarioId));
        break;

    case 'localizacao':
        $conversaId = (int)($input['conversa_id'] ?? 0);
        $latitude = (float)($input['latitude'] ?? 0);
        $longitude = (float)($input['longitude'] ?? 0);
        echo json_encode($ctrl->enviarLocalizacao($conversaId, $usuarioId, $latitude, $longitude));
        break;

    default:
        http_response_code(422);
        echo json_encode(['ok' => false, 'erro' => 'Ação inválida.']);
}
