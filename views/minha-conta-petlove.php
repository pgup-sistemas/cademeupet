<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$pageTitle  = 'Meu Pet Love | Cadê Meu Pet?';
$ctrl       = new PetLoveController();
$ic         = new PetLoveInteresseController();
$dados      = $ctrl->meusPets();
$pets       = $dados['pets'];
$recebidos  = $dados['interesses_receb'];
$enviados   = $dados['interesses_env'];

// Processar respostas a interesses
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $res = $ic->responder($_POST);
    setFlashMessage($res['msg'], $res['ok'] ? MSG_SUCCESS : MSG_ERROR);
    redirect('/minha-conta/petlove');
}

$breadcrumbs = [
    ['label' => 'Início',        'url' => BASE_URL],
    ['label' => 'Pet Love',      'url' => BASE_URL . '/petlove'],
    ['label' => 'Minha Conta'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 fw-bold mb-0">
            <i class="fa-solid fa-heart me-2 text-danger"></i>Meu Pet Love
        </h1>
        <a href="<?php echo BASE_URL; ?>/petlove/novo" class="btn btn-cmp-primary">
            <i class="fa-solid fa-plus me-1"></i>Cadastrar pet
        </a>
    </div>

    <!-- Abas -->
    <ul class="nav nav-tabs mb-4" id="petloveTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pets-tab" data-bs-toggle="tab" data-bs-target="#aba-pets"
                    type="button" role="tab">
                <i class="fa-solid fa-paw me-1"></i>Meus Pets
                <span class="badge bg-secondary ms-1"><?php echo count($pets); ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="recebidos-tab" data-bs-toggle="tab" data-bs-target="#aba-recebidos"
                    type="button" role="tab">
                <i class="fa-solid fa-inbox me-1"></i>Interesses Recebidos
                <?php $pendentes = count(array_filter($recebidos, fn($r) => $r['status'] === 'pendente')); ?>
                <?php if ($pendentes > 0): ?>
                    <span class="badge bg-danger ms-1"><?php echo $pendentes; ?></span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="enviados-tab" data-bs-toggle="tab" data-bs-target="#aba-enviados"
                    type="button" role="tab">
                <i class="fa-solid fa-paper-plane me-1"></i>Interesses Enviados
                <span class="badge bg-secondary ms-1"><?php echo count($enviados); ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="petloveTabsContent">

        <!-- ABA: Meus Pets -->
        <div class="tab-pane fade show active" id="aba-pets" role="tabpanel">
            <?php if (empty($pets)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-paw fa-3x mb-3 d-block opacity-25"></i>
                    <h5>Você ainda não cadastrou nenhum pet no Pet Love.</h5>
                    <a href="<?php echo BASE_URL; ?>/petlove/novo" class="btn btn-cmp-primary mt-2">
                        <i class="fa-solid fa-plus me-1"></i>Cadastrar meu primeiro pet
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($pets as $pet): ?>
                        <?php
                        $foto = !empty($pet['foto_principal'])
                            ? BASE_URL . '/uploads/petlove/' . $pet['foto_principal']
                            : null;
                        $statusClasses = ['ativo' => 'success','pausado' => 'warning','removido' => 'danger'];
                        $statusLabels  = ['ativo' => 'Ativo','pausado' => 'Pausado','removido' => 'Removido'];
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="position-relative">
                                    <?php if ($foto): ?>
                                        <img src="<?php echo $foto; ?>"
                                             class="card-img-top"
                                             style="aspect-ratio:1/1;object-fit:cover;"
                                             alt="<?php echo sanitize($pet['nome']); ?>">
                                    <?php else: ?>
                                        <div class="card-img-top d-flex align-items-center justify-content-center bg-light text-muted"
                                             style="aspect-ratio:1/1;">
                                            <i class="fa-solid fa-paw fa-3x opacity-25"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="position-absolute top-0 end-0 m-2 badge bg-<?php echo $statusClasses[$pet['status']] ?? 'secondary'; ?>">
                                        <?php echo $statusLabels[$pet['status']] ?? ucfirst($pet['status']); ?>
                                    </span>
                                    <?php if ($pet['interesses_pendentes'] > 0): ?>
                                        <span class="position-absolute top-0 start-0 m-2 badge bg-danger">
                                            <i class="fa-solid fa-heart me-1"></i><?php echo $pet['interesses_pendentes']; ?> novo<?php echo $pet['interesses_pendentes'] > 1 ? 's' : ''; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body pb-2">
                                    <h5 class="fw-bold mb-1"><?php echo sanitize($pet['nome']); ?></h5>
                                    <p class="small text-muted mb-0">
                                        <?php echo sanitize($pet['raca']); ?> ·
                                        <?php echo PetLove::labelPorte($pet['porte']); ?> ·
                                        <?php echo $pet['sexo'] === 'macho' ? 'Macho' : 'Fêmea'; ?>
                                    </p>
                                    <p class="small text-muted mb-0">
                                        <i class="fa-solid fa-location-dot me-1"></i>
                                        <?php echo sanitize($pet['cidade'] . ', ' . $pet['estado']); ?>
                                    </p>
                                </div>
                                <div class="card-footer bg-transparent border-0 d-flex gap-2 px-3 pb-3">
                                    <a href="<?php echo BASE_URL; ?>/petlove/<?php echo (int)$pet['id']; ?>"
                                       class="btn btn-sm btn-outline-primary flex-grow-1">
                                        <i class="fa-solid fa-eye me-1"></i>Ver
                                    </a>
                                    <?php if ($pet['status'] === 'ativo'): ?>
                                        <a href="<?php echo BASE_URL; ?>/petlove/status/<?php echo (int)$pet['id']; ?>/pausado"
                                           class="btn btn-sm btn-outline-warning"
                                           onclick="return confirm('Pausar este pet?')">
                                            <i class="fa-solid fa-pause"></i>
                                        </a>
                                    <?php elseif ($pet['status'] === 'pausado'): ?>
                                        <a href="<?php echo BASE_URL; ?>/petlove/status/<?php echo (int)$pet['id']; ?>/ativo"
                                           class="btn btn-sm btn-outline-success">
                                            <i class="fa-solid fa-play"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($pet['status'] !== 'removido'): ?>
                                        <a href="<?php echo BASE_URL; ?>/petlove/status/<?php echo (int)$pet['id']; ?>/removido"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Remover este pet do Pet Love?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ABA: Interesses Recebidos -->
        <div class="tab-pane fade" id="aba-recebidos" role="tabpanel">
            <?php if (empty($recebidos)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                    <h5>Nenhum interesse recebido ainda.</h5>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($recebidos as $r): ?>
                        <?php
                        $badgeStatus = ['pendente' => 'warning','aceito' => 'success','recusado' => 'danger'];
                        $labelStatus = ['pendente' => 'Pendente','aceito' => 'Aceito','recusado' => 'Recusado'];
                        ?>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-start justify-content-between gap-3">
                                        <div>
                                            <div class="fw-bold"><?php echo sanitize($r['interessado_nome']); ?></div>
                                            <div class="small text-muted">
                                                Interesse em <a href="<?php echo BASE_URL; ?>/petlove/<?php echo (int)$r['petlove_id']; ?>"><?php echo sanitize($r['pet_nome']); ?></a>
                                                <?php if ($r['pet_interessado_nome']): ?>
                                                    · Com: <?php echo sanitize($r['pet_interessado_nome']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($r['mensagem']): ?>
                                                <p class="mt-2 mb-0 text-muted fst-italic">"<?php echo sanitize($r['mensagem']); ?>"</p>
                                            <?php endif; ?>
                                            <?php if ($r['status'] === 'aceito'): ?>
                                                <div class="mt-2 small">
                                                    <i class="fa-solid fa-phone me-1 text-success"></i><?php echo sanitize($r['interessado_tel']); ?>
                                                    &nbsp;·&nbsp;
                                                    <i class="fa-solid fa-envelope me-1 text-primary"></i><?php echo sanitize($r['interessado_email']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex flex-column align-items-end gap-2 flex-shrink-0">
                                            <span class="badge bg-<?php echo $badgeStatus[$r['status']] ?? 'secondary'; ?> text-dark">
                                                <?php echo $labelStatus[$r['status']] ?? $r['status']; ?>
                                            </span>
                                            <?php if ($r['status'] === 'pendente'): ?>
                                                <div class="d-flex gap-2">
                                                    <form method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="interesse_id" value="<?php echo (int)$r['id']; ?>">
                                                        <input type="hidden" name="acao" value="aceitar">
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fa-solid fa-check me-1"></i>Aceitar
                                                        </button>
                                                    </form>
                                                    <form method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="interesse_id" value="<?php echo (int)$r['id']; ?>">
                                                        <input type="hidden" name="acao" value="recusar">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fa-solid fa-xmark me-1"></i>Recusar
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ABA: Interesses Enviados -->
        <div class="tab-pane fade" id="aba-enviados" role="tabpanel">
            <?php if (empty($enviados)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-paper-plane fa-3x mb-3 d-block opacity-25"></i>
                    <h5>Você ainda não enviou nenhum interesse.</h5>
                    <a href="<?php echo BASE_URL; ?>/petlove" class="btn btn-outline-primary mt-2">Explorar pets</a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($enviados as $e): ?>
                        <?php
                        $petFotoEnv = !empty($e['pet_foto'])
                            ? BASE_URL . '/uploads/petlove/' . $e['pet_foto']
                            : null;
                        $badgeStatusE = ['pendente' => 'warning','aceito' => 'success','recusado' => 'danger'];
                        $labelStatusE = ['pendente' => 'Aguardando resposta','aceito' => 'Aceito!','recusado' => 'Recusado'];
                        ?>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-3 d-flex gap-3 align-items-center">
                                    <?php if ($petFotoEnv): ?>
                                        <img src="<?php echo $petFotoEnv; ?>"
                                             style="width:64px;height:64px;object-fit:cover;border-radius:.5rem;"
                                             alt="<?php echo sanitize($e['pet_nome']); ?>">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center bg-light rounded flex-shrink-0"
                                             style="width:64px;height:64px;">
                                            <i class="fa-solid fa-paw text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="fw-bold text-truncate">
                                            <a href="<?php echo BASE_URL; ?>/petlove/<?php echo (int)$e['petlove_id']; ?>">
                                                <?php echo sanitize($e['pet_nome']); ?>
                                            </a>
                                        </div>
                                        <div class="small text-muted"><?php echo sanitize($e['pet_especie']); ?> · <?php echo sanitize($e['pet_raca']); ?></div>
                                        <div class="small text-muted">Tutor: <?php echo sanitize($e['dono_nome']); ?></div>
                                        <?php if ($e['status'] === 'aceito'): ?>
                                            <div class="small mt-1 text-success fw-semibold">
                                                <i class="fa-solid fa-circle-check me-1"></i>Contato liberado — acesse o perfil do pet
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-<?php echo $badgeStatusE[$e['status']] ?? 'secondary'; ?> text-dark flex-shrink-0">
                                        <?php echo $labelStatusE[$e['status']] ?? $e['status']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
