<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Parceiros | PetFinder';
$metaOgTitle = 'Parceiros PetFinder';
$metaOgDescription = 'Encontre serviços pet confiáveis e ajude a manter o PetFinder sustentável.';
$metaOgUrl = BASE_URL . '/parceiros';

$perfilModel = new ParceiroPerfil();

$cidadeFiltro = isset($_GET['cidade']) ? trim((string)$_GET['cidade']) : null;
$categoriaFiltro = isset($_GET['categoria']) ? trim((string)$_GET['categoria']) : null;

$perfis = [];
try {
    $perfis = $perfilModel->listPublic($cidadeFiltro, $categoriaFiltro);
} catch (Throwable $e) {
    error_log('[Parceiros] listPublic: ' . $e->getMessage());
    $perfis = [];
}

$categorias = [
    [
        'key' => 'petshop',
        'title' => 'Pet Shops',
        'icon' => 'bi-bag-heart',
        'desc' => 'Rações, acessórios, banho e tosa com qualidade.',
    ],
    [
        'key' => 'clinica',
        'title' => 'Clínicas Veterinárias',
        'icon' => 'bi-heart-pulse',
        'desc' => 'Consultas, vacinas e emergências com profissionais.',
    ],
    [
        'key' => 'hotel',
        'title' => 'Hotéis e Creches',
        'icon' => 'bi-house-heart',
        'desc' => 'Hospedagem e rotina segura quando você precisar.',
    ],
    [
        'key' => 'adestrador',
        'title' => 'Adestradores',
        'icon' => 'bi-stars',
        'desc' => 'Educação e comportamento para uma vida melhor.',
    ],
];

$cards = [
    [
        'title' => 'Parceiro Destaque (em breve)',
        'category' => 'Clínica Veterinária',
        'city' => 'Porto Velho - RO',
        'badge' => 'Verificado',
        'phone' => '(69) 00000-0000',
        'whatsapp' => '(69) 00000-0000',
        'desc' => 'Perfil empresarial com fotos, serviços, horários e contato direto.',
    ],
    [
        'title' => 'Pet Shop (em breve)',
        'category' => 'Pet Shop',
        'city' => 'Porto Velho - RO',
        'badge' => 'Novo',
        'phone' => '(69) 00000-0000',
        'whatsapp' => '(69) 00000-0000',
        'desc' => 'Rações, banho e tosa com qualidade e entrega rápida.',
    ],
    [
        'title' => 'Hotel/Creche (em breve)',
        'category' => 'Hotel',
        'city' => 'Porto Velho - RO',
        'badge' => 'Agenda',
        'phone' => '(69) 00000-0000',
        'whatsapp' => '(69) 00000-0000',
        'desc' => 'Um lugar seguro para seu pet enquanto você viaja ou trabalha.',
    ],
];

include __DIR__ . '/../includes/header.php';
?>

<section class="partners-hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <div class="partners-hero-badge mb-3">
                    <i class="bi bi-shield-check me-2"></i>
                    Serviços pet confiáveis
                </div>
                <h1 class="display-5 fw-bold mb-3">Parceiros PetFinder</h1>
                <p class="lead mb-4">
                    Encontre pet shops, clínicas, hotéis e adestradores na sua região.
                    Este espaço ajuda a manter o PetFinder sustentável sem tirar o foco dos anúncios de pets.
                </p>

                <div class="partners-quick-actions d-flex flex-wrap gap-2">
                    <a href="#categorias" class="btn btn-light btn-lg">
                        <i class="bi bi-grid me-2"></i>
                        Ver categorias
                    </a>
                    <a href="<?php echo BASE_URL; ?>/parceiros/inscricao" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-briefcase me-2"></i>
                        Quero ser parceiro
                    </a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="partners-hero-card">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="partners-hero-icon">
                            <i class="bi bi-rocket-takeoff"></i>
                        </div>
                        <div>
                            <div class="fw-bold">Plano Parceiro</div>
                            <div class="text-muted">Receita recorrente para manter o PetFinder</div>
                        </div>
                    </div>
                    <ul class="partners-hero-list">
                        <li><i class="bi bi-check2-circle"></i> Perfil com contatos e localização</li>
                        <li><i class="bi bi-check2-circle"></i> Destaque por cidade e categoria</li>
                        <li><i class="bi bi-check2-circle"></i> Selo de verificação (curadoria)</li>
                    </ul>
                    <a href="<?php echo BASE_URL; ?>/parceiros/inscricao" class="btn btn-partners-primary w-100 btn-lg mt-3">
                        <i class="bi bi-chat-dots me-2"></i>
                        Solicitar cadastro
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5" id="categorias">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="h3 fw-bold mb-2">Categorias</h2>
            <p class="text-muted mb-0">Escolha o tipo de serviço que você precisa.</p>
        </div>

        <div class="row g-4">
            <?php foreach ($categorias as $cat): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="partner-category-card">
                        <div class="partner-category-icon">
                            <i class="bi <?php echo sanitize($cat['icon']); ?>"></i>
                        </div>
                        <h3 class="h5 fw-bold mb-2"><?php echo sanitize($cat['title']); ?></h3>
                        <p class="text-muted mb-0"><?php echo sanitize($cat['desc']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="partners-directory py-5" id="lista">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <h2 class="h3 fw-bold mb-1">Diretório</h2>
                <p class="text-muted mb-0">Exemplos de como os perfis vão aparecer (em breve).</p>
            </div>
            <div class="partners-filter-placeholder">
                <form method="GET" class="w-100">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                        <input type="text" class="form-control" name="cidade" placeholder="Cidade" value="<?php echo sanitize((string)($cidadeFiltro ?? '')); ?>">
                        <select class="form-select" name="categoria">
                            <option value="">Todas as categorias</option>
                            <option value="petshop" <?php echo $categoriaFiltro === 'petshop' ? 'selected' : ''; ?>>Pet Shop</option>
                            <option value="clinica" <?php echo $categoriaFiltro === 'clinica' ? 'selected' : ''; ?>>Clínica</option>
                            <option value="hotel" <?php echo $categoriaFiltro === 'hotel' ? 'selected' : ''; ?>>Hotel/Creche</option>
                            <option value="adestrador" <?php echo $categoriaFiltro === 'adestrador' ? 'selected' : ''; ?>>Adestrador</option>
                            <option value="outro" <?php echo $categoriaFiltro === 'outro' ? 'selected' : ''; ?>>Outro</option>
                        </select>
                        <button class="btn btn-primary" type="submit">Filtrar</button>
                    </div>
                    <div class="small text-muted mt-1">Mostrando apenas perfis publicados.</div>
                </form>
            </div>
        </div>

        <div class="row g-4">
            <?php if (!empty($perfis)): ?>
                <?php foreach ($perfis as $p): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="partner-card <?php echo !empty($p['destaque']) ? 'partner-card-highlight' : ''; ?>">
                            <?php if (!empty($p['destaque'])): ?>
                                <div class="partner-card-ribbon"><i class="bi bi-star-fill"></i> Destaque</div>
                            <?php endif; ?>
                            <div class="partner-card-header">
                                <div>
                                    <div class="partner-card-title">
                                        <?php if (!empty($p['destaque'])): ?>
                                            <i class="bi bi-star-fill partner-card-title-icon"></i>
                                        <?php endif; ?>
                                        <?php echo sanitize($p['nome_fantasia']); ?>
                                    </div>
                                    <div class="partner-card-subtitle"><?php echo sanitize($p['categoria']); ?> • <?php echo sanitize($p['cidade']); ?> - <?php echo sanitize($p['estado']); ?></div>
                                </div>
                                <span class="badge partner-badge <?php echo !empty($p['destaque']) ? 'partner-badge-highlight' : ''; ?>">
                                    <?php
                                        if (!empty($p['destaque'])) {
                                            echo 'Destaque';
                                        } elseif (!empty($p['verificado'])) {
                                            echo 'Verificado';
                                        } else {
                                            echo 'Parceiro';
                                        }
                                    ?>
                                </span>
                            </div>
                            <div class="partner-card-body">
                                <p class="text-muted mb-3"><?php echo sanitize(truncate((string)($p['descricao'] ?? ''), 120)); ?></p>
                                <div class="partner-contact">
                                    <?php if (!empty($p['telefone'])): ?>
                                        <div class="partner-contact-item">
                                            <i class="bi bi-telephone"></i>
                                            <span><?php echo sanitize(formatPhone($p['telefone'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($p['whatsapp'])): ?>
                                        <div class="partner-contact-item">
                                            <i class="bi bi-whatsapp"></i>
                                            <span><?php echo sanitize(formatPhone($p['whatsapp'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="partner-card-footer">
                                <a class="btn btn-outline-primary w-100" href="<?php echo BASE_URL; ?>/parceiro/<?php echo sanitize($p['slug']); ?>">
                                    Ver perfil
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php foreach ($cards as $card): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="partner-card">
                            <div class="partner-card-header">
                                <div>
                                    <div class="partner-card-title"><?php echo sanitize($card['title']); ?></div>
                                    <div class="partner-card-subtitle"><?php echo sanitize($card['category']); ?> • <?php echo sanitize($card['city']); ?></div>
                                </div>
                                <span class="badge partner-badge"><?php echo sanitize($card['badge']); ?></span>
                            </div>
                            <div class="partner-card-body">
                                <p class="text-muted mb-3"><?php echo sanitize($card['desc']); ?></p>
                                <div class="partner-contact">
                                    <div class="partner-contact-item">
                                        <i class="bi bi-telephone"></i>
                                        <span><?php echo sanitize($card['phone']); ?></span>
                                    </div>
                                    <div class="partner-contact-item">
                                        <i class="bi bi-whatsapp"></i>
                                        <span><?php echo sanitize($card['whatsapp']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="partner-card-footer">
                                <a class="btn btn-outline-secondary w-100" href="<?php echo BASE_URL; ?>/parceiros/inscricao">
                                    Quero aparecer aqui
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="partners-cta py-5" id="quero-ser-parceiro">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <h2 class="h3 fw-bold mb-2">Sua empresa quer aparecer aqui?</h2>
                <p class="mb-0">
                    Cadastre um perfil empresarial e divulgue seus serviços.
                    Você apoia a plataforma e ajuda tutores a encontrarem serviços confiáveis.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a class="btn btn-light btn-lg w-100 w-lg-auto" href="<?php echo BASE_URL; ?>/parceiros/inscricao">
                    <i class="bi bi-envelope me-2"></i>
                    Solicitar parceria
                </a>
                <div class="small mt-2 opacity-75">
                    Você faz a inscrição e recebe instruções no e-mail após aprovação.
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
