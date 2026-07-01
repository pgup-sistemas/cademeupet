<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$pageTitle = 'Admin - Denúncias - Cadê Meu Pet?';
$db = getDB();

// Processar análise de denúncia
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Erro de validação.', MSG_ERROR);
        redirect('/admin/denuncias');
    }

    $denunciaId = (int)($_POST['denuncia_id'] ?? 0);
    $acao       = $_POST['acao'] ?? '';
    $acoes      = ['procedente', 'improcedente'];

    if ($denunciaId > 0 && in_array($acao, $acoes, true)) {
        $denuncia   = $db->fetchOne('SELECT d.*, a.nome_pet, a.especie, a.usuario_id AS dono_id,
                                            u.nome AS dono_nome, u.email AS dono_email
                                     FROM denuncias d
                                     JOIN anuncios a ON a.id = d.anuncio_id
                                     LEFT JOIN usuarios u ON u.id = a.usuario_id
                                     WHERE d.id = ?', [$denunciaId]);
        $dadosAntes = $denuncia ? ['status' => $denuncia['status']] : [];

        $db->update('denuncias', [
            'status'        => $acao,
            'analisada_em'  => date('Y-m-d H:i:s'),
            'analisada_por' => (int)getUserId(),
        ], 'id = ?', [$denunciaId]);

        auditLog('analisar_denuncia', 'denuncias', $denunciaId, $dadosAntes, ['status' => $acao]);

        if ($acao === 'procedente' && $denuncia) {
            $anuncioId = (int)$denuncia['anuncio_id'];

            // Bloquear o anúncio automaticamente
            $statusAntes = $db->fetchOne('SELECT status FROM anuncios WHERE id = ?', [$anuncioId]) ?: [];
            $db->update('anuncios', ['status' => STATUS_BLOQUEADO], 'id = ?', [$anuncioId]);
            auditLog('bloquear_anuncio_denuncia', 'anuncios', $anuncioId, $statusAntes, ['status' => STATUS_BLOQUEADO]);
            cacheClear(null);

            // Notificar o dono do anúncio
            if (!empty($denuncia['dono_email'])) {
                $nomePet  = sanitize($denuncia['nome_pet'] ?: ucfirst($denuncia['especie']));
                $assunto  = "Seu anúncio foi bloqueado por denúncia - Cadê Meu Pet?";
                $corpo    = "<p>Olá, {$denuncia['dono_nome']}!</p>"
                          . "<p>Infelizmente, seu anúncio de <strong>{$nomePet}</strong> foi bloqueado "
                          . "após análise de uma denúncia recebida.</p>"
                          . "<p>Se acredita que houve um engano, entre em contato com nossa equipe.</p>"
                          . "<p>Equipe Cadê Meu Pet?</p>";
                sendEmail($denuncia['dono_email'], $assunto, $corpo);
            }
        }

        setFlashMessage('Denúncia marcada como ' . $acao . ($acao === 'procedente' ? ' — anúncio bloqueado.' : '.'), MSG_SUCCESS);
    }

    redirect('/admin/denuncias');
}

$filtro   = in_array($_GET['filtro'] ?? '', ['pendente', 'procedente', 'improcedente']) ? $_GET['filtro'] : 'pendente';

$contagens = [];
foreach (['pendente', 'procedente', 'improcedente'] as $s) {
    $row = $db->fetchOne("SELECT COUNT(*) AS n FROM denuncias WHERE status = ?", [$s]);
    $contagens[$s] = (int)($row['n'] ?? 0);
}

$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$limite = 20;
$offset = ($pagina - 1) * $limite;

$totalDenuncias = $contagens[$filtro] ?? 0;
$totalPaginas   = (int)ceil($totalDenuncias / $limite);

$denuncias = $db->fetchAll(
    "SELECT d.*, a.nome_pet, a.especie, a.tipo, u.nome AS denunciante_nome, u.email AS denunciante_email
     FROM denuncias d
     JOIN anuncios a ON a.id = d.anuncio_id
     LEFT JOIN usuarios u ON u.id = d.usuario_id
     WHERE d.status = ?
     ORDER BY d.data_denuncia DESC
     LIMIT $limite OFFSET $offset",
    [$filtro]
) ?: [];

$breadcrumbs = [
    ['label' => 'Início',     'url' => BASE_URL],
    ['label' => 'Admin',      'url' => BASE_URL . '/admin'],
    ['label' => 'Denúncias'],
];
$suppressBreadcrumbBar = true;
include __DIR__ . '/../includes/header.php';

$flashMsg = getFlashMessage();
?>

<div class="admin-layout">

    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

    <div class="admin-main py-4 px-4">

    <!-- Topbar mobile -->
    <div class="d-flex d-lg-none align-items-center gap-2 mb-3 flex-wrap">
        <a href="<?php echo BASE_URL; ?>/admin"            class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-gauge"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/usuarios"   class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-users"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/anuncios"   class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-list"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/moderacao"  class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-shield-halved"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/financeiro" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-chart-line"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/parceiros"  class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-handshake"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/config"     class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-gear"></i></a>
    </div>

    <?php include __DIR__ . '/../includes/admin-breadcrumb.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0"><i class="fa-solid fa-flag me-2 text-danger"></i>Denúncias</h1>
        <a href="<?php echo BASE_URL; ?>/admin" class="btn btn-outline-secondary btn-sm d-none d-lg-inline-flex">
            <i class="fa-solid fa-arrow-left me-1"></i>Voltar ao Admin
        </a>
    </div>

    <?php if ($flashMsg): ?>
        <div class="alert alert-<?php echo $flashMsg['type'] === MSG_SUCCESS ? 'success' : 'danger'; ?> alert-dismissible fade show">
            <?php echo sanitize($flashMsg['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Contadores por status -->
    <div class="row g-3 mb-4">
        <?php foreach (['pendente' => 'warning', 'procedente' => 'danger', 'improcedente' => 'success'] as $s => $cor): ?>
            <div class="col-auto">
                <a href="?filtro=<?php echo $s; ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm <?php echo $filtro === $s ? 'border-' . $cor . ' border-2' : ''; ?>" style="min-width:130px;">
                        <div class="card-body text-center py-3">
                            <div class="fw-bold fs-4 text-<?php echo $cor; ?>"><?php echo $contagens[$s]; ?></div>
                            <div class="small text-muted"><?php echo ucfirst($s); ?></div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($denuncias)): ?>
        <div class="alert alert-info">Nenhuma denúncia <?php echo $filtro; ?> no momento.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Anúncio</th>
                        <th>Denunciante</th>
                        <th>Motivo</th>
                        <th>Detalhes</th>
                        <th>Data</th>
                        <?php if ($filtro === 'pendente'): ?>
                            <th>Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($denuncias as $d): ?>
                        <tr>
                            <td class="text-muted small"><?php echo (int)$d['id']; ?></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/anuncio/<?php echo (int)$d['anuncio_id']; ?>/" target="_blank" class="text-decoration-none fw-semibold">
                                    <?php echo sanitize($d['nome_pet'] ?: ('Pet ' . ucfirst($d['especie']))); ?>
                                </a>
                                <div class="text-muted small"><?php echo sanitize($d['tipo']); ?> &mdash; #<?php echo (int)$d['anuncio_id']; ?></div>
                            </td>
                            <td>
                                <?php if (!empty($d['denunciante_nome'])): ?>
                                    <div><?php echo sanitize($d['denunciante_nome']); ?></div>
                                    <div class="text-muted small"><?php echo sanitize($d['denunciante_email']); ?></div>
                                <?php else: ?>
                                    <span class="text-muted small">Anônimo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $motivoLabel = [
                                    'inapropriado' => 'Conteúdo inapropriado',
                                    'spam'         => 'Spam / duplicado',
                                    'venda'        => 'Venda disfarçada',
                                    'golpe'        => 'Possível golpe',
                                    'outro'        => 'Outro',
                                ];
                                echo sanitize($motivoLabel[$d['motivo']] ?? $d['motivo']);
                                ?>
                            </td>
                            <td class="small text-muted" style="max-width:200px;">
                                <?php echo $d['descricao'] ? sanitize($d['descricao']) : '—'; ?>
                            </td>
                            <td class="small text-muted"><?php echo formatDateTimeBR($d['data_denuncia']); ?></td>
                            <?php if ($filtro === 'pendente'): ?>
                                <td>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="denuncia_id" value="<?php echo (int)$d['id']; ?>">
                                        <input type="hidden" name="acao" value="procedente">
                                        <button type="submit" class="btn btn-danger btn-sm me-1"
                                                onclick="return confirm('Marcar como procedente (conteúdo irregular)?')">
                                            Procedente
                                        </button>
                                    </form>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="denuncia_id" value="<?php echo (int)$d['id']; ?>">
                                        <input type="hidden" name="acao" value="improcedente">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                                            Improcedente
                                        </button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPaginas > 1): ?>
            <nav class="mt-3">
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
