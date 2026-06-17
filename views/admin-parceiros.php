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
$tab = in_array($tab, ['inscricoes', 'pagamentos'], true) ? $tab : 'inscricoes';

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
                $valor = (float)envValue('PARTNER_PLAN_BASIC_PRICE', 79.90);
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
}

$inscricoesPendentes = $inscricaoModel->listByStatus('pendente');
$inscricoesAprovadas = $inscricaoModel->listByStatus('aprovada');
$pagamentosPendentes = $pagamentoModel->listByStatus('pendente');

$breadcrumbs = [
    ['label' => 'Início',    'url' => BASE_URL],
    ['label' => 'Admin',     'url' => BASE_URL . '/admin'],
    ['label' => 'Parceiros'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Admin - Parceiros</h1>
            <p class="text-muted mb-0">Aprovar inscrições, validar pagamentos e publicar perfis.</p>
        </div>
        <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>/admin">Voltar</a>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'inscricoes' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/parceiros?tab=inscricoes">Inscrições</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'pagamentos' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/parceiros?tab=pagamentos">Pagamentos</a>
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
                                            <div class="small text-muted"><?php echo sanitize($i['telefone']); ?></div>
                                        </td>
                                        <td class="text-end">
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
                <?php endif; ?>
            </div>
        </div>

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
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inscricoesAprovadas as $i): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo sanitize($i['nome_fantasia']); ?></td>
                                        <td><span class="badge bg-light text-dark"><?php echo sanitize($i['categoria']); ?></span></td>
                                        <td><?php echo sanitize($i['cidade']); ?> - <?php echo sanitize($i['estado']); ?></td>
                                        <td class="text-muted small"><?php echo sanitize((string)($i['aprovada_em'] ?? '')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
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
                <?php endif; ?>

                <div class="alert alert-info mt-3 mb-0">
                    Ao aprovar, a assinatura vira <strong>ativa</strong> e o perfil é publicado automaticamente.
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
