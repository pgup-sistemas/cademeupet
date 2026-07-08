<?php
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config.php';
}

$pageTitle = $pageTitle ?? 'Cadê Meu Pet?';
$includeMapAssets = $includeMapAssets ?? false;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // SEO defaults: meta description, robots and canonical
    $metaDescription = $metaDescription ?? $metaOgDescription ?? SITE_DESCRIPTION;
    // Truncate description for meta (safe fallback if helper exists)
    if (function_exists('truncate')) {
        $metaDescription = truncate((string)$metaDescription, 160);
    } else {
        // ensure not too long
        if (mb_strlen((string)$metaDescription) > 160) {
            $metaDescription = mb_substr((string)$metaDescription, 0, 157) . '...';
        }
    }
    $metaRobots = $metaRobots ?? 'index, follow';
    // Current canonical URL
    $currentUrl = rtrim((string)BASE_URL, '/') . ($_SERVER['REQUEST_URI'] ?? '/');
    $canonical = $canonical ?? $metaOgUrl ?? $currentUrl;
    ?>

    <title><?php echo sanitize($pageTitle); ?></title>
    <meta name="description" content="<?php echo sanitize($metaDescription); ?>">
    <meta name="robots" content="<?php echo sanitize($metaRobots); ?>">
    <link rel="canonical" href="<?php echo sanitize($canonical); ?>">
    <link rel="sitemap" type="application/xml" title="Sitemap" href="<?php echo rtrim(defined('SITE_URL') ? SITE_URL : BASE_URL, '/'); ?>/sitemap.xml">

    <link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>/img/favicon.svg">
    <link rel="alternate icon" href="<?php echo ASSETS_URL; ?>/img/favicon.svg">
    <meta name="theme-color" content="#E85D2B">

    <?php if (!empty($metaOgTitle) || !empty($metaOgDescription) || !empty($metaOgImage) || !empty($metaOgUrl)): ?>
        <meta property="og:type" content="website">
        <?php if (!empty($metaOgTitle)): ?><meta property="og:title" content="<?php echo sanitize($metaOgTitle); ?>"><?php endif; ?>
        <?php if (!empty($metaOgDescription)): ?><meta property="og:description" content="<?php echo sanitize($metaOgDescription); ?>"><?php endif; ?>
        <?php if (!empty($metaOgUrl)): ?><meta property="og:url" content="<?php echo sanitize($metaOgUrl); ?>"><?php endif; ?>
        <?php if (!empty($metaOgImage)): ?><meta property="og:image" content="<?php echo sanitize($metaOgImage); ?>"><?php endif; ?>
        <meta name="twitter:card" content="summary_large_image">
        <?php if (!empty($metaOgTitle)): ?><meta name="twitter:title" content="<?php echo sanitize($metaOgTitle); ?>"><?php endif; ?>
        <?php if (!empty($metaOgDescription)): ?><meta name="twitter:description" content="<?php echo sanitize($metaOgDescription); ?>"><?php endif; ?>
        <?php if (!empty($metaOgImage)): ?><meta name="twitter:image" content="<?php echo sanitize($metaOgImage); ?>"><?php endif; ?>
    <?php else: ?>
        <!-- Defaults for social sharing -->
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="<?php echo sanitize(SITE_NAME); ?>">
        <meta property="og:title" content="<?php echo sanitize($pageTitle); ?>">
        <meta property="og:description" content="<?php echo sanitize($metaDescription); ?>">
        <meta property="og:url" content="<?php echo sanitize($canonical); ?>">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="<?php echo sanitize($pageTitle); ?>">
        <meta name="twitter:description" content="<?php echo sanitize($metaDescription); ?>">
    <?php endif; ?>

    <!-- JSON-LD organization + website -->
    <script type="application/ld+json">
    <?php echo json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                'name' => SITE_NAME,
                'url' => rtrim(BASE_URL, '/'),
                'logo' => rtrim(BASE_URL, '/') . '/assets/img/logo.svg'
            ],
            [
                '@type' => 'WebSite',
                'url' => rtrim(BASE_URL, '/'),
                'name' => SITE_NAME,
                'description' => $metaDescription
            ]
        ]
    ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); ?>
    </script>

    <?php
    // Page-level JSON-LD (optional). Accepts array or pre-encoded JSON string.
    if (!empty($pageJsonLd)) {
        if (is_array($pageJsonLd)) {
            $pageJson = json_encode($pageJsonLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        } else {
            $pageJson = trim((string)$pageJsonLd);
        }
        if ($pageJson !== ''): ?>
            <script type="application/ld+json">
            <?php echo $pageJson; ?>
            </script>
        <?php endif;
    }
    ?>

    <?php if (function_exists('envValue') && ($g = envValue('GOOGLE_SITE_VERIFICATION')) !== ''): ?>
        <meta name="google-site-verification" content="<?php echo sanitize($g); ?>">
    <?php endif; ?>
    <?php if (function_exists('envValue') && ($b = envValue('BING_SITE_VERIFICATION')) !== ''): ?>
        <meta name="msvalidate.01" content="<?php echo sanitize($b); ?>">
    <?php endif; ?>
    <link href="<?php echo ASSETS_URL; ?>/css/vendors/google-fonts.css" rel="stylesheet">
    <link href="<?php echo ASSETS_URL; ?>/css/vendors/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo ASSETS_URL; ?>/css/vendors/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?php echo ASSETS_URL; ?>/css/vendors/fontawesome.min.css" rel="stylesheet">
    <?php if (!empty($includeMapAssets)): ?>
        <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/vendors/leaflet/leaflet.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/cademeupet.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/views.css">
</head>
<body>
    <header class="navbar navbar-expand-lg sticky-top shadow-sm" style="background-color:var(--cmp-primary);">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?php echo BASE_URL; ?>">
                <i class="fa-solid fa-paw fa-lg text-white"></i>
                <span class="text-white fw-bold fs-5">Cadê Meu Pet?</span>
            </a>
            <a class="btn btn-light fw-semibold px-3 d-lg-none me-2" href="<?php echo BASE_URL; ?>/novo-anuncio" style="color:var(--cmp-primary); font-size:.85rem;">
                <i class="fa-solid fa-plus me-1"></i>Publicar
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Menu">
                <i class="fa-solid fa-bars text-white fa-lg"></i>
            </button>
            <nav class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item dropdown">
                        <a class="nav-link text-white dropdown-toggle" href="<?php echo BASE_URL; ?>/busca" role="button" data-bs-toggle="dropdown" data-bs-auto-close="true">
                            <i class="fa-solid fa-magnifying-glass me-1"></i>Anúncios
                        </a>
                        <ul class="dropdown-menu shadow border-0">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/busca">
                                <i class="fa-solid fa-border-all me-2 text-secondary"></i>Ver todos</a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/busca?tipo=perdido">
                                <i class="fa-solid fa-circle-exclamation me-2 text-danger"></i>Pets Perdidos</a>
                            </li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/busca?tipo=encontrado">
                                <i class="fa-solid fa-circle-check me-2 text-success"></i>Pets Encontrados</a>
                            </li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/busca?tipo=doacao">
                                <i class="fa-solid fa-heart me-2 text-warning"></i>Pets para Adoção</a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo BASE_URL; ?>/petlove">
                            <i class="fa-solid fa-heart me-1"></i>Pet Love
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo BASE_URL; ?>/parceiros">
                            <i class="fa-solid fa-handshake me-1"></i>Parceiros
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo BASE_URL; ?>/triagem">
                            <i class="fa-solid fa-kit-medical me-1"></i>Triagem Veterinária
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <?php $_naoLidasMsgs = (new ConversaController())->contarNaoLidas(getUserId()); ?>
                        <li class="nav-item">
                            <a class="nav-link text-white position-relative" href="<?php echo BASE_URL; ?>/mensagens" title="Mensagens">
                                <i class="fa-solid fa-comments"></i>
                                <span class="d-lg-none ms-1">Mensagens</span>
                                <span id="navMensagensBadge" class="badge bg-danger rounded-pill <?php echo $_naoLidasMsgs > 0 ? '' : 'd-none'; ?>" style="font-size:.65rem;"><?php echo $_naoLidasMsgs; ?></span>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link text-white dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <?php
                                    $userFullName = (string)($_SESSION['user_name'] ?? 'Usuário');
                                    $userFirstName = trim(strtok($userFullName, ' '));
                                    if ($userFirstName === '') { $userFirstName = 'Usuário'; }
                                ?>
                                <i class="fa-solid fa-user me-1"></i>
                                <span class="d-none d-lg-inline">Olá, <?php echo sanitize($userFirstName); ?></span>
                                <span class="d-lg-none">Conta</span>
                            </a>
                            <?php
                                $_parcInscricao = getDB()->fetchOne(
                                    'SELECT id FROM parceiro_inscricoes WHERE usuario_id = ? LIMIT 1',
                                    [getUserId()]
                                );
                                $_isParceiro = !empty($_parcInscricao);
                            ?>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/perfil">
                                    <i class="fa-solid fa-user me-2 text-secondary"></i>Meu Perfil</a>
                                </li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/mensagens">
                                    <i class="fa-solid fa-comments me-2 text-secondary"></i>Mensagens
                                    <?php if ($_naoLidasMsgs > 0): ?><span class="badge bg-danger rounded-pill ms-1"><?php echo $_naoLidasMsgs; ?></span><?php endif; ?>
                                    </a>
                                </li>
                                <?php if (!$_isParceiro): ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/meus-anuncios">
                                    <i class="fa-solid fa-list me-2 text-secondary"></i>Meus Anúncios</a>
                                </li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/meus-pets">
                                    <i class="fa-solid fa-paw me-2 text-secondary"></i>Ficha de Saúde dos Pets</a>
                                </li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/termos-adocao">
                                    <i class="fa-solid fa-file-signature me-2 text-secondary"></i>Termos de Adoção</a>
                                </li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/favoritos">
                                    <i class="fa-solid fa-heart me-2 text-secondary"></i>Meus Favoritos</a>
                                </li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/alertas">
                                    <i class="fa-solid fa-bell me-2 text-secondary"></i>Meus Alertas</a>
                                </li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/matches">
                                    <i class="fa-solid fa-magnifying-glass me-2 text-secondary"></i>Matches Sugeridos</a>
                                </li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/triagem/historico">
                                    <i class="fa-solid fa-kit-medical me-2 text-secondary"></i>Minhas Triagens</a>
                                </li>
                                <?php if ($_isParceiro || isAdmin()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <?php if ($_isParceiro): ?>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/parceiro/painel">
                                        <i class="fa-solid fa-handshake me-2 text-secondary"></i>Painel do Parceiro</a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if (isAdmin()): ?>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin">
                                        <i class="fa-solid fa-shield-halved me-2 text-secondary"></i>Painel Admin</a>
                                    </li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i>Sair</a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?php echo BASE_URL; ?>/login">
                                <i class="fa-solid fa-right-to-bracket me-1"></i>Entrar
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item ms-lg-2 d-none d-lg-block">
                        <a class="btn btn-light fw-semibold px-3" href="<?php echo BASE_URL; ?>/novo-anuncio" style="color:var(--cmp-primary);">
                            <i class="fa-solid fa-plus me-1"></i>Publicar Anúncio
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <?php if (!empty($breadcrumbs) && count($breadcrumbs) > 1 && empty($suppressBreadcrumbBar)): ?>
    <nav aria-label="breadcrumb" class="breadcrumb-bar">
        <div class="container">
            <ol class="breadcrumb mb-0 py-2 small">
                <?php foreach ($breadcrumbs as $i => $crumb):
                    $isLast = ($i === count($breadcrumbs) - 1);
                ?>
                    <?php if ($isLast): ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo sanitize($crumb['label']); ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item">
                            <a href="<?php echo htmlspecialchars((string)($crumb['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo sanitize($crumb['label']); ?></a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </div>
    </nav>
    <?php endif; ?>

    <main class="main-content">
        <div class="container mt-3">
            <?php displayFlashMessage(); ?>
        </div>

