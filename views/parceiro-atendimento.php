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
            redirect('/parceiro/atendimento?id=' . $atendimentoId . '&aba=' . urlencode($_POST['aba_atual'] ?? 'anamnese'));
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

$vacinasExistentes = Atendimento::decodificarVacinas($atendimento['vacinas_aplicadas'] ?? null);
if (empty($vacinasExistentes)) {
    $vacinasExistentes = [['nome' => '', 'data' => '', 'lote' => '']];
}
$examesExistentes = Atendimento::decodificarExames($atendimento['exames_solicitados'] ?? null);
if (empty($examesExistentes)) {
    $examesExistentes = [['nome' => '', 'observacao' => '']];
}
$medicamentosExistentes = Atendimento::decodificarMedicamentos($atendimento['medicamentos_prescritos'] ?? null);
if (empty($medicamentosExistentes)) {
    $medicamentosExistentes = [['nome' => '', 'dosagem' => '', 'via' => '', 'frequencia' => '', 'duracao' => '']];
}

$historicoPet = array_values(array_filter(
    $controller->historicoDoPet((int)$atendimento['pet_id']),
    function ($a) use ($atendimentoId) { return (int)$a['id'] !== $atendimentoId; }
));

$abaInicial = $_GET['aba'] ?? 'anamnese';
$abasValidas = ['anamnese', 'exame-fisico', 'exames', 'receituario', 'diagnostico', 'historico', 'documentos'];
if (!in_array($abaInicial, $abasValidas, true)) {
    $abaInicial = 'anamnese';
}

$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL],
    ['label' => 'Painel', 'url' => BASE_URL . '/parceiro/painel'],
    ['label' => 'Atendimentos', 'url' => BASE_URL . '/parceiro/atendimentos'],
    ['label' => 'Atendimento #' . $atendimentoId],
];
$suppressBreadcrumbBar = true;
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/parceiro-sidebar.php'; ?>

    <main class="admin-content p-4">

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

    <ul class="nav nav-tabs mb-3" id="atendimentoTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-anamnese" data-aba="anamnese" type="button">Anamnese</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-exame-fisico" data-aba="exame-fisico" type="button">Exame Físico</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-exames" data-aba="exames" type="button">Exames</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-receituario" data-aba="receituario" type="button">Receituário</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-diagnostico" data-aba="diagnostico" type="button">Diagnóstico e Conduta</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-historico" data-aba="historico" type="button">Histórico do Pet</button>
        </li>
        <?php if ($atendimento['status'] === 'finalizado'): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-documentos" data-aba="documentos" type="button">Documentos</button>
            </li>
        <?php endif; ?>
    </ul>

    <form method="POST" id="formAtendimento">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="aba_atual" id="abaAtualInput" value="<?php echo sanitize($abaInicial); ?>">
        <fieldset <?php echo $somenteLeitura ? 'disabled' : ''; ?>>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-anamnese" role="tabpanel">
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body p-4">
                            <h2 class="h6 fw-bold mb-3">Motivo e anamnese</h2>
                            <p><strong>Motivo da consulta:</strong> <?php echo sanitize($atendimento['motivo_consulta']); ?></p>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Anamnese</label>
                                <textarea class="form-control" name="anamnese" rows="6"><?php echo sanitize($atendimento['anamnese'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-exame-fisico" role="tabpanel">
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
                            <h2 class="h6 fw-bold mb-3">Vacinas aplicadas nesta consulta</h2>
                            <div id="vacinasContainer">
                                <?php foreach ($vacinasExistentes as $v): ?>
                                    <div class="row repeater-linha mb-2">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control form-control-sm" name="vacina_nome[]" placeholder="Nome da vacina (ex: V10, Antirrábica)" value="<?php echo sanitize($v['nome'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" class="form-control form-control-sm" name="vacina_data[]" value="<?php echo sanitize($v['data'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control form-control-sm" name="vacina_lote[]" placeholder="Lote" value="<?php echo sanitize($v['lote'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger remover-linha">&times;</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (!$somenteLeitura): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2 btn-adicionar-linha" data-alvo="vacinasContainer" data-modelo="vacina">+ Adicionar vacina</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-exames" role="tabpanel">
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body p-4">
                            <h2 class="h6 fw-bold mb-3">Exames solicitados</h2>
                            <div id="examesContainer">
                                <?php foreach ($examesExistentes as $ex): ?>
                                    <div class="row repeater-linha mb-2">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control form-control-sm" name="exame_nome[]" placeholder="Ex: Hemograma completo, Raio-X torácico" value="<?php echo sanitize($ex['nome'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control form-control-sm" name="exame_observacao[]" placeholder="Observação (opcional)" value="<?php echo sanitize($ex['observacao'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger remover-linha">&times;</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (!$somenteLeitura): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2 btn-adicionar-linha" data-alvo="examesContainer" data-modelo="exame">+ Adicionar exame</button>
                            <?php endif; ?>
                            <p class="text-muted small mt-2 mb-0">Registro da solicitação — anexar resultado fica para uma fase futura.</p>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-receituario" role="tabpanel">
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body p-4">
                            <h2 class="h6 fw-bold mb-3">Receituário</h2>
                            <div id="medicamentosContainer">
                                <?php foreach ($medicamentosExistentes as $m): ?>
                                    <div class="row repeater-linha mb-2">
                                        <div class="col-md-3">
                                            <input type="text" class="form-control form-control-sm" name="medicamento_nome[]" placeholder="Medicamento" value="<?php echo sanitize($m['nome'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control form-control-sm" name="medicamento_dosagem[]" placeholder="Dosagem" value="<?php echo sanitize($m['dosagem'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control form-control-sm" name="medicamento_via[]" placeholder="Via (oral, IM...)" value="<?php echo sanitize($m['via'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control form-control-sm" name="medicamento_frequencia[]" placeholder="Frequência" value="<?php echo sanitize($m['frequencia'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control form-control-sm" name="medicamento_duracao[]" placeholder="Duração" value="<?php echo sanitize($m['duracao'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger remover-linha">&times;</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (!$somenteLeitura): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2 btn-adicionar-linha" data-alvo="medicamentosContainer" data-modelo="medicamento">+ Adicionar medicamento</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-diagnostico" role="tabpanel">
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body p-4">
                            <h2 class="h6 fw-bold mb-3">Diagnóstico e conduta</h2>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Diagnóstico</label>
                                <textarea class="form-control" name="diagnostico" rows="3"><?php echo sanitize($atendimento['diagnostico'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Conduta</label>
                                <textarea class="form-control" name="conduta" rows="3"><?php echo sanitize($atendimento['conduta'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Próxima consulta recomendada</label>
                                <input type="date" class="form-control" name="proxima_consulta_recomendada" value="<?php echo sanitize($atendimento['proxima_consulta_recomendada'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>

        <?php if (!$somenteLeitura): ?>
            <div class="d-flex gap-2">
                <button type="submit" name="action" value="salvar" class="btn btn-outline-primary">Salvar rascunho</button>
                <button type="submit" name="action" value="finalizar" class="btn btn-success" onclick="return confirm('Finalizar o atendimento? Depois de finalizado, o registro não pode mais ser editado.');">Finalizar atendimento</button>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Este atendimento já foi <?php echo $atendimento['status']; ?> e não pode mais ser editado.</div>
        <?php endif; ?>
    </form>

    <div class="tab-content">
        <div class="tab-pane fade" id="tab-historico" role="tabpanel">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">Atendimentos anteriores deste pet</h2>
                    <?php if (empty($historicoPet)): ?>
                        <div class="alert alert-info mb-0">Este é o primeiro atendimento registrado para este pet.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($historicoPet as $a): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-semibold"><?php echo sanitize($a['motivo_consulta']); ?></div>
                                            <div class="small text-muted"><?php echo sanitize($a['clinica_nome']); ?> — <?php echo sanitize($a['veterinario_nome']); ?></div>
                                        </div>
                                        <span class="badge bg-<?php echo $a['status'] === 'finalizado' ? 'success' : 'warning text-dark'; ?>"><?php echo ucfirst($a['status']); ?></span>
                                    </div>
                                    <?php if ($a['status'] === 'finalizado'): ?>
                                        <div class="small mt-2">
                                            <?php if (!empty($a['diagnostico'])): ?><p class="mb-1"><strong>Diagnóstico:</strong> <?php echo nl2br(sanitize($a['diagnostico'])); ?></p><?php endif; ?>
                                            <?php if (!empty($a['conduta'])): ?><p class="mb-1"><strong>Conduta:</strong> <?php echo nl2br(sanitize($a['conduta'])); ?></p><?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="small text-muted mt-1"><?php echo formatDateTimeBR($a['criado_em']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($atendimento['status'] === 'finalizado'): ?>
            <div class="tab-pane fade" id="tab-documentos" role="tabpanel">
                <?php $laudos = $laudoController->buscarPorAtendimento($atendimentoId); ?>
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold mb-3">Documentos (laudo/atestado/receituário)</h2>

                        <?php if (!empty($laudos)): ?>
                            <div class="list-group mb-3">
                                <?php
                                $tipoLabel = ['laudo' => 'Laudo', 'atestado' => 'Atestado', 'receituario' => 'Receituário'];
                                $statusBadgeDoc = ['rascunho' => 'secondary', 'aguardando_assinaturas' => 'warning text-dark', 'assinado' => 'success', 'revogado' => 'secondary'];
                                ?>
                                <?php foreach ($laudos as $l): ?>
                                    <a class="list-group-item list-group-item-action" href="<?php echo BASE_URL; ?>/laudo?id=<?php echo (int)$l['id']; ?>">
                                        <div class="d-flex justify-content-between">
                                            <span><?php echo $tipoLabel[$l['tipo']] ?? ucfirst($l['tipo']); ?> — <?php echo sanitize($l['codigo_verificacao']); ?></span>
                                            <span class="badge bg-<?php echo $statusBadgeDoc[$l['documento_status']] ?? 'secondary'; ?>"><?php echo ucfirst(str_replace('_', ' ', $l['documento_status'])); ?></span>
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
            </div>
        <?php endif; ?>
    </div>

    </main>
</div>

<script>
(function () {
    // ── Repeaters genéricos (vacina/exame/medicamento) ──────────────────
    var modelos = {
        vacina: '<div class="col-md-5"><input type="text" class="form-control form-control-sm" name="vacina_nome[]" placeholder="Nome da vacina (ex: V10, Antirrábica)"></div>' +
                '<div class="col-md-3"><input type="date" class="form-control form-control-sm" name="vacina_data[]"></div>' +
                '<div class="col-md-3"><input type="text" class="form-control form-control-sm" name="vacina_lote[]" placeholder="Lote"></div>' +
                '<div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remover-linha">&times;</button></div>',
        exame:  '<div class="col-md-5"><input type="text" class="form-control form-control-sm" name="exame_nome[]" placeholder="Ex: Hemograma completo, Raio-X torácico"></div>' +
                '<div class="col-md-6"><input type="text" class="form-control form-control-sm" name="exame_observacao[]" placeholder="Observação (opcional)"></div>' +
                '<div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remover-linha">&times;</button></div>',
        medicamento: '<div class="col-md-3"><input type="text" class="form-control form-control-sm" name="medicamento_nome[]" placeholder="Medicamento"></div>' +
                '<div class="col-md-2"><input type="text" class="form-control form-control-sm" name="medicamento_dosagem[]" placeholder="Dosagem"></div>' +
                '<div class="col-md-2"><input type="text" class="form-control form-control-sm" name="medicamento_via[]" placeholder="Via (oral, IM...)"></div>' +
                '<div class="col-md-2"><input type="text" class="form-control form-control-sm" name="medicamento_frequencia[]" placeholder="Frequência"></div>' +
                '<div class="col-md-2"><input type="text" class="form-control form-control-sm" name="medicamento_duracao[]" placeholder="Duração"></div>' +
                '<div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remover-linha">&times;</button></div>'
    };

    document.querySelectorAll('.btn-adicionar-linha').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var container = document.getElementById(btn.dataset.alvo);
            var div = document.createElement('div');
            div.className = 'row repeater-linha mb-2';
            div.innerHTML = modelos[btn.dataset.modelo];
            container.appendChild(div);
        });
    });

    document.querySelectorAll('#vacinasContainer, #examesContainer, #medicamentosContainer').forEach(function (container) {
        container.addEventListener('click', function (e) {
            if (!e.target.classList.contains('remover-linha')) return;
            var linhas = container.querySelectorAll('.repeater-linha');
            if (linhas.length > 1) {
                e.target.closest('.repeater-linha').remove();
            } else {
                e.target.closest('.repeater-linha').querySelectorAll('input').forEach(function (i) { i.value = ''; });
            }
        });
    });

    // ── Abas com deep-link via ?aba=X ───────────────────────────────────
    var abaInicial = <?php echo json_encode($abaInicial); ?>;
    var abaAtualInput = document.getElementById('abaAtualInput');

    var botaoInicial = document.querySelector('#atendimentoTabs [data-aba="' + abaInicial + '"]');
    if (!botaoInicial) {
        botaoInicial = document.querySelector('#atendimentoTabs .nav-link');
    }
    if (botaoInicial && window.bootstrap) {
        new bootstrap.Tab(botaoInicial).show();
    }

    document.querySelectorAll('#atendimentoTabs button').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () {
            var aba = btn.dataset.aba;
            if (abaAtualInput) abaAtualInput.value = aba;
            var url = new URL(window.location.href);
            url.searchParams.set('aba', aba);
            window.history.replaceState(null, '', url);
        });
    });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
