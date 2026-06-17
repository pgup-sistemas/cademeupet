<?php
require_once __DIR__ . '/../config.php';

$pageTitle       = 'Pet Love — Encontre um par para seu pet | Cadê Meu Pet?';
$metaDescription = 'Encontre o parceiro ideal para o cruzamento do seu pet. Filtre por espécie, raça, porte e localização.';

$controller = new PetLoveController();
$resultado  = $controller->vitrine();
$pets       = $resultado['pets'];

$breadcrumbs = [
    ['label' => 'Início',   'url' => BASE_URL],
    ['label' => 'Pet Love'],
];
include __DIR__ . '/../includes/header.php';
?>

<!-- Banner ético obrigatório -->
<div class="alert alert-info alert-dismissible fade show mb-0 rounded-0 border-0" role="alert"
     style="background:linear-gradient(90deg,#e8f4f8,#d1ecf1);border-left:4px solid #0dcaf0 !important;">
    <div class="container d-flex align-items-center gap-3">
        <i class="fa-solid fa-circle-info fa-lg text-info flex-shrink-0"></i>
        <span>
            O Cadê Meu Pet? incentiva a <strong>criação responsável</strong>.
            Consulte sempre um veterinário antes do cruzamento.
            <a href="#etica" class="alert-link ms-1">Saiba mais</a>
        </span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
</div>

<!-- Hero Pet Love -->
<div class="petlove-hero py-5"
     style="background:linear-gradient(135deg,#ff6b35 0%,#e5499a 100%);color:#fff;">
    <div class="container text-center">
        <h1 class="fw-bold mb-2">
            <i class="fa-solid fa-heart me-2"></i>Pet Love
        </h1>
        <p class="lead mb-4 opacity-90">Encontre o par ideal para o seu pet — raça, porte e proximidade</p>
        <?php if (isLoggedIn()): ?>
            <a href="<?php echo BASE_URL; ?>/petlove/novo" class="btn btn-light btn-lg fw-semibold"
               style="color:#e5499a;">
                <i class="fa-solid fa-plus me-2"></i>Cadastrar meu pet
            </a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/login?redirect=/petlove/novo" class="btn btn-light btn-lg fw-semibold"
               style="color:#e5499a;">
                <i class="fa-solid fa-right-to-bracket me-2"></i>Entre para cadastrar
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="container py-4">
    <div class="row g-4">

        <!-- Filtros -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm sticky-top" style="top:80px;">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-sliders me-2 text-primary"></i>Filtros</h6>
                    <form method="GET" action="<?php echo BASE_URL; ?>/petlove" id="formFiltros">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Espécie</label>
                            <select name="especie" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                <option value="cachorro" <?php echo ($_GET['especie'] ?? '') === 'cachorro' ? 'selected' : ''; ?>>Cachorro</option>
                                <option value="gato"     <?php echo ($_GET['especie'] ?? '') === 'gato'     ? 'selected' : ''; ?>>Gato</option>
                                <option value="outro"    <?php echo ($_GET['especie'] ?? '') === 'outro'    ? 'selected' : ''; ?>>Outro</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Sexo</label>
                            <select name="sexo" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="macho"  <?php echo ($_GET['sexo'] ?? '') === 'macho'  ? 'selected' : ''; ?>>Macho</option>
                                <option value="femea"  <?php echo ($_GET['sexo'] ?? '') === 'femea'  ? 'selected' : ''; ?>>Fêmea</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Porte</label>
                            <select name="porte" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <?php foreach (['mini' => 'Miniatura','pequeno' => 'Pequeno','medio' => 'Médio','grande' => 'Grande','gigante' => 'Gigante'] as $v => $l): ?>
                                    <option value="<?php echo $v; ?>" <?php echo ($_GET['porte'] ?? '') === $v ? 'selected' : ''; ?>><?php echo $l; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Raça</label>
                            <input type="text" name="raca" class="form-control form-control-sm"
                                   placeholder="Ex: Labrador" value="<?php echo sanitize($_GET['raca'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Cidade</label>
                            <input type="text" name="cidade" class="form-control form-control-sm"
                                   placeholder="Ex: Porto Velho" value="<?php echo sanitize($_GET['cidade'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Pedigree</label>
                            <select name="tem_pedigree" class="form-select form-select-sm">
                                <option value="">Qualquer</option>
                                <option value="1" <?php echo ($_GET['tem_pedigree'] ?? '') === '1' ? 'selected' : ''; ?>>Com pedigree</option>
                                <option value="0" <?php echo ($_GET['tem_pedigree'] ?? '') === '0' ? 'selected' : ''; ?>>Sem pedigree</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-cmp-primary btn-sm w-100">
                            <i class="fa-solid fa-magnifying-glass me-1"></i>Filtrar
                        </button>
                        <?php if (!empty(array_filter($_GET ?? []))): ?>
                            <a href="<?php echo BASE_URL; ?>/petlove" class="btn btn-outline-secondary btn-sm w-100 mt-2">
                                <i class="fa-solid fa-xmark me-1"></i>Limpar filtros
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Grid de pets -->
        <div class="col-lg-9">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h5 fw-bold mb-0">
                    <?php echo number_format($resultado['total']); ?> pet<?php echo $resultado['total'] !== 1 ? 's' : ''; ?> disponíve<?php echo $resultado['total'] !== 1 ? 'is' : 'l'; ?>
                </h2>
                <span class="text-muted small">Página <?php echo $resultado['pagina']; ?> de <?php echo max(1, $resultado['paginas']); ?></span>
            </div>

            <?php if (empty($pets)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-heart-crack fa-3x mb-3 d-block opacity-50"></i>
                    <h5>Nenhum pet encontrado com esses filtros.</h5>
                    <a href="<?php echo BASE_URL; ?>/petlove" class="btn btn-outline-primary mt-2">Ver todos</a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($pets as $pet): ?>
                        <?php
                            $foto = $pet['foto_principal']
                                ? (BASE_URL . '/uploads/petlove/' . $pet['foto_principal'])
                                : null;
                            $sexoIcon  = $pet['sexo'] === 'macho' ? 'fa-mars' : 'fa-venus';
                            $sexoClass = $pet['sexo'] === 'macho' ? 'badge-sexo-macho' : 'badge-sexo-femea';
                            $sexoLabel = $pet['sexo'] === 'macho' ? 'Macho' : 'Fêmea';
                        ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="card petlove-card h-100">
                                <a href="<?php echo BASE_URL; ?>/petlove/<?php echo (int)$pet['id']; ?>"
                                   class="text-decoration-none text-dark">
                                    <div class="position-relative">
                                        <?php if ($foto): ?>
                                            <img src="<?php echo $foto; ?>"
                                                 class="card-img-top"
                                                 alt="<?php echo sanitize($pet['nome']); ?>"
                                                 loading="lazy">
                                        <?php else: ?>
                                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light text-muted"
                                                 style="aspect-ratio:1/1;">
                                                <i class="fa-solid fa-paw fa-3x opacity-25"></i>
                                            </div>
                                        <?php endif; ?>
                                        <span class="<?php echo $sexoClass; ?>">
                                            <i class="fa-solid <?php echo $sexoIcon; ?>"></i> <?php echo $sexoLabel; ?>
                                        </span>
                                    </div>
                                    <div class="card-body pb-2">
                                        <h5 class="card-title fs-6 fw-bold mb-1"><?php echo sanitize($pet['nome']); ?></h5>
                                        <p class="card-text small text-muted mb-2">
                                            <i class="fa-solid fa-paw me-1"></i><?php echo sanitize($pet['raca']); ?>
                                            &nbsp;·&nbsp;
                                            <i class="fa-solid fa-ruler me-1"></i><?php echo PetLove::labelPorte($pet['porte']); ?>
                                        </p>
                                        <p class="card-text small text-muted mb-2">
                                            <i class="fa-solid fa-cake-candles me-1"></i><?php echo PetLove::labelIdade((int)$pet['idade_meses']); ?>
                                            &nbsp;·&nbsp;
                                            <i class="fa-solid fa-location-dot me-1"></i><?php echo sanitize($pet['cidade'] . ', ' . $pet['estado']); ?>
                                        </p>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php if ($pet['tem_pedigree']): ?>
                                                <span class="badge bg-success"><i class="fa-solid fa-award me-1"></i>Pedigree</span>
                                            <?php endif; ?>
                                            <?php if ($pet['vacinado']): ?>
                                                <span class="badge bg-info text-dark"><i class="fa-solid fa-syringe me-1"></i>Vacinado</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                                <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
                                    <a href="<?php echo BASE_URL; ?>/petlove/<?php echo (int)$pet['id']; ?>"
                                       class="btn btn-sm btn-cmp-primary w-100">
                                        <i class="fa-solid fa-heart me-1"></i>Ver perfil
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Paginação -->
                <?php if ($resultado['paginas'] > 1): ?>
                    <nav class="mt-4" aria-label="Paginação">
                        <ul class="pagination justify-content-center">
                            <?php for ($p = 1; $p <= $resultado['paginas']; $p++): ?>
                                <li class="page-item <?php echo $p === $resultado['pagina'] ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $p])); ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Seção ética -->
    <div id="etica" class="row mt-5 pt-4 border-top">
        <div class="col-lg-8 mx-auto text-center">
            <h3 class="fw-bold mb-3"><i class="fa-solid fa-heart-pulse me-2 text-danger"></i>Criação Responsável</h3>
            <div class="row g-3 text-start mt-2">
                <div class="col-md-4">
                    <div class="d-flex gap-3">
                        <i class="fa-solid fa-syringe fa-lg text-success mt-1 flex-shrink-0"></i>
                        <div>
                            <strong>Pet saudável</strong>
                            <p class="small text-muted mb-0">Certifique-se de que seu pet está vacinado e vermifugado antes do cruzamento.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-3">
                        <i class="fa-solid fa-stethoscope fa-lg text-primary mt-1 flex-shrink-0"></i>
                        <div>
                            <strong>Consulta veterinária</strong>
                            <p class="small text-muted mb-0">Recomendamos consultar um veterinário antes e após o cruzamento.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-3">
                        <i class="fa-solid fa-flag fa-lg text-danger mt-1 flex-shrink-0"></i>
                        <div>
                            <strong>Denuncie irregularidades</strong>
                            <p class="small text-muted mb-0">Suspeita de criadouro irregular ou maus-tratos? Use o botão de denúncia.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
