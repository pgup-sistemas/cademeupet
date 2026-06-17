<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Transparência - Cadê Meu Pet?';

$doacaoController = new DoacaoController();
$doacaoModel = new Doacao();

$metaAtual = $doacaoController->metaAtual();

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max(1, $pagina);
$limite = 20;
$offset = ($pagina - 1) * $limite;

$totalAprovadas = $doacaoModel->countApprovedDonations();
$totalArrecadado = $doacaoModel->sumApprovedDonations();

$totalPublicas = $doacaoModel->countApprovedDonationsPublic();
$doacoesPublicas = $doacaoModel->getApprovedDonationsPublic($limite, $offset);
$totalPaginas = (int)ceil($totalPublicas / $limite);

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">📊 Transparência</h1>
                    <p class="text-muted mb-0">Relatório público de custos, metas e andamento do Cadê Meu Pet?.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/doar/" class="btn btn-success">
                    💚 Fazer uma doação
                </a>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h2 class="h5 fw-bold mb-3">✅ O que já temos</h2>
                    <ul class="mb-0">
                        <li>Plataforma web funcionando (cadastro, login, anúncios, busca, favoritos)</li>
                        <li>Envio de e-mails via SMTP (PHPMailer)</li>
                        <li>Upload de fotos e exibição de anúncios</li>
                        <li>Integração de pagamento via Pix (Efí Bank)</li>
                        <li>Webhook para confirmação de pagamentos</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h2 class="h5 fw-bold mb-3">🗺️ Próximos passos</h2>
                    <ul class="mb-0">
                        <li>Melhorar a busca por proximidade com geolocalização/mapas</li>
                        <li>Implementar pagamento via cartão de crédito</li>
                        <li>Relatório mensal detalhado (custos e entradas) com histórico</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h2 class="h5 fw-bold mb-3">📌 Como você pode ajudar</h2>
                    <p class="mb-0">
                        Você pode contribuir com melhorias, sugestões ou apoiando financeiramente. 
                        Toda ajuda mantém o Cadê Meu Pet? disponível e gratuito para mais pessoas.
                    </p>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="text-muted small">Total arrecadado (aprovadas)</div>
                            <div class="h4 fw-bold mb-0"><?php echo formatMoney((float)$totalArrecadado); ?></div>
                            <div class="text-muted small mt-1"><?php echo (int)$totalAprovadas; ?> doações confirmadas</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <div>
                                    <div class="text-muted small">Meta mensal</div>
                                    <div class="h5 fw-bold mb-0"><?php echo sanitize($metaAtual['descricao'] ?? 'Meta do mês'); ?></div>
                                </div>
                                <div class="text-end">
                                    <?php
                                        $valorMeta = (float)($metaAtual['valor_meta'] ?? 0);
                                        $valorArrecadadoMeta = (float)($metaAtual['valor_arrecadado'] ?? 0);
                                        $percentual = $valorMeta > 0 ? min(100, round(($valorArrecadadoMeta / $valorMeta) * 100)) : 0;
                                    ?>
                                    <div class="fw-semibold"><?php echo formatMoney($valorArrecadadoMeta); ?> / <?php echo formatMoney($valorMeta); ?></div>
                                    <div class="text-muted small"><?php echo $percentual; ?>%</div>
                                </div>
                            </div>
                            <div class="progress mt-3" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentual; ?>%" aria-valuenow="<?php echo $percentual; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="row g-2 mt-3 small text-muted">
                                <div class="col-6 col-md-3">Servidor: <?php echo formatMoney((float)($metaAtual['custos_servidor'] ?? 0)); ?></div>
                                <div class="col-6 col-md-3">Manutenção: <?php echo formatMoney((float)($metaAtual['custos_manutencao'] ?? 0)); ?></div>
                                <div class="col-6 col-md-3">Outros: <?php echo formatMoney((float)($metaAtual['custos_outros'] ?? 0)); ?></div>
                                <div class="col-6 col-md-3">Mês: <?php echo !empty($metaAtual['mes_referencia']) ? date('m/Y', strtotime($metaAtual['mes_referencia'])) : date('m/Y'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h2 class="h5 fw-bold mb-3">💚 Doações recentes (públicas)</h2>

                    <?php if (empty($doacoesPublicas)): ?>
                        <p class="text-muted mb-0">Ainda não há doações públicas para exibir.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($doacoesPublicas as $doacao): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                        <div>
                                            <div class="fw-bold"><?php echo sanitize($doacao['nome_doador'] ?? 'Apoiador anônimo'); ?></div>
                                            <?php if (!empty($doacao['mensagem'])): ?>
                                                <div class="text-muted small"><?php echo sanitize($doacao['mensagem']); ?></div>
                                            <?php endif; ?>
                                            <div class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($doacao['data_doacao'])); ?></div>
                                        </div>
                                        <div class="fw-semibold"><?php echo formatMoney((float)($doacao['valor'] ?? 0)); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalPaginas > 1): ?>
                            <nav class="mt-4" aria-label="Paginação">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php
                                        $prev = max(1, $pagina - 1);
                                        $next = min($totalPaginas, $pagina + 1);
                                    ?>
                                    <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo BASE_URL . '/transparencia?pagina=' . $prev; ?>">Anterior</a>
                                    </li>
                                    <li class="page-item disabled"><span class="page-link"><?php echo $pagina; ?> / <?php echo $totalPaginas; ?></span></li>
                                    <li class="page-item <?php echo $pagina >= $totalPaginas ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo BASE_URL . '/transparencia?pagina=' . $next; ?>">Próxima</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
