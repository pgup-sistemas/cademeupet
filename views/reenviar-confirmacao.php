<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Reenviar Confirmação - Cadê Meu Pet?';

if (isLoggedIn()) {
    redirect('/');
}

$usuarioController = new UsuarioController();
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Falha na validação. Recarregue a página.';
    } elseif (isRateLimited('reenviar_confirm_' . getClientIP(), 3, 600)) {
        $errors[] = 'Muitas tentativas. Aguarde 10 minutos antes de tentar novamente.';
    } else {
        $email = $_POST['email'] ?? '';
        $result = $usuarioController->reenviarConfirmacaoEmail($email);

        if (!empty($result['success'])) {
            $successMessage = 'Se o e-mail estiver cadastrado, enviamos um novo link de confirmação.';
        } elseif (!empty($result['errors'])) {
            $errors = $result['errors'];
        }
    }
}

$breadcrumbs = [
    ['label' => 'Início',                 'url' => BASE_URL],
    ['label' => 'Reenviar Confirmação'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="logo-icon-large mb-3"><i class="fa-solid fa-envelope"></i></div>
                        <h2 class="fw-bold mb-2">Reenviar Confirmação</h2>
                        <p class="text-muted">Não recebeu o e-mail de confirmação? Enviamos um novo.</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo sanitize($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success">
                            <?php echo sanitize($successMessage); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail cadastrado</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email"
                                       class="form-control"
                                       id="email"
                                       name="email"
                                       placeholder="seu@email.com"
                                       required
                                       autofocus>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="bi bi-envelope"></i> Reenviar E-mail de Confirmação
                        </button>

                        <div class="text-center">
                            <a href="<?php echo BASE_URL; ?>/login/" class="text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Voltar ao Login
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
