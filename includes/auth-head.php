<?php
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config.php';
}
$pageTitle = $pageTitle ?? 'Cadê Meu Pet?';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($pageTitle); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>/img/favicon.svg">
    <meta name="theme-color" content="#E85D2B">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo ASSETS_URL; ?>/css/vendors/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?php echo ASSETS_URL; ?>/css/vendors/fontawesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/cademeupet.css">
</head>
<body>
