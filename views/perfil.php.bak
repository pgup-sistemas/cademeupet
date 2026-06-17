
<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Meu Perfil - PetFinder';

requireLogin();

$usuarioId = (int)getUserId();
$usuarioModel = new Usuario();
$usuarioController = new UsuarioController();

$usuario = $usuarioModel->findById($usuarioId);
if (!$usuario) {
    setFlashMessage('Usuário não encontrado.', MSG_ERROR);
    redirect('/');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Falha na validação do formulário. Recarregue a página e tente novamente.';
    } else {
        if (isset($_POST['atualizar_perfil'])) {
            $result = $usuarioController->atualizarPerfil($usuarioId, $_POST);
            if (!empty($result['success'])) {
                setFlashMessage($result['message'] ?? 'Perfil atualizado com sucesso.', MSG_SUCCESS);
                redirect('/perfil.php');
            }
            if (!empty($result['errors'])) {
                $errors = array_merge($errors, (array)$result['errors']);
            }
        }

        if (isset($_POST['atualizar_foto'])) {
            $result = $usuarioController->atualizarFotoPerfil($usuarioId, $_FILES['foto_perfil'] ?? []);
            if (!empty($result['success'])) {
                setFlashMessage('Foto de perfil atualizada com sucesso.', MSG_SUCCESS);
                redirect('/perfil.php');
            }
            if (!empty($result['errors'])) {
                $errors = array_merge($errors, (array)$result['errors']);
            }
        }
    }
}

$usuario = $usuarioModel->findById($usuarioId) ?: $usuario;

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 fw-bold mb-0">Meu Perfil</h1>
                <span class="badge <?php echo !empty($usuario['email_confirmado']) ? 'bg-success' : 'bg-warning text-dark'; ?>">
                    <?php echo !empty($usuario['email_confirmado']) ? 'E-mail confirmado' : 'E-mail não confirmado'; ?>
                </span>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach (array_unique($errors) as $error): ?>
                            <li><?php echo sanitize($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow border-0 mb-4">
                <div class="card-body p-4">
                    <div class="row g-4 align-items-center">
                        <div class="col-md-4 text-center">
                            <?php if (!empty($usuario['foto_perfil'])): ?>
                                <img src="<?php echo BASE_URL; ?>/uploads/perfil/<?php echo sanitize($usuario['foto_perfil']); ?>" alt="Foto de perfil" class="rounded-circle" style="width: 140px; height: 140px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width: 140px; height: 140px;">
                                    <i class="bi bi-person" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>

                            <form method="POST" enctype="multipart/form-data" class="mt-3">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="file" name="foto_perfil" class="form-control" accept="image/*" required>
                                <button type="submit" name="atualizar_foto" value="1" class="btn btn-outline-primary w-100 mt-2">
                                    Atualizar foto
                                </button>
                            </form>
                        </div>

                        <div class="col-md-8">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Nome</label>
                                        <input type="text" class="form-control form-control-lg" name="nome" value="<?php echo sanitize($usuario['nome'] ?? ''); ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">E-mail</label>
                                        <input type="email" class="form-control form-control-lg" value="<?php echo sanitize($usuario['email'] ?? ''); ?>" disabled>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Telefone</label>
                                        <input type="text" class="form-control form-control-lg" name="telefone" value="<?php echo sanitize($usuario['telefone'] ?? ''); ?>" required>
                                    </div>

                                    <div class="col-md-8">
                                        <label class="form-label">Cidade</label>
                                        <input type="text" class="form-control form-control-lg" name="cidade" value="<?php echo sanitize($usuario['cidade'] ?? ''); ?>">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">UF</label>
                                        <input type="text" class="form-control form-control-lg" name="estado" maxlength="2" value="<?php echo sanitize($usuario['estado'] ?? ''); ?>">
                                    </div>

                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="notificacoes_email" name="notificacoes_email" value="1" <?php echo !empty($usuario['notificacoes_email']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="notificacoes_email">Receber notificações por e-mail</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" name="atualizar_perfil" value="1" class="btn btn-primary btn-lg flex-grow-1">
                                        Salvar alterações
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

