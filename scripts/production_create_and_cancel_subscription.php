<?php
/**
 * Script de teste PROD - Cria e cancela assinatura (USO SENSÍVEL)
 * Execução: php scripts/production_create_and_cancel_subscription.php
 * ATENÇÃO: Este script fará chamadas reais em produção.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Doacao.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../controllers/PagamentoController.php';

$usuarioId = 2;
$valorMensal = 2.00; // R$ 2,00

$doacaoModel = new Doacao();
$usuarioModel = new Usuario();
$pagController = new PagamentoController();

echo "=== PRODUCTION SUBSCRIPTION TEST ===\n";

$usuario = $usuarioModel->findById($usuarioId);
if (empty($usuario)) {
    echo "Erro: usuário ID $usuarioId não encontrado. Abortando.\n";
    exit(1);
}

// Criar doação pendente para referenciar a assinatura
$doacaoData = [
    'usuario_id' => $usuarioId,
    'valor' => $valorMensal,
    'tipo' => 'mensal',
    'metodo_pagamento' => 'cartao_recorrente',
    'status' => 'pendente',
    'data_doacao' => date('Y-m-d H:i:s'),
    'nome_doador' => $usuario['nome'] ?? $usuario['email'] ?? 'Test User',
    'email_doador' => $usuario['email'] ?? null,
    'exibir_mural' => 0,
];

try {
    $doacaoId = $doacaoModel->create($doacaoData);
    echo "Doação criada (ID: $doacaoId)\n";
} catch (Exception $e) {
    echo "Erro ao criar doação: " . $e->getMessage() . "\n";
    exit(1);
}

// Chamar método que cria assinatura (chamará createPlan + createOneStepSubscriptionLink)
try {
    echo "Chamando PagamentoController->criarAssinaturaCartaoDoacao($usuarioId, $doacaoId, $valorMensal)\n";
    $resp = $pagController->criarAssinaturaCartaoDoacao($usuarioId, $doacaoId, $valorMensal);

    $logPath = __DIR__ . '/../logs/production_subscription_' . date('Ymd_His') . '.log';
    @mkdir(dirname($logPath), 0755, true);
    file_put_contents($logPath, "RESP: " . print_r($resp, true));

    echo "Resposta da API salva em: $logPath\n";
    echo "Resposta (resumida):\n" . json_encode($resp) . "\n";

    // Atualizar doação com plan_id e payment_url se existentes
    $update = [];
    if (!empty($resp['_petfinder_plan_id'])) {
        $update['efi_plan_id'] = (int)$resp['_petfinder_plan_id'];
    }
    if (!empty($resp['payment_url'])) {
        $update['payment_url'] = $resp['payment_url'];
    }
    if (!empty($update)) {
        $doacaoModel->update((int)$doacaoId, $update);
        echo "Doação atualizada com dados da API.\n";
    }

    // Se a resposta indicar uma subscription_id, tentar cancelar imediatamente
    $subscriptionId = null;
    if (!empty($resp['subscription_id'])) {
        $subscriptionId = (string)$resp['subscription_id'];
    } elseif (!empty($resp['data']['subscription_id'])) {
        $subscriptionId = (string)$resp['data']['subscription_id'];
    }

    if ($subscriptionId !== null) {
        echo "Subscription ID encontrado: $subscriptionId. Tentando cancelar...\n";
        $api = $pagController->getApi();
        $cancelResp = $api->cancelSubscription(['id' => $subscriptionId]);
        file_put_contents($logPath, "\nCANCEL: " . print_r($cancelResp, true), FILE_APPEND);
        echo "Cancelamento enviado. Resposta: " . json_encode($cancelResp) . "\n";

        // Atualizar doação como cancelada
        $doacaoModel->update((int)$doacaoId, ['status' => 'cancelada', 'cancelada_em' => date('Y-m-d H:i:s')]);
        echo "Doação marcada como cancelada no BD.\n";
    } else {
        echo "Subscription ID não disponível na resposta — não foi possível cancelar automaticamente.\n";
        echo "Você deve acompanhar o payment_url e, após confirmação de assinatura, cancelar via admin ou endpoint de cancelamento.\n";
    }

} catch (Exception $e) {
    echo "Erro durante criação/cancelamento: " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/../logs/production_subscription_error_' . date('Ymd_His') . '.log', $e->getMessage());
    exit(1);
}

echo "Fim do teste de produção. Verifique logs e webhook para eventos.\n";

?>