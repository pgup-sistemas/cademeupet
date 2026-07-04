<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Painel do Parceiro | Cadê Meu Pet?';

$usuarioId = (int)(getUserId() ?? 0);
$usuarioModel = new Usuario();
$inscricaoModel = new ParceiroInscricao();
$perfilModel = new ParceiroPerfil();
$assinaturaModel = new ParceiroAssinatura();

$usuario = $usuarioModel->findById($usuarioId);
$inscricao = $inscricaoModel->findByUserId($usuarioId);
$perfil = $perfilModel->findByUserId($usuarioId);
$assinatura = $assinaturaModel->findByUserId($usuarioId);

$etapaInscricaoOk = $inscricao && $inscricao['status'] === 'aprovada';
$etapaPerfilOk = $perfil && !empty($perfil['nome_fantasia']) && !empty($perfil['categoria']) && !empty($perfil['cidade']) && !empty($perfil['estado']);
$etapaPagamentoOk = $assinatura && ($assinatura['status'] ?? '') === 'ativa';

$solicitacoesTriagem = [];
if ($perfil && ($perfil['categoria'] ?? '') === 'clinica') {
    $triagemController = new TriagemController();
    $solicitacoesTriagem = $triagemController->painelParceiro((int)$perfil['id']);
}

$breadcrumbs = [
    ['label' => 'Início',    'url' => BASE_URL],
    ['label' => 'Parceiros', 'url' => BASE_URL . '/parceiros'],
    ['label' => 'Painel'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Painel do Parceiro</h1>
            <p class="text-muted mb-0">Acompanhe o status e conclua as etapas para publicar seu perfil.</p>
        </div>
        <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>/parceiros">Ver diretório</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Etapas</h2>

                    <div class="list-group">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">1) Inscrição e aprovação</div>
                                <div class="small text-muted">Você solicita e o admin aprova o seu acesso de parceiro.</div>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge <?php echo $etapaInscricaoOk ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                    <?php echo $etapaInscricaoOk ? 'OK' : 'Pendente'; ?>
                                </span>
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>/parceiros/inscricao">Ver</a>
                            </div>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">2) Completar perfil empresarial</div>
                                <div class="small text-muted">Dados do negócio que aparecerão em Parceiros.</div>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge <?php echo $etapaPerfilOk ? 'bg-success' : ($etapaInscricaoOk ? 'bg-warning text-dark' : 'bg-light text-dark'); ?>">
                                    <?php echo $etapaPerfilOk ? 'OK' : ($etapaInscricaoOk ? 'Pendente' : 'Bloqueado'); ?>
                                </span>
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>/parceiro/perfil" <?php echo $etapaInscricaoOk ? '' : 'aria-disabled="true" tabindex="-1"'; ?>>Editar</a>
                            </div>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">3) Pagamento / assinatura</div>
                                <div class="small text-muted">Pagamento mensal via PIX/manual (MVP). Admin valida para ativar.</div>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge <?php echo $etapaPagamentoOk ? 'bg-success' : ($etapaInscricaoOk ? 'bg-warning text-dark' : 'bg-light text-dark'); ?>">
                                    <?php echo $etapaPagamentoOk ? 'Ativa' : ($etapaInscricaoOk ? 'Pendente' : 'Bloqueado'); ?>
                                </span>
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>/parceiro/pagamento" <?php echo $etapaInscricaoOk ? '' : 'aria-disabled="true" tabindex="-1"'; ?>>Ver</a>
                            </div>
                        </div>
                    </div>

                    <?php if ($etapaPagamentoOk && $perfil && !empty($perfil['publicado'])): ?>
                        <div class="alert alert-success mt-4 mb-0">
                            Seu perfil está publicado! Acesse em: <strong><?php echo BASE_URL; ?>/parceiro/<?php echo sanitize($perfil['slug']); ?></strong>
                        </div>
                    <?php elseif ($etapaPagamentoOk): ?>
                        <div class="alert alert-info mt-4 mb-0">
                            Pagamento ativo. Seu perfil será publicado automaticamente quando o admin liberar a publicação.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="fw-bold mb-2">Seu status</div>
                    <div class="text-muted small mb-3">Conta: <?php echo sanitize((string)($usuario['email'] ?? '')); ?></div>

                    <div class="d-grid gap-2">
                        <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>/parceiro/perfil">Editar perfil</a>
                        <a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>/parceiro/pagamento">Pagamento</a>
                        <?php if ($etapaPagamentoOk): ?>
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelarAssinatura">
                                <i class="fas fa-times-circle me-1"></i>Cancelar Assinatura
                            </button>
                        <?php endif; ?>
                        <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>/ajuda">Ajuda</a>
                    </div>
                </div>
            </div>

            <?php if (!$inscricao): ?>
                <div class="alert alert-warning mt-3">
                    Você ainda não solicitou parceria. <a href="<?php echo BASE_URL; ?>/parceiros/inscricao">Clique aqui</a>.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($perfil && ($perfil['categoria'] ?? '') === 'clinica'): ?>
        <div class="row mt-2">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-3">Solicitações de triagem direcionadas a você</h2>
                        <p class="text-muted small">Tutores que fizeram triagem de emergência e foram direcionados à sua clínica.</p>

                        <?php if (empty($solicitacoesTriagem)): ?>
                            <div class="alert alert-info mb-0">Nenhuma solicitação de triagem até o momento.</div>
                        <?php else: ?>
                            <?php
                            $urgLabel = ['critica' => 'Crítica', 'alta' => 'Alta', 'moderada' => 'Moderada', 'baixa' => 'Baixa'];
                            $urgBadge = ['critica' => 'bg-danger', 'alta' => 'bg-warning text-dark', 'moderada' => 'bg-info text-dark', 'baixa' => 'bg-secondary'];
                            ?>
                            <div class="list-group">
                                <?php foreach ($solicitacoesTriagem as $s): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <span class="badge <?php echo $urgBadge[$s['nivel_urgencia']] ?? 'bg-secondary'; ?>">
                                                    <?php echo $urgLabel[$s['nivel_urgencia']] ?? $s['nivel_urgencia']; ?>
                                                </span>
                                                <span class="fw-semibold ms-2"><?php echo ucfirst(sanitize($s['especie'])); ?></span>
                                                <?php if (!empty($s['tutor_nome'])): ?>
                                                    · <?php echo sanitize($s['tutor_nome']); ?>
                                                <?php elseif (!empty($s['nome_contato'])): ?>
                                                    · <?php echo sanitize($s['nome_contato']); ?>
                                                <?php endif; ?>
                                                <?php if (!empty($s['telefone_contato'])): ?>
                                                    · <?php echo sanitize($s['telefone_contato']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($s['conversa_id'])): ?>
                                                <a href="<?php echo BASE_URL; ?>/mensagens" class="btn btn-sm btn-outline-primary">Ver conversa</a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-muted mt-1"><?php echo formatDateTimeBR($s['criado_em']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Cancelamento de Assinatura -->
<div class="modal fade" id="modalCancelarAssinatura" tabindex="-1" aria-labelledby="modalCancelarAssinaturaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCancelarAssinaturaLabel">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Cancelar Assinatura
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <strong>Atenção!</strong> Ao cancelar sua assinatura:
                    <ul class="mb-0 mt-2">
                        <li>Seu perfil será despublicado em até 24 horas</li>
                        <li>Você perderá o destaque na listagem</li>
                        <li>Não haverá mais cobranças futuras</li>
                    </ul>
                </div>
                
                <p>Tem certeza que deseja prosseguir com o cancelamento?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </button>
                <a href="<?php echo BASE_URL; ?>/parceiro/cancelar" class="btn btn-danger">
                    <i class="fas fa-times-circle me-1"></i>Sim, Cancelar Assinatura
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
