<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$pageTitle = 'Admin - Moderação - Cadê Meu Pet?';
$adminCtrl = new AdminController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Erro de validação. Recarregue a página.', MSG_ERROR);
        redirect('/admin/moderacao');
    }
    $anuncioId = (int)($_POST['anuncio_id'] ?? 0);
    $adminCtrl->processarModeracaoAnuncio($anuncioId, $_POST['acao'] ?? '', trim($_POST['motivo'] ?? ''));
    redirect('/admin/moderacao');
}

$filtro = $_GET['filtro'] ?? 'pendente';
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
[
    'anuncios'     => $anuncios,
    'contagens'    => $contagens,
    'totalPaginas' => $totalPaginas,
] = $adminCtrl->listarFilaModeracaoAnuncios($filtro, $pagina);

$breadcrumbs = [
    ['label' => 'Início',    'url' => BASE_URL],
    ['label' => 'Admin',     'url' => BASE_URL . '/admin'],
    ['label' => 'Moderação'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">

    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

    <div class="admin-main py-4 px-4">

    <!-- Topbar mobile -->
    <div class="d-flex d-lg-none align-items-center gap-2 mb-3 flex-wrap">
        <a href="<?php echo BASE_URL; ?>/admin"            class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-gauge"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/usuarios"   class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-users"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/anuncios"   class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-list"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/moderacao"  class="btn btn-sm btn-primary"><i class="fa-solid fa-shield-halved"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/financeiro" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-chart-line"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/parceiros"  class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-handshake"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/config"     class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-gear"></i></a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0"><i class="fa-solid fa-shield-halved me-2"></i>Fila de Moderação</h1>
        <a href="<?php echo BASE_URL; ?>/admin" class="btn btn-outline-secondary btn-sm d-none d-lg-inline-flex">
            <i class="fa-solid fa-arrow-left me-1"></i>Voltar ao Admin
        </a>
    </div>

    <!-- Stats de moderação -->
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <a href="?filtro=pendente" class="text-decoration-none">
                <div class="card border-0 shadow-sm <?php echo $filtro === 'pendente' ? 'border-warning border-2' : ''; ?>" style="min-width:130px;">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold fs-4 text-warning"><?php echo (int)($contagens['pendentes'] ?? 0); ?></div>
                        <div class="small text-muted">Pendentes</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-auto">
            <a href="?filtro=aprovado" class="text-decoration-none">
                <div class="card border-0 shadow-sm <?php echo $filtro === 'aprovado' ? 'border-success border-2' : ''; ?>" style="min-width:130px;">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold fs-4 text-success"><?php echo (int)($contagens['aprovados'] ?? 0); ?></div>
                        <div class="small text-muted">Aprovados</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-auto">
            <a href="?filtro=rejeitado" class="text-decoration-none">
                <div class="card border-0 shadow-sm <?php echo $filtro === 'rejeitado' ? 'border-danger border-2' : ''; ?>" style="min-width:130px;">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold fs-4 text-danger"><?php echo (int)($contagens['rejeitados'] ?? 0); ?></div>
                        <div class="small text-muted">Rejeitados</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <?php $flash = getFlashMessage(); if ($flash): ?>
        <div class="alert alert-<?php echo sanitize($flash['type']); ?> alert-dismissible">
            <?php echo sanitize($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($anuncios)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-check-circle fa-3x mb-3 d-block opacity-25"></i>
            <p>Nenhum anúncio <?php echo $filtro; ?>.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($anuncios as $a): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm h-100">
                        <?php if (!empty($a['foto'])): ?>
                            <img src="<?php echo BASE_URL; ?>/uploads/anuncios/<?php echo sanitize($a['foto']); ?>"
                                 class="card-img-top" style="height:160px;object-fit:cover;"
                                 alt="<?php echo sanitize($a['nome_pet'] ?? ''); ?>">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height:80px;">
                                <i class="fa-solid fa-camera text-muted fa-2x"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="mb-2">
                                <span class="badge bg-<?php echo $a['tipo'] === 'perdido' ? 'danger' : ($a['tipo'] === 'doacao' ? 'primary' : 'success'); ?>">
                                    <?php echo $a['tipo'] === 'perdido' ? 'Perdido' : ($a['tipo'] === 'doacao' ? 'Adoção' : 'Encontrado'); ?>
                                </span>
                            </div>
                            <h6 class="fw-bold mb-1"><?php echo sanitize($a['nome_pet'] ?: 'Pet ' . ucfirst($a['especie'])); ?></h6>
                            <p class="text-muted small mb-1">
                                <i class="fa-solid fa-location-dot me-1"></i>
                                <?php echo sanitize($a['bairro'] ? $a['bairro'] . ', ' : ''); ?><?php echo sanitize($a['cidade']); ?> - <?php echo sanitize($a['estado']); ?>
                            </p>
                            <p class="text-muted small mb-2">
                                <i class="fa-solid fa-user me-1"></i><?php echo sanitize($a['autor_nome']); ?>
                                &nbsp;·&nbsp;<?php echo timeAgo($a['data_publicacao']); ?>
                            </p>
                            <?php if (!empty($a['descricao'])): ?>
                                <p class="small mb-2"><?php echo sanitize(mb_substr($a['descricao'], 0, 120)) . (mb_strlen($a['descricao']) > 120 ? '...' : ''); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($a['moderacao_motivo'])): ?>
                                <p class="small text-danger mb-2"><i class="fa-solid fa-circle-info me-1"></i><?php echo sanitize($a['moderacao_motivo']); ?></p>
                            <?php endif; ?>
                        </div>

                        <?php if ($filtro === 'pendente'): ?>
                            <div class="card-footer bg-transparent border-0 pb-3 px-3">
                                <div class="d-flex gap-2">
                                    <!-- Aprovar -->
                                    <form method="POST" class="flex-grow-1">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="anuncio_id" value="<?php echo (int)$a['id']; ?>">
                                        <input type="hidden" name="acao" value="aprovar">
                                        <button type="submit" class="btn btn-success btn-sm w-100">
                                            <i class="fa-solid fa-check me-1"></i>Aprovar
                                        </button>
                                    </form>
                                    <!-- Rejeitar -->
                                    <button type="button" class="btn btn-danger btn-sm flex-grow-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalRejeitar"
                                            data-anuncio-id="<?php echo (int)$a['id']; ?>"
                                            data-nome-pet="<?php echo sanitize($a['nome_pet'] ?: 'Pet'); ?>">
                                        <i class="fa-solid fa-xmark me-1"></i>Rejeitar
                                    </button>
                                    <!-- Ver -->
                                    <a href="<?php echo BASE_URL; ?>/anuncio/<?php echo (int)$a['id']; ?>/"
                                       class="btn btn-outline-secondary btn-sm" target="_blank">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card-footer bg-transparent border-0 pb-3 px-3">
                                <a href="<?php echo BASE_URL; ?>/anuncio/<?php echo (int)$a['id']; ?>/"
                                   class="btn btn-outline-secondary btn-sm w-100" target="_blank">
                                    <i class="fa-solid fa-eye me-1"></i>Ver anúncio
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPaginas > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?filtro=<?php echo $filtro; ?>&pagina=<?php echo $pagina - 1; ?>">Anterior</a>
                    </li>
                    <?php for ($p = max(1, $pagina - 2); $p <= min($totalPaginas, $pagina + 2); $p++): ?>
                        <li class="page-item <?php echo $p === $pagina ? 'active' : ''; ?>">
                            <a class="page-link" href="?filtro=<?php echo $filtro; ?>&pagina=<?php echo $p; ?>"><?php echo $p; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $pagina >= $totalPaginas ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?filtro=<?php echo $filtro; ?>&pagina=<?php echo $pagina + 1; ?>">Próxima</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>

    </div><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<!-- Modal Rejeitar -->
<div class="modal fade" id="modalRejeitar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="anuncio_id" id="rejAnuncioId" value="">
                <input type="hidden" name="acao" value="rejeitar">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-ban text-danger me-2"></i>Rejeitar anúncio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Informe o motivo para <strong id="rejNomePet"></strong>. O autor será notificado por e-mail.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo (opcional)</label>
                        <textarea class="form-control" name="motivo" rows="3"
                                  placeholder="Ex: Imagem inapropriada, informações insuficientes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar rejeição</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const modalRejeitar = document.getElementById('modalRejeitar');
if (modalRejeitar) {
    modalRejeitar.addEventListener('show.bs.modal', function(e) {
        const btn = e.relatedTarget;
        document.getElementById('rejAnuncioId').value = btn.dataset.anuncioId || '';
        document.getElementById('rejNomePet').textContent = btn.dataset.nomePet || 'o pet';
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
