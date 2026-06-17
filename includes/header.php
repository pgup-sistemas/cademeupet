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

    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Crect width='512' height='512' rx='100' fill='%23FF6B35'/%3E%3Cpath fill='%23fff' d='M226.5 92.9c14.3 42.9-.3 86.2-32.6 96.8s-70.1-16.7-84.4-59.6c-14.3-42.9.3-86.2 32.6-96.8S212.2 50 226.5 92.9zM340.7 17c15.5 48.5-1.9 97.5-38.7 109.5s-79.3-18.5-94.8-67S208.1 61 244.9 49 325.2-31.5 340.7 17zM400 96c10.5 38.5-8.1 77-41.6 86.1S296 167.7 285.5 129.2s8.1-77 41.6-86.1S389.5 57.5 400 96zm22.7 188c-12.7 42.4-51.1 67.4-85.7 55.9S286.5 283 299.2 240.6s51.1-67.4 85.7-55.9S435.4 241.6 422.7 284zm-281 119c13.5 39.5 1.4 80.3-27 90.9s-65.2-14.5-78.7-54S37.4 359.6 65.8 349s62.4 14.5 75.9 54zm257 29.2c9.3 38.5-12.4 76.9-48.1 85.9s-74.5-16-83.8-54.5 12.4-76.9 48.1-85.9 74.5 16 83.8 54.5z'/%3E%3C/svg%3E">

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <?php if (!empty($includeMapAssets)): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/cademeupet.css">
</head>
<body>
    <header class="navbar navbar-expand-lg sticky-top shadow-sm" style="background-color:var(--cmp-primary);">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?php echo BASE_URL; ?>">
                <i class="fa-solid fa-paw fa-lg text-white"></i>
                <span class="text-white fw-bold fs-5">Cadê Meu Pet?</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Menu">
                <i class="fa-solid fa-bars text-white fa-lg"></i>
            </button>
            <nav class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo BASE_URL; ?>/busca">
                            <i class="fa-solid fa-magnifying-glass me-1"></i>Buscar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo BASE_URL; ?>/busca?tipo=perdido">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>Perdidos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo BASE_URL; ?>/busca?tipo=encontrado">
                            <i class="fa-solid fa-circle-check me-1"></i>Encontrados
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo BASE_URL; ?>/petlove">
                            <i class="fa-solid fa-heart me-1"></i>Pet Love
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
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
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/perfil">
                                    <i class="fa-solid fa-user me-2 text-secondary"></i>Meu Perfil</a>
                                </li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/meus-anuncios">
                                    <i class="fa-solid fa-list me-2 text-secondary"></i>Meus Anúncios</a>
                                </li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/favoritos">
                                    <i class="fa-solid fa-heart me-2 text-secondary"></i>Meus Favoritos</a>
                                </li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/alertas">
                                    <i class="fa-solid fa-bell me-2 text-secondary"></i>Meus Alertas</a>
                                </li>
                                <?php if (isAdmin()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin">
                                        <i class="fa-solid fa-shield-halved me-2 text-secondary"></i>Painel Admin</a>
                                    </li>
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
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-light fw-semibold px-3" href="<?php echo BASE_URL; ?>/novo-anuncio" style="color:var(--cmp-primary);">
                            <i class="fa-solid fa-plus me-1"></i>Publicar Anúncio
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <?php if (!empty($breadcrumbs) && count($breadcrumbs) > 1): ?>
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

