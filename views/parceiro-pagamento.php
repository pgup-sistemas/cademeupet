<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Pagamento Parceiro | Cadê Meu Pet?';

$usuarioId = (int)(getUserId() ?? 0);
$usuarioModel = new Usuario();
$inscricaoModel = new ParceiroInscricao();
$perfilModel = new ParceiroPerfil();
$assinaturaModel = new ParceiroAssinatura();
$pagamentoModel = new ParceiroPagamento();

$usuario = $usuarioModel->findById($usuarioId);
$inscricao = $inscricaoModel->findByUserId($usuarioId);
$perfil = $perfilModel->findByUserId($usuarioId);
$assinatura = $assinaturaModel->findByUserId($usuarioId);

if (!$inscricao || $inscricao['status'] !== 'aprovada') {
    setFlashMessage('Seu acesso de parceiro ainda não foi aprovado.', MSG_WARNING);
    redirect('/parceiro/painel');
}

$defaultPlano         = $assinatura['plano'] ?? 'basico';
$defaultPeriodicidade = $assinatura['periodicidade'] ?? 'mensal';
$defaultValorBasico   = (float)getConfig('parceiro_plano_basico_mensal',   '79.90');
$defaultValorDestaque = (float)getConfig('parceiro_plano_destaque_mensal', '129.90');
$defaultMetodoPagamento = 'pix';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // No POST, verifica se o contrato foi aceito para a combinação plano+periodicidade enviada
    $postPlano         = in_array($_POST['plano'] ?? '', ['basico','destaque'], true) ? $_POST['plano'] : $defaultPlano;
    $postMetodo        = in_array($_POST['metodo_pagamento'] ?? '', ['pix','cartao_avista','cartao_recorrente'], true) ? $_POST['metodo_pagamento'] : $defaultMetodoPagamento;
    $postPeriodicidade = in_array($_POST['periodicidade'] ?? '', ['mensal','anual'], true) ? $_POST['periodicidade'] : $defaultPeriodicidade;
    if ($postMetodo === 'pix')               $postPeriodicidade = 'anual';
    if ($postMetodo === 'cartao_recorrente') $postPeriodicidade = 'mensal';

    $contratoModel = new ParceiroContrato();
    if (!$contratoModel->findAceiteAtivo($usuarioId, $postPlano, $postPeriodicidade)) {
        // Ainda não aceitou — redireciona para leitura do contrato
        redirect('/parceiro/contrato?plano=' . urlencode($postPlano) . '&periodicidade=' . urlencode($postPeriodicidade) . '&metodo=' . urlencode($postMetodo));
    }

    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Falha na validação do formulário. Recarregue e tente novamente.', MSG_ERROR);
        redirect('/parceiro/pagamento');
    }

    $plano = (string)($_POST['plano'] ?? $defaultPlano);
    $validPlanos = ['basico', 'destaque'];
    if (!in_array($plano, $validPlanos, true)) {
        $plano = 'basico';
    }

    $periodicidade = (string)($_POST['periodicidade'] ?? $defaultPeriodicidade);
    $validPeriodicidades = ['mensal', 'anual'];
    if (!in_array($periodicidade, $validPeriodicidades, true)) {
        $periodicidade = 'mensal';
    }

    $metodoPagamento = (string)($_POST['metodo_pagamento'] ?? $defaultMetodoPagamento);
    $validMetodosPagamento = ['pix', 'cartao_avista', 'cartao_recorrente'];
    if (!in_array($metodoPagamento, $validMetodosPagamento, true)) {
        $metodoPagamento = 'pix';
    }

    // Regras de negócio:
    // - Pix: apenas valor total (anual)
    // - Cartão recorrente: mensal
    // - Cartão à vista: permite mensal (total do mês) ou anual (total do ano)
    if ($metodoPagamento === 'pix') {
        $periodicidade = 'anual';
    } elseif ($metodoPagamento === 'cartao_recorrente') {
        $periodicidade = 'mensal';
    }

    $valorMensalPlano = $plano === 'destaque' ? $defaultValorDestaque : $defaultValorBasico;
    $valor = $periodicidade === 'anual' ? ($valorMensalPlano * 12) : $valorMensalPlano;

    try {
        $pagamentoModel->closePendingForUser($usuarioId);

        if (!$assinatura) {
            $assinaturaModel->create([
                'usuario_id' => $usuarioId,
                'plano' => $plano,
                'periodicidade' => $periodicidade,
                'valor_mensal' => $valorMensalPlano,
                'status' => 'pendente_pagamento',
                'metodo_pagamento' => 'gateway',
            ]);
        } else {
            $assinaturaModel->updateForUser($usuarioId, [
                'plano' => $plano,
                'periodicidade' => $periodicidade,
                'valor_mensal' => $valorMensalPlano,
                'status' => ($assinatura['status'] === 'ativa') ? 'ativa' : 'pendente_pagamento',
                'metodo_pagamento' => 'gateway',
            ]);
        }

        $pagamentoId = $pagamentoModel->create([
            'usuario_id' => $usuarioId,
            'plano' => $plano,
            'periodicidade' => $periodicidade,
            'gateway_tipo' => $metodoPagamento,
            'valor' => $valor,
            'metodo' => 'gateway',
            'status' => 'pendente',
        ]);

        $pagamentoController = new PagamentoController();

        if ($metodoPagamento === 'pix') {
            $descricaoPix = 'Assinatura Parceiro Cadê Meu Pet? #' . (int)$pagamentoId;
            $pix = $pagamentoController->criarCobrancaPixParceiro((int)$pagamentoId, (float)$valor, $descricaoPix);

            $pagamentoModel->update((int)$pagamentoId, [
                'referencia' => (string)$pix['txid'],
                'comprovante_texto' => (string)($pix['qrcode']['qrcode'] ?? null),
            ]);

            if (!isset($_SESSION['pix_parceiros']) || !is_array($_SESSION['pix_parceiros'])) {
                $_SESSION['pix_parceiros'] = [];
            }
            $_SESSION['pix_parceiros'][(int)$pagamentoId] = [
                'txid' => (string)$pix['txid'],
                'qrcode' => $pix['qrcode'],
            ];

            redirect('/parceiro/pix?id=' . (int)$pagamentoId);
        }

        if ($metodoPagamento === 'cartao_recorrente') {
            $resp = $pagamentoController->criarAssinaturaCartaoParceiro($usuarioId, (int)$pagamentoId, (float)$valorMensalPlano, (string)$plano);
            $paymentUrl = (string)($resp['data']['payment_url'] ?? '');
            $subscriptionId = (string)($resp['data']['subscription_id'] ?? '');
            $chargeId = (string)($resp['data']['charge']['id'] ?? '');

            $pagamentoModel->update((int)$pagamentoId, [
                'payment_url' => $paymentUrl !== '' ? $paymentUrl : null,
                'efi_subscription_id' => $subscriptionId !== '' ? $subscriptionId : null,
                'efi_charge_id' => $chargeId !== '' ? $chargeId : null,
            ]);
            if ($paymentUrl !== '') {
                redirect('/parceiro-abrir-pagamento.php?id=' . (int)$pagamentoId);
            }
            throw new Exception('Não foi possível gerar o link de pagamento do cartão recorrente.');
        }

        $resp = $pagamentoController->criarLinkPagamentoParceiro((int)$pagamentoId, (float)$valor, $metodoPagamento, $periodicidade);
        $paymentUrl = (string)($resp['data']['payment_url'] ?? ($resp['payment_url'] ?? ''));
        $chargeId = (string)($resp['data']['charge_id'] ?? ($resp['data']['charge']['id'] ?? ''));

        $pagamentoModel->update((int)$pagamentoId, [
            'payment_url' => $paymentUrl !== '' ? $paymentUrl : null,
            'efi_charge_id' => $chargeId !== '' ? $chargeId : null,
        ]);

        if ($paymentUrl !== '') {
            redirect('/parceiro-abrir-pagamento.php?id=' . (int)$pagamentoId);
        }

        throw new Exception('Não foi possível gerar o link de pagamento.');
    } catch (Throwable $e) {
        error_log('[Parceiro Pagamento] enviar: ' . $e->getMessage());

        $msg = 'Erro ao iniciar pagamento. Verifique as credenciais da Efí e tente novamente.';
        if (defined('APP_ENV') && APP_ENV !== 'production') {
            $msg .= ' Detalhe: ' . $e->getMessage();
        }

        setFlashMessage($msg, MSG_ERROR);
        redirect('/parceiro/pagamento');
    }
}

$breadcrumbs = [
    ['label' => 'Início',    'url' => BASE_URL],
    ['label' => 'Parceiros', 'url' => BASE_URL . '/parceiros'],
    ['label' => 'Pagamento'],
];
include __DIR__ . '/../includes/header.php';
?>

<?php
$metodoPagamentoAtual = (string)($_POST['metodo_pagamento'] ?? $defaultMetodoPagamento);
if (!in_array($metodoPagamentoAtual, ['pix', 'cartao_avista', 'cartao_recorrente'], true)) {
    $metodoPagamentoAtual = 'pix';
}
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Pagamento do Parceiro</h1>
            <p class="text-muted mb-0">Escolha Pix (à vista) ou Cartão (à vista ou recorrente). Após a confirmação, sua assinatura é ativada automaticamente.</p>
        </div>
        <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>/parceiro/painel">Voltar ao painel</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Pagamento</h2>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Método de pagamento</label>
                                <?php
                                    // Verificação simplificada da disponibilidade do EFI
                                    $efiAvailable = false;
                                    try {
                                        $efiAvailable = (class_exists('Efi\\EfiPay') || class_exists('EfiPay'))
                                            && !empty(EFI_CLIENT_ID) && !empty(EFI_CLIENT_SECRET)
                                            && file_exists((string)EFI_CERTIFICATE_PATH);
                                    } catch (Throwable $ex) {
                                        $efiAvailable = false;
                                    }
                                ?>

                                <select name="metodo_pagamento" id="metodo_pagamento" class="form-select">
                                    <option value="pix" <?php echo $metodoPagamentoAtual === 'pix' ? 'selected' : ''; ?>>Pix (à vista)</option>
                                    <?php if ($efiAvailable): ?>
                                        <option value="cartao_avista" <?php echo $metodoPagamentoAtual === 'cartao_avista' ? 'selected' : ''; ?>>Cartão (à vista)</option>
                                        <option value="cartao_recorrente" <?php echo $metodoPagamentoAtual === 'cartao_recorrente' ? 'selected' : ''; ?>>Cartão (recorrente)</option>
                                    <?php endif; ?>
                                </select>

                                <?php if (!$efiAvailable): ?>
                                    <div class="alert alert-warning mt-2">Pagamentos por cartão estão temporariamente indisponíveis. Contate o administrador.</div>
                                <?php else: ?>
                                    <div class="form-text">No Pix o pagamento é à vista. No cartão recorrente a cobrança é mensal automática.</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Plano</label>
                                <?php $planoAtual = (string)($assinatura['plano'] ?? 'basico'); ?>
                                <select name="plano" id="plano" class="form-select">
                                    <option value="basico" <?php echo $planoAtual === 'basico' ? 'selected' : ''; ?>>Básico</option>
                                    <option value="destaque" <?php echo $planoAtual === 'destaque' ? 'selected' : ''; ?>>Destaque</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Periodicidade</label>
                                <?php $periodicidadeAtual = (string)($assinatura['periodicidade'] ?? 'mensal'); ?>
                                <select name="periodicidade" id="periodicidade" class="form-select">
                                    <option value="mensal" <?php echo $periodicidadeAtual === 'mensal' ? 'selected' : ''; ?>>Mensal</option>
                                    <option value="anual" <?php echo $periodicidadeAtual === 'anual' ? 'selected' : ''; ?>>Anual</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Valor</label>
                                <?php $valorMensalTela = ($planoAtual === 'destaque') ? $defaultValorDestaque : $defaultValorBasico; ?>
                                <?php $valorTela = ($periodicidadeAtual === 'anual') ? ($valorMensalTela * 12) : $valorMensalTela; ?>
                                <input type="text" id="valor" class="form-control" value="R$ <?php echo sanitize(number_format($valorTela, 2, ',', '.')); ?>" readonly>
                                <div class="form-text">O valor é calculado automaticamente pelo plano e periodicidade.</div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-success btn-lg">Continuar</button>
                            <a href="<?php echo BASE_URL; ?>/parceiro/perfil" class="btn btn-outline-secondary btn-lg">Editar perfil</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-2">Status da assinatura</h2>
                    <?php if ($assinatura): ?>
                        <div class="text-muted small">Plano: <strong><?php echo sanitize((string)$assinatura['plano']); ?></strong></div>
                        <div class="text-muted small">Status: <strong><?php echo sanitize((string)$assinatura['status']); ?></strong></div>
                        <?php if (!empty($assinatura['pago_ate'])): ?>
                            <div class="text-muted small">Pago até: <strong><?php echo formatDateBR($assinatura['pago_ate']); ?></strong></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-muted small">Você ainda não possui assinatura registrada.</div>
                    <?php endif; ?>

                    <hr>

                    <div class="text-muted small">
                        Após o pagamento ser confirmado, sua assinatura é ativada.
                        O admin é notificado e seu perfil pode ser publicado e exibido na página de Parceiros.
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-3">
                <div class="card-body p-4">
                    <div class="fw-bold mb-2">Precisa de ajuda?</div>
                    <div class="text-muted small mb-3">Leia o passo-a-passo em Ajuda.</div>
                    <a class="btn btn-outline-primary w-100" href="<?php echo BASE_URL; ?>/ajuda">Ver Ajuda</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var metodo = document.getElementById('metodo_pagamento');
        var plano = document.getElementById('plano');
        var periodicidade = document.getElementById('periodicidade');
        var valor = document.getElementById('valor');

        if (!metodo || !plano || !periodicidade || !valor) {
            return;
        }

        function parseBRL(n) {
            return 'R$ ' + n.toFixed(2).replace('.', ',');
        }

        function getValorMensalPlano() {
            return plano.value === 'destaque' ? <?php echo json_encode($defaultValorDestaque); ?> : <?php echo json_encode($defaultValorBasico); ?>;
        }

        function applyRules() {
            if (metodo.value === 'pix') {
                periodicidade.value = 'anual';
                periodicidade.disabled = true;
            } else if (metodo.value === 'cartao_recorrente') {
                periodicidade.value = 'mensal';
                periodicidade.disabled = true;
            } else {
                periodicidade.disabled = false;
            }

            var mensal = getValorMensalPlano();
            var total = periodicidade.value === 'anual' ? (mensal * 12) : mensal;
            valor.value = parseBRL(total);
        }

        metodo.addEventListener('change', applyRules);
        plano.addEventListener('change', applyRules);
        periodicidade.addEventListener('change', applyRules);
        applyRules();
    })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
