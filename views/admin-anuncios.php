<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$pageTitle  = 'Admin - Anúncios - Cadê Meu Pet?';
$adminCtrl  = new AdminController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Erro de validação. Recarregue a página.', MSG_ERROR);
        redirect('/admin/anuncios');
    }
    $anuncioId = (int)($_POST['anuncio_id'] ?? 0);
    if ($anuncioId > 0) {
        $adminCtrl->processarAcaoAnuncio($anuncioId, $_POST['acao'] ?? '');
    }
    redirect('/admin/anuncios');
}

$filtros  = [
    'status' => $_GET['status'] ?? 'todos',
    'tipo'   => $_GET['tipo']   ?? '',
    'busca'  => $_GET['busca']  ?? '',
];
$pagina   = max(1, (int)($_GET['pagina'] ?? 1));

[
    'anuncios'     => $anuncios,
    'total'        => $totalRows,
    'contagens'    => $contagens,
    'totalPaginas' => $totalPaginas,
] = $adminCtrl->listarAnuncios($filtros, $pagina);

$filtroStatus = $filtros['status'];
$filtroBusca  = $filtros['busca'];
$filtroTipo   = $filtros['tipo'];

$breadcrumbs = [
    ['label' => 'Início',   'url' => BASE_URL],
    ['label' => 'Admin',    'url' => BASE_URL . '/admin'],
    ['label' => 'Anúncios'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0"><i class="fa-solid fa-list-ul me-2"></i>Gerenciar Anúncios</h1>
        <a href="<?php echo BASE_URL; ?>/admin" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>Voltar ao Admin
        </a>
    </div>

    <!-- Contagens -->
    <div class="row g-3 mb-4">
        <?php
        $filtroLinks = [
            'todos'    => ['label' => 'Todos',     'count' => $contagens['total']    ?? 0, 'color' => 'secondary'],
            'ativo'    => ['label' => 'Ativos',    'count' => $contagens['ativos']   ?? 0, 'color' => 'success'],
            'inativo'  => ['label' => 'Inativos',  'count' => $contagens['inativos'] ?? 0, 'color' => 'danger'],
            'resolvido'=> ['label' => 'Resolvidos','count' => $contagens['resolvidos']?? 0,'color' => 'info'],
            'expirado' => ['label' => 'Expirados', 'count' => $contagens['expirados']?? 0, 'color' => 'warning'],
        ];
        foreach ($filtroLinks as $val => $info):
        ?>
        <div class="col-auto">
            <a href="?status=<?php echo $val; ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm <?php echo $filtroStatus === $val ? "border-{$info['color']} border-2" : ''; ?>" style="min-width:110px;">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold fs-5 text-<?php echo $info['color']; ?>"><?php echo (int)$info['count']; ?></div>
                        <div class="small text-muted"><?php echo $info['label']; ?></div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <?php $flash = getFlashMessage(); if ($flash): ?>
        <div class="alert alert-<?php echo sanitize($flash['type']); ?> alert-dismissible">
            <?php echo sanitize($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filtros de busca -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="status" value="<?php echo sanitize($filtroStatus); ?>">
                <div class="col-md-5">
                    <input type="text" name="busca" class="form-control form-control-sm"
                           placeholder="Buscar por pet, cidade, autor..."
                           value="<?php echo sanitize($filtroBusca); ?>">
                </div>
                <div class="col-md-3">
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">Todos os tipos</option>
                        <option value="perdido"    <?php echo $filtroTipo === 'perdido'    ? 'selected' : ''; ?>>Perdido</option>
                        <option value="encontrado" <?php echo $filtroTipo === 'encontrado' ? 'selected' : ''; ?>>Encontrado</option>
                        <option value="doacao"     <?php echo $filtroTipo === 'doacao'     ? 'selected' : ''; ?>>Adoção</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-magnifying-glass me-1"></i>Filtrar
                    </button>
                    <a href="/<?php echo ltrim(BASE_URL, '/'); ?>/admin/anuncios" class="btn btn-outline-secondary btn-sm ms-1">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
            <span class="fw-semibold">
                <?php echo number_format($totalRows, 0, ',', '.'); ?> anúncios encontrados
            </span>
            <span class="text-muted small">Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($anuncios)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-magnifying-glass fa-3x mb-3 d-block opacity-25"></i>
                    <p>Nenhum anúncio encontrado com esses filtros.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Pet / Tipo</th>
                                <th>Autor</th>
                                <th>Local</th>
                                <th>Status</th>
                                <th>Mod.</th>
                                <th class="text-end">Views</th>
                                <th>Publicado</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($anuncios as $a): ?>
                                <tr>
                                    <td class="text-muted small"><?php echo (int)$a['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($a['foto'])): ?>
                                                <img src="<?php echo BASE_URL; ?>/uploads/anuncios/<?php echo sanitize($a['foto']); ?>"
                                                     width="36" height="36"
                                                     style="object-fit:cover;border-radius:6px;">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center"
                                                     style="width:36px;height:36px;border-radius:6px;">
                                                    <i class="fa-solid fa-paw text-muted small"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-semibold small"><?php echo sanitize($a['nome_pet'] ?: 'Pet ' . ucfirst($a['especie'])); ?></div>
                                                <span class="badge bg-<?php echo $a['tipo'] === 'perdido' ? 'danger' : ($a['tipo'] === 'doacao' ? 'primary' : 'success'); ?> badge-sm" style="font-size:.65rem;">
                                                    <?php echo $a['tipo'] === 'perdido' ? 'Perdido' : ($a['tipo'] === 'doacao' ? 'Adoção' : 'Encontrado'); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?php echo sanitize($a['autor_nome']); ?></div>
                                        <div class="text-muted" style="font-size:.7rem;"><?php echo sanitize($a['autor_email']); ?></div>
                                    </td>
                                    <td class="small text-muted"><?php echo sanitize($a['cidade']); ?>, <?php echo sanitize($a['estado']); ?></td>
                                    <td>
                                        <?php
                                        $statusMap = [
                                            'ativo'     => ['success', 'Ativo'],
                                            'inativo'   => ['danger',  'Inativo'],
                                            'resolvido' => ['info',    'Resolvido'],
                                            'expirado'  => ['warning', 'Expirado'],
                                        ];
                                        $sInfo = $statusMap[$a['status']] ?? ['secondary', ucfirst($a['status'])];
                                        ?>
                                        <span class="badge bg-<?php echo $sInfo[0]; ?>"><?php echo $sInfo[1]; ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $modMap = [
                                            'pendente'  => ['warning', 'Pendente'],
                                            'aprovado'  => ['success', 'Aprovado'],
                                            'rejeitado' => ['danger',  'Rejeitado'],
                                        ];
                                        $mInfo = $modMap[$a['moderacao_status']] ?? ['secondary', ucfirst($a['moderacao_status'] ?? '')];
                                        ?>
                                        <span class="badge bg-<?php echo $mInfo[0]; ?>"><?php echo $mInfo[1]; ?></span>
                                    </td>
                                    <td class="text-end small"><?php echo number_format((int)($a['visualizacoes'] ?? 0), 0, ',', '.'); ?></td>
                                    <td class="small text-muted"><?php echo date('d/m/Y', strtotime($a['data_publicacao'])); ?></td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="<?php echo BASE_URL; ?>/anuncio/<?php echo (int)$a['id']; ?>/"
                                               class="btn btn-outline-secondary btn-sm" target="_blank" title="Ver">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <?php if ($a['status'] === 'ativo'): ?>
                                                <button type="button" class="btn btn-outline-warning btn-sm btn-acao"
                                                        data-anuncio-id="<?php echo (int)$a['id']; ?>"
                                                        data-acao="desativar"
                                                        data-nome="<?php echo sanitize($a['nome_pet'] ?: 'Pet'); ?>"
                                                        title="Desativar">
                                                    <i class="fa-solid fa-eye-slash"></i>
                                                </button>
                                            <?php elseif ($a['status'] === 'inativo'): ?>
                                                <button type="button" class="btn btn-outline-success btn-sm btn-acao"
                                                        data-anuncio-id="<?php echo (int)$a['id']; ?>"
                                                        data-acao="ativar"
                                                        data-nome="<?php echo sanitize($a['nome_pet'] ?: 'Pet'); ?>"
                                                        title="Reativar">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm btn-acao"
                                                    data-anuncio-id="<?php echo (int)$a['id']; ?>"
                                                    data-acao="excluir"
                                                    data-nome="<?php echo sanitize($a['nome_pet'] ?: 'Pet'); ?>"
                                                    title="Excluir">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($totalPaginas > 1): ?>
            <div class="card-footer bg-transparent border-0 py-3">
                <nav>
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <?php
                        $qBase = http_build_query(array_filter([
                            'status' => $filtroStatus !== 'todos' ? $filtroStatus : null,
                            'busca'  => $filtroBusca ?: null,
                            'tipo'   => $filtroTipo ?: null,
                        ]));
                        $qBase = $qBase ? '&' . $qBase : '';
                        ?>
                        <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?><?php echo $qBase; ?>">Anterior</a>
                        </li>
                        <?php for ($p = max(1, $pagina - 2); $p <= min($totalPaginas, $pagina + 2); $p++): ?>
                            <li class="page-item <?php echo $p === $pagina ? 'active' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $p; ?><?php echo $qBase; ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $pagina >= $totalPaginas ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?><?php echo $qBase; ?>">Próxima</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmação de ação -->
<div class="modal fade" id="modalAcao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <form method="POST" id="formAcao">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="anuncio_id" id="acaoAnuncioId" value="">
                <input type="hidden" name="acao" id="acaoNome" value="">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="acaoTitulo">Confirmar ação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="acaoTexto">Deseja confirmar esta ação?</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm" id="acaoBtnConfirmar">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.btn-acao').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const acao  = this.dataset.acao;
        const id    = this.dataset.anuncioId;
        const nome  = this.dataset.nome;

        document.getElementById('acaoAnuncioId').value = id;
        document.getElementById('acaoNome').value      = acao;

        const textos = {
            desativar: { titulo: 'Desativar anúncio', texto: 'Desativar o anúncio de <strong>' + nome + '</strong>?', cor: 'warning' },
            ativar:    { titulo: 'Reativar anúncio',  texto: 'Reativar o anúncio de <strong>' + nome + '</strong>?', cor: 'success' },
            excluir:   { titulo: 'Excluir anúncio',   texto: 'Excluir <strong>permanentemente</strong> o anúncio de <strong>' + nome + '</strong>? Esta ação não pode ser desfeita.', cor: 'danger' },
        };
        const t = textos[acao] || { titulo: 'Confirmar', texto: 'Confirmar?', cor: 'primary' };

        document.getElementById('acaoTitulo').textContent       = t.titulo;
        document.getElementById('acaoTexto').innerHTML          = t.texto;
        document.getElementById('acaoBtnConfirmar').className   = 'btn btn-sm btn-' + t.cor;

        new bootstrap.Modal(document.getElementById('modalAcao')).show();
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
