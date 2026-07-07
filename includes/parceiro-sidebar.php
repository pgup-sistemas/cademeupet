<?php
/**
 * Sidebar reutilizável para todas as páginas do parceiro (mesmo padrão
 * visual da sidebar do admin — ver includes/admin-sidebar.php).
 * Detecta a página ativa pelo REQUEST_URI.
 * $parceiroPerfilAtual é opcional — busca do banco se não estiver definido.
 */
$__uriParceiro = strtok($_SERVER['REQUEST_URI'] ?? '', '?');

if (!isset($parceiroPerfilAtual)) {
    try {
        $__perfilModel = new ParceiroPerfil();
        $parceiroPerfilAtual = $__perfilModel->findByUserId((int)(getUserId() ?? 0));
        unset($__perfilModel);
    } catch (Throwable $__e) {
        $parceiroPerfilAtual = null;
        unset($__e);
    }
}
$__ehClinica = $parceiroPerfilAtual && ($parceiroPerfilAtual['categoria'] ?? '') === 'clinica';

if (!function_exists('__adminNavActive')) {
    function __adminNavActive(string $path, string $uri): string {
        return (rtrim($uri, '/') === rtrim($path, '/')) ? 'active' : '';
    }
}
?>
<!-- Sidebar do Parceiro -->
<div id="parceiroSidebar" class="admin-nav admin-sidebar-wrap py-3 d-none d-lg-flex flex-column">

    <div class="admin-sidebar-header mb-2">
        <span class="admin-sidebar-title">Parceiro</span>
        <button class="admin-sidebar-toggle" id="parceiroSidebarToggle" title="Recolher menu" aria-label="Recolher menu">
            <i class="fa-solid fa-angles-left" id="parceiroSidebarToggleIcon"></i>
        </button>
    </div>

    <nav class="nav flex-column px-2 gap-1 flex-grow-1">

        <div class="nav-label px-2 small text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.05em;">Empresa</div>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/parceiro/painel', $__uriParceiro); ?>"
           href="<?php echo BASE_URL; ?>/parceiro/painel"
           title="Painel">
            <i class="fa-solid fa-gauge me-2"></i><span class="nav-label">Painel</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/parceiro/perfil', $__uriParceiro); ?>"
           href="<?php echo BASE_URL; ?>/parceiro/perfil"
           title="Perfil da Empresa">
            <i class="fa-solid fa-building me-2"></i><span class="nav-label">Perfil da Empresa</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/parceiro/pagamento', $__uriParceiro); ?>"
           href="<?php echo BASE_URL; ?>/parceiro/pagamento"
           title="Financeiro">
            <i class="fa-solid fa-chart-line me-2"></i><span class="nav-label">Financeiro</span>
        </a>

        <?php if ($__ehClinica): ?>
            <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/parceiro/veterinarios', $__uriParceiro); ?>"
               href="<?php echo BASE_URL; ?>/parceiro/veterinarios"
               title="Veterinários">
                <i class="fa-solid fa-user-doctor me-2"></i><span class="nav-label">Veterinários</span>
            </a>

            <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/parceiro/atendimentos', $__uriParceiro); ?>"
               href="<?php echo BASE_URL; ?>/parceiro/atendimentos"
               title="Atendimentos">
                <i class="fa-solid fa-notes-medical me-2"></i><span class="nav-label">Atendimentos</span>
            </a>
        <?php endif; ?>

        <hr>

        <div class="nav-label px-2 small text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.05em;">Minha conta</div>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/perfil', $__uriParceiro); ?>"
           href="<?php echo BASE_URL; ?>/perfil"
           title="Meu Perfil">
            <i class="fa-solid fa-user me-2"></i><span class="nav-label">Meu Perfil</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/mensagens', $__uriParceiro); ?>"
           href="<?php echo BASE_URL; ?>/mensagens"
           title="Mensagens">
            <i class="fa-solid fa-comments me-2"></i><span class="nav-label">Mensagens</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/meus-anuncios', $__uriParceiro); ?>"
           href="<?php echo BASE_URL; ?>/meus-anuncios"
           title="Meus Anúncios">
            <i class="fa-solid fa-list me-2"></i><span class="nav-label">Meus Anúncios</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/meus-pets', $__uriParceiro); ?>"
           href="<?php echo BASE_URL; ?>/meus-pets"
           title="Meus Pets">
            <i class="fa-solid fa-paw me-2"></i><span class="nav-label">Meus Pets</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/termos-adocao', $__uriParceiro); ?>"
           href="<?php echo BASE_URL; ?>/termos-adocao"
           title="Termos de Adoção">
            <i class="fa-solid fa-file-signature me-2"></i><span class="nav-label">Termos de Adoção</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/favoritos', $__uriParceiro); ?>"
           href="<?php echo BASE_URL; ?>/favoritos"
           title="Meus Favoritos">
            <i class="fa-solid fa-heart me-2"></i><span class="nav-label">Meus Favoritos</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/alertas', $__uriParceiro); ?>"
           href="<?php echo BASE_URL; ?>/alertas"
           title="Meus Alertas">
            <i class="fa-solid fa-bell me-2"></i><span class="nav-label">Meus Alertas</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/matches', $__uriParceiro); ?>"
           href="<?php echo BASE_URL; ?>/matches"
           title="Matches Sugeridos">
            <i class="fa-solid fa-magnifying-glass me-2"></i><span class="nav-label">Matches Sugeridos</span>
        </a>

        <a class="nav-link <?php echo __adminNavActive(parse_url(BASE_URL, PHP_URL_PATH).'/triagem/historico', $__uriParceiro); ?>"
           href="<?php echo BASE_URL; ?>/triagem/historico"
           title="Minhas Triagens">
            <i class="fa-solid fa-kit-medical me-2"></i><span class="nav-label">Minhas Triagens</span>
        </a>

        <hr>

        <a class="nav-link" href="<?php echo BASE_URL; ?>" title="Ir para o site">
            <i class="fa-solid fa-arrow-left me-2"></i><span class="nav-label">Ir para o site</span>
        </a>

    </nav>
</div>

<script>
(function () {
    var sidebar = document.getElementById('parceiroSidebar');
    var toggle  = document.getElementById('parceiroSidebarToggle');
    var icon    = document.getElementById('parceiroSidebarToggleIcon');
    if (!sidebar || !toggle) return;

    function apply(collapsed) {
        if (collapsed) {
            sidebar.classList.add('collapsed');
            icon.className = 'fa-solid fa-angles-right';
            toggle.title = 'Expandir menu';
            toggle.setAttribute('aria-label', 'Expandir menu');
        } else {
            sidebar.classList.remove('collapsed');
            icon.className = 'fa-solid fa-angles-left';
            toggle.title = 'Recolher menu';
            toggle.setAttribute('aria-label', 'Recolher menu');
        }
    }

    apply(localStorage.getItem('parceiroSidebarCollapsed') === 'true');

    toggle.addEventListener('click', function () {
        var collapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('parceiroSidebarCollapsed', !collapsed);
        apply(!collapsed);
    });
})();
</script>
