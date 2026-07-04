<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Meus Pets | Cadê Meu Pet?';
$usuarioId = (int)getUserId();
$controller = new PetController();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Falha na validação do formulário. Atualize a página e tente novamente.';
    } else {
        $acao = $_POST['action'] ?? 'criar';

        if ($acao === 'criar') {
            $resultado = $controller->criar($usuarioId, $_POST, $_FILES['foto'] ?? []);
            if (!empty($resultado['success'])) {
                setFlashMessage('Pet cadastrado com sucesso!', MSG_SUCCESS);
                redirect('/meus-pets');
            } else {
                $errors = $resultado['errors'] ?? ['Não foi possível cadastrar o pet.'];
            }
        } elseif ($acao === 'desativar') {
            $petId = (int)($_POST['pet_id'] ?? 0);
            if ($petId && $controller->desativar($petId, $usuarioId)) {
                setFlashMessage('Pet removido da sua lista.', MSG_SUCCESS);
            } else {
                $errors[] = 'Não foi possível remover este pet.';
            }
            redirect('/meus-pets');
        }
    }
}

$pets = $controller->listarPorTutor($usuarioId);

$opcoesEspecie = [
    ESPECIE_CACHORRO => 'Cachorro',
    ESPECIE_GATO => 'Gato',
    ESPECIE_AVE => 'Ave',
    ESPECIE_OUTRO => 'Outro',
];

$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL],
    ['label' => 'Meus Pets'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Cadastrar novo pet</h2>
                    <p class="text-muted small">A ficha do seu pet acumula o histórico de atendimentos veterinários feitos por clínicas parceiras.</p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $erro): ?>
                                    <li><?php echo sanitize($erro); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="mt-3">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="criar">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nome do pet</label>
                            <input type="text" class="form-control" name="nome" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Espécie</label>
                            <select class="form-select" name="especie" required>
                                <?php foreach ($opcoesEspecie as $valor => $rotulo): ?>
                                    <option value="<?php echo $valor; ?>"><?php echo $rotulo; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Raça</label>
                                <input type="text" class="form-control" name="raca">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Sexo</label>
                                <select class="form-select" name="sexo">
                                    <option value="">Não informado</option>
                                    <option value="macho">Macho</option>
                                    <option value="femea">Fêmea</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Data de nascimento</label>
                                <input type="date" class="form-control" name="data_nascimento">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Ou idade aproximada (meses)</label>
                                <input type="number" min="0" class="form-control" name="idade_aproximada_meses">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Cor</label>
                            <input type="text" class="form-control" name="cor">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Foto</label>
                            <input type="file" class="form-control" name="foto" accept="image/jpeg,image/png,image/webp">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Cadastrar pet</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <h2 class="h4 fw-bold mb-3">Meus pets</h2>

            <?php if (empty($pets)): ?>
                <div class="alert alert-info">Você ainda não cadastrou nenhum pet.</div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($pets as $pet): ?>
                        <div class="col-md-6">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body p-3">
                                    <?php if (!empty($pet['foto'])): ?>
                                        <img src="<?php echo BASE_URL; ?>/uploads/pets/<?php echo sanitize($pet['foto']); ?>" class="img-fluid rounded mb-2" style="max-height:150px;object-fit:cover;width:100%;" alt="">
                                    <?php endif; ?>
                                    <h3 class="h6 fw-bold mb-1"><?php echo sanitize($pet['nome']); ?></h3>
                                    <p class="text-muted small mb-2">
                                        <?php echo sanitize($opcoesEspecie[$pet['especie']] ?? ucfirst($pet['especie'])); ?>
                                        <?php if (!empty($pet['raca'])): ?> · <?php echo sanitize($pet['raca']); ?><?php endif; ?>
                                    </p>
                                    <form method="POST" onsubmit="return confirm('Remover este pet da sua lista?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="desativar">
                                        <input type="hidden" name="pet_id" value="<?php echo (int)$pet['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
