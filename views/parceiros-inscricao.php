<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Inscrição de Parceiro | Cadê Meu Pet?';

$usuarioId = (int)(getUserId() ?? 0);
$usuarioModel = new Usuario();
$inscricaoModel = new ParceiroInscricao();

$usuario = $usuarioModel->findById($usuarioId);
$inscricao = $inscricaoModel->findByUserId($usuarioId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Falha na validação do formulário. Recarregue a página e tente novamente.', MSG_ERROR);
        redirect('/parceiros/inscricao');
    }

    if ($inscricao) {
        setFlashMessage('Você já possui uma inscrição registrada. Aguarde a análise.', MSG_INFO);
        redirect('/parceiros/inscricao');
    }

    $categoria = (string)($_POST['categoria'] ?? '');
    $nomeFantasia = trim((string)($_POST['nome_fantasia'] ?? ''));
    $cidade = trim((string)($_POST['cidade'] ?? ($usuario['cidade'] ?? '')));
    $estado = strtoupper(trim((string)($_POST['estado'] ?? ($usuario['estado'] ?? ''))));
    $mensagem = trim((string)($_POST['mensagem'] ?? ''));

    $errors = [];
    $validCategorias = ['petshop', 'clinica', 'hotel', 'adestrador', 'outro'];
    if (!in_array($categoria, $validCategorias, true)) {
        $errors[] = 'Selecione uma categoria válida.';
    }
    if ($nomeFantasia === '') {
        $errors[] = 'Informe o nome fantasia.';
    }
    if ($nomeFantasia !== '' && mb_strlen($nomeFantasia) < 3) {
        $errors[] = 'O nome fantasia deve ter pelo menos 3 caracteres.';
    }
    if ($cidade === '') {
        $errors[] = 'Informe a cidade.';
    }
    if ($estado === '' || strlen($estado) !== 2) {
        $errors[] = 'Informe o estado (UF).';
    } else {
        $validUFs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
        if (!in_array($estado, $validUFs, true)) {
            $errors[] = 'Informe uma UF válida (ex.: SP, RJ, MG).';
        }
    }

    if ($mensagem !== '' && mb_strlen($mensagem) > 2000) {
        $errors[] = 'A mensagem é muito longa (máx. 2000 caracteres).';
    }

    if (!empty($errors)) {
        setFlashMessage(implode(' ', $errors), MSG_ERROR);
        redirect('/parceiros/inscricao');
    }

    try {
        $inscricaoModel->create([
            'usuario_id' => $usuarioId,
            'categoria' => $categoria,
            'nome_fantasia' => $nomeFantasia,
            'cidade' => $cidade,
            'estado' => $estado,
            'mensagem' => $mensagem !== '' ? $mensagem : null,
            'status' => 'pendente',
        ]);

        $userEmail = (string)($usuario['email'] ?? '');
        $userName = (string)($usuario['nome'] ?? '');
        if ($userEmail !== '') {
            $link = BASE_URL . '/parceiro/painel';
            $subject = 'Recebemos sua inscrição de Parceiro - Cadê Meu Pet?';
            $message = "<html><body style='font-family: Arial, sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color:#2196F3;'>Inscrição recebida</h2>
                    <p>Olá " . sanitize($userName) . ", recebemos sua solicitação de parceria.</p>
                    <p>Nossa equipe irá analisar e você será notificado por e-mail quando houver uma resposta.</p>
                    <p style='margin: 24px 0;'>
                        <a href='{$link}' style='background:#2196F3;color:#fff;padding:12px 22px;text-decoration:none;border-radius:8px;display:inline-block;'>
                            Acompanhar no Painel do Parceiro
                        </a>
                    </p>
                    <p style='color:#666;font-size:12px;'>{$link}</p>
                </div>
            </body></html>";

            $sent = sendEmail($userEmail, $subject, $message);
            if (!$sent) {
                error_log('[Parceiros] Falha ao enviar email de confirmação de inscrição para: ' . $userEmail);
            }
        }

        setFlashMessage('Inscrição enviada! Nossa equipe vai analisar e você será notificado por e-mail.', MSG_SUCCESS);
        redirect('/parceiro/painel');
    } catch (Throwable $e) {
        error_log('[Parceiros] inscrição: ' . $e->getMessage());
        setFlashMessage('Erro ao enviar inscrição. Tente novamente.', MSG_ERROR);
        redirect('/parceiros/inscricao');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Inscrição de Parceiro</h1>
                    <p class="text-muted mb-0">Solicite seu perfil empresarial para divulgar serviços no Cadê Meu Pet?.</p>
                </div>
                <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>/parceiro/painel">Painel do Parceiro</a>
            </div>

            <?php if ($inscricao): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <div class="fw-bold">Sua inscrição</div>
                                <div class="text-muted small">Status atual: <strong><?php echo sanitize($inscricao['status']); ?></strong></div>
                            </div>
                            <span class="badge <?php echo $inscricao['status'] === 'aprovada' ? 'bg-success' : ($inscricao['status'] === 'recusada' ? 'bg-danger' : 'bg-warning text-dark'); ?>">
                                <?php echo $inscricao['status'] === 'aprovada' ? 'Aprovada' : ($inscricao['status'] === 'recusada' ? 'Recusada' : 'Pendente'); ?>
                            </span>
                        </div>

                        <hr>

                        <?php if ($inscricao['status'] === 'aprovada'): ?>
                            <div class="alert alert-success mb-0">
                                Sua inscrição foi aprovada! Acesse o <a href="<?php echo BASE_URL; ?>/parceiro/painel">Painel do Parceiro</a> para concluir o perfil e o pagamento.
                            </div>
                        <?php elseif ($inscricao['status'] === 'recusada'): ?>
                            <div class="alert alert-danger mb-0">
                                Sua inscrição foi recusada. Se você acredita que foi um engano, entre em contato pelo menu Ajuda.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                Recebemos sua inscrição. Em breve você receberá um e-mail com a resposta.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Categoria</label>
                                    <select name="categoria" class="form-select" required>
                                        <option value="" selected disabled>Selecione</option>
                                        <option value="petshop">Pet Shop</option>
                                        <option value="clinica">Clínica Veterinária</option>
                                        <option value="hotel">Hotel/Creche</option>
                                        <option value="adestrador">Adestrador</option>
                                        <option value="outro">Outro</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nome fantasia</label>
                                    <input type="text" name="nome_fantasia" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Cidade</label>
                                    <input type="text" name="cidade" class="form-control" value="<?php echo sanitize((string)($usuario['cidade'] ?? '')); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Estado (UF)</label>
                                    <input type="text" name="estado" class="form-control" maxlength="2" value="<?php echo sanitize((string)($usuario['estado'] ?? '')); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Sobre / serviços (opcional)</label>
                                    <textarea name="mensagem" class="form-control" rows="5" placeholder="Escreva sobre seus serviços, diferenciais, horários e links. Vamos usar isso para te ajudar a montar o perfil."></textarea>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">Enviar inscrição</button>
                                <a href="<?php echo BASE_URL; ?>/parceiros" class="btn btn-outline-secondary btn-lg">Voltar</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
