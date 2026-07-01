<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Recuperar Senha - Cadê Meu Pet?';

if (isLoggedIn()) {
    redirect('/');
}

$usuarioController = new UsuarioController();
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Falha na validação. Recarregue a página.';
    } elseif (isRateLimited('recuperar_senha_' . getClientIP(), 3, 600)) {
        $errors[] = 'Muitas tentativas. Aguarde 10 minutos antes de tentar novamente.';
    } else {
        $email = $_POST['email'] ?? '';
        $result = $usuarioController->solicitarResetSenha($email);

        if (!empty($result['success'])) {
            $successMessage = 'Se o e-mail estiver cadastrado, enviaremos um link para redefinir sua senha.';
        } elseif (!empty($result['errors'])) {
            $errors = $result['errors'];
        } elseif (!empty($result['error'])) {
            $errors[] = $result['error'];
        }
    }
}

include __DIR__ . '/../includes/auth-head.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <a href="<?php echo BASE_URL; ?>" class="auth-brand">
            <i class="fa-solid fa-paw"></i> Cadê Meu Pet?
        </a>
        <h1 class="auth-title">Recuperar senha</h1>
        <p class="auth-subtitle">Enviaremos um link para redefinir sua senha</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <?php foreach ($errors as $error): echo sanitize($error); endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success py-2 small">
                <i class="bi bi-check-circle me-1"></i><?php echo sanitize($successMessage); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="mb-3">
                <label for="email" class="form-label fw-semibold small mb-1">E-mail cadastrado</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="seu@email.com" required autofocus>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                <i class="bi bi-send me-1"></i>Enviar link de recuperação
            </button>
        </form>

        <div class="auth-divider"></div>

        <a href="<?php echo BASE_URL; ?>/login/" class="btn btn-outline-secondary w-100">
            <i class="bi bi-arrow-left me-1"></i>Voltar ao login
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/auth-foot.php'; ?>
