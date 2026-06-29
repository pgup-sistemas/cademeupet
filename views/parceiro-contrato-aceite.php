<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Contrato de Parceria | Cadê Meu Pet?';

$usuarioId      = (int)(getUserId() ?? 0);
$usuarioModel   = new Usuario();
$inscricaoModel = new ParceiroInscricao();
$assinaturaModel = new ParceiroAssinatura();
$contratoModel  = new ParceiroContrato();

$usuario   = $usuarioModel->findById($usuarioId);
$inscricao = $inscricaoModel->findByUserId($usuarioId);

if (!$inscricao || $inscricao['status'] !== 'aprovada') {
    setFlashMessage('Acesso não autorizado.', MSG_WARNING);
    redirect('/parceiro/painel');
}

// Parâmetros vindos da página de pagamento via GET/sessão
$plano         = (string)($_GET['plano'] ?? $_SESSION['contrato_plano'] ?? 'basico');
$periodicidade = (string)($_GET['periodicidade'] ?? $_SESSION['contrato_periodicidade'] ?? 'mensal');
$metodo        = (string)($_GET['metodo'] ?? $_SESSION['contrato_metodo'] ?? 'pix');

$validPlanos         = ['basico', 'destaque'];
$validPeriodicidades = ['mensal', 'anual'];
$validMetodos        = ['pix', 'cartao_avista', 'cartao_recorrente'];

if (!in_array($plano, $validPlanos, true))         $plano         = 'basico';
if (!in_array($periodicidade, $validPeriodicidades, true)) $periodicidade = 'mensal';
if (!in_array($metodo, $validMetodos, true))       $metodo        = 'pix';

// Aplica regras de negócio de periodicidade (igual ao parceiro-pagamento)
if ($metodo === 'pix')               $periodicidade = 'anual';
if ($metodo === 'cartao_recorrente') $periodicidade = 'mensal';

$valorMensal = $plano === 'destaque'
    ? (float)getConfig('parceiro_plano_destaque_mensal', '129.90')
    : (float)getConfig('parceiro_plano_basico_mensal',   '79.90');

// ── POST: gravar aceite ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Falha na validação. Recarregue e tente novamente.', MSG_ERROR);
        redirect("/parceiro/contrato?plano={$plano}&periodicidade={$periodicidade}&metodo={$metodo}");
    }

    if (empty($_POST['aceito_termos'])) {
        setFlashMessage('Você precisa marcar que leu e aceita o contrato para continuar.', MSG_WARNING);
        redirect("/parceiro/contrato?plano={$plano}&periodicidade={$periodicidade}&metodo={$metodo}");
    }

    // Gera hash SHA-256 do conteúdo do contrato (identificador imutável desta versão)
    $hashContrato = hash('sha256',
        ParceiroContrato::VERSAO_ATUAL . '|' . $plano . '|' . $periodicidade . '|' . number_format($valorMensal, 2, '.', '')
    );

    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $ip = trim(explode(',', $ip)[0]);

    $contratoModel->registrarAceite([
        'usuario_id'      => $usuarioId,
        'versao_contrato' => ParceiroContrato::VERSAO_ATUAL,
        'plano'           => $plano,
        'periodicidade'   => $periodicidade,
        'valor_mensal'    => $valorMensal,
        'ip_aceite'       => $ip,
        'user_agent'      => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        'hash_contrato'   => $hashContrato,
    ]);

    // Marca sessão para o parceiro-pagamento saber que o contrato foi aceito
    $_SESSION['contrato_aceito'] = [
        'plano'         => $plano,
        'periodicidade' => $periodicidade,
        'metodo'        => $metodo,
        'ts'            => time(),
    ];

    redirect("/parceiro/pagamento?plano={$plano}&periodicidade={$periodicidade}&metodo={$metodo}");
}

// ── Verifica se já aceitou esta combinação ───────────────────────────────────
$aceiteExistente = $contratoModel->findAceiteAtivo($usuarioId, $plano, $periodicidade);

$breadcrumbs = [
    ['label' => 'Início',    'url' => BASE_URL],
    ['label' => 'Parceiros', 'url' => BASE_URL . '/parceiros'],
    ['label' => 'Pagamento', 'url' => BASE_URL . '/parceiro/pagamento'],
    ['label' => 'Contrato'],
];
include __DIR__ . '/../includes/header.php';

// Prepara variáveis para o template
$contrato_usuario       = $usuario;
$contrato_inscricao     = $inscricao;
$contrato_plano         = $plano;
$contrato_periodicidade = $periodicidade;
$contrato_valor_mensal  = $valorMensal;
$contrato_aceite        = null; // null = modo pré-assinatura
$contrato_versao        = ParceiroContrato::VERSAO_ATUAL;
?>

<style>
.contrato-body { font-size: .92rem; line-height: 1.75; color: #212529; }
.contrato-secao { font-size: 1rem; font-weight: 700; margin-top: 1.5rem; margin-bottom: .5rem; text-transform: uppercase; letter-spacing: .03em; }
.contrato-tabela td { font-size: .88rem; }
@media print {
    .no-print { display: none !important; }
    .contrato-card { border: none !important; box-shadow: none !important; }
    body { font-size: 12pt; }
}
</style>

<div class="container py-4" style="max-width: 860px;">

    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-4 no-print">
        <div>
            <h1 class="h4 fw-bold mb-1">Contrato de Parceria</h1>
            <p class="text-muted mb-0">
                Leia o contrato abaixo com atenção.
                O aceite é obrigatório para prosseguir com o pagamento.
            </p>
        </div>
        <a class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
           href="<?php echo url('/parceiro/pagamento'); ?>">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <?php if ($aceiteExistente): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 no-print mb-3">
        <i class="bi bi-check-circle-fill"></i>
        <span>
            Você já aceitou este contrato em
            <strong><?php echo date('d/m/Y \à\s H:i', strtotime($aceiteExistente['aceito_em'])); ?></strong>.
            Pode prosseguir para o pagamento ou aceitar novamente se preferir.
        </span>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 contrato-card mb-4">
        <div class="card-body p-4 p-md-5">
            <?php include __DIR__ . '/../includes/parceiro-contrato-template.php'; ?>
        </div>
    </div>

    <?php /* ── Barra de ações ── */ ?>
    <div class="card shadow-sm border-0 no-print">
        <div class="card-body p-4">
            <form method="POST" id="form-aceite">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="plano"         value="<?php echo sanitize($plano); ?>">
                <input type="hidden" name="periodicidade" value="<?php echo sanitize($periodicidade); ?>">
                <input type="hidden" name="metodo"        value="<?php echo sanitize($metodo); ?>">

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="aceito_termos" id="aceito_termos" value="1">
                    <label class="form-check-label fw-semibold" for="aceito_termos">
                        Li, compreendi e aceito os termos do contrato acima, incluindo
                        os valores, condições de cancelamento e política de privacidade (LGPD).
                    </label>
                </div>

                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <button type="submit"
                            class="btn btn-success d-inline-flex align-items-center gap-2"
                            id="btn-aceitar"
                            disabled>
                        <i class="bi bi-pen"></i> Aceitar e ir para pagamento
                    </button>

                    <a href="<?php echo url('/parceiro/contrato-pdf?plano=' . urlencode($plano) . '&periodicidade=' . urlencode($periodicidade)); ?>"
                       target="_blank"
                       class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-pdf"></i> Baixar PDF
                    </a>

                    <button type="button"
                            class="btn btn-outline-secondary d-inline-flex align-items-center gap-2"
                            onclick="window.print()">
                        <i class="bi bi-printer"></i> Imprimir
                    </button>
                </div>

                <div class="text-muted small mt-2">
                    <i class="bi bi-shield-lock me-1"></i>
                    Seu aceite é registrado com IP, data/hora e versão do contrato — evidência jurídica válida no Marco Civil da Internet.
                </div>
            </form>
        </div>
    </div>

    <?php if ($aceiteExistente): ?>
    <div class="mt-3 no-print">
        <a href="<?php echo url('/parceiro/pagamento?plano=' . urlencode($plano) . '&periodicidade=' . urlencode($periodicidade) . '&metodo=' . urlencode($metodo) . '&contrato_ok=1'); ?>"
           class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-right-circle"></i> Já aceitei — ir direto para pagamento
        </a>
    </div>
    <?php endif; ?>

</div>

<script>
(function () {
    var check = document.getElementById('aceito_termos');
    var btn   = document.getElementById('btn-aceitar');
    if (!check || !btn) return;
    check.addEventListener('change', function () {
        btn.disabled = !this.checked;
    });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
