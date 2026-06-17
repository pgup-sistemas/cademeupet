<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Criar Conta - PetFinder';

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

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="logo-icon-large mb-3">🐾</div>
                        <h2 class="fw-bold mb-2">Crie sua conta</h2>
                        <p class="text-muted">Leva menos de 2 minutos para começar a ajudar pets perdidos</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h6 class="fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Corrija os campos abaixo:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $erro): ?>
                                    <li><?php echo sanitize($erro); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nome" class="form-label">Nome completo *</label>
                                <input type="text" class="form-control form-control-lg" id="nome" name="nome" placeholder="Ex: Ana Souza"
                                       value="<?php echo sanitize($_POST['nome'] ?? ''); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="telefone" class="form-label">Telefone *</label>
                                <input type="text" class="form-control form-control-lg" id="telefone" name="telefone" placeholder="(69) 99999-9999"
                                       value="<?php echo sanitize($_POST['telefone'] ?? ''); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="voce@email.com"
                                       value="<?php echo sanitize($_POST['email'] ?? ''); ?>" required>
                            </div>

                            <div class="col-md-3">
                                <label for="cidade" class="form-label">Cidade</label>
                                <input type="text" class="form-control form-control-lg" id="cidade" name="cidade" placeholder="Porto Velho"
                                       value="<?php echo sanitize($_POST['cidade'] ?? ''); ?>">
                            </div>

                            <div class="col-md-3">
                                <label for="estado" class="form-label">UF</label>
                                <input type="text" maxlength="2" class="form-control form-control-lg text-uppercase" id="estado" name="estado" placeholder="RO"
                                       value="<?php echo sanitize($_POST['estado'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="senha" class="form-label">Senha *</label>
                                <input type="password" class="form-control form-control-lg" id="senha" name="senha" placeholder="Mínimo 8 caracteres"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label for="confirma_senha" class="form-label">Confirmar senha *</label>
                                <input type="password" class="form-control form-control-lg" id="confirma_senha" name="confirma_senha" placeholder="Digite novamente"
                                       required>
                            </div>
                        </div>

                        <div class="form-text text-muted mt-3">
                            * Campos obrigatórios. Guardamos seus dados com segurança seguindo a LGPD.
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 mt-4">
                            <i class="bi bi-person-check"></i> Criar Conta
                        </button>

                        <div class="text-center mt-4">
                            <p class="text-muted mb-0">Já tem uma conta? <a href="<?php echo BASE_URL; ?>/login/" class="text-decoration-none">Entrar agora</a></p>
                        </div>
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

.form-control-lg {
    border-radius: 10px;
    padding: 14px 18px;
}

.btn-lg {
    padding: 12px;
    border-radius: 10px;
    font-weight: 600;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
