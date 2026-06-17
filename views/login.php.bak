<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Login - PetFinder';

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
    $infoMessages[] = 'Conta criada com sucesso! Confirme seu email para acessar o PetFinder.';
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

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <!-- Card de Login -->
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <!-- Cabeçalho -->
                    <div class="text-center mb-4">
                        <div class="logo-icon-large mb-3">🐾</div>
                        <h2 class="fw-bold mb-2">Bem-vindo de Volta!</h2>
                        <p class="text-muted">Faça login para continuar</p>
                    </div>
                    
                    <!-- Erro -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <?php echo sanitize($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($infoMessages as $info): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="bi bi-info-circle"></i>
                            <?php echo sanitize($info); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Formulário -->
                    <form method="POST" action="" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email"
                                       placeholder="seu@email.com"
                                       value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                                       required
                                       autofocus>
                            </div>
                        </div>
                        
                        <!-- Senha -->
                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="senha" 
                                       name="senha"
                                       placeholder="Sua senha"
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        onclick="togglePassword()">
                                    <i class="bi bi-eye" id="senha-icon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Opções -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="lembrar" 
                                       name="lembrar">
                                <label class="form-check-label small" for="lembrar">
                                    Lembrar-me
                                </label>
                            </div>
                            <a href="<?php echo BASE_URL; ?>/reenviar-confirmacao/" 
                               class="small text-decoration-none">
                                Não recebeu o e-mail? Reenviar
                            </a>
                            <a href="<?php echo BASE_URL; ?>/recuperar-senha/" 
                               class="small text-decoration-none">
                                Esqueci minha senha
                            </a>
                        </div>
                        
                        <!-- Botão Submit -->
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right"></i> Entrar
                        </button>
                        
                        <!-- Divisor -->
                        <div class="text-center my-3">
                            <span class="text-muted small">OU</span>
                        </div>
                        
                        <!-- Botão Cadastro -->
                        <a href="<?php echo BASE_URL; ?>/cadastro/" 
                           class="btn btn-outline-success btn-lg w-100">
                            <i class="bi bi-person-plus"></i> Criar Nova Conta
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.logo-icon-large {
    font-size: 4em;
}

.card {
    border-radius: 15px;
}

.input-group-text {
    background: white;
}

.form-control:focus {
    border-color: #2196F3;
    box-shadow: 0 0 0 0.2rem rgba(33, 150, 243, 0.25);
}

.btn-lg {
    padding: 12px;
    border-radius: 8px;
    font-weight: 600;
}
</style>

<script>
// Toggle mostrar senha
function togglePassword() {
    const field = document.getElementById('senha');
    const icon = document.getElementById('senha-icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Auto-focus no email
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('email').focus();
});

// Validação do formulário
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value;
    const senha = document.getElementById('senha').value;
    
    if (!email || !senha) {
        e.preventDefault();
        alert('Por favor, preencha todos os campos!');
        return false;
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>