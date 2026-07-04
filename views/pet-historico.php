<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$usuarioId = (int)getUserId();
$petId = (int)($_GET['id'] ?? 0);

$petController = new PetController();
$pet = $petController->buscarSeForDoTutor($petId, $usuarioId);
if (!$pet) {
    setFlashMessage('Pet não encontrado.', MSG_ERROR);
    redirect('/meus-pets');
}

$pageTitle = 'Histórico de ' . $pet['nome'] . ' | Cadê Meu Pet?';
$atendimentoController = new AtendimentoController();
$historico = $atendimentoController->historicoDoPet($petId);

$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL],
    ['label' => 'Meus Pets', 'url' => BASE_URL . '/meus-pets'],
    ['label' => sanitize($pet['nome'])],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <h1 class="h3 fw-bold mb-1"><?php echo sanitize($pet['nome']); ?></h1>
    <p class="text-muted mb-4"><?php echo sanitize(ucfirst($pet['especie'])); ?><?php if (!empty($pet['raca'])): ?> · <?php echo sanitize($pet['raca']); ?><?php endif; ?></p>

    <h2 class="h5 fw-bold mb-3">Histórico de atendimentos</h2>
    <?php if (empty($historico)): ?>
        <div class="alert alert-info">Nenhum atendimento veterinário registrado ainda para este pet.</div>
    <?php else: ?>
        <div class="list-group shadow-sm">
            <?php foreach ($historico as $a): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold"><?php echo sanitize($a['motivo_consulta']); ?></div>
                            <div class="small text-muted">
                                <?php echo sanitize($a['clinica_nome']); ?> — <?php echo sanitize($a['veterinario_nome']); ?>
                            </div>
                        </div>
                        <span class="badge bg-<?php echo $a['status'] === 'finalizado' ? 'success' : 'warning text-dark'; ?>"><?php echo ucfirst($a['status']); ?></span>
                    </div>
                    <?php if ($a['status'] === 'finalizado'): ?>
                        <div class="small mt-2">
                            <?php if (!empty($a['diagnostico'])): ?><p class="mb-1"><strong>Diagnóstico:</strong> <?php echo nl2br(sanitize($a['diagnostico'])); ?></p><?php endif; ?>
                            <?php if (!empty($a['conduta'])): ?><p class="mb-1"><strong>Conduta:</strong> <?php echo nl2br(sanitize($a['conduta'])); ?></p><?php endif; ?>
                            <?php if (!empty($a['peso_kg'])): ?><p class="mb-1"><strong>Peso:</strong> <?php echo sanitize((string)$a['peso_kg']); ?> kg</p><?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="small text-muted mt-1"><?php echo formatDateTimeBR($a['criado_em']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
