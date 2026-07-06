<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Atendimentos | Cadê Meu Pet?';
$usuarioId = (int)getUserId();
$controller = new AtendimentoController();
$errors = [];

$veterinario = $controller->veterinarioAprovadoOuNull($usuarioId);
$perfilModel = new ParceiroPerfil();
$perfilDono = $perfilModel->findByUserId($usuarioId);
$ehDonoClinica = $perfilDono && $perfilDono['categoria'] === 'clinica';

if (!$veterinario && !$ehDonoClinica) {
    setFlashMessage('Este recurso é exclusivo para veterinários aprovados ou donos de clínica parceira.', MSG_ERROR);
    redirect('/parceiro/painel');
}

$termoBusca = trim($_GET['buscar'] ?? '');
$petsEncontrados = [];
if ($veterinario && $termoBusca !== '') {
    $petsEncontrados = $controller->buscarPetsPorTermo($termoBusca);
}

$termoBuscaTutor = trim($_GET['buscar_tutor'] ?? '');
$tutoresEncontrados = [];
if ($veterinario && $termoBuscaTutor !== '') {
    $tutoresEncontrados = $controller->buscarTutoresPorTermo($termoBuscaTutor);
}

$tutorSelecionadoId = (int)($_GET['tutor_id'] ?? 0);
$tutorSelecionado = $tutorSelecionadoId ? $controller->buscarTutorPorId($tutorSelecionadoId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Falha na validação do formulário. Atualize a página e tente novamente.';
    } elseif ($veterinario) {
        $acao = $_POST['action'] ?? '';

        if ($acao === 'criar_pet_e_abrir') {
            $tutorId = (int)($_POST['tutor_usuario_id'] ?? 0);
            if (!$tutorId || !$controller->buscarTutorPorId($tutorId)) {
                $errors[] = 'Selecione um tutor válido (use a busca por nome ou telefone).';
            }
            $rPet = empty($errors) ? $controller->criarPetDuranteAtendimento($usuarioId, $tutorId, [
                'nome' => $_POST['pet_nome'] ?? '', 'especie' => $_POST['pet_especie'] ?? '',
                'raca' => $_POST['pet_raca'] ?? '', 'sexo' => $_POST['pet_sexo'] ?? '',
            ]) : ['success' => false];
            if (!empty($rPet['success'])) {
                $rAtend = $controller->abrir($usuarioId, (int)$rPet['pet_id'], $_POST['motivo_consulta'] ?? '');
                if (!empty($rAtend['success'])) {
                    redirect('/parceiro/atendimento?id=' . $rAtend['id']);
                }
                $errors = $rAtend['errors'] ?? ['Erro ao abrir atendimento.'];
            } elseif (empty($errors)) {
                $errors = $rPet['errors'] ?? ['Erro ao cadastrar pet.'];
            }
        } elseif ($acao === 'abrir_existente') {
            $rAtend = $controller->abrir($usuarioId, (int)$_POST['pet_id'], $_POST['motivo_consulta'] ?? '');
            if (!empty($rAtend['success'])) {
                redirect('/parceiro/atendimento?id=' . $rAtend['id']);
            }
            $errors = $rAtend['errors'] ?? ['Erro ao abrir atendimento.'];
        }
    }
}

$emAndamento = $veterinario ? $controller->listarEmAndamento($usuarioId) : [];
$daClinica = $ehDonoClinica ? $controller->listarDaClinica($usuarioId) : [];

$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL],
    ['label' => 'Parceiros', 'url' => BASE_URL . '/parceiros'],
    ['label' => 'Painel', 'url' => BASE_URL . '/parceiro/painel'],
    ['label' => 'Atendimentos'],
];
$suppressBreadcrumbBar = true;
$parceiroPerfilAtual = $perfilDono;
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/parceiro-sidebar.php'; ?>

    <main class="admin-content p-4">

    <h1 class="h3 fw-bold mb-4">Atendimentos</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?php echo sanitize($e); ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <?php if (!$veterinario): ?>
        <div class="alert alert-warning">
            Você é dono desta clínica, mas para abrir atendimentos é necessário estar cadastrado e aprovado como veterinário.
            <a href="<?php echo BASE_URL; ?>/parceiro/veterinarios">Cadastre-se aqui</a>.
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Abrir novo atendimento</h2>

                <form method="GET" class="mb-3">
                    <label class="form-label fw-semibold">Buscar pet já cadastrado (nome do pet, nome ou telefone do tutor)</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="buscar" value="<?php echo sanitize($termoBusca); ?>">
                        <button type="submit" class="btn btn-outline-primary">Buscar</button>
                    </div>
                </form>

                <?php if ($termoBusca !== ''): ?>
                    <?php if (empty($petsEncontrados)): ?>
                        <div class="alert alert-info">Nenhum pet encontrado. Se o tutor já tem conta, cadastre o pet abaixo.</div>
                    <?php else: ?>
                        <div class="list-group mb-3">
                            <?php foreach ($petsEncontrados as $p): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo sanitize($p['nome']); ?></strong> (<?php echo sanitize(ucfirst($p['especie'])); ?>)
                                        — Tutor: <?php echo sanitize($p['tutor_nome']); ?> (<?php echo sanitize($p['tutor_telefone']); ?>)
                                    </div>
                                    <form method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="abrir_existente">
                                        <input type="hidden" name="pet_id" value="<?php echo (int)$p['id']; ?>">
                                        <input type="text" class="form-control form-control-sm" name="motivo_consulta" placeholder="Motivo da consulta" required>
                                        <button type="submit" class="btn btn-sm btn-primary">Abrir atendimento</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <details class="mt-2" <?php echo $tutorSelecionado ? 'open' : ''; ?>>
                    <summary class="text-primary" style="cursor:pointer;">Pet não encontrado? Cadastrar novo pet (tutor já com conta)</summary>

                    <?php if (!$tutorSelecionado): ?>
                        <form method="GET" class="mt-3">
                            <input type="hidden" name="buscar" value="<?php echo sanitize($termoBusca); ?>">
                            <label class="form-label fw-semibold">1) Buscar o tutor (nome ou telefone)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="buscar_tutor" value="<?php echo sanitize($termoBuscaTutor); ?>">
                                <button type="submit" class="btn btn-outline-primary">Buscar tutor</button>
                            </div>
                        </form>

                        <?php if ($termoBuscaTutor !== ''): ?>
                            <?php if (empty($tutoresEncontrados)): ?>
                                <div class="alert alert-info mt-2 mb-0">Nenhum tutor encontrado com esse nome/telefone. Ele precisa ter uma conta cadastrada na plataforma primeiro.</div>
                            <?php else: ?>
                                <div class="list-group mt-2">
                                    <?php foreach ($tutoresEncontrados as $t): ?>
                                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                           href="?buscar=<?php echo urlencode($termoBusca); ?>&buscar_tutor=<?php echo urlencode($termoBuscaTutor); ?>&tutor_id=<?php echo (int)$t['id']; ?>#novo-pet">
                                            <span><?php echo sanitize($t['nome']); ?> — <?php echo sanitize($t['telefone']); ?></span>
                                            <span class="badge bg-primary">Selecionar</span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                    <form method="POST" class="mt-3" id="novo-pet">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="criar_pet_e_abrir">
                        <input type="hidden" name="tutor_usuario_id" value="<?php echo (int)$tutorSelecionado['id']; ?>">
                        <div class="alert alert-secondary d-flex justify-content-between align-items-center">
                            <span>Tutor selecionado: <strong><?php echo sanitize($tutorSelecionado['nome']); ?></strong> (<?php echo sanitize($tutorSelecionado['telefone']); ?>)</span>
                            <a href="?buscar=<?php echo urlencode($termoBusca); ?>" class="btn btn-sm btn-outline-secondary">Trocar tutor</a>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nome do pet</label>
                                <input type="text" class="form-control" name="pet_nome" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Espécie</label>
                                <select class="form-select" name="pet_especie" required>
                                    <option value="cachorro">Cachorro</option>
                                    <option value="gato">Gato</option>
                                    <option value="ave">Ave</option>
                                    <option value="outro">Outro</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Motivo da consulta</label>
                            <input type="text" class="form-control" name="motivo_consulta" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Cadastrar pet e abrir atendimento</button>
                    </form>
                    <?php endif; ?>
                </details>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Meus atendimentos em andamento</h2>
                <?php if (empty($emAndamento)): ?>
                    <div class="alert alert-info mb-0">Nenhum atendimento em andamento.</div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($emAndamento as $a): ?>
                            <a class="list-group-item list-group-item-action" href="<?php echo BASE_URL; ?>/parceiro/atendimento?id=<?php echo (int)$a['id']; ?>">
                                <?php echo sanitize($a['pet_nome']); ?> — <?php echo sanitize($a['motivo_consulta']); ?>
                                <span class="small text-muted d-block"><?php echo formatDateTimeBR($a['criado_em']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($ehDonoClinica): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Atendimentos da clínica (todos os veterinários)</h2>
                <?php if (empty($daClinica)): ?>
                    <div class="alert alert-info mb-0">Nenhum atendimento registrado ainda.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Pet</th><th>Veterinário</th><th>Motivo</th><th>Status</th><th>Data</th></tr></thead>
                            <tbody>
                                <?php foreach ($daClinica as $a): ?>
                                    <tr>
                                        <td><?php echo sanitize($a['pet_nome']); ?></td>
                                        <td><?php echo sanitize($a['veterinario_nome']); ?></td>
                                        <td><?php echo sanitize($a['motivo_consulta']); ?></td>
                                        <td><?php echo sanitize(ucfirst($a['status'])); ?></td>
                                        <td><?php echo formatDateTimeBR($a['criado_em']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
