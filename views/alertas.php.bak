<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Meus Alertas - PetFinder';
$controller = new AlertaController();
$usuarioId = (int) getUserId();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Falha na validação do formulário. Atualize a página e tente novamente.';
    } else {
        $acao = $_POST['action'] ?? 'create';
        $alertaId = isset($_POST['alerta_id']) ? (int) $_POST['alerta_id'] : null;

        switch ($acao) {
            case 'create':
                $resultado = $controller->criar($usuarioId, $_POST, true);
                if (!empty($resultado['success'])) {
                    setFlashMessage('Alerta criado com sucesso! Você receberá um resumo por e-mail.', MSG_SUCCESS);
                    redirect('/alertas.php');
                } else {
                    $errors = $resultado['errors'] ?? ['Não foi possível criar o alerta.'];
                }
                break;

            case 'toggle':
                if ($alertaId && $controller->alternarStatus($usuarioId, $alertaId)) {
                    setFlashMessage('Status do alerta atualizado.', MSG_SUCCESS);
                    redirect('/alertas.php');
                } else {
                    $errors[] = 'Não foi possível alterar o status deste alerta.';
                }
                break;

            case 'delete':
                if ($alertaId && $controller->remover($usuarioId, $alertaId)) {
                    setFlashMessage('Alerta removido com sucesso.', MSG_SUCCESS);
                    redirect('/alertas.php');
                } else {
                    $errors[] = 'Não foi possível remover este alerta.';
                }
                break;

            default:
                $errors[] = 'Ação inválida.';
        }
    }
}

$alertas = $controller->listarPorUsuario($usuarioId);
$opcoesTipo = [
    'ambos' => 'Todos',
    'perdido' => 'Perdidos',
    'encontrado' => 'Encontrados'
];
$opcoesEspecie = [
    '' => 'Qualquer espécie',
    ESPECIE_CACHORRO => 'Cachorro',
    ESPECIE_GATO => 'Gato',
    ESPECIE_AVE => 'Ave',
    ESPECIE_OUTRO => 'Outro'
];
$opcoesRaio = [5, 10, 20, 50];

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Criar novo alerta</h2>
                    <p class="text-muted small">Receba e-mails quando anúncios corresponderem aos filtros escolhidos.</p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $erro): ?>
                                    <li><?php echo sanitize($erro); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="mt-3">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="create">

                        <div class="mb-3">
                            <label for="tipo" class="form-label fw-semibold">Tipo de anúncio</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <?php foreach ($opcoesTipo as $valor => $rotulo): ?>
                                    <option value="<?php echo $valor; ?>"><?php echo $rotulo; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="especie" class="form-label fw-semibold">Espécie</label>
                            <select class="form-select" id="especie" name="especie">
                                <?php foreach ($opcoesEspecie as $valor => $rotulo): ?>
                                    <option value="<?php echo $valor; ?>"><?php echo $rotulo; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Opcional, escolha "Qualquer" para não filtrar.</small>
                        </div>

                        <div class="mb-3">
                            <label for="cidade" class="form-label fw-semibold">Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade" required placeholder="Ex: Porto Velho">
                        </div>

                        <div class="mb-3">
                            <label for="estado" class="form-label fw-semibold">Estado (UF)</label>
                            <input type="text" class="form-control" id="estado" name="estado" maxlength="2" required placeholder="UF">
                        </div>

                        <div class="mb-3">
                            <label for="raio_km" class="form-label fw-semibold">Raio de busca</label>
                            <select class="form-select" id="raio_km" name="raio_km">
                                <?php foreach ($opcoesRaio as $raio): ?>
                                    <option value="<?php echo $raio; ?>"><?php echo $raio; ?> km</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-3">
                            <i class="bi bi-bell me-1"></i> Salvar alerta
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 fw-bold mb-0">Meus alertas</h2>
                <span class="badge bg-light text-dark">
                    <?php echo count($alertas); ?> / <?php echo MAX_ALERTS_PER_USER; ?> ativos
                </span>
            </div>

            <?php if (empty($alertas)): ?>
                <div class="alert alert-info">
                    Você ainda não possui alertas configurados. Crie um alerta ao lado para ser avisado sobre novos anúncios.
                </div>
            <?php else: ?>
                <div class="list-group shadow-sm">
                    <?php foreach ($alertas as $alerta): ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-<?php echo $alerta['ativo'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $alerta['ativo'] ? 'Ativo' : 'Pausado'; ?>
                                        </span>
                                        <h3 class="h6 fw-semibold mb-0">
                                            <?php echo sanitize($opcoesTipo[$alerta['tipo']] ?? 'Todos'); ?>
                                            <?php if (!empty($alerta['especie'])): ?>
                                                · <?php echo ucfirst(sanitize($alerta['especie'])); ?>
                                            <?php endif; ?>
                                        </h3>
                                    </div>
                                    <p class="text-muted small mb-0 mt-1">
                                        <?php echo sanitize($alerta['cidade']); ?> - <?php echo sanitize($alerta['estado']); ?> · <?php echo (int) $alerta['raio_km']; ?> km<br>
                                        Último envio: <?php echo $alerta['ultimo_envio'] ? formatDateTimeBR($alerta['ultimo_envio']) : 'Nunca'; ?>
                                    </p>
                                </div>
                                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="alerta_id" value="<?php echo (int) $alerta['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <?php echo $alerta['ativo'] ? 'Pausar' : 'Reativar'; ?>
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline ms-2" onsubmit="return confirm('Deseja realmente excluir este alerta?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="alerta_id" value="<?php echo (int) $alerta['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            Excluir
                                        </button>
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
