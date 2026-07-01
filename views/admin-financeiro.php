<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$pageTitle = 'Admin - Financeiro - Cadê Meu Pet?';

$doacaoController = new DoacaoController();
$doacaoModel = new Doacao();

$parceiroPagamentoModel = new ParceiroPagamento();
$parceiroAssinaturaModel = new ParceiroAssinatura();

$metaAtual = $doacaoController->metaAtual();

$tab = isset($_GET['tab']) ? trim((string)$_GET['tab']) : 'doacoes';
$tab = in_array($tab, ['doacoes', 'parceiros'], true) ? $tab : 'doacoes';

$status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$allowedStatus = ['', 'pendente', 'aprovada', 'cancelada', 'estornada'];
if (!in_array($status, $allowedStatus, true)) {
    $status = '';
}

$partnerStatus = isset($_GET['partner_status']) ? trim((string)$_GET['partner_status']) : '';
$allowedPartnerStatus = ['', 'pendente', 'aprovado', 'recusado'];
if (!in_array($partnerStatus, $allowedPartnerStatus, true)) {
    $partnerStatus = '';
}

$partnerMes = isset($_GET['partner_mes']) ? trim((string)$_GET['partner_mes']) : '';
if ($partnerMes !== '' && !preg_match('/^\d{4}-\d{2}$/', $partnerMes)) {
    $partnerMes = '';
}

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max(1, $pagina);
$limite = 30;
$offset = ($pagina - 1) * $limite;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Falha na validação do formulário. Recarregue a página.', MSG_ERROR);
        redirect('/admin/financeiro');
    }

    $acao = (string)($_POST['acao'] ?? '');

    if ($acao === 'update_goal') {
        $metaId = (int)($_POST['meta_id'] ?? 0);
        $valorMeta = (float)($_POST['valor_meta'] ?? 0);
        $custosServidor = (float)($_POST['custos_servidor'] ?? 0);
        $custosManutencao = (float)($_POST['custos_manutencao'] ?? 0);
        $custosOutros = (float)($_POST['custos_outros'] ?? 0);
        $descricao = sanitize($_POST['descricao'] ?? '');

        if ($metaId > 0) {
            $db = getDB();
            $db->update(
                'metas_financeiras',
                [
                    'valor_meta' => $valorMeta,
                    'custos_servidor' => $custosServidor,
                    'custos_manutencao' => $custosManutencao,
                    'custos_outros' => $custosOutros,
                    'descricao' => $descricao,
                ],
                'id = ?',
                [$metaId]
            );
            setFlashMessage('Meta financeira atualizada.', MSG_SUCCESS);
        }

        redirect('/admin/financeiro');
    }

    if ($acao === 'set_donation_status') {
        $id = (int)($_POST['id'] ?? 0);
        $novoStatus = (string)($_POST['status'] ?? '');
        if ($id > 0 && in_array($novoStatus, ['pendente', 'aprovada', 'cancelada', 'estornada'], true)) {
            $doacao = $doacaoModel->findById($id);
            if ($doacao) {
                $doacaoModel->updateStatus($id, $novoStatus);
                setFlashMessage('Status da doação atualizado.', MSG_SUCCESS);
            }
        }
        redirect('/admin/financeiro');
    }

    setFlashMessage('Ação inválida.', MSG_ERROR);
    redirect('/admin/financeiro');
}

$total = $doacaoModel->countAll($status !== '' ? $status : null);
$doacoes = $doacaoModel->findAll($limite, $offset, $status !== '' ? $status : null);
$totalPaginas = (int)ceil($total / $limite);

$partnerTotal = $parceiroPagamentoModel->countAll($partnerStatus !== '' ? $partnerStatus : null, $partnerMes !== '' ? $partnerMes : null);
$partnerPagamentos = $parceiroPagamentoModel->findAll($limite, $offset, $partnerStatus !== '' ? $partnerStatus : null, $partnerMes !== '' ? $partnerMes : null);
$partnerTotalPaginas = (int)ceil($partnerTotal / $limite);

$doacaoResumo = $doacaoModel->getDashboardSummary();
$parceiroResumo = $parceiroPagamentoModel->getDashboardSummary();
$assinaturaResumo = $parceiroAssinaturaModel->getDashboardSummary(7);
$assinaturasExpirando = $parceiroAssinaturaModel->listExpiringSoon(7, 10);

$totalGeralMesAtual = (float)($doacaoResumo['mes_atual'] ?? 0) + (float)($parceiroResumo['mes_atual'] ?? 0);

$breadcrumbs = [
    ['label' => 'Início',     'url' => BASE_URL],
    ['label' => 'Admin',      'url' => BASE_URL . '/admin'],
    ['label' => 'Financeiro'],
];
$suppressBreadcrumbBar = true;
include __DIR__ . '/../includes/header.php';

// Verificar disponibilidade da integração com EFI (para pagamentos por cartão)
$efiAvailable = false;
try {
    $efiAvailable = (class_exists('Efi\\EfiPay') || class_exists('EfiPay'))
        && !empty(EFI_CLIENT_ID) && !empty(EFI_CLIENT_SECRET)
        && file_exists((string)EFI_CERTIFICATE_PATH);
} catch (Throwable $ex) {
    $efiAvailable = false;
}

?>

<div class="admin-layout">

    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

    <div class="admin-main py-4 px-4">

    <!-- Topbar mobile -->
    <div class="d-flex d-lg-none align-items-center gap-2 mb-3 flex-wrap">
        <a href="<?php echo BASE_URL; ?>/admin"            class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-gauge"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/usuarios"   class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-users"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/anuncios"   class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-list"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/moderacao"  class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-shield-halved"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/financeiro" class="btn btn-sm btn-primary"><i class="fa-solid fa-chart-line"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/parceiros"  class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-handshake"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/config"     class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-gear"></i></a>
    </div>

    <?php include __DIR__ . '/../includes/admin-breadcrumb.php'; ?>

    <?php if (! $efiAvailable): ?>
        <div class="alert alert-warning">
            <strong>Atenção:</strong> A integração com o gateway EFI parece estar indisponível (SDK, credenciais ou certificado ausente). Pagamentos por cartão podem falhar. Verifique a instalação do Composer, as credenciais `EFI_CLIENT_ID` / `EFI_CLIENT_SECRET` e o caminho `EFI_CERTIFICATE_PATH`.
        </div>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">Admin · Financeiro</h1>
            <p class="text-muted mb-0">Meta mensal, doações e pagamentos de parceiros.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="fw-bold mb-2">Doações</div>
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Total aprovado</div>
                            <div class="h5 mb-0"><?php echo formatMoney((float)($doacaoResumo['total_aprovado'] ?? 0)); ?></div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Mês atual</div>
                            <div class="h5 mb-0"><?php echo formatMoney((float)($doacaoResumo['mes_atual'] ?? 0)); ?></div>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2 flex-wrap">
                        <span class="badge bg-light text-dark">Total: <?php echo (int)($doacaoResumo['total_doacoes'] ?? 0); ?></span>
                        <span class="badge bg-light text-dark">Recorrente: <?php echo formatMoney((float)($doacaoResumo['recorrente'] ?? 0)); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="fw-bold mb-2">Parceiros</div>
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Total aprovado</div>
                            <div class="h5 mb-0"><?php echo formatMoney((float)($parceiroResumo['total_aprovado'] ?? 0)); ?></div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Mês atual</div>
                            <div class="h5 mb-0"><?php echo formatMoney((float)($parceiroResumo['mes_atual'] ?? 0)); ?></div>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2 flex-wrap">
                        <span class="badge bg-light text-dark">Pagamentos: <?php echo (int)($parceiroResumo['total_pagamentos'] ?? 0); ?></span>
                        <a class="badge bg-light text-dark text-decoration-none" href="<?php echo BASE_URL; ?>/admin/financeiro?tab=parceiros&partner_status=pendente">Pendentes: <?php echo (int)($parceiroResumo['pendentes'] ?? 0); ?></a>
                        <a class="badge bg-light text-dark text-decoration-none" href="<?php echo BASE_URL; ?>/admin/financeiro?tab=parceiros&partner_status=aprovado">Aprovados: <?php echo (int)($parceiroResumo['aprovados'] ?? 0); ?></a>
                        <a class="badge bg-light text-dark text-decoration-none" href="<?php echo BASE_URL; ?>/admin/financeiro?tab=parceiros&partner_status=recusado">Recusados: <?php echo (int)($parceiroResumo['recusados'] ?? 0); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="fw-bold mb-2">Total geral (mês atual)</div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <div class="text-muted small">Doações + Parceiros</div>
                            <div class="h4 mb-0"><?php echo formatMoney((float)$totalGeralMesAtual); ?></div>
                        </div>
                        <div class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>/admin/financeiro?tab=parceiros">Ver parceiros</a>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2 flex-wrap">
                        <span class="badge bg-light text-dark">Doações (mês): <?php echo formatMoney((float)($doacaoResumo['mes_atual'] ?? 0)); ?></span>
                        <span class="badge bg-light text-dark">Parceiros (mês): <?php echo formatMoney((float)($parceiroResumo['mes_atual'] ?? 0)); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="fw-bold mb-2">Assinaturas de parceiros</div>
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Ativas</div>
                            <div class="h5 mb-0"><?php echo (int)($assinaturaResumo['ativas'] ?? 0); ?></div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Expirando (7 dias)</div>
                            <div class="h5 mb-0"><?php echo (int)($assinaturaResumo['expirando'] ?? 0); ?></div>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2 flex-wrap">
                        <span class="badge bg-light text-dark">Pendentes: <?php echo (int)($assinaturaResumo['pendentes'] ?? 0); ?></span>
                        <span class="badge bg-light text-dark">Suspensas: <?php echo (int)($assinaturaResumo['suspensas'] ?? 0); ?></span>
                        <span class="badge bg-light text-dark">Canceladas: <?php echo (int)($assinaturaResumo['canceladas'] ?? 0); ?></span>
                    </div>

                    <div class="mt-3">
                        <div class="text-muted small mb-2">Expirando em breve</div>
                        <?php if (empty($assinaturasExpirando)): ?>
                            <div class="text-muted small">Nenhuma assinatura expirando.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Parceiro</th>
                                            <th class="text-end">Pago até</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assinaturasExpirando as $a): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?php echo sanitize((string)($a['usuario_nome'] ?? '')); ?></div>
                                                    <div class="text-muted small"><?php echo sanitize((string)($a['email'] ?? '')); ?></div>
                                                </td>
                                                <td class="text-end text-muted small"><?php echo !empty($a['pago_ate']) ? date('d/m/Y', strtotime($a['pago_ate'])) : '-'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-3">
                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo BASE_URL; ?>/admin/parceiros">Gerenciar parceiros</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">Meta financeira atual</h2>

            <?php if (empty($metaAtual)): ?>
                <p class="text-muted mb-0">Nenhuma meta ativa cadastrada.</p>
            <?php else: ?>
                <form method="POST" action="" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="acao" value="update_goal">
                    <input type="hidden" name="meta_id" value="<?php echo (int)($metaAtual['id'] ?? 0); ?>">

                    <div class="col-md-3">
                        <label for="meta-valor" class="form-label">Valor meta</label>
                        <input type="number" id="meta-valor" step="0.01" name="valor_meta" class="form-control" value="<?php echo sanitize((string)($metaAtual['valor_meta'] ?? 0)); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="meta-servidor" class="form-label">Custos servidor</label>
                        <input type="number" id="meta-servidor" step="0.01" name="custos_servidor" class="form-control" value="<?php echo sanitize((string)($metaAtual['custos_servidor'] ?? 0)); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="meta-manutencao" class="form-label">Custos manutenção</label>
                        <input type="number" id="meta-manutencao" step="0.01" name="custos_manutencao" class="form-control" value="<?php echo sanitize((string)($metaAtual['custos_manutencao'] ?? 0)); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="meta-outros" class="form-label">Custos outros</label>
                        <input type="number" id="meta-outros" step="0.01" name="custos_outros" class="form-control" value="<?php echo sanitize((string)($metaAtual['custos_outros'] ?? 0)); ?>">
                    </div>

                    <div class="col-12">
                        <label for="meta-descricao" class="form-label">Descrição</label>
                        <textarea id="meta-descricao" name="descricao" class="form-control" rows="2"><?php echo sanitize((string)($metaAtual['descricao'] ?? '')); ?></textarea>
                    </div>

                    <div class="col-12 d-grid d-md-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Salvar meta</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'doacoes' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/financeiro?tab=doacoes">Doações</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'parceiros' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/financeiro?tab=parceiros">Parceiros</a>
        </li>
    </ul>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <?php if ($tab === 'doacoes'): ?>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h2 class="h6 fw-bold mb-0">Doações</h2>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php
                            $exportDoacoesBase = [
                                'tab' => 'doacoes',
                            ];
                            if ($status !== '') {
                                $exportDoacoesBase['status'] = $status;
                            }
                            $exportDoacoesCsv = $exportDoacoesBase;
                            $exportDoacoesCsv['format'] = 'csv';
                            $exportDoacoesPdf = $exportDoacoesBase;
                            $exportDoacoesPdf['format'] = 'pdf';
                        ?>
                        <a class="btn btn-outline-secondary" href="<?php echo BASE_URL . '/admin/financeiro/export?' . http_build_query($exportDoacoesCsv); ?>">Exportar CSV</a>
                        <a class="btn btn-outline-secondary" href="<?php echo BASE_URL . '/admin/financeiro/export?' . http_build_query($exportDoacoesPdf); ?>">Exportar PDF</a>

                        <form method="GET" action="" class="d-flex gap-2">
                        <input type="hidden" name="tab" value="doacoes">
                        <select name="status" class="form-select">
                            <?php foreach ($allowedStatus as $s): ?>
                                <option value="<?php echo sanitize($s); ?>" <?php echo $s === $status ? 'selected' : ''; ?>>
                                    <?php echo $s === '' ? 'Todos' : ucfirst($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-primary" type="submit">Filtrar</button>
                        </form>
                    </div>
                </div>

                <?php if (empty($doacoes)): ?>
                    <p class="text-muted mb-0">Nenhuma doação encontrada.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Valor</th>
                                <th>Doador</th>
                                <th>Método</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doacoes as $d): ?>
                                <tr>
                                    <td><?php echo (int)$d['id']; ?></td>
                                    <td><?php echo !empty($d['data_doacao']) ? date('d/m/Y H:i', strtotime($d['data_doacao'])) : '-'; ?></td>
                                    <td><?php echo formatMoney((float)($d['valor'] ?? 0)); ?></td>
                                    <td><?php echo sanitize($d['nome_doador'] ?? ''); ?></td>
                                    <td><?php echo sanitize($d['metodo_pagamento'] ?? ''); ?></td>
                                    <td><span class="badge bg-light text-dark"><?php echo sanitize($d['status'] ?? ''); ?></span></td>
                                    <td class="text-end">
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="acao" value="set_donation_status">
                                            <input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
                                            <select name="status" class="form-select form-select-sm d-inline" style="width: auto; display: inline-block;">
                                                <?php foreach (['pendente', 'aprovada', 'cancelada', 'estornada'] as $st): ?>
                                                    <option value="<?php echo $st; ?>" <?php echo ($d['status'] ?? '') === $st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-sm btn-outline-primary" type="submit">Salvar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPaginas > 1): ?>
                    <?php
                        $prev = max(1, $pagina - 1);
                        $next = min($totalPaginas, $pagina + 1);
                        $qs = 'tab=doacoes';
                        if ($status !== '') {
                            $qs .= '&status=' . urlencode($status);
                        }
                    ?>
                    <nav class="mt-3" aria-label="Paginação">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo BASE_URL . '/admin/financeiro?pagina=' . $prev . '&' . $qs; ?>">Anterior</a>
                            </li>
                            <li class="page-item disabled"><span class="page-link"><?php echo $pagina; ?> / <?php echo $totalPaginas; ?></span></li>
                            <li class="page-item <?php echo $pagina >= $totalPaginas ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo BASE_URL . '/admin/financeiro?pagina=' . $next . '&' . $qs; ?>">Próxima</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
                <?php endif; ?>

            <?php else: ?>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h2 class="h6 fw-bold mb-0">Pagamentos de Parceiros</h2>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php
                            $exportParceirosBase = [
                                'tab' => 'parceiros',
                            ];
                            if ($partnerMes !== '') {
                                $exportParceirosBase['partner_mes'] = $partnerMes;
                            }
                            if ($partnerStatus !== '') {
                                $exportParceirosBase['partner_status'] = $partnerStatus;
                            }
                            $exportParceirosCsv = $exportParceirosBase;
                            $exportParceirosCsv['format'] = 'csv';
                            $exportParceirosPdf = $exportParceirosBase;
                            $exportParceirosPdf['format'] = 'pdf';
                        ?>
                        <a class="btn btn-outline-secondary" href="<?php echo BASE_URL . '/admin/financeiro/export?' . http_build_query($exportParceirosCsv); ?>">Exportar CSV</a>
                        <a class="btn btn-outline-secondary" href="<?php echo BASE_URL . '/admin/financeiro/export?' . http_build_query($exportParceirosPdf); ?>">Exportar PDF</a>

                        <form method="GET" action="" class="d-flex gap-2 flex-wrap">
                        <input type="hidden" name="tab" value="parceiros">
                        <input type="month" name="partner_mes" class="form-control" value="<?php echo sanitize($partnerMes); ?>">
                        <select name="partner_status" class="form-select">
                            <?php foreach ($allowedPartnerStatus as $s): ?>
                                <option value="<?php echo sanitize($s); ?>" <?php echo $s === $partnerStatus ? 'selected' : ''; ?>>
                                    <?php echo $s === '' ? 'Todos' : ucfirst($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-primary" type="submit">Filtrar</button>
                        </form>
                    </div>
                </div>

                <?php if (empty($partnerPagamentos)): ?>
                    <p class="text-muted mb-0">Nenhum pagamento de parceiro encontrado.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Data</th>
                                    <th>Usuário</th>
                                    <th>Plano</th>
                                    <th>Método</th>
                                    <th>Periodicidade</th>
                                    <th>Valor</th>
                                    <th>Referência</th>
                                    <th>Efí Charge</th>
                                    <th>Efí Subscription</th>
                                    <th>Status</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($partnerPagamentos as $p): ?>
                                    <tr>
                                        <td><?php echo (int)$p['id']; ?></td>
                                        <td class="text-muted small"><?php echo !empty($p['data_criacao']) ? date('d/m/Y H:i', strtotime($p['data_criacao'])) : '-'; ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo sanitize($p['usuario_nome'] ?? ''); ?></div>
                                            <div class="text-muted small"><?php echo sanitize($p['email'] ?? ''); ?></div>
                                        </td>
                                        <td><span class="badge bg-light text-dark"><?php echo sanitize((string)($p['plano'] ?? '')); ?></span></td>
                                        <td class="text-muted small"><?php echo sanitize((string)($p['gateway_tipo'] ?? $p['metodo'] ?? '')); ?></td>
                                        <td class="text-muted small"><?php echo sanitize((string)($p['periodicidade'] ?? '')); ?></td>
                                        <td><?php echo formatMoney((float)($p['valor'] ?? 0)); ?></td>
                                        <td class="text-muted small"><?php echo sanitize((string)($p['referencia'] ?? $p['efi_charge_id'] ?? '')); ?></td>
                                        <td class="text-muted small"><?php echo sanitize((string)($p['efi_charge_id'] ?? '')); ?></td>
                                        <td class="text-muted small"><?php echo sanitize((string)($p['efi_subscription_id'] ?? '')); ?></td>
                                        <td><span class="badge bg-light text-dark"><?php echo sanitize((string)($p['status'] ?? '')); ?></span></td>
                                        <td class="text-end">
                                            <?php if (!empty($p['payment_url'])): ?>
                                                <a class="btn btn-sm btn-outline-primary" href="<?php echo sanitize((string)$p['payment_url']); ?>" target="_blank" rel="noopener noreferrer">Abrir checkout</a>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($partnerTotalPaginas > 1): ?>
                        <?php
                            $prev = max(1, $pagina - 1);
                            $next = min($partnerTotalPaginas, $pagina + 1);

                            $qs = 'tab=parceiros';
                            if ($partnerStatus !== '') {
                                $qs .= '&partner_status=' . urlencode($partnerStatus);
                            }
                            if ($partnerMes !== '') {
                                $qs .= '&partner_mes=' . urlencode($partnerMes);
                            }
                        ?>
                        <nav class="mt-3" aria-label="Paginação">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo BASE_URL . '/admin/financeiro?pagina=' . $prev . '&' . $qs; ?>">Anterior</a>
                                </li>
                                <li class="page-item disabled"><span class="page-link"><?php echo $pagina; ?> / <?php echo $partnerTotalPaginas; ?></span></li>
                                <li class="page-item <?php echo $pagina >= $partnerTotalPaginas ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo BASE_URL . '/admin/financeiro?pagina=' . $next . '&' . $qs; ?>">Próxima</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    </div><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
