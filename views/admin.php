<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$pageTitle = 'Painel Admin - Cadê Meu Pet?';

$db = getDB();

// KPIs principais
$stats = $db->fetchOne('SELECT * FROM view_estatisticas') ?: [];

// Tendências hoje
$hoje = $db->fetchOne("
    SELECT
        (SELECT COUNT(*) FROM usuarios  WHERE DATE(data_cadastro)   = CURDATE()) AS usuarios_hoje,
        (SELECT COUNT(*) FROM anuncios  WHERE DATE(data_publicacao) = CURDATE()) AS anuncios_hoje,
        (SELECT COUNT(*) FROM anuncios  WHERE DATE(resolvido_em)    = CURDATE()) AS reunioes_hoje,
        (SELECT COUNT(*) FROM anuncios  WHERE moderacao_status = 'pendente')     AS pendentes_moderacao
") ?: [];

// Tendências mês
$mes = $db->fetchOne("
    SELECT
        (SELECT COUNT(*) FROM usuarios
          WHERE MONTH(data_cadastro) = MONTH(NOW()) AND YEAR(data_cadastro) = YEAR(NOW())) AS usuarios_mes,
        (SELECT COUNT(*) FROM anuncios
          WHERE status = 'resolvido'
            AND MONTH(resolvido_em) = MONTH(NOW()) AND YEAR(resolvido_em) = YEAR(NOW()))    AS reunioes_mes
") ?: [];

// Últimos usuários cadastrados
$ultimosUsuarios = $db->fetchAll("
    SELECT id, nome, email, data_cadastro, ativo
    FROM usuarios ORDER BY data_cadastro DESC LIMIT 5
");

// Últimos anúncios publicados
$ultimosAnuncios = $db->fetchAll("
    SELECT a.id, a.nome_pet, a.especie, a.tipo, a.status, a.cidade, a.estado,
           a.moderacao_status, a.data_publicacao, u.nome AS autor
    FROM anuncios a
    JOIN usuarios u ON a.usuario_id = u.id
    ORDER BY a.data_publicacao DESC LIMIT 8
");

// Últimas doações
$ultimasDoacoes = $db->fetchAll("
    SELECT d.id, d.valor, d.status, d.data_doacao, u.nome AS doador
    FROM doacoes d
    LEFT JOIN usuarios u ON d.usuario_id = u.id
    ORDER BY d.data_doacao DESC LIMIT 5
");

$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL],
    ['label' => 'Admin',  'url' => BASE_URL . '/admin'],
    ['label' => 'Dashboard'],
];
include __DIR__ . '/../includes/header.php';
?>

<style>
.admin-nav { background: #1A1A2E; min-height: calc(100vh - 56px); }
.admin-nav .nav-link { color: rgba(255,255,255,.7); border-radius: 8px; padding: .5rem .875rem; }
.admin-nav .nav-link:hover,
.admin-nav .nav-link.active { color: #fff; background: rgba(255,255,255,.1); }
.admin-nav .nav-link i { width: 20px; }
.kpi-card { border-radius: 16px; border: none; transition: transform .15s; }
.kpi-card:hover { transform: translateY(-2px); }
.kpi-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
</style>

<div class="container-fluid px-0">
    <div class="row g-0">

        <!-- Sidebar -->
        <div class="col-lg-2 admin-nav py-3 d-none d-lg-block">
            <div class="px-3 mb-3">
                <span class="text-white fw-bold">Admin</span>
            </div>
            <nav class="nav flex-column px-2 gap-1">
                <a class="nav-link active" href="<?php echo BASE_URL; ?>/admin">
                    <i class="fa-solid fa-gauge me-2"></i>Dashboard
                </a>
                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/usuarios">
                    <i class="fa-solid fa-users me-2"></i>Usuários
                </a>
                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/anuncios">
                    <i class="fa-solid fa-list me-2"></i>Anúncios
                </a>
                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/moderacao">
                    <i class="fa-solid fa-shield-halved me-2"></i>Moderação
                    <?php if ((int)($hoje['pendentes_moderacao'] ?? 0) > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?php echo (int)$hoje['pendentes_moderacao']; ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/financeiro">
                    <i class="fa-solid fa-chart-line me-2"></i>Financeiro
                </a>
                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/parceiros">
                    <i class="fa-solid fa-handshake me-2"></i>Parceiros
                </a>
                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/config">
                    <i class="fa-solid fa-gear me-2"></i>Configurações
                </a>
                <hr style="border-color:rgba(255,255,255,.15);">
                <a class="nav-link" href="<?php echo BASE_URL; ?>">
                    <i class="fa-solid fa-arrow-left me-2"></i>Voltar ao site
                </a>
            </nav>
        </div>

        <!-- Conteúdo -->
        <div class="col-lg-10 py-4 px-4">

            <!-- Topbar mobile -->
            <div class="d-flex d-lg-none align-items-center gap-2 mb-4 flex-wrap">
                <a href="<?php echo BASE_URL; ?>/admin/usuarios"    class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-users"></i></a>
                <a href="<?php echo BASE_URL; ?>/admin/anuncios"    class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-list"></i></a>
                <a href="<?php echo BASE_URL; ?>/admin/moderacao"   class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-shield-halved"></i></a>
                <a href="<?php echo BASE_URL; ?>/admin/financeiro"  class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-chart-line"></i></a>
                <a href="<?php echo BASE_URL; ?>/admin/config"      class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-gear"></i></a>
            </div>

            <h1 class="h4 fw-bold mb-4">Dashboard</h1>

            <?php $flash = getFlashMessage(); if ($flash): ?>
                <div class="alert alert-<?php echo sanitize($flash['type']); ?> alert-dismissible">
                    <?php echo sanitize($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- KPI Cards -->
            <div class="row g-3 mb-4">
                <!-- Usuários -->
                <div class="col-6 col-xl-3">
                    <div class="card kpi-card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="kpi-icon" style="background:#e8f4fd;color:#2196F3;">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div>
                                <div class="fw-bold fs-4 lh-1"><?php echo number_format((int)($stats['usuarios_ativos'] ?? 0)); ?></div>
                                <div class="text-muted small">Usuários ativos</div>
                                <div class="text-success small">+<?php echo (int)($hoje['usuarios_hoje'] ?? 0); ?> hoje</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Anúncios -->
                <div class="col-6 col-xl-3">
                    <div class="card kpi-card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="kpi-icon" style="background:#fff3e0;color:#FF9800;">
                                <i class="fa-solid fa-list"></i>
                            </div>
                            <div>
                                <div class="fw-bold fs-4 lh-1"><?php echo number_format((int)($stats['anuncios_ativos'] ?? 0)); ?></div>
                                <div class="text-muted small">Anúncios ativos</div>
                                <div class="text-success small">+<?php echo (int)($hoje['anuncios_hoje'] ?? 0); ?> hoje</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Reuniões -->
                <div class="col-6 col-xl-3">
                    <div class="card kpi-card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="kpi-icon" style="background:#fce4ec;color:#E91E63;">
                                <i class="fa-solid fa-heart-circle-check"></i>
                            </div>
                            <div>
                                <div class="fw-bold fs-4 lh-1"><?php echo number_format((int)($stats['casos_resolvidos'] ?? 0)); ?></div>
                                <div class="text-muted small">Casos resolvidos</div>
                                <div class="text-success small">+<?php echo (int)($mes['reunioes_mes'] ?? 0); ?> este mês</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Doações -->
                <div class="col-6 col-xl-3">
                    <div class="card kpi-card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="kpi-icon" style="background:#e8f5e9;color:#4CAF50;">
                                <i class="fa-solid fa-circle-dollar-to-slot"></i>
                            </div>
                            <div>
                                <div class="fw-bold fs-4 lh-1"><?php echo formatMoney((float)($stats['total_doacoes'] ?? 0)); ?></div>
                                <div class="text-muted small">Total doações</div>
                                <div class="text-success small"><?php echo formatMoney((float)($stats['doacoes_mes_atual'] ?? 0)); ?> este mês</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs secundários -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card border-0 bg-danger bg-opacity-10 text-center py-3">
                        <div class="fw-bold fs-5 text-danger"><?php echo number_format((int)($stats['perdidos_ativos'] ?? 0)); ?></div>
                        <div class="small text-muted">Perdidos ativos</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 bg-success bg-opacity-10 text-center py-3">
                        <div class="fw-bold fs-5 text-success"><?php echo number_format((int)($stats['encontrados_ativos'] ?? 0)); ?></div>
                        <div class="small text-muted">Encontrados ativos</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 bg-primary bg-opacity-10 text-center py-3">
                        <div class="fw-bold fs-5 text-primary"><?php echo number_format((int)($stats['doacoes_ativas'] ?? 0)); ?></div>
                        <div class="small text-muted">Para adoção</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <a href="<?php echo BASE_URL; ?>/admin/moderacao" class="text-decoration-none">
                        <div class="card border-0 bg-warning bg-opacity-10 text-center py-3">
                            <div class="fw-bold fs-5 text-warning"><?php echo (int)($hoje['pendentes_moderacao'] ?? 0); ?></div>
                            <div class="small text-muted">Pend. moderação</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Tabelas rápidas -->
            <div class="row g-4">
                <!-- Últimos usuários -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent fw-bold d-flex justify-content-between align-items-center">
                            <span><i class="fa-solid fa-user-plus me-2 text-primary"></i>Últimos usuários</span>
                            <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="btn btn-sm btn-outline-primary">Ver todos</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <tbody>
                                    <?php foreach ($ultimosUsuarios as $u): ?>
                                        <tr>
                                            <td class="ps-3 py-2">
                                                <div class="fw-semibold small"><?php echo sanitize($u['nome']); ?></div>
                                                <div class="text-muted" style="font-size:.75rem;"><?php echo sanitize($u['email']); ?></div>
                                            </td>
                                            <td class="text-muted small pe-3" style="white-space:nowrap;"><?php echo timeAgo($u['data_cadastro']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Últimos anúncios -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent fw-bold d-flex justify-content-between align-items-center">
                            <span><i class="fa-solid fa-bullhorn me-2 text-warning"></i>Últimos anúncios</span>
                            <a href="<?php echo BASE_URL; ?>/admin/anuncios" class="btn btn-sm btn-outline-primary">Ver todos</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <tbody>
                                    <?php foreach ($ultimosAnuncios as $a): ?>
                                        <tr>
                                            <td class="ps-3 py-2">
                                                <span class="badge bg-<?php echo $a['tipo'] === 'perdido' ? 'danger' : ($a['tipo'] === 'doacao' ? 'primary' : 'success'); ?> me-1">
                                                    <?php echo $a['tipo'] === 'perdido' ? 'P' : ($a['tipo'] === 'doacao' ? 'A' : 'E'); ?>
                                                </span>
                                                <span class="fw-semibold small"><?php echo sanitize($a['nome_pet'] ?: ucfirst($a['especie'])); ?></span>
                                                <span class="text-muted small ms-1"><?php echo sanitize($a['cidade']); ?></span>
                                                <?php if (($a['moderacao_status'] ?? '') === 'pendente'): ?>
                                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">pend.</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-muted small pe-3" style="white-space:nowrap;"><?php echo timeAgo($a['data_publicacao']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Últimas doações -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent fw-bold d-flex justify-content-between align-items-center">
                            <span><i class="fa-solid fa-circle-dollar-to-slot me-2 text-success"></i>Últimas doações</span>
                            <a href="<?php echo BASE_URL; ?>/admin/financeiro" class="btn btn-sm btn-outline-primary">Ver todas</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <?php if (empty($ultimasDoacoes)): ?>
                                    <tbody><tr><td class="text-muted small ps-3 py-3">Nenhuma doação ainda.</td></tr></tbody>
                                <?php else: ?>
                                    <tbody>
                                        <?php foreach ($ultimasDoacoes as $d): ?>
                                            <tr>
                                                <td class="ps-3 py-2">
                                                    <div class="fw-semibold small"><?php echo sanitize($d['doador'] ?? 'Anônimo'); ?></div>
                                                    <div class="text-muted" style="font-size:.75rem;"><?php echo timeAgo($d['data_doacao']); ?></div>
                                                </td>
                                                <td class="pe-3 text-end">
                                                    <span class="fw-bold text-success small"><?php echo formatMoney((float)$d['valor']); ?></span>
                                                    <br>
                                                    <span class="badge bg-<?php echo $d['status'] === 'aprovada' ? 'success' : ($d['status'] === 'pendente' ? 'warning text-dark' : 'secondary'); ?>" style="font-size:.65rem;"><?php echo sanitize($d['status']); ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Ações rápidas -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent fw-bold">
                            <i class="fa-solid fa-bolt me-2 text-warning"></i>Ações rápidas
                        </div>
                        <div class="card-body d-flex flex-column gap-2">
                            <a href="<?php echo BASE_URL; ?>/admin/moderacao" class="btn btn-outline-warning text-start">
                                <i class="fa-solid fa-shield-halved me-2"></i>
                                Moderar anúncios
                                <?php if ((int)($hoje['pendentes_moderacao'] ?? 0) > 0): ?>
                                    <span class="badge bg-warning text-dark ms-1"><?php echo (int)$hoje['pendentes_moderacao']; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>/admin/anuncios?status=bloqueado" class="btn btn-outline-danger text-start">
                                <i class="fa-solid fa-ban me-2"></i>Ver anúncios bloqueados
                            </a>
                            <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="btn btn-outline-primary text-start">
                                <i class="fa-solid fa-user-check me-2"></i>Gerenciar usuários
                            </a>
                            <a href="<?php echo BASE_URL; ?>/admin/config" class="btn btn-outline-secondary text-start">
                                <i class="fa-solid fa-gear me-2"></i>Configurações do site
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
