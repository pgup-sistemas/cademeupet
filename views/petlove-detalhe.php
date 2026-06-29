<?php
require_once __DIR__ . '/../config.php';

$id   = (int)($_GET['id'] ?? 0);
$ctrl = new PetLoveController();
$pet  = $ctrl->detalhe($id);

if (!$pet) {
    setFlashMessage('Pet não encontrado.', MSG_WARNING);
    redirect('/petlove');
}

$pageTitle       = sanitize($pet['nome']) . ' — Pet Love | Cadê Meu Pet?';
$metaDescription = sanitize(truncate($pet['descricao'] ?? 'Conheça ' . $pet['nome'] . ' no Pet Love.', 160));
$fotoPrincipal   = !empty($pet['fotos'][0]['caminho'])
    ? BASE_URL . '/uploads/petlove/' . $pet['fotos'][0]['caminho']
    : null;
$metaOgImage = $fotoPrincipal;
$metaOgTitle = $pageTitle;

$isOwner   = isLoggedIn() && (int)$pet['usuario_id'] === getUserId();
$sexoIcon  = $pet['sexo'] === 'macho' ? 'fa-mars' : 'fa-venus';
$sexoLabel = $pet['sexo'] === 'macho' ? 'Macho' : 'Fêmea';
$sexoClass = $pet['sexo'] === 'macho' ? 'badge-sexo-macho' : 'badge-sexo-femea';

$breadcrumbs = [
    ['label' => 'Início',    'url' => BASE_URL],
    ['label' => 'Pet Love',  'url' => BASE_URL . '/petlove'],
    ['label' => sanitize($pet['nome'])],
];

include __DIR__ . '/../includes/header.php';
?>

<!-- Banner ético -->
<div class="alert alert-info alert-dismissible fade show mb-0 rounded-0 border-0"
     style="background:linear-gradient(90deg,#e8f4f8,#d1ecf1);">
    <div class="container d-flex align-items-center gap-3">
        <i class="fa-solid fa-circle-info text-info flex-shrink-0"></i>
        <span>O Cadê Meu Pet? incentiva a <strong>criação responsável</strong>. Consulte sempre um veterinário antes do cruzamento.</span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
</div>

<div class="container py-5">

    <div class="row g-4">
        <!-- Coluna esquerda: fotos + info -->
        <div class="col-lg-7">

            <!-- Carrossel de fotos -->
            <?php if (!empty($pet['fotos'])): ?>
                <div id="carouselPetLove" class="carousel slide shadow-sm rounded mb-4" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($pet['fotos'] as $i => $foto): ?>
                            <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                                <div class="carousel-blur-bg"
                                     style="background-image:url('<?php echo BASE_URL . '/uploads/petlove/' . sanitize($foto['caminho']); ?>');"></div>
                                <img src="<?php echo BASE_URL . '/uploads/petlove/' . sanitize($foto['caminho']); ?>"
                                     class="d-block w-100 rounded carousel-photo"
                                     alt="Foto de <?php echo sanitize($pet['nome']); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($pet['fotos']) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselPetLove" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Anterior</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselPetLove" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Próxima</span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center bg-light rounded mb-4 text-muted"
                     style="height:320px;">
                    <div class="text-center">
                        <i class="fa-solid fa-paw fa-3x opacity-25 d-block mb-2"></i>
                        Sem fotos cadastradas
                    </div>
                </div>
            <?php endif; ?>

            <!-- Dados principais -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <h2 class="h4 fw-bold mb-1"><?php echo sanitize($pet['nome']); ?></h2>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="<?php echo $sexoClass; ?> badge">
                                    <i class="fa-solid <?php echo $sexoIcon; ?>"></i> <?php echo $sexoLabel; ?>
                                </span>
                                <span class="badge bg-light text-dark"><?php echo PetLove::labelEspecie($pet['especie']); ?></span>
                                <span class="badge bg-light text-dark"><?php echo sanitize($pet['raca']); ?></span>
                                <span class="badge bg-light text-dark"><?php echo PetLove::labelPorte($pet['porte']); ?></span>
                            </div>
                        </div>
                        <?php if ($pet['status'] === 'pausado'): ?>
                            <span class="badge bg-warning text-dark">Pausado</span>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-label">Idade</span>
                                <span class="info-value"><?php echo PetLove::labelIdade((int)$pet['idade_meses']); ?></span>
                            </div>
                        </div>
                        <?php if ($pet['peso_kg']): ?>
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-label">Peso</span>
                                <span class="info-value"><?php echo number_format((float)$pet['peso_kg'], 1); ?> kg</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($pet['cor']): ?>
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-label">Cor</span>
                                <span class="info-value"><?php echo sanitize($pet['cor']); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-label">Objetivo</span>
                                <span class="info-value"><?php echo ['cruzamento' => 'Cruzamento','pedigree' => 'Cruzamento c/ pedigree','companhia' => 'Companhia'][$pet['objetivo']] ?? ucfirst($pet['objetivo']); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-label">Localização</span>
                                <span class="info-value"><?php echo sanitize($pet['cidade'] . ', ' . $pet['estado']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Badges de saúde -->
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php if ($pet['vacinado']): ?>
                            <span class="badge bg-success"><i class="fa-solid fa-syringe me-1"></i>Vacinado</span>
                        <?php endif; ?>
                        <?php if ($pet['vermifugado']): ?>
                            <span class="badge bg-info text-dark"><i class="fa-solid fa-pills me-1"></i>Vermifugado</span>
                        <?php endif; ?>
                        <?php if ($pet['tem_pedigree']): ?>
                            <span class="badge bg-warning text-dark"><i class="fa-solid fa-award me-1"></i>Pedigree
                                <?php if ($pet['pedigree_num']): ?>
                                    — <?php echo sanitize($pet['pedigree_num']); ?>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($pet['criacao_responsavel']): ?>
                            <span class="badge bg-secondary"><i class="fa-solid fa-circle-check me-1"></i>Criação responsável</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($pet['descricao']): ?>
                        <h5 class="fw-bold">Sobre <?php echo sanitize($pet['nome']); ?></h5>
                        <p class="mb-0"><?php echo nl2br(sanitize($pet['descricao'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Coluna direita: ações + matches -->
        <div class="col-lg-5">
            <div class="sticky-top" style="top:80px;">

                <!-- Card de ação -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-1">Tutor: <?php echo sanitize($pet['tutor_nome']); ?></h5>
                        <p class="text-muted small mb-3">
                            <i class="fa-solid fa-location-dot me-1"></i>
                            <?php echo sanitize($pet['cidade'] . ' — ' . $pet['estado']); ?>
                        </p>

                        <?php if ($isOwner): ?>
                            <div class="d-grid gap-2">
                                <a href="<?php echo BASE_URL; ?>/minha-conta/petlove"
                                   class="btn btn-outline-primary">
                                    <i class="fa-solid fa-list me-2"></i>Gerenciar meus pets
                                </a>
                            </div>
                        <?php elseif (isLoggedIn()): ?>
                            <?php if ($pet['ja_interesse']): ?>
                                <div class="alert alert-success py-2 mb-0 text-center">
                                    <i class="fa-solid fa-circle-check me-2"></i>Interesse já enviado!
                                </div>
                            <?php elseif ($pet['status'] === 'ativo' && $pet['disponivel']): ?>
                                <button class="btn btn-cmp-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modalInteresse">
                                    <i class="fa-solid fa-heart me-2"></i>Tenho interesse no cruzamento
                                </button>
                            <?php else: ?>
                                <div class="alert alert-warning py-2 mb-0 text-center">
                                    <i class="fa-solid fa-pause me-2"></i>Pet temporariamente indisponível
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="<?php echo BASE_URL; ?>/login?redirect=/petlove/<?php echo (int)$pet['id']; ?>"
                               class="btn btn-cmp-primary w-100">
                                <i class="fa-solid fa-right-to-bracket me-2"></i>Entre para demonstrar interesse
                            </a>
                        <?php endif; ?>

                        <hr class="my-3">
                        <!-- Denúncia -->
                        <button class="btn btn-outline-danger btn-sm w-100"
                                onclick="alert('Para denunciar, envie um e-mail para suporte@cademeupet.com.br com o link desta página.')">
                            <i class="fa-solid fa-flag me-1"></i>Denunciar anúncio
                        </button>
                    </div>
                </div>

                <!-- Matches sugeridos -->
                <?php if (!empty($pet['matches'])): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent fw-bold py-3">
                            <i class="fa-solid fa-heart-pulse me-2 text-danger"></i>Matches sugeridos
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($pet['matches'], 0, 5) as $m): ?>
                                <?php
                                $mFoto = !empty($m['foto_principal'])
                                    ? BASE_URL . '/uploads/petlove/' . $m['foto_principal']
                                    : null;
                                ?>
                                <a href="<?php echo BASE_URL; ?>/petlove/<?php echo (int)$m['id']; ?>"
                                   class="list-group-item list-group-item-action px-3 py-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if ($mFoto): ?>
                                            <img src="<?php echo $mFoto; ?>"
                                                 style="width:48px;height:48px;object-fit:cover;border-radius:.5rem;"
                                                 alt="<?php echo sanitize($m['nome']); ?>">
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center bg-light rounded"
                                                 style="width:48px;height:48px;">
                                                <i class="fa-solid fa-paw text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1 min-width-0">
                                            <div class="fw-semibold text-truncate"><?php echo sanitize($m['nome']); ?></div>
                                            <div class="small text-muted"><?php echo sanitize($m['raca']); ?> · <?php echo sanitize($m['cidade']); ?></div>
                                        </div>
                                        <div class="text-end flex-shrink-0">
                                            <div class="fw-bold" style="color:var(--cmp-primary);"><?php echo $m['score']; ?>%</div>
                                            <?php if ($m['distancia_km'] !== null): ?>
                                                <div class="small text-muted"><?php echo number_format($m['distancia_km'], 0); ?> km</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <!-- Barra de compatibilidade -->
                                    <div class="petlove-compatibility-bar mt-2">
                                        <div class="petlove-compatibility-fill" style="width:<?php echo $m['score']; ?>%"></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($pet['matches']) > 5): ?>
                            <div class="card-footer text-center py-2">
                                <a href="<?php echo BASE_URL; ?>/petlove?especie=<?php echo urlencode($pet['especie']); ?>&porte=<?php echo urlencode($pet['porte']); ?>"
                                   class="small text-primary">Ver mais matches</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div><!-- /sticky -->
        </div>
    </div>
</div>

<!-- Modal: Demonstrar Interesse -->
<?php if (isLoggedIn() && !$isOwner && !$pet['ja_interesse'] && $pet['status'] === 'ativo'): ?>
    <?php
    $meusPets = (new PetLove())->buscarPorUsuario(getUserId());
    $meusPetsCompativeis = array_filter($meusPets, fn($mp) =>
        $mp['especie'] === $pet['especie']
        && $mp['sexo']  !== $pet['sexo']
        && $mp['status'] === 'ativo'
    );
    ?>
    <div class="modal fade" id="modalInteresse" tabindex="-1" aria-labelledby="modalInteresseLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalInteresseLabel">
                        <i class="fa-solid fa-heart text-danger me-2"></i>Interesse em cruzamento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form method="POST" action="<?php echo BASE_URL; ?>/petlove/interesse">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="petlove_id" value="<?php echo (int)$pet['id']; ?>">
                        <p class="text-muted small mb-3">
                            Ao enviar, o tutor de <strong><?php echo sanitize($pet['nome']); ?></strong> será notificado e poderá aceitar ou recusar o interesse.
                        </p>
                        <?php if ($meusPetsCompativeis): ?>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Qual dos seus pets? (opcional)</label>
                                <select name="pet_interessado_id" class="form-select">
                                    <option value="">Não especificar</option>
                                    <?php foreach ($meusPetsCompativeis as $mp): ?>
                                        <option value="<?php echo (int)$mp['id']; ?>">
                                            <?php echo sanitize($mp['nome']); ?> — <?php echo sanitize($mp['raca']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mensagem (opcional)</label>
                            <textarea name="mensagem" class="form-control" rows="3"
                                      placeholder="Apresente seu pet ou esclareça detalhes..."
                                      maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-cmp-primary">
                            <i class="fa-solid fa-paper-plane me-1"></i>Enviar interesse
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
