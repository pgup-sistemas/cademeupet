<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Atendimento | Cadê Meu Pet?';
$usuarioId = (int)getUserId();
$controller = new AtendimentoController();
$laudoController = new LaudoController();
$errors = [];

$atendimentoId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Falha na validação do formulário. Atualize a página e tente novamente.';
    } else {
        $acao = $_POST['action'] ?? '';

        if ($acao === 'salvar') {
            $resultado = $controller->atualizarCampos($atendimentoId, $usuarioId, $_POST);
            if (!empty($resultado['success'])) {
                setFlashMessage('Atendimento salvo.', MSG_SUCCESS);
            } else {
                $errors = $resultado['errors'] ?? ['Erro ao salvar.'];
            }
            redirect('/parceiro/atendimento?id=' . $atendimentoId);
        } elseif ($acao === 'finalizar') {
            $controller->atualizarCampos($atendimentoId, $usuarioId, $_POST);
            $resultado = $controller->finalizar($atendimentoId, $usuarioId);
            if (!empty($resultado['success'])) {
                setFlashMessage('Atendimento finalizado com sucesso.', MSG_SUCCESS);
                redirect('/parceiro/atendimentos');
            } else {
                $errors = $resultado['errors'] ?? ['Erro ao finalizar.'];
            }
        } elseif ($acao === 'gerar_laudo') {
            $resultado = $laudoController->gerar($atendimentoId, $usuarioId, $_POST['tipo_laudo'] ?? '', $_POST['conteudo_laudo'] ?? '');
            if (!empty($resultado['success'])) {
                setFlashMessage('Documento gerado. Revise e assine para torná-lo definitivo.', MSG_SUCCESS);
                redirect('/laudo?id=' . $resultado['id']);
            } else {
                $errors = $resultado['errors'] ?? ['Erro ao gerar documento.'];
            }
        }
    }
}

$atendimento = $controller->buscarSeForDoVeterinario($atendimentoId, $usuarioId);
if (!$atendimento) {
    setFlashMessage('Atendimento não encontrado ou você não tem acesso a ele.', MSG_ERROR);
    redirect('/parceiro/atendimentos');
}

$somenteLeitura = $atendimento['status'] !== 'em_andamento';

$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL],
    ['label' => 'Painel', 'url' => BASE_URL . '/parceiro/painel'],
    ['label' => 'Atendimentos', 'url' => BASE_URL . '/parceiro/atendimentos'],
    ['label' => 'Atendimento #' . $atendimentoId],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1"><?php echo sanitize($atendimento['pet_nome']); ?> (<?php echo sanitize(ucfirst($atendimento['pet_especie'])); ?>)</h1>
            <p class="text-muted mb-0">Veterinário: <?php echo sanitize($atendimento['veterinario_nome']); ?> — CRMV <?php echo sanitize($atendimento['crmv_numero'] . '-' . $atendimento['crmv_uf']); ?></p>
        </div>
        <span class="badge bg-<?php echo $atendimento['status'] === 'finalizado' ? 'success' : ($atendimento['status'] === 'cancelado' ? 'secondary' : 'warning text-dark'); ?>">
            <?php echo ucfirst($atendimento['status']); ?>
        </span>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?php echo sanitize($e); ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <fieldset <?php echo $somenteLeitura ? 'disabled' : ''; ?>>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">Motivo e anamnese</h2>
                    <p><strong>Motivo da consulta:</strong> <?php echo sanitize($atendimento['motivo_consulta']); ?></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Anamnese</label>
                        <textarea class="form-control" name="anamnese" rows="3"><?php echo sanitize($atendimento['anamnese'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">Exame físico</h2>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Peso (kg)</label>
                            <input type="number" step="0.01" class="form-control" name="peso_kg" value="<?php echo sanitize((string)($atendimento['peso_kg'] ?? '')); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Temperatura (°C)</label>
                            <input type="number" step="0.1" class="form-control" name="temperatura_c" value="<?php echo sanitize((string)($atendimento['temperatura_c'] ?? '')); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Freq. cardíaca (bpm)</label>
                            <input type="number" class="form-control" name="frequencia_cardiaca_bpm" value="<?php echo sanitize((string)($atendimento['frequencia_cardiaca_bpm'] ?? '')); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Freq. respiratória (mpm)</label>
                            <input type="number" class="form-control" name="frequencia_respiratoria_mpm" value="<?php echo sanitize((string)($atendimento['frequencia_respiratoria_mpm'] ?? '')); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Mucosas</label>
                            <input type="text" class="form-control" name="mucosas" placeholder="Ex: normocoradas" value="<?php echo sanitize($atendimento['mucosas'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Grau de hidratação</label>
                            <input type="text" class="form-control" name="grau_hidratacao" placeholder="Ex: normohidratado" value="<?php echo sanitize($atendimento['grau_hidratacao'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Exame físico (observações complementares)</label>
                        <textarea class="form-control" name="exame_fisico" rows="3"><?php echo sanitize($atendimento['exame_fisico'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">Diagnóstico e conduta</h2>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Diagnóstico</label>
                        <textarea class="form-control" name="diagnostico" rows="2"><?php echo sanitize($atendimento['diagnostico'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Conduta</label>
                        <textarea class="form-control" name="conduta" rows="2"><?php echo sanitize($atendimento['conduta'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Medicamentos prescritos</label>
                        <textarea class="form-control" name="medicamentos_prescritos" rows="2"><?php echo sanitize($atendimento['medicamentos_prescritos'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Próxima consulta recomendada</label>
                        <input type="date" class="form-control" name="proxima_consulta_recomendada" value="<?php echo sanitize($atendimento['proxima_consulta_recomendada'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </fieldset>

        <?php if (!$somenteLeitura): ?>
            <button type="submit" name="action" value="salvar" class="btn btn-outline-primary">Salvar rascunho</button>
            <button type="submit" name="action" value="finalizar" class="btn btn-success" onclick="return confirm('Finalizar o atendimento? Depois de finalizado, o registro não pode mais ser editado.');">Finalizar atendimento</button>
        <?php else: ?>
            <div class="alert alert-info">Este atendimento já foi <?php echo $atendimento['status']; ?> e não pode mais ser editado.</div>
        <?php endif; ?>
    </form>

    <?php if ($atendimento['status'] === 'finalizado'): ?>
        <?php $laudos = $laudoController->buscarPorAtendimento($atendimentoId); ?>
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Documentos (laudo/atestado/receituário)</h2>

                <?php if (!empty($laudos)): ?>
                    <div class="list-group mb-3">
                        <?php
                        $tipoLabel = ['laudo' => 'Laudo', 'atestado' => 'Atestado', 'receituario' => 'Receituário'];
                        $statusBadge = ['rascunho' => 'secondary', 'aguardando_assinaturas' => 'warning text-dark', 'assinado' => 'success', 'revogado' => 'secondary'];
                        ?>
                        <?php foreach ($laudos as $l): ?>
                            <a class="list-group-item list-group-item-action" href="<?php echo BASE_URL; ?>/laudo?id=<?php echo (int)$l['id']; ?>">
                                <div class="d-flex justify-content-between">
                                    <span><?php echo $tipoLabel[$l['tipo']] ?? ucfirst($l['tipo']); ?> — <?php echo sanitize($l['codigo_verificacao']); ?></span>
                                    <span class="badge bg-<?php echo $statusBadge[$l['documento_status']] ?? 'secondary'; ?>"><?php echo ucfirst(str_replace('_', ' ', $l['documento_status'])); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <details>
                    <summary class="text-primary" style="cursor:pointer;">Gerar novo documento</summary>
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="gerar_laudo">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tipo</label>
                            <select class="form-select" name="tipo_laudo" required>
                                <option value="laudo">Laudo</option>
                                <option value="atestado">Atestado</option>
                                <option value="receituario">Receituário</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Conteúdo</label>
                            <textarea class="form-control" name="conteudo_laudo" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Gerar documento (ainda não assinado)</button>
                    </form>
                </details>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
