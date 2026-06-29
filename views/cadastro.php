<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Criar Conta - Cadê Meu Pet?';

if (isLoggedIn()) {
    redirect('/');
}

$usuarioController = new UsuarioController();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Falha na validação do formulário. Recarregue a página e tente novamente.';
    } else {
        $resultado = $usuarioController->registrar($_POST);

        if (!empty($resultado['success'])) {
            redirect('/login/?registered=1');
        }

        if (!empty($resultado['errors'])) {
            $errors = $resultado['errors'];
        }
    }
}

include __DIR__ . '/../includes/auth-head.php';
?>

<div class="auth-wrapper">
    <div class="auth-card auth-card-wide">
        <a href="<?php echo BASE_URL; ?>" class="auth-brand">
            <i class="fa-solid fa-paw"></i> Cadê Meu Pet?
        </a>
        <h1 class="auth-title">Crie sua conta</h1>
        <p class="auth-subtitle">Leva menos de 2 minutos para começar a ajudar pets perdidos</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 small">
                <i class="bi bi-exclamation-triangle me-1"></i><strong>Corrija os campos abaixo:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    <?php foreach ($errors as $erro): ?>
                        <li><?php echo sanitize($erro); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="row g-3">
                <div class="col-sm-6">
                    <label for="nome" class="form-label fw-semibold small mb-1">Nome completo *</label>
                    <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex: Ana Souza"
                           value="<?php echo sanitize($_POST['nome'] ?? ''); ?>" required autofocus>
                </div>
                <div class="col-sm-6">
                    <label for="telefone" class="form-label fw-semibold small mb-1">Telefone *</label>
                    <input type="text" class="form-control" id="telefone" name="telefone" placeholder="(69) 99999-9999"
                           value="<?php echo sanitize($_POST['telefone'] ?? ''); ?>" required>
                </div>
                <div class="col-12">
                    <label for="email" class="form-label fw-semibold small mb-1">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="voce@email.com"
                           value="<?php echo sanitize($_POST['email'] ?? ''); ?>" required>
                </div>
                <div class="col-sm-9">
                    <label for="cidade" class="form-label fw-semibold small mb-1">Cidade</label>
                    <input type="text" class="form-control" id="cidade" name="cidade" placeholder="Porto Velho"
                           value="<?php echo sanitize($_POST['cidade'] ?? ''); ?>">
                </div>
                <div class="col-sm-3">
                    <label for="estado" class="form-label fw-semibold small mb-1">UF</label>
                    <input type="text" maxlength="2" class="form-control text-uppercase" id="estado" name="estado" placeholder="RO"
                           value="<?php echo sanitize($_POST['estado'] ?? ''); ?>">
                </div>
                <div class="col-sm-6">
                    <label for="senha" class="form-label fw-semibold small mb-1">Senha *</label>
                    <input type="password" class="form-control" id="senha" name="senha" placeholder="Mínimo 8 caracteres" required>
                </div>
                <div class="col-sm-6">
                    <label for="confirma_senha" class="form-label fw-semibold small mb-1">Confirmar senha *</label>
                    <input type="password" class="form-control" id="confirma_senha" name="confirma_senha" placeholder="Digite novamente" required>
                </div>
            </div>

            <p class="text-muted small mt-3 mb-0">* Obrigatórios. Seus dados são protegidos pela LGPD.</p>

            <button type="submit" class="btn btn-primary w-100 fw-semibold mt-3">
                <i class="bi bi-person-check me-1"></i>Criar Conta
            </button>
        </form>

        <div class="auth-divider">JÁ TENHO CONTA</div>

        <a href="<?php echo BASE_URL; ?>/login/" class="btn btn-outline-secondary w-100">
            <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/auth-foot.php'; ?>
