<?php
/**
 * Sidebar reutilizável para todas as páginas admin.
 * Detecta a página ativa pelo REQUEST_URI.
 * $hoje['pendentes_moderacao'] é opcional — consulta o DB se não estiver definido.
 */
$__uri = strtok($_SERVER['REQUEST_URI'] ?? '', '?');

if (!isset($hoje['pendentes_moderacao'])) {
    try {
        $__db = getDB();
        $__row = $__db->fetchOne("SELECT COUNT(*) AS n FROM anuncios WHERE moderacao_status = 'pendente'");
        $hoje['pendentes_moderacao'] = (int)($__row['n'] ?? 0);
        unset($__db, $__row);
    } catch (Throwable $__e) {
        $hoje['pendentes_moderacao'] = 0;
        unset($__e);
    }
}

function __adminNavActive(string $path, string $uri): string {
    return (rtrim($uri, '/') === rtrim($path, '/')) ? 'active' : '';
}
?>
<!-- Admin Sidebar -->
<div id="adminSidebar" class="admin-nav admin-sidebar-wrap py-3 d-none d-lg-flex flex-column">

    <div class="admin-sidebar-header mb-2">
        <span class="admin-sidebar-title">Admin</span>
        <button class="admin-sidebar-toggle" id="sidebarToggle" title="Recolher menu" aria-label="Recolher menu">
            <i class="fa-solid fa-angles-left" id="sidebarToggleIcon"></i>
        </button>
    </div>

    <nav class="nav flex-column px-2 gap-1 flex-grow-1">

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/admin', $__uri); ?>"
           href="<?php echo BASE_URL; ?>/admin"
           title="Dashboard">
            <i class="fa-solid fa-gauge me-2"></i><span class="nav-label">Dashboard</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/admin/usuarios', $__uri); ?>"
           href="<?php echo BASE_URL; ?>/admin/usuarios"
           title="Usuários">
            <i class="fa-solid fa-users me-2"></i><span class="nav-label">Usuários</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/admin/anuncios', $__uri); ?>"
           href="<?php echo BASE_URL; ?>/admin/anuncios"
           title="Anúncios">
            <i class="fa-solid fa-list me-2"></i><span class="nav-label">Anúncios</span>
        </a>

        <a class="nav-link position-relative <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/admin/moderacao', $__uri); ?>"
           href="<?php echo BASE_URL; ?>/admin/moderacao"
           title="Moderação">
            <i class="fa-solid fa-shield-halved me-2"></i><span class="nav-label">Moderação</span>
            <?php if ((int)($hoje['pendentes_moderacao'] ?? 0) > 0): ?>
                <span class="badge bg-warning text-dark ms-auto mod-badge"><?php echo (int)$hoje['pendentes_moderacao']; ?></span>
            <?php endif; ?>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/admin/financeiro', $__uri); ?>"
           href="<?php echo BASE_URL; ?>/admin/financeiro"
           title="Financeiro">
            <i class="fa-solid fa-chart-line me-2"></i><span class="nav-label">Financeiro</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/admin/parceiros', $__uri); ?>"
           href="<?php echo BASE_URL; ?>/admin/parceiros"
           title="Parceiros">
            <i class="fa-solid fa-handshake me-2"></i><span class="nav-label">Parceiros</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/admin/denuncias', $__uri); ?>"
           href="<?php echo BASE_URL; ?>/admin/denuncias"
           title="Denúncias">
            <i class="fa-solid fa-flag me-2"></i><span class="nav-label">Denúncias</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/admin/cancelamentos', $__uri); ?>"
           href="<?php echo BASE_URL; ?>/admin/cancelamentos"
           title="Cancelamentos">
            <i class="fa-solid fa-ban me-2"></i><span class="nav-label">Cancelamentos</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/admin/depoimentos', $__uri); ?>"
           href="<?php echo BASE_URL; ?>/admin/depoimentos"
           title="Depoimentos">
            <i class="fa-solid fa-quote-left me-2"></i><span class="nav-label">Depoimentos</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/admin/api-keys', $__uri); ?>"
           href="<?php echo BASE_URL; ?>/admin/api-keys"
           title="API Pública">
            <i class="fa-solid fa-plug me-2"></i><span class="nav-label">API Pública</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/admin/veterinarios', $__uri); ?>"
           href="<?php echo BASE_URL; ?>/admin/veterinarios"
           title="Veterinários">
            <i class="fa-solid fa-user-doctor me-2"></i><span class="nav-label">Veterinários</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/admin/config', $__uri); ?>"
           href="<?php echo BASE_URL; ?>/admin/config"
           title="Configurações">
            <i class="fa-solid fa-gear me-2"></i><span class="nav-label">Configurações</span>
        </a>

        <hr>

        <a class="nav-link" href="<?php echo BASE_URL; ?>" title="Voltar ao site">
            <i class="fa-solid fa-arrow-left me-2"></i><span class="nav-label">Voltar ao site</span>
        </a>

    </nav>
</div>

<script>
(function () {
    var sidebar  = document.getElementById('adminSidebar');
    var toggle   = document.getElementById('sidebarToggle');
    var icon     = document.getElementById('sidebarToggleIcon');
    if (!sidebar || !toggle) return;

    function apply(collapsed) {
        if (collapsed) {
            sidebar.classList.add('collapsed');
            icon.className  = 'fa-solid fa-angles-right';
            toggle.title    = 'Expandir menu';
            toggle.setAttribute('aria-label', 'Expandir menu');
        } else {
            sidebar.classList.remove('collapsed');
            icon.className  = 'fa-solid fa-angles-left';
            toggle.title    = 'Recolher menu';
            toggle.setAttribute('aria-label', 'Recolher menu');
        }
    }

    /* Aplica estado salvo imediatamente (antes do paint) */
    apply(localStorage.getItem('adminSidebarCollapsed') === 'true');

    toggle.addEventListener('click', function () {
        var collapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('adminSidebarCollapsed', !collapsed);
        apply(!collapsed);
    });
})();
</script>
