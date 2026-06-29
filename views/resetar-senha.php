<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Redefinir Senha - Cadê Meu Pet?';

if (isLoggedIn()) {
    redirect('/');
}

$token = $_GET['token'] ?? '';
$errors = [];
$successMessage = '';
 $infoMessage = '';

if (empty($token)) {
    $errors[] = 'Token inválido.';
}

$usuarioController = new UsuarioController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Falha na validação. Recarregue a página.';
    } else {
        $tokenPost = $_POST['token'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $confirma = $_POST['confirma_senha'] ?? '';

        if (empty($tokenPost)) {
            $errors[] = 'Token inválido.';
        } else {
            $result = $usuarioController->resetarSenha($tokenPost, $senha, $confirma);

            if (!empty($result['success'])) {
                $successMessage = 'Senha redefinida com sucesso!';
                if (!empty($result['confirmation_sent'])) {
                    $infoMessage = 'Como seu e-mail ainda não foi confirmado, enviamos automaticamente um novo link de confirmação. Verifique sua caixa de entrada (e spam).';
                } else {
                    $successMessage .= ' Você já pode fazer login.';
                }
            } elseif (!empty($result['error'])) {
                $errors[] = $result['error'];
            }
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
        <h1 class="auth-title">Redefinir senha</h1>
        <p class="auth-subtitle">Crie uma nova senha para sua conta</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <ul class="mb-0 ps-3 mt-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo sanitize($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success py-2 small">
                <i class="bi bi-check-circle me-1"></i><?php echo sanitize($successMessage); ?>
            </div>
            <?php if (!empty($infoMessage)): ?>
                <div class="alert alert-info py-2 small">
                    <i class="bi bi-info-circle me-1"></i><?php echo sanitize($infoMessage); ?>
                </div>
            <?php endif; ?>
            <a class="btn btn-primary w-100 fw-semibold" href="<?php echo BASE_URL; ?>/login/">
                <i class="bi bi-box-arrow-in-right me-1"></i>Ir para login
            </a>
        <?php else: ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="token" value="<?php echo sanitize($token); ?>">

                <div class="mb-3">
                    <label for="senha" class="form-label fw-semibold small mb-1">Nova senha</label>
                    <input type="password" class="form-control" id="senha" name="senha"
                           placeholder="Mínimo 8 caracteres" required autofocus>
                    <div class="form-text small">Maiúsculas, minúsculas e números.</div>
                </div>

                <div class="mb-3">
                    <label for="confirma_senha" class="form-label fw-semibold small mb-1">Confirmar nova senha</label>
                    <input type="password" class="form-control" id="confirma_senha" name="confirma_senha"
                           placeholder="Digite novamente" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-check-lg me-1"></i>Salvar nova senha
                </button>
            </form>

            <div class="auth-divider"></div>

            <a href="<?php echo BASE_URL; ?>/login/" class="btn btn-outline-secondary w-100">
                <i class="bi bi-arrow-left me-1"></i>Voltar ao login
            </a>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/auth-foot.php'; ?>
