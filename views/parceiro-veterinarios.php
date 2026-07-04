<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Veterinários da Clínica | Cadê Meu Pet?';
$usuarioId = (int)getUserId();
$perfilModel = new ParceiroPerfil();
$perfil = $perfilModel->findByUserId($usuarioId);

if (!$perfil || $perfil['categoria'] !== 'clinica') {
    setFlashMessage('Este recurso é exclusivo para parceiros da categoria Clínica.', MSG_ERROR);
    redirect('/parceiro/painel');
}

$controller = new ParceiroVeterinarioController();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Falha na validação do formulário. Atualize a página e tente novamente.';
    } else {
        $emailVet = trim($_POST['email_veterinario'] ?? '');
        $usuarioModel = new Usuario();
        $contaVet = $emailVet !== '' ? $usuarioModel->findByEmail($emailVet) : null;

        if (!$contaVet) {
            $errors[] = 'Não encontramos uma conta com esse e-mail. O veterinário precisa criar uma conta na plataforma antes de ser cadastrado pela clínica.';
        } else {
            $resultado = $controller->cadastrar($usuarioId, (int)$contaVet['id'], $_POST);
            if (!empty($resultado['success'])) {
                setFlashMessage('Veterinário cadastrado! Aguardando validação do CRMV pela nossa equipe.', MSG_SUCCESS);
                redirect('/parceiro/veterinarios');
            } else {
                $errors = $resultado['errors'] ?? ['Não foi possível cadastrar o veterinário.'];
            }
        }
    }
}

$veterinarios = $controller->listarDaClinica($usuarioId);

$statusLabel = [
    'pendente_validacao' => 'Aguardando validação',
    'aprovado' => 'Aprovado',
    'rejeitado' => 'Rejeitado',
    'suspenso' => 'Suspenso',
];
$statusBadge = [
    'pendente_validacao' => 'warning text-dark',
    'aprovado' => 'success',
    'rejeitado' => 'danger',
    'suspenso' => 'secondary',
];

$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL],
    ['label' => 'Parceiros', 'url' => BASE_URL . '/parceiros'],
    ['label' => 'Painel', 'url' => BASE_URL . '/parceiro/painel'],
    ['label' => 'Veterinários'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <h1 class="h3 fw-bold mb-4">Veterinários da clínica</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $erro): ?>
                    <li><?php echo sanitize($erro); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">Cadastrar novo veterinário</h2>
            <p class="text-muted small">O veterinário precisa já ter uma conta cadastrada na plataforma (com o mesmo e-mail informado aqui). A validação do CRMV é feita manualmente pela nossa equipe antes de liberar o acesso.</p>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">E-mail da conta do veterinário</label>
                        <input type="email" class="form-control" name="email_veterinario" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Nome completo</label>
                        <input type="text" class="form-control" name="nome_completo" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-semibold">Número do CRMV</label>
                        <input type="text" class="form-control" name="crmv_numero" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">UF do CRMV</label>
                        <input type="text" class="form-control" name="crmv_uf" maxlength="2" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Cadastrar veterinário</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">Equipe cadastrada</h2>
            <?php if (empty($veterinarios)): ?>
                <div class="alert alert-info mb-0">Nenhum veterinário cadastrado ainda.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>CRMV</th>
                                <th>Status</th>
                                <th>Motivo (se rejeitado)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($veterinarios as $v): ?>
                                <tr>
                                    <td><?php echo sanitize($v['nome_completo']); ?></td>
                                    <td><?php echo sanitize($v['crmv_numero'] . '-' . $v['crmv_uf']); ?></td>
                                    <td><span class="badge bg-<?php echo $statusBadge[$v['status']] ?? 'secondary'; ?>"><?php echo $statusLabel[$v['status']] ?? $v['status']; ?></span></td>
                                    <td class="small text-muted"><?php echo sanitize($v['motivo_rejeicao'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
