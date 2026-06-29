<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Login - Cadê Meu Pet?';

// Se já estiver logado, redireciona
if (isLoggedIn()) {
    redirect('/');
}

$error = '';
$infoMessages = [];

if (isset($_GET['logout'])) {
    $infoMessages[] = 'Você saiu com segurança. Volte sempre!';
}

if (isset($_GET['registered'])) {
    $infoMessages[] = 'Conta criada com sucesso! Confirme seu email para acessar o Cadê Meu Pet?.';
}

$usuarioController = new UsuarioController();

// Processa o login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $lembrar = isset($_POST['lembrar']);

    // Valida CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Erro de validação. Recarregue a página.';
    } else {
        $result = $usuarioController->login($email, $senha, $lembrar);

        if (!empty($result['success'])) {
            $redirect = $_GET['redirect'] ?? '/';
            redirect($redirect);
        }

        if (!empty($result['need_confirmation'])) {
            $infoMessages[] = 'Confirme seu email para finalizar o login. Verifique sua caixa de entrada.';
        }

        if (!empty($result['error'])) {
            $error = $result['error'];
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
        <h1 class="auth-title">Bem-vindo de volta</h1>
        <p class="auth-subtitle">Entre para ajudar pets perdidos</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                <i class="bi bi-exclamation-triangle me-1"></i><?php echo sanitize($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php foreach ($infoMessages as $info): ?>
            <div class="alert alert-info alert-dismissible fade show py-2 small" role="alert">
                <i class="bi bi-info-circle me-1"></i><?php echo sanitize($info); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>

        <form method="POST" action="" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="mb-3">
                <label for="email" class="form-label fw-semibold small mb-1">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="seu@email.com"
                           value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                           required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="senha" class="form-label fw-semibold small mb-0">Senha</label>
                    <a href="<?php echo BASE_URL; ?>/recuperar-senha/" class="small text-decoration-none">Esqueci minha senha</a>
                </div>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="senha" name="senha"
                           placeholder="Sua senha" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                        <i class="bi bi-eye" id="senha-icon"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="lembrar" name="lembrar">
                    <label class="form-check-label small text-muted" for="lembrar">Lembrar-me</label>
                </div>
                <a href="<?php echo BASE_URL; ?>/reenviar-confirmacao/" class="small text-decoration-none text-muted">
                    Não recebeu o e-mail?
                </a>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
            </button>
        </form>

        <div class="auth-divider">OU</div>

        <a href="<?php echo BASE_URL; ?>/cadastro/" class="btn btn-outline-secondary w-100">
            <i class="bi bi-person-plus me-1"></i>Criar conta gratuita
        </a>
    </div>
</div>

<script>
function togglePassword() {
    const field = document.getElementById('senha');
    const icon  = document.getElementById('senha-icon');
    const isHidden = field.type === 'password';
    field.type = isHidden ? 'text' : 'password';
    icon.classList.toggle('bi-eye', !isHidden);
    icon.classList.toggle('bi-eye-slash', isHidden);
}
document.getElementById('loginForm').addEventListener('submit', function(e) {
    if (!document.getElementById('email').value || !document.getElementById('senha').value) {
        e.preventDefault();
        alert('Preencha todos os campos.');
    }
});
</script>

<?php include __DIR__ . '/../includes/auth-foot.php'; ?>