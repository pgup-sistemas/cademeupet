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
$laudoController = new LaudoController();
$laudos = $laudoController->historicoDoPet($petId);
$tipoLabel = ['laudo' => 'Laudo', 'atestado' => 'Atestado', 'receituario' => 'Receituário', 'pedido_exame' => 'Pedido de Exames'];
$carteiraVacinacao = $atendimentoController->carteiraDeVacinacao($petId);

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

    <h2 class="h5 fw-bold mb-3">Carteira de vacinação</h2>
    <?php if (empty($carteiraVacinacao)): ?>
        <div class="alert alert-info">Nenhuma vacina registrada ainda.</div>
    <?php else: ?>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-striped">
                <thead><tr><th>Vacina</th><th>Data</th><th>Lote</th><th>Aplicada em</th></tr></thead>
                <tbody>
                    <?php foreach ($carteiraVacinacao as $v): ?>
                        <tr>
                            <td><?php echo sanitize($v['nome']); ?></td>
                            <td><?php echo !empty($v['data']) ? sanitize(date('d/m/Y', strtotime($v['data']))) : '—'; ?></td>
                            <td><?php echo sanitize($v['lote'] ?? '—'); ?></td>
                            <td class="small text-muted"><?php echo sanitize($v['clinica']); ?> — <?php echo sanitize($v['veterinario']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2 class="h5 fw-bold mb-3">Histórico de atendimentos</h2>
    <?php if (empty($historico)): ?>
        <div class="alert alert-info">Nenhum atendimento veterinário registrado ainda para este pet.</div>
    <?php else: ?>
        <div class="list-group shadow-sm">
            <?php foreach ($historico as $a): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-secondary me-1"><?php echo Atendimento::labelTipoAtendimento($a['tipo_atendimento'] ?? 'consulta'); ?></span>
                            <span class="fw-semibold"><?php echo sanitize($a['motivo_consulta']); ?></span>
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

    <h2 class="h5 fw-bold mb-3 mt-4">Documentos assinados (laudos, atestados, receituários)</h2>
    <?php if (empty($laudos)): ?>
        <div class="alert alert-info">Nenhum documento assinado ainda.</div>
    <?php else: ?>
        <div class="list-group shadow-sm">
            <?php foreach ($laudos as $l): ?>
                <a class="list-group-item list-group-item-action" href="<?php echo BASE_URL; ?>/laudo?id=<?php echo (int)$l['id']; ?>">
                    <div class="d-flex justify-content-between">
                        <span><?php echo $tipoLabel[$l['tipo']] ?? ucfirst($l['tipo']); ?> — <?php echo sanitize($l['motivo_consulta']); ?></span>
                        <span class="small text-muted"><?php echo formatDateTimeBR($l['documento_criado_em']); ?></span>
                    </div>
                    <div class="small text-muted"><?php echo sanitize($l['clinica_nome']); ?> — <?php echo sanitize($l['veterinario_nome']); ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
