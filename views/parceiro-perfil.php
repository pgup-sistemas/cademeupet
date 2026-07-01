<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Meu Perfil Parceiro | Cadê Meu Pet?';

$usuarioId = (int)(getUserId() ?? 0);
$usuarioModel = new Usuario();
$inscricaoModel = new ParceiroInscricao();
$perfilModel = new ParceiroPerfil();
$assinaturaModel = new ParceiroAssinatura();

$usuario = $usuarioModel->findById($usuarioId);
$inscricao = $inscricaoModel->findByUserId($usuarioId);
$perfil = $perfilModel->findByUserId($usuarioId);
$assinatura = $assinaturaModel->findByUserId($usuarioId);

if (!$inscricao || $inscricao['status'] !== 'aprovada') {
    setFlashMessage('Seu acesso de parceiro ainda não foi aprovado.', MSG_WARNING);
    redirect('/parceiro/painel');
}

$assinaturaInativa = $assinatura && !in_array($assinatura['status'] ?? '', ['ativa', 'pendente'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Falha na validação do formulário. Recarregue a página e tente novamente.', MSG_ERROR);
        redirect('/parceiro/perfil');
    }

    $categoria = (string)($_POST['categoria'] ?? ($perfil['categoria'] ?? $inscricao['categoria'] ?? ''));
    $nomeFantasia = trim((string)($_POST['nome_fantasia'] ?? ($perfil['nome_fantasia'] ?? $inscricao['nome_fantasia'] ?? '')));
    $descricao = trim((string)($_POST['descricao'] ?? ''));
    if ($descricao === '' && !$perfil && !empty($inscricao['mensagem'])) {
        $descricao = trim((string)$inscricao['mensagem']);
    }
    $telefone = trim((string)($_POST['telefone'] ?? ($usuario['telefone'] ?? '')));
    $whatsapp = trim((string)($_POST['whatsapp'] ?? ($usuario['telefone'] ?? '')));
    $emailContato = trim((string)($_POST['email_contato'] ?? ($usuario['email'] ?? '')));
    $site = trim((string)($_POST['site'] ?? ''));
    $instagram = trim((string)($_POST['instagram'] ?? ''));
    $endereco = trim((string)($_POST['endereco'] ?? ''));
    $bairro = trim((string)($_POST['bairro'] ?? ''));
    $cidade = trim((string)($_POST['cidade'] ?? ($inscricao['cidade'] ?? '')));
    $estado = strtoupper(trim((string)($_POST['estado'] ?? ($inscricao['estado'] ?? ''))));

    $errors = [];
    $validCategorias = ['petshop', 'clinica', 'hotel', 'adestrador', 'outro'];
    if (!in_array($categoria, $validCategorias, true)) {
        $errors[] = 'Categoria inválida.';
    }
    if ($nomeFantasia === '') {
        $errors[] = 'Informe o nome fantasia.';
    }
    if ($cidade === '') {
        $errors[] = 'Informe a cidade.';
    }
    if ($estado === '' || strlen($estado) !== 2) {
        $errors[] = 'Informe o estado (UF).';
    }
    if ($emailContato !== '' && !isValidEmail($emailContato)) {
        $errors[] = 'Email de contato inválido.';
    }

    if (!empty($errors)) {
        setFlashMessage(implode(' ', $errors), MSG_ERROR);
        redirect('/parceiro/perfil');
    }

    try {
        if (!$perfil) {
            $slug = $perfilModel->generateUniqueSlug($nomeFantasia, $cidade, $estado);
            $perfilModel->create([
                'usuario_id' => $usuarioId,
                'slug' => $slug,
                'nome_fantasia' => $nomeFantasia,
                'categoria' => $categoria,
                'descricao' => $descricao !== '' ? $descricao : null,
                'telefone' => $telefone !== '' ? preg_replace('/[^0-9]/', '', $telefone) : null,
                'whatsapp' => $whatsapp !== '' ? preg_replace('/[^0-9]/', '', $whatsapp) : null,
                'email_contato' => $emailContato !== '' ? $emailContato : null,
                'site' => $site !== '' ? $site : null,
                'instagram' => $instagram !== '' ? $instagram : null,
                'endereco' => $endereco !== '' ? $endereco : null,
                'bairro' => $bairro !== '' ? $bairro : null,
                'cidade' => $cidade,
                'estado' => $estado,
                'publicado' => 0,
            ]);
        } else {
            $perfilModel->update((int)$perfil['id'], [
                'nome_fantasia' => $nomeFantasia,
                'categoria' => $categoria,
                'descricao' => $descricao !== '' ? $descricao : null,
                'telefone' => $telefone !== '' ? preg_replace('/[^0-9]/', '', $telefone) : null,
                'whatsapp' => $whatsapp !== '' ? preg_replace('/[^0-9]/', '', $whatsapp) : null,
                'email_contato' => $emailContato !== '' ? $emailContato : null,
                'site' => $site !== '' ? $site : null,
                'instagram' => $instagram !== '' ? $instagram : null,
                'endereco' => $endereco !== '' ? $endereco : null,
                'bairro' => $bairro !== '' ? $bairro : null,
                'cidade' => $cidade,
                'estado' => $estado,
            ]);
        }

        setFlashMessage('Perfil atualizado. Agora finalize o pagamento para ativar a assinatura.', MSG_SUCCESS);
        redirect('/parceiro/painel');
    } catch (Throwable $e) {
        error_log('[Parceiro Perfil] salvar: ' . $e->getMessage());
        setFlashMessage('Erro ao salvar perfil. Tente novamente.', MSG_ERROR);
        redirect('/parceiro/perfil');
    }
}

$perfil = $perfilModel->findByUserId($usuarioId);

$breadcrumbs = [
    ['label' => 'Início',    'url' => BASE_URL],
    ['label' => 'Parceiros', 'url' => BASE_URL . '/parceiros'],
    ['label' => 'Meu Perfil'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">

    <?php if (!empty($assinaturaInativa)): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div>
            Sua assinatura está <strong><?php echo htmlspecialchars($assinatura['status'] ?? 'inativa', ENT_QUOTES, 'UTF-8'); ?></strong>.
            Você pode editar seu perfil, mas ele não ficará visível no diretório enquanto a assinatura não estiver ativa.
            <a href="<?php echo BASE_URL; ?>/parceiro/painel" class="alert-link ms-1">Ir ao painel para regularizar.</a>
        </div>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Meu Perfil Parceiro</h1>
            <p class="text-muted mb-0">Esses dados aparecem no diretório de parceiros quando seu perfil estiver publicado.</p>
        </div>
        <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>/parceiro/painel">Voltar ao painel</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Categoria</label>
                                <select name="categoria" class="form-select" required>
                                    <?php
                                        $catValue = (string)($perfil['categoria'] ?? $inscricao['categoria'] ?? '');
                                    ?>
                                    <option value="petshop" <?php echo $catValue === 'petshop' ? 'selected' : ''; ?>>Pet Shop</option>
                                    <option value="clinica" <?php echo $catValue === 'clinica' ? 'selected' : ''; ?>>Clínica Veterinária</option>
                                    <option value="hotel" <?php echo $catValue === 'hotel' ? 'selected' : ''; ?>>Hotel/Creche</option>
                                    <option value="adestrador" <?php echo $catValue === 'adestrador' ? 'selected' : ''; ?>>Adestrador</option>
                                    <option value="outro" <?php echo $catValue === 'outro' ? 'selected' : ''; ?>>Outro</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nome fantasia</label>
                                <input type="text" name="nome_fantasia" class="form-control" value="<?php echo sanitize((string)($perfil['nome_fantasia'] ?? $inscricao['nome_fantasia'] ?? '')); ?>" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descrição</label>
                                <textarea name="descricao" class="form-control" rows="5" placeholder="Conte quais serviços você oferece, horários, diferenciais..."><?php echo sanitize((string)($perfil['descricao'] ?? $inscricao['mensagem'] ?? '')); ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone" class="form-control" value="<?php echo sanitize((string)($perfil['telefone'] ?? $usuario['telefone'] ?? '')); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">WhatsApp</label>
                                <input type="text" name="whatsapp" class="form-control" value="<?php echo sanitize((string)($perfil['whatsapp'] ?? $usuario['telefone'] ?? '')); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email de contato</label>
                                <input type="email" name="email_contato" class="form-control" value="<?php echo sanitize((string)($perfil['email_contato'] ?? $usuario['email'] ?? '')); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Site</label>
                                <input type="text" name="site" class="form-control" value="<?php echo sanitize((string)($perfil['site'] ?? '')); ?>" placeholder="https://...">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Instagram</label>
                                <input type="text" name="instagram" class="form-control" value="<?php echo sanitize((string)($perfil['instagram'] ?? '')); ?>" placeholder="@seuinstagram ou https://instagram.com/...">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Endereço</label>
                                <input type="text" name="endereco" class="form-control" value="<?php echo sanitize((string)($perfil['endereco'] ?? '')); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Bairro</label>
                                <input type="text" name="bairro" class="form-control" value="<?php echo sanitize((string)($perfil['bairro'] ?? '')); ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Cidade</label>
                                <input type="text" name="cidade" class="form-control" value="<?php echo sanitize((string)($perfil['cidade'] ?? $inscricao['cidade'] ?? '')); ?>" required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">UF</label>
                                <input type="text" name="estado" class="form-control" maxlength="2" value="<?php echo sanitize((string)($perfil['estado'] ?? $inscricao['estado'] ?? '')); ?>" required>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Salvar</button>
                            <a href="<?php echo BASE_URL; ?>/parceiro/pagamento" class="btn btn-outline-success btn-lg">Pagamento</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="fw-bold mb-2">Publicação</div>
                    <div class="text-muted small">
                        <?php if ($assinatura && ($assinatura['status'] ?? '') === 'ativa'): ?>
                            Assinatura ativa. Aguarde o admin publicar seu perfil.
                        <?php else: ?>
                            Para publicar, conclua o pagamento.
                        <?php endif; ?>
                    </div>
                    <?php if ($perfil && !empty($perfil['slug'])): ?>
                        <hr>
                        <div class="small text-muted">Link do seu perfil:</div>
                        <div class="fw-semibold"><?php echo BASE_URL; ?>/parceiro/<?php echo sanitize($perfil['slug']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
