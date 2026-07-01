<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$pageTitle = 'Painel Admin - Cadê Meu Pet?';

$adminCtrl = new AdminController();
[
    'stats'           => $stats,
    'hoje'            => $hoje,
    'mes'             => $mes,
    'ultimosUsuarios' => $ultimosUsuarios,
    'ultimosAnuncios' => $ultimosAnuncios,
    'ultimasDoacoes'  => $ultimasDoacoes,
] = $adminCtrl->getDashboardData();

$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL],
    ['label' => 'Admin',  'url' => BASE_URL . '/admin'],
    ['label' => 'Dashboard'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">

    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

    <!-- Conteúdo -->
    <div class="admin-main py-4 px-4">

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
                    <a href="<?php echo BASE_URL; ?>/admin/anuncios?tipo=perdido" class="text-decoration-none">
                        <div class="card kpi-card shadow-sm h-100">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="kpi-icon" style="background:#fdecea;color:#e53935;">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                </div>
                                <div>
                                    <div class="fw-bold fs-4 lh-1 text-dark"><?php echo number_format((int)($stats['perdidos_ativos'] ?? 0)); ?></div>
                                    <div class="text-muted small">Perdidos ativos</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="<?php echo BASE_URL; ?>/admin/anuncios?tipo=encontrado" class="text-decoration-none">
                        <div class="card kpi-card shadow-sm h-100">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="kpi-icon" style="background:#e8f5e9;color:#43a047;">
                                    <i class="fa-solid fa-circle-check"></i>
                                </div>
                                <div>
                                    <div class="fw-bold fs-4 lh-1 text-dark"><?php echo number_format((int)($stats['encontrados_ativos'] ?? 0)); ?></div>
                                    <div class="text-muted small">Encontrados ativos</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="<?php echo BASE_URL; ?>/admin/anuncios?tipo=doacao" class="text-decoration-none">
                        <div class="card kpi-card shadow-sm h-100">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="kpi-icon" style="background:#e3f2fd;color:#1e88e5;">
                                    <i class="fa-solid fa-hand-holding-heart"></i>
                                </div>
                                <div>
                                    <div class="fw-bold fs-4 lh-1 text-dark"><?php echo number_format((int)($stats['doacoes_ativas'] ?? 0)); ?></div>
                                    <div class="text-muted small">Para adoção</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="<?php echo BASE_URL; ?>/admin/moderacao" class="text-decoration-none">
                        <div class="card kpi-card shadow-sm h-100">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="kpi-icon" style="background:#fff8e1;color:#f57f17;">
                                    <i class="fa-solid fa-clock"></i>
                                </div>
                                <div>
                                    <div class="fw-bold fs-4 lh-1 text-dark"><?php echo (int)($hoje['pendentes_moderacao'] ?? 0); ?></div>
                                    <div class="text-muted small">Pend. moderação</div>
                                </div>
                            </div>
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
                                                    <div class="text-muted" style="font-size:.75rem;">
                                                        <?php if (!empty($d['email_doador'])): ?>
                                                            <?php echo sanitize($d['email_doador']); ?> &middot;
                                                        <?php endif; ?>
                                                        <?php echo timeAgo($d['data_doacao']); ?>
                                                        <?php if (!empty($d['metodo_pagamento'])): ?>
                                                            &middot; <span class="text-uppercase"><?php echo sanitize($d['metodo_pagamento']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="pe-3 text-end" style="white-space:nowrap;">
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
                            <a href="<?php echo BASE_URL; ?>/admin/denuncias" class="btn btn-outline-danger text-start">
                                <i class="fa-solid fa-flag me-2"></i>Ver denúncias
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
</div><!-- /.admin-layout -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
