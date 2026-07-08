<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Meus Anúncios - Cadê Meu Pet?';

requireLogin();

$anuncioModel = new Anuncio();
$usuarioId = (int)getUserId();

$statusFiltro = isset($_GET['status']) ? (string)$_GET['status'] : '';
$statusPermitidos = [STATUS_ATIVO, STATUS_RESOLVIDO, STATUS_EXPIRADO];
$statusSelecionado = in_array($statusFiltro, $statusPermitidos, true) ? $statusFiltro : STATUS_ATIVO;

$anuncios = $anuncioModel->findByUser($usuarioId, 100, 0, $statusSelecionado);

$breadcrumbs = [
    ['label' => 'Início',        'url' => BASE_URL],
    ['label' => 'Meus Anúncios'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 fw-bold mb-0">Meus Anúncios</h1>
        <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/novo-anuncio/">
            <i class="bi bi-plus-lg"></i> Publicar
        </a>
    </div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $statusSelecionado === STATUS_ATIVO ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/meus-anuncios/?status=<?php echo urlencode(STATUS_ATIVO); ?>">
                Ativos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $statusSelecionado === STATUS_RESOLVIDO ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/meus-anuncios/?status=<?php echo urlencode(STATUS_RESOLVIDO); ?>">
                Resolvidos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $statusSelecionado === STATUS_EXPIRADO ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/meus-anuncios/?status=<?php echo urlencode(STATUS_EXPIRADO); ?>">
                Expirados
            </a>
        </li>
    </ul>

    <?php if (empty($anuncios)): ?>
        <div class="alert alert-info">
            <?php if ($statusSelecionado === STATUS_RESOLVIDO): ?>
                Você ainda não tem anúncios resolvidos.
            <?php elseif ($statusSelecionado === STATUS_EXPIRADO): ?>
                Você ainda não tem anúncios expirados.
            <?php else: ?>
                Você ainda não publicou nenhum anúncio.
                <a href="<?php echo BASE_URL; ?>/novo-anuncio/" class="alert-link">Publicar agora</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($anuncios as $anuncio): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="anuncio-card card h-100 shadow-sm border-0">
                        <?php if (!empty($anuncio['foto'])): ?>
                            <img src="<?php echo BASE_URL; ?>/uploads/anuncios/<?php echo sanitize($anuncio['foto']); ?>" class="card-img-top" alt="Foto">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center bg-light" style="height: 200px;">
                                <div class="text-center text-muted">
                                    <i class="bi bi-camera" style="font-size: 2rem;"></i>
                                    <div class="small">Sem foto</div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="badge bg-<?php echo $anuncio['tipo'] === 'perdido' ? 'danger' : ($anuncio['tipo'] === 'doacao' ? 'primary' : 'success'); ?> mb-2">
                                        <?php echo $anuncio['tipo'] === 'perdido' ? 'Perdido' : ($anuncio['tipo'] === 'doacao' ? 'Adoção' : 'Encontrado'); ?>
                                    </div>
                                    <?php if (($anuncio['status'] ?? '') === STATUS_RESOLVIDO): ?>
                                        <span class="badge bg-success ms-1 mb-2">
                                            <i class="fa-solid fa-heart-circle-check me-1"></i>Reunido!
                                        </span>
                                    <?php endif; ?>
                                    <h5 class="card-title mb-1">
                                        <?php echo sanitize($anuncio['nome_pet'] ?: ('Pet ' . ucfirst($anuncio['especie']))); ?>
                                    </h5>
                                    <?php if (!empty($anuncio['parceiro_nome_fantasia'])): ?>
                                        <div class="small text-primary fw-semibold mb-1">
                                            <i class="fa-solid fa-building me-1"></i><?php echo sanitize($anuncio['parceiro_nome_fantasia']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-muted small">
                                        <?php echo sanitize($anuncio['cidade']); ?> - <?php echo sanitize($anuncio['estado']); ?>
                                    </div>
                                </div>
                                <span class="badge bg-secondary">
                                    <?php echo sanitize($anuncio['status']); ?>
                                </span>
                            </div>

                            <div class="mt-3 d-flex gap-2">
                                <a class="btn btn-outline-primary btn-sm flex-grow-1" href="<?php echo BASE_URL; ?>/anuncio/<?php echo (int)$anuncio['id']; ?>/">
                                    Ver
                                </a>
                                <a class="btn btn-primary btn-sm flex-grow-1" href="<?php echo BASE_URL; ?>/editar-anuncio/<?php echo (int)$anuncio['id']; ?>/">
                                    Editar
                                </a>
                                <?php if (($anuncio['status'] ?? '') === STATUS_ATIVO): ?>
                                    <button type="button"
                                            class="btn btn-outline-success btn-sm flex-grow-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalResolvido"
                                            data-anuncio-id="<?php echo (int)$anuncio['id']; ?>"
                                            data-nome-pet="<?php echo sanitize($anuncio['nome_pet'] ?: ucfirst($anuncio['especie'])); ?>">
                                        <i class="fa-solid fa-heart-circle-check me-1"></i>Reunido!
                                    </button>
                                <?php endif; ?>

                                <?php if (($anuncio['status'] ?? '') === STATUS_RESOLVIDO && $anuncio['tipo'] === 'doacao'): ?>
                                    <a class="btn btn-outline-primary btn-sm flex-grow-1" href="<?php echo BASE_URL; ?>/termo-adocao?anuncio_id=<?php echo (int)$anuncio['id']; ?>">
                                        <i class="fa-solid fa-file-signature me-1"></i>Termo de Adoção
                                    </a>
                                <?php endif; ?>
                                <?php if (($anuncio['status'] ?? '') === STATUS_RESOLVIDO): ?>
                                    <form method="POST" action="<?php echo BASE_URL; ?>/marcar-ativo.php" class="flex-grow-1" onsubmit="return confirm('Reativar este anúncio?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="anuncio_id" value="<?php echo (int)$anuncio['id']; ?>">
                                        <input type="hidden" name="return_to" value="/meus-anuncios.php">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                            Reativar
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if (($anuncio['status'] ?? '') === STATUS_EXPIRADO): ?>
                                    <form method="POST" action="<?php echo BASE_URL; ?>/renovar-anuncio" class="flex-grow-1" onsubmit="return confirm('Renovar este anúncio por mais <?php echo AD_EXPIRATION_DAYS; ?> dias?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="anuncio_id" value="<?php echo (int)$anuncio['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm w-100">
                                            <i class="fa-solid fa-rotate-right me-1"></i>Renovar
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if (($anuncio['status'] ?? '') !== STATUS_INATIVO): ?>
                                    <form method="POST" action="<?php echo BASE_URL; ?>/excluir-anuncio.php" class="flex-grow-1" onsubmit="return confirm('Tem certeza que deseja excluir este anúncio? Ele deixará de aparecer nas buscas.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="anuncio_id" value="<?php echo (int)$anuncio['id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                            Excluir
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-footer bg-white border-0 pt-0 pb-3">
                            <div class="text-muted small">
                                Publicado em <?php echo formatDateTimeBR($anuncio['data_publicacao'] ?? ''); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Marcar como Resolvido -->
<div class="modal fade" id="modalResolvido" tabindex="-1" aria-labelledby="modalResolvidoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?php echo BASE_URL; ?>/marcar-resolvido.php">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="anuncio_id" id="modalAnuncioId" value="">
                <input type="hidden" name="return_to" value="/meus-anuncios">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalResolvidoLabel">
                        <i class="fa-solid fa-heart-circle-check text-success me-2"></i>Pet reencontrado!
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Que alegria! Conte brevemente como foi o reencontro de <strong id="modalNomePet"></strong>
                        — sua historia inspira outras pessoas.
                    </p>
                    <div class="mb-3">
                        <label for="historia_reuniao" class="form-label fw-semibold">Historia do reencontro (opcional)</label>
                        <textarea class="form-control"
                                  id="historia_reuniao"
                                  name="historia_reuniao"
                                  rows="3"
                                  maxlength="500"
                                  placeholder="Ex: Encontramos o Rex a dois quarteiroes de casa, graças a uma vizinha que viu o anuncio..."></textarea>
                        <div class="form-text text-end"><span id="charCount">0</span>/500</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-heart-circle-check me-1"></i>Confirmar reencontro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const modalResolvido = document.getElementById('modalResolvido');
if (modalResolvido) {
    modalResolvido.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        document.getElementById('modalAnuncioId').value = btn.dataset.anuncioId || '';
        document.getElementById('modalNomePet').textContent = btn.dataset.nomePet || 'o pet';
    });

    const textarea = document.getElementById('historia_reuniao');
    const counter  = document.getElementById('charCount');
    if (textarea && counter) {
        textarea.addEventListener('input', () => { counter.textContent = textarea.value.length; });
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
