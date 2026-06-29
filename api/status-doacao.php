<?php
/**
 * PetFinder - Verificar Status de Doação
 * 
 * Endpoint usado pelo JavaScript para verificar em tempo real se o PIX foi confirmado
 * Chamado a cada 5 segundos enquanto usuário está na página de doação
 * 
 * Requisição:
 * POST /api/status-doacao.php
 * {"id": 32, "txid": "..."}
 * 
 * Resposta:
 * {"ok": true, "status": "aprovada|processando|erro"}
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// ═══════════════════════════════════════════════════════════════════════════
// Validar Requisição
// ═══════════════════════════════════════════════════════════════════════════

// Apenas POST aceito
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

// Obter dados
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json']);
    exit;
}

$doacaoId = isset($data['id']) ? (int)$data['id'] : 0;
$txid = isset($data['txid']) ? trim((string)$data['txid']) : '';

if ($doacaoId <= 0 || empty($txid)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_id_or_txid']);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// Buscar Doação
// ═══════════════════════════════════════════════════════════════════════════

try {
    $doacaoModel = new Doacao();
    $doacao = $doacaoModel->findById($doacaoId);

    if (empty($doacao)) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'doacao_not_found']);
        exit;
    }

    // Validar TXID
    if ((string)$doacao['transaction_id'] !== $txid) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'txid_mismatch']);
        exit;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Verificar Status Atual
    // ═══════════════════════════════════════════════════════════════════════════

    $statusAtual = (string)($doacao['status'] ?? 'pendente');
    $isApproved = ($statusAtual === 'aprovada' || $statusAtual === 'aprovado');

    // Se já está aprovado, retornar logo
    if ($isApproved) {
        http_response_code(200);
        echo json_encode([
            'ok' => true,
            'status' => 'aprovada',
            'aprovada' => true,
            'message' => 'Pagamento confirmado. Obrigado pela sua doação!',
            'doacao_id' => $doacaoId,
            'timestamp' => $doacao['updated_at'] ?? null
        ]);
        exit;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Sincronizar com API EFI
    // ═══════════════════════════════════════════════════════════════════════════

    try {
        $pagamentoController = new PagamentoController();
        $atualizada = $pagamentoController->sincronizarStatusDoacaoPix($doacaoId, $txid);

        if (!empty($atualizada)) {
            $novoStatus = (string)($atualizada['status'] ?? 'pendente');
            $novoApproved = ($novoStatus === 'aprovada' || $novoStatus === 'aprovado');
            
            http_response_code(200);
            echo json_encode([
                'ok' => true,
                'status' => $novoStatus,
                'aprovada' => $novoApproved,
                'message' => $novoApproved ? 'Pagamento confirmado. Obrigado pela sua doação!' : null,
                'doacao_id' => $doacaoId,
                'timestamp' => $atualizada['updated_at'] ?? null
            ]);
            exit;
        }
    } catch (Exception $e) {
        error_log('[status-doacao] Erro ao sincronizar: ' . $e->getMessage());
        // Continuar mesmo com erro
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Retornar Status Atual
    // ═══════════════════════════════════════════════════════════════════════════

    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'status' => $statusAtual === 'pendente' ? 'processando' : $statusAtual,
        'aprovada' => $isApproved,
        'message' => $isApproved ? 'Pagamento confirmado.' : null,
        'doacao_id' => $doacaoId,
        'timestamp' => $doacao['updated_at'] ?? null
    ]);
    exit;

} catch (Exception $e) {
    error_log('[status-doacao] Erro: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'internal_error', 'message' => $e->getMessage()]);
    exit;
}
?>
