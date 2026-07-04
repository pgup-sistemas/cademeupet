<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$pageTitle = 'Admin - Veterinários | Cadê Meu Pet?';
$controller = new ParceiroVeterinarioController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Falha na validação do formulário. Recarregue a página.', MSG_ERROR);
        redirect('/admin/veterinarios');
    }

    $acao = $_POST['action'] ?? '';
    $veterinarioId = (int)($_POST['veterinario_id'] ?? 0);
    $adminId = (int)getUserId();

    if ($acao === 'aprovar') {
        $resultado = $controller->aprovar($veterinarioId, $adminId);
        setFlashMessage($resultado['success'] ? 'Veterinário aprovado.' : ($resultado['error'] ?? 'Erro.'), $resultado['success'] ? MSG_SUCCESS : MSG_ERROR);
    } elseif ($acao === 'rejeitar') {
        $motivo = trim($_POST['motivo'] ?? 'Não especificado');
        $resultado = $controller->rejeitar($veterinarioId, $adminId, $motivo);
        setFlashMessage($resultado['success'] ? 'Veterinário rejeitado.' : ($resultado['error'] ?? 'Erro.'), $resultado['success'] ? MSG_SUCCESS : MSG_ERROR);
    }
    redirect('/admin/veterinarios');
}

$pendentes = $controller->listarFilaAdmin();
$todos = $controller->listarTodosAdmin();

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
    ['label' => 'Admin',  'url' => BASE_URL . '/admin'],
    ['label' => 'Veterinários'],
];
$suppressBreadcrumbBar = true;
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

    <main class="admin-content p-4">
        <h1 class="h3 fw-bold mb-4">Validação de Veterinários (CRMV)</h1>
        <p class="text-muted">Não há API pública/gratuita de validação de CRMV — confira manualmente no site do conselho da UF antes de aprovar.</p>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Fila de validação (<?php echo count($pendentes); ?>)</h2>
                <?php if (empty($pendentes)): ?>
                    <div class="alert alert-info mb-0">Nenhuma solicitação pendente.</div>
                <?php else: ?>
                    <?php foreach ($pendentes as $v): ?>
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <div class="fw-semibold"><?php echo sanitize($v['nome_completo']); ?></div>
                                    <div class="small text-muted">
                                        CRMV <?php echo sanitize($v['crmv_numero'] . '-' . $v['crmv_uf']); ?> ·
                                        Clínica: <?php echo sanitize($v['clinica_nome']); ?>
                                        (<?php echo sanitize($v['clinica_cidade'] . '/' . $v['clinica_estado']); ?>)
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <form method="POST" onsubmit="return confirm('Confirma que validou o CRMV no site do conselho?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="aprovar">
                                        <input type="hidden" name="veterinario_id" value="<?php echo (int)$v['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success">Aprovar</button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalRejeitar<?php echo (int)$v['id']; ?>">
                                        Rejeitar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="modalRejeitar<?php echo (int)$v['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Rejeitar veterinário</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="rejeitar">
                                            <input type="hidden" name="veterinario_id" value="<?php echo (int)$v['id']; ?>">
                                            <label class="form-label">Motivo</label>
                                            <textarea class="form-control" name="motivo" required></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-danger">Rejeitar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Todos os veterinários</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr><th>Nome</th><th>CRMV</th><th>Clínica</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todos as $v): ?>
                                <tr>
                                    <td><?php echo sanitize($v['nome_completo']); ?></td>
                                    <td><?php echo sanitize($v['crmv_numero'] . '-' . $v['crmv_uf']); ?></td>
                                    <td><?php echo sanitize($v['clinica_nome']); ?></td>
                                    <td><span class="badge bg-<?php echo $statusBadge[$v['status']] ?? 'secondary'; ?>"><?php echo $statusLabel[$v['status']] ?? $v['status']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
