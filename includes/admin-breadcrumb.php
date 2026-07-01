<?php
/**
 * Breadcrumb interno das páginas admin.
 * Renderizado dentro do .admin-main (ao lado do sidebar), não mais como
 * barra de largura total acima de todo o layout.
 * Usa a mesma variável $breadcrumbs que cada view já define.
 */
if (empty($breadcrumbs) || count($breadcrumbs) <= 1) {
    return;
}
?>
<nav aria-label="breadcrumb" class="admin-breadcrumb mb-3">
    <ol class="breadcrumb mb-0 small">
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
</nav>
