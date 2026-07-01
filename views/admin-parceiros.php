<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$pageTitle = 'Admin - Parceiros | Cadê Meu Pet?';

$inscricaoModel = new ParceiroInscricao();
$perfilModel = new ParceiroPerfil();
$assinaturaModel = new ParceiroAssinatura();
$pagamentoModel = new ParceiroPagamento();
$usuarioModel = new Usuario();

$adminId = (int)(getUserId() ?? 0);

$tab = isset($_GET['tab']) ? (string)$_GET['tab'] : 'inscricoes';
$tab = in_array($tab, ['inscricoes', 'pagamentos', 'assinaturas'], true) ? $tab : 'inscricoes';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Falha na validação do formulário. Recarregue a página.', MSG_ERROR);
        redirect('/admin/parceiros');
    }

    $acao = (string)($_POST['acao'] ?? '');

    if ($acao === 'aprovar_inscricao') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $inscricao = $inscricaoModel->findById($id);
            if (!$inscricao) {
                setFlashMessage('Inscrição não encontrada.', MSG_ERROR);
                redirect('/admin/parceiros?tab=inscricoes');
            }

            $inscricaoModel->approve($id, $adminId);

            $usuarioId = (int)$inscricao['usuario_id'];
            $usuarioModel->update($usuarioId, ['tipo_usuario' => 'parceiro']);

            $assinatura = $assinaturaModel->findByUserId($usuarioId);
            if (!$assinatura) {
                $valor = (float)getConfig('parceiro_plano_basico_mensal', '79.90');
                $assinaturaModel->create([
                    'usuario_id' => $usuarioId,
                    'plano' => 'basico',
                    'valor_mensal' => $valor,
                    'status' => 'pendente_pagamento',
                    'metodo_pagamento' => 'pix_manual',
                ]);
            }

            $perfil = $perfilModel->findByUserId($usuarioId);
            if (!$perfil) {
                $slug = $perfilModel->generateUniqueSlug((string)$inscricao['nome_fantasia'], (string)$inscricao['cidade'], (string)$inscricao['estado']);
                $perfilModel->create([
                    'usuario_id' => $usuarioId,
                    'slug' => $slug,
                    'nome_fantasia' => $inscricao['nome_fantasia'],
                    'categoria' => $inscricao['categoria'],
                    'cidade' => $inscricao['cidade'],
                    'estado' => $inscricao['estado'],
                    'publicado' => 0,
                ]);
            }

            $email = (string)($inscricao['email'] ?? '');
            if ($email !== '') {
                $link = BASE_URL . '/parceiro/painel';
                $subject = 'Sua inscrição de Parceiro foi aprovada - Cadê Meu Pet?';
                $message = "<html><body style='font-family: Arial, sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <h2 style='color:#2196F3;'>Parabéns! Sua inscrição foi aprovada.</h2>
                        <p>Agora você pode completar o perfil e enviar o pagamento para ativar a assinatura.</p>
                        <p style='margin: 24px 0;'>
                            <a href='{$link}' style='background:#2196F3;color:#fff;padding:12px 22px;text-decoration:none;border-radius:8px;display:inline-block;'>
                                Acessar Painel do Parceiro
                            </a>
                        </p>
                        <p style='color:#666;font-size:12px;'>{$link}</p>
                    </div>
                </body></html>";
                sendEmail($email, $subject, $message);
            }

            setFlashMessage('Inscrição aprovada. O usuário já pode configurar o perfil e pagamento.', MSG_SUCCESS);
            redirect('/admin/parceiros?tab=inscricoes');
        } catch (Throwable $e) {
            error_log('[Admin Parceiros] aprovar_inscricao: ' . $e->getMessage());
            setFlashMessage('Erro ao aprovar inscrição.', MSG_ERROR);
            redirect('/admin/parceiros?tab=inscricoes');
        }
    }

    if ($acao === 'recusar_inscricao') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $inscricao = $inscricaoModel->findById($id);
            if (!$inscricao) {
                setFlashMessage('Inscrição não encontrada.', MSG_ERROR);
                redirect('/admin/parceiros?tab=inscricoes');
            }

            $inscricaoModel->reject($id, $adminId);

            $email = (string)($inscricao['email'] ?? '');
            if ($email !== '') {
                $subject = 'Inscrição de Parceiro - Cadê Meu Pet?';
                $message = "<html><body style='font-family: Arial, sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <h2 style='color:#FF4444;'>Inscrição recusada</h2>
                        <p>Olá, analisamos sua solicitação e no momento não foi possível aprovar.</p>
                        <p>Se desejar, responda este e-mail com mais informações sobre seu negócio.</p>
                    </div>
                </body></html>";
                sendEmail($email, $subject, $message);
            }

            setFlashMessage('Inscrição recusada.', MSG_SUCCESS);
            redirect('/admin/parceiros?tab=inscricoes');
        } catch (Throwable $e) {
            error_log('[Admin Parceiros] recusar_inscricao: ' . $e->getMessage());
            setFlashMessage('Erro ao recusar inscrição.', MSG_ERROR);
            redirect('/admin/parceiros?tab=inscricoes');
        }
    }

    if ($acao === 'reabrir_inscricao') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $inscricao = $inscricaoModel->findById($id);
            if (!$inscricao) {
                setFlashMessage('Inscrição não encontrada.', MSG_ERROR);
                redirect('/admin/parceiros?tab=inscricoes');
            }
            $inscricaoModel->reopen($id, $adminId);
            setFlashMessage('Inscrição reaberta e voltou para pendentes.', MSG_SUCCESS);
            redirect('/admin/parceiros?tab=inscricoes');
        } catch (Throwable $e) {
            error_log('[Admin Parceiros] reabrir_inscricao: ' . $e->getMessage());
            setFlashMessage('Erro ao reabrir inscrição.', MSG_ERROR);
            redirect('/admin/parceiros?tab=inscricoes');
        }
    }

    if ($acao === 'excluir_inscricao') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $inscricao = $inscricaoModel->findById($id);
            if (!$inscricao) {
                setFlashMessage('Inscrição não encontrada.', MSG_ERROR);
                redirect('/admin/parceiros?tab=inscricoes');
            }
            $inscricaoModel->delete($id);
            // Reverte tipo_usuario para 'usuario' caso não tenha perfil publicado
            $usuarioId  = (int)$inscricao['usuario_id'];
            $perfil     = $perfilModel->findByUserId($usuarioId);
            if (!$perfil || !(int)($perfil['publicado'] ?? 0)) {
                $usuarioModel->update($usuarioId, ['tipo_usuario' => 'usuario']);
            }
            setFlashMessage('Inscrição excluída. O solicitante poderá fazer uma nova inscrição.', MSG_SUCCESS);
            redirect('/admin/parceiros?tab=inscricoes');
        } catch (Throwable $e) {
            error_log('[Admin Parceiros] excluir_inscricao: ' . $e->getMessage());
            setFlashMessage('Erro ao excluir inscrição.', MSG_ERROR);
            redirect('/admin/parceiros?tab=inscricoes');
        }
    }

    if ($acao === 'aprovar_pagamento') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $pagamento = $pagamentoModel->findById($id);
            if (!$pagamento) {
                setFlashMessage('Pagamento não encontrado.', MSG_ERROR);
                redirect('/admin/parceiros?tab=pagamentos');
            }

            $pagamentoModel->approve($id, $adminId);

            $usuarioId = (int)$pagamento['usuario_id'];

            $periodicidade = (string)($pagamento['periodicidade'] ?? 'mensal');
            $pagoAte = $periodicidade === 'anual'
                ? date('Y-m-d', strtotime('+1 year'))
                : date('Y-m-d', strtotime('+30 days'));
            $assinaturaModel->updateForUser($usuarioId, [
                'status' => 'ativa',
                'ultimo_pagamento_em' => date('Y-m-d H:i:s'),
                'pago_ate' => $pagoAte,
                'proxima_cobranca' => $pagoAte,
            ]);

            $perfilModel->publishForUser($usuarioId, true);
            $perfilModel->setHighlightForUser($usuarioId, ($pagamento['plano'] ?? '') === 'destaque');

            $perfil = $perfilModel->findByUserId($usuarioId);
            $usuario = $usuarioModel->findById($usuarioId);
            $email = (string)($usuario['email'] ?? '');

            if ($email !== '' && $perfil) {
                $link = BASE_URL . '/parceiro/' . $perfil['slug'];
                $subject = 'Pagamento aprovado - Perfil publicado - Cadê Meu Pet?';
                $message = "<html><body style='font-family: Arial, sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <h2 style='color:#00CC66;'>Pagamento aprovado!</h2>
                        <p>Sua assinatura foi ativada e seu perfil de parceiro já está publicado.</p>
                        <p style='margin: 24px 0;'>
                            <a href='{$link}' style='background:#00CC66;color:#fff;padding:12px 22px;text-decoration:none;border-radius:8px;display:inline-block;'>
                                Ver meu perfil
                            </a>
                        </p>
                        <p style='color:#666;font-size:12px;'>{$link}</p>
                    </div>
                </body></html>";
                sendEmail($email, $subject, $message);
            }

            setFlashMessage('Pagamento aprovado e perfil publicado.', MSG_SUCCESS);
            redirect('/admin/parceiros?tab=pagamentos');
        } catch (Throwable $e) {
            error_log('[Admin Parceiros] aprovar_pagamento: ' . $e->getMessage());
            setFlashMessage('Erro ao aprovar pagamento.', MSG_ERROR);
            redirect('/admin/parceiros?tab=pagamentos');
        }
    }

    if ($acao === 'recusar_pagamento') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $pagamento = $pagamentoModel->findById($id);
            if (!$pagamento) {
                setFlashMessage('Pagamento não encontrado.', MSG_ERROR);
                redirect('/admin/parceiros?tab=pagamentos');
            }

            $pagamentoModel->reject($id, $adminId);

            setFlashMessage('Pagamento recusado.', MSG_SUCCESS);
            redirect('/admin/parceiros?tab=pagamentos');
        } catch (Throwable $e) {
            error_log('[Admin Parceiros] recusar_pagamento: ' . $e->getMessage());
            setFlashMessage('Erro ao recusar pagamento.', MSG_ERROR);
            redirect('/admin/parceiros?tab=pagamentos');
        }
    }

    if ($acao === 'cancelar_assinatura') {
        $usuarioIdAlvo = (int)($_POST['usuario_id'] ?? 0);
        try {
            $assinatura = $assinaturaModel->findByUserId($usuarioIdAlvo);
            if (!$assinatura) {
                setFlashMessage('Assinatura não encontrada.', MSG_ERROR);
                redirect('/admin/parceiros?tab=assinaturas');
            }

            // Cancela no gateway se for recorrente com subscription_id
            $subscriptionId = (string)($assinatura['efi_subscription_id'] ?? '');
            if ($subscriptionId !== '' && $subscriptionId !== '0') {
                // Tenta cancelar na EFI; ignora erro se já estiver cancelada
                try {
                    $pagamentoController = new PagamentoController();
                    $pagamentoController->cancelarAssinaturaGateway($subscriptionId);
                } catch (Exception $eGateway) {
                    error_log('[Admin Parceiros] cancelar_assinatura gateway: ' . $eGateway->getMessage());
                    // Continua: cancela localmente mesmo se o gateway falhar
                }
            }

            // Cancela localmente
            $assinaturaModel->cancelar($usuarioIdAlvo);

            // Despublica o perfil imediatamente
            $perfilModel->publishForUser($usuarioIdAlvo, false);

            // Notifica o parceiro
            $usuario = $usuarioModel->findById($usuarioIdAlvo);
            if (!empty($usuario['email'])) {
                $pagoAte = !empty($assinatura['pago_ate']) ? formatDateBR($assinatura['pago_ate']) : null;
                $subject = 'Assinatura cancelada - Cadê Meu Pet?';
                $pagoMsg = $pagoAte ? "<p>Seu acesso ficará disponível até <strong>{$pagoAte}</strong>, referente ao período já pago.</p>" : '';
                $message = "<html><body style='font-family:Arial,sans-serif;'>
                    <div style='max-width:600px;margin:0 auto;padding:20px;'>
                        <h2 style='color:#dc3545;'>Assinatura cancelada</h2>
                        <p>Olá, {$usuario['nome']}!</p>
                        <p>Sua assinatura no plano <strong>" . ucfirst((string)($assinatura['plano'] ?? 'básico')) . "</strong> foi cancelada.</p>
                        {$pagoMsg}
                        <p>Se desejar retomar, acesse o painel e faça um novo pagamento.</p>
                        <p style='color:#666;font-size:12px;'>Em caso de dúvidas entre em contato pelo menu Ajuda.</p>
                    </div>
                </body></html>";
                sendEmail($usuario['email'], $subject, $message);
            }

            setFlashMessage('Assinatura cancelada. Cobranças futuras interrompidas e perfil despublicado.', MSG_SUCCESS);
            redirect('/admin/parceiros?tab=assinaturas');
        } catch (Throwable $e) {
            error_log('[Admin Parceiros] cancelar_assinatura: ' . $e->getMessage());
            setFlashMessage('Erro ao cancelar assinatura: ' . $e->getMessage(), MSG_ERROR);
            redirect('/admin/parceiros?tab=assinaturas');
        }
    }
}

$porPagina = 20;
$pagPi = max(1, (int)($_GET['pi'] ?? 1));
$pagPa = max(1, (int)($_GET['pa'] ?? 1));
$pagPr = max(1, (int)($_GET['pr'] ?? 1));
$pagPp = max(1, (int)($_GET['pp'] ?? 1));
$pagAs = max(1, (int)($_GET['as'] ?? 1));

$countInscricoesPendentes = $inscricaoModel->countByStatus('pendente');
$countInscricoesAprovadas = $inscricaoModel->countByStatus('aprovada');
$countInscricoesRecusadas = $inscricaoModel->countByStatus('recusada');
$countPagamentosPendentes = $pagamentoModel->countByStatus('pendente');
$countAssinaturas         = $assinaturaModel->countAll();

$totalPaginasPi = (int)ceil($countInscricoesPendentes / $porPagina);
$totalPaginasPa = (int)ceil($countInscricoesAprovadas / $porPagina);
$totalPaginasPr = (int)ceil($countInscricoesRecusadas / $porPagina);
$totalPaginasPp = (int)ceil($countPagamentosPendentes / $porPagina);
$totalPaginasAs = (int)ceil($countAssinaturas / $porPagina);

$inscricoesPendentes = $inscricaoModel->listByStatus('pendente', $porPagina, ($pagPi - 1) * $porPagina);
$inscricoesAprovadas = $inscricaoModel->listByStatus('aprovada', $porPagina, ($pagPa - 1) * $porPagina);
$inscricoesRecusadas = $inscricaoModel->listByStatus('recusada', $porPagina, ($pagPr - 1) * $porPagina);
$pagamentosPendentes = $pagamentoModel->listByStatus('pendente', $porPagina, ($pagPp - 1) * $porPagina);
$todasAssinaturas    = $assinaturaModel->listAll($porPagina, ($pagAs - 1) * $porPagina);

$breadcrumbs = [
    ['label' => 'Início',    'url' => BASE_URL],
    ['label' => 'Admin',     'url' => BASE_URL . '/admin'],
    ['label' => 'Parceiros'],
];
$suppressBreadcrumbBar = true;
include __DIR__ . '/../includes/header.php';
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
        <a href="<?php echo BASE_URL; ?>/admin/financeiro" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-chart-line"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/parceiros"  class="btn btn-sm btn-primary"><i class="fa-solid fa-handshake"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/config"     class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-gear"></i></a>
    </div>

    <?php include __DIR__ . '/../includes/admin-breadcrumb.php'; ?>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">Admin - Parceiros</h1>
            <p class="text-muted mb-0">Aprovar inscrições, validar pagamentos e publicar perfis.</p>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'inscricoes' ? 'active' : ''; ?>"
               href="<?php echo BASE_URL; ?>/admin/parceiros?tab=inscricoes">
                Inscrições
                <?php if ($countInscricoesPendentes > 0): ?>
                    <span class="badge bg-warning text-dark ms-1"><?php echo $countInscricoesPendentes; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'pagamentos' ? 'active' : ''; ?>"
               href="<?php echo BASE_URL; ?>/admin/parceiros?tab=pagamentos">
                Pagamentos
                <?php if ($countPagamentosPendentes > 0): ?>
                    <span class="badge bg-warning text-dark ms-1"><?php echo $countPagamentosPendentes; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'assinaturas' ? 'active' : ''; ?>"
               href="<?php echo BASE_URL; ?>/admin/parceiros?tab=assinaturas">
                Assinaturas
            </a>
        </li>
    </ul>

    <?php if ($tab === 'inscricoes'): ?>
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3">Inscrições pendentes</h2>
                <?php if (empty($inscricoesPendentes)): ?>
                    <div class="text-muted">Nenhuma inscrição pendente.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Categoria</th>
                                    <th>Cidade</th>
                                    <th>Contato</th>
                                    <th>Mensagem</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inscricoesPendentes as $i): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo sanitize($i['nome_fantasia']); ?></div>
                                            <div class="text-muted small"><?php echo sanitize($i['usuario_nome'] ?? ''); ?></div>
                                        </td>
                                        <td><span class="badge bg-light text-dark"><?php echo sanitize($i['categoria']); ?></span></td>
                                        <td><?php echo sanitize($i['cidade']); ?> - <?php echo sanitize($i['estado']); ?></td>
                                        <td>
                                            <div class="small"><?php echo sanitize($i['email']); ?></div>
                                            <?php
                                            $tel = preg_replace('/\D/', '', (string)($i['telefone'] ?? ''));
                                            if ($tel !== '' && !preg_match('/^0+$/', $tel)):
                                            ?>
                                            <div class="small">
                                                <a href="https://wa.me/55<?php echo $tel; ?>" target="_blank" rel="noopener" class="text-success text-decoration-none">
                                                    <i class="bi bi-whatsapp me-1"></i><?php echo sanitize((string)($i['telefone'] ?? '')); ?>
                                                </a>
                                            </div>
                                            <?php else: ?>
                                            <div class="small text-muted fst-italic">Sem telefone</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted" style="max-width:200px;">
                                            <?php echo $i['mensagem'] ? sanitize(mb_substr((string)$i['mensagem'], 0, 100)) . (mb_strlen((string)$i['mensagem']) > 100 ? '…' : '') : '<span class="fst-italic">—</span>'; ?>
                                        </td>
                                        <td class="text-end" style="white-space:nowrap;">
                                            <a class="btn btn-sm btn-outline-secondary"
                                               href="<?php echo BASE_URL; ?>/admin/parceiro-editar?id=<?php echo (int)$i['id']; ?>"
                                               title="Editar dados">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="acao" value="aprovar_inscricao">
                                                <input type="hidden" name="id" value="<?php echo (int)$i['id']; ?>">
                                                <button class="btn btn-sm btn-success" type="submit">Aprovar</button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="acao" value="recusar_inscricao">
                                                <input type="hidden" name="id" value="<?php echo (int)$i['id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Recusar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($totalPaginasPi > 1): ?>
                        <nav class="mt-3">
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <li class="page-item <?php echo $pagPi <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?tab=inscricoes&pi=<?php echo $pagPi - 1; ?>">Anterior</a>
                                </li>
                                <li class="page-item disabled"><span class="page-link"><?php echo $pagPi; ?> / <?php echo $totalPaginasPi; ?></span></li>
                                <li class="page-item <?php echo $pagPi >= $totalPaginasPi ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?tab=inscricoes&pi=<?php echo $pagPi + 1; ?>">Próxima</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($countInscricoesRecusadas > 0): ?>
        <div class="card shadow-sm border-0 border-danger mb-3" style="border-left: 4px solid #dc3545 !important;">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3 text-danger">
                    <i class="bi bi-x-circle me-1"></i>Inscrições recusadas
                    <span class="badge bg-danger ms-1"><?php echo $countInscricoesRecusadas; ?></span>
                </h2>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Categoria</th>
                                <th>Cidade</th>
                                <th>Contato</th>
                                <th>Mensagem</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscricoesRecusadas as $i): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo sanitize($i['nome_fantasia']); ?></div>
                                        <div class="text-muted small"><?php echo sanitize($i['usuario_nome'] ?? ''); ?></div>
                                    </td>
                                    <td><span class="badge bg-light text-dark"><?php echo sanitize($i['categoria']); ?></span></td>
                                    <td><?php echo sanitize($i['cidade']); ?> - <?php echo sanitize($i['estado']); ?></td>
                                    <td>
                                        <div class="small"><?php echo sanitize($i['email']); ?></div>
                                        <?php
                                        $tel = preg_replace('/\D/', '', (string)($i['telefone'] ?? ''));
                                        if ($tel !== '' && !preg_match('/^0+$/', $tel)): ?>
                                        <div class="small">
                                            <a href="https://wa.me/55<?php echo $tel; ?>" target="_blank" rel="noopener" class="text-success text-decoration-none">
                                                <i class="bi bi-whatsapp me-1"></i><?php echo sanitize((string)($i['telefone'] ?? '')); ?>
                                            </a>
                                        </div>
                                        <?php else: ?>
                                        <div class="small text-muted fst-italic">Sem telefone</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted" style="max-width:200px;">
                                        <?php echo $i['mensagem'] ? sanitize(mb_substr((string)$i['mensagem'], 0, 100)) . (mb_strlen((string)$i['mensagem']) > 100 ? '…' : '') : '<span class="fst-italic">—</span>'; ?>
                                    </td>
                                    <td class="text-end" style="white-space:nowrap;">
                                        <a class="btn btn-sm btn-outline-secondary"
                                           href="<?php echo BASE_URL; ?>/admin/parceiro-editar?id=<?php echo (int)$i['id']; ?>"
                                           title="Editar dados">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="acao" value="aprovar_inscricao">
                                            <input type="hidden" name="id" value="<?php echo (int)$i['id']; ?>">
                                            <button class="btn btn-sm btn-success" type="submit" title="Aprovar diretamente">Aprovar</button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="acao" value="reabrir_inscricao">
                                            <input type="hidden" name="id" value="<?php echo (int)$i['id']; ?>">
                                            <button class="btn btn-sm btn-outline-warning" type="submit" title="Volta para Pendentes">Reabrir</button>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Excluir esta inscrição? O parceiro poderá fazer uma nova.')">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="acao" value="excluir_inscricao">
                                            <input type="hidden" name="id" value="<?php echo (int)$i['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit" title="Excluir definitivamente">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalPaginasPr > 1): ?>
                    <nav class="mt-3">
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <li class="page-item <?php echo $pagPr <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?tab=inscricoes&pr=<?php echo $pagPr - 1; ?>">Anterior</a>
                            </li>
                            <li class="page-item disabled"><span class="page-link"><?php echo $pagPr; ?> / <?php echo $totalPaginasPr; ?></span></li>
                            <li class="page-item <?php echo $pagPr >= $totalPaginasPr ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?tab=inscricoes&pr=<?php echo $pagPr + 1; ?>">Próxima</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3">Inscrições aprovadas</h2>
                <?php if (empty($inscricoesAprovadas)): ?>
                    <div class="text-muted">Nenhuma inscrição aprovada.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Categoria</th>
                                    <th>Cidade</th>
                                    <th>Data aprovação</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inscricoesAprovadas as $i): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo sanitize($i['nome_fantasia']); ?></td>
                                        <td><span class="badge bg-light text-dark"><?php echo sanitize($i['categoria']); ?></span></td>
                                        <td><?php echo sanitize($i['cidade']); ?> - <?php echo sanitize($i['estado']); ?></td>
                                        <td class="text-muted small"><?php echo sanitize((string)($i['aprovada_em'] ?? '')); ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                                               href="<?php echo BASE_URL; ?>/admin/parceiro-editar?id=<?php echo (int)$i['id']; ?>">
                                                <i class="bi bi-pencil"></i> Editar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($totalPaginasPa > 1): ?>
                        <nav class="mt-3">
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <li class="page-item <?php echo $pagPa <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?tab=inscricoes&pa=<?php echo $pagPa - 1; ?>">Anterior</a>
                                </li>
                                <li class="page-item disabled"><span class="page-link"><?php echo $pagPa; ?> / <?php echo $totalPaginasPa; ?></span></li>
                                <li class="page-item <?php echo $pagPa >= $totalPaginasPa ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?tab=inscricoes&pa=<?php echo $pagPa + 1; ?>">Próxima</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($tab === 'pagamentos'): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3">Pagamentos pendentes</h2>
                <?php if (empty($pagamentosPendentes)): ?>
                    <div class="text-muted">Nenhum pagamento pendente.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Plano</th>
                                    <th>Valor</th>
                                    <th>Referência</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagamentosPendentes as $p): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo sanitize($p['usuario_nome']); ?></div>
                                            <div class="text-muted small"><?php echo sanitize($p['email']); ?></div>
                                        </td>
                                        <td><span class="badge bg-light text-dark"><?php echo sanitize($p['plano']); ?></span></td>
                                        <td><?php echo formatMoney((float)$p['valor']); ?></td>
                                        <td class="text-muted small"><?php echo sanitize((string)($p['referencia'] ?? '')); ?></td>
                                        <td class="text-end">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="acao" value="aprovar_pagamento">
                                                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                                <button class="btn btn-sm btn-success" type="submit">Aprovar</button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="acao" value="recusar_pagamento">
                                                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Recusar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($totalPaginasPp > 1): ?>
                        <nav class="mt-3">
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <li class="page-item <?php echo $pagPp <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?tab=pagamentos&pp=<?php echo $pagPp - 1; ?>">Anterior</a>
                                </li>
                                <li class="page-item disabled"><span class="page-link"><?php echo $pagPp; ?> / <?php echo $totalPaginasPp; ?></span></li>
                                <li class="page-item <?php echo $pagPp >= $totalPaginasPp ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?tab=pagamentos&pp=<?php echo $pagPp + 1; ?>">Próxima</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="alert alert-info mt-3 mb-0">
                    Ao aprovar, a assinatura vira <strong>ativa</strong> e o perfil é publicado automaticamente.
                </div>
            </div>
        </div>

    <?php elseif ($tab === 'assinaturas'): ?>

        <?php
        $statusAssinaturaLabel = [
            'ativa'              => ['label' => 'Ativa',              'badge' => 'success'],
            'pendente_pagamento' => ['label' => 'Aguard. pagamento',  'badge' => 'warning'],
            'suspensa'           => ['label' => 'Suspensa',           'badge' => 'secondary'],
            'cancelada'          => ['label' => 'Cancelada',          'badge' => 'danger'],
        ];
        $metodoLabel = [
            'pix_manual'        => 'Pix (manual)',
            'gateway'           => 'Gateway',
        ];
        $gatewayLabel = [
            'pix'               => 'Pix',
            'cartao_avista'     => 'Cartão à vista',
            'cartao_recorrente' => 'Cartão recorrente',
        ];
        ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-1">Assinaturas de parceiros</h2>
                <p class="text-muted small mb-3">
                    Assinaturas com cartão recorrente podem ser canceladas aqui — a cobrança na EFI é interrompida imediatamente e o perfil é despublicado.
                </p>

                <?php if (empty($todasAssinaturas)): ?>
                    <div class="text-muted">Nenhuma assinatura cadastrada.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Parceiro</th>
                                    <th>Plano</th>
                                    <th>Método</th>
                                    <th>Status</th>
                                    <th>Pago até</th>
                                    <th>Próx. cobrança</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todasAssinaturas as $a): ?>
                                <?php
                                    $si = $statusAssinaturaLabel[$a['status']] ?? ['label' => $a['status'], 'badge' => 'secondary'];
                                    $temRecorrente = !empty($a['pagamento_subscription_id']) || !empty($a['efi_subscription_id']);
                                    $subscriptionId = !empty($a['efi_subscription_id']) ? (string)$a['efi_subscription_id'] : (string)($a['pagamento_subscription_id'] ?? '');
                                    $podecancelar = in_array($a['status'], ['ativa', 'pendente_pagamento', 'suspensa'], true);
                                    $gateway = $gatewayLabel[$a['gateway_tipo'] ?? ''] ?? ($metodoLabel[$a['metodo_pagamento']] ?? '—');
                                ?>
                                <tr class="<?php echo $a['status'] === 'cancelada' ? 'text-muted' : ''; ?>">
                                    <td>
                                        <div class="fw-semibold"><?php echo sanitize((string)($a['usuario_nome'] ?? '')); ?></div>
                                        <div class="small text-muted"><?php echo sanitize((string)($a['email'] ?? '')); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?php echo ucfirst(sanitize($a['plano'])); ?></span>
                                        <div class="small text-muted"><?php echo sanitize($a['periodicidade']); ?></div>
                                    </td>
                                    <td class="small">
                                        <?php echo sanitize($gateway); ?>
                                        <?php if ($subscriptionId !== '' && $subscriptionId !== '0'): ?>
                                            <div class="text-muted" style="font-size:.75rem;">
                                                <i class="bi bi-link-45deg"></i> Sub #<?php echo sanitize($subscriptionId); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $si['badge']; ?>"><?php echo $si['label']; ?></span>
                                        <?php if ($a['status'] === 'cancelada' && !empty($a['cancelada_em'])): ?>
                                            <div class="small text-muted"><?php echo formatDateBR($a['cancelada_em']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <?php echo !empty($a['pago_ate']) ? formatDateBR($a['pago_ate']) : '—'; ?>
                                    </td>
                                    <td class="small">
                                        <?php if (!empty($a['proxima_cobranca']) && $a['status'] === 'ativa'): ?>
                                            <?php echo formatDateBR($a['proxima_cobranca']); ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end" style="white-space:nowrap;">
                                        <?php if (in_array($a['status'], ['ativa', 'pendente_pagamento', 'suspensa'], true)): ?>
                                        <form method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('Cancelar assinatura de <?php echo addslashes(sanitize((string)($a['usuario_nome'] ?? ''))); ?>?\n\nIsso irá:\n• Interromper cobranças futuras na EFI\n• Despublicar o perfil imediatamente\n• Notificar o parceiro por e-mail')">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="acao" value="cancelar_assinatura">
                                            <input type="hidden" name="usuario_id" value="<?php echo (int)$a['usuario_id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1" type="submit">
                                                <i class="bi bi-x-circle"></i> Cancelar
                                            </button>
                                        </form>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPaginasAs > 1): ?>
                        <nav class="mt-3">
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <li class="page-item <?php echo $pagAs <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?tab=assinaturas&as=<?php echo $pagAs - 1; ?>">Anterior</a>
                                </li>
                                <li class="page-item disabled"><span class="page-link"><?php echo $pagAs; ?> / <?php echo $totalPaginasAs; ?></span></li>
                                <li class="page-item <?php echo $pagAs >= $totalPaginasAs ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?tab=assinaturas&as=<?php echo $pagAs + 1; ?>">Próxima</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>

                    <div class="alert alert-warning d-flex gap-2 align-items-start mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div class="small">
                            <strong>Cartão recorrente:</strong> cancelar aqui interrompe a assinatura na EFI Bank — nenhuma cobrança futura será feita.
                            O parceiro mantém acesso ao sistema até o fim do período já pago (<em>pago até</em>).
                            <br><strong>Pix / Cartão à vista:</strong> sem recorrência ativa. O cancelamento encerra a assinatura no sistema apenas.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>

    </div><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
