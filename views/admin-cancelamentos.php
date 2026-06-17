<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$pageTitle = 'Admin - Cancelamentos | Cadê Meu Pet?';

$cancelamentoController = new CancelamentoController();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Falha na validação do formulário. Recarregue a página.', MSG_ERROR);
        redirect('/admin/cancelamentos');
    }

    $acao = (string)($_POST['acao'] ?? '');

    if ($acao === 'exportar_relatorio') {
        $relatorio = $cancelamentoController->listarLogsCancelamento(1000);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="cancelamentos_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Cabeçalho
        fputcsv($output, [
            'ID', 'Usuário', 'Email', 'Tipo', 'Motivo', 'Responsável', 
            'Gateway Response', 'IP Address', 'Data Cancelamento'
        ]);
        
        // Dados
        foreach ($relatorio as $item) {
            fputcsv($output, [
                $item['id'],
                $item['usuario_nome'] ?? 'N/A',
                $item['email'] ?? 'N/A',
                $item['tipo'],
                $item['motivo'] ?? '',
                $item['responsavel'],
                $item['gateway_response'] ?? '',
                $item['ip_address'] ?? '',
                $item['data_cancelamento']
            ]);
        }
        
        fclose($output);
        exit;
    }
}

// Paginação
$pagina = (int)($_GET['pagina'] ?? 1);
$pagina = max(1, $pagina);
$limite = 20;
$offset = ($pagina - 1) * $limite;

$cancelamentos = $cancelamentoController->listarLogsCancelamento($limite, $offset);
$estatisticas = $cancelamentoController->getEstatisticasCancelamento();

$breadcrumbs = [
    ['label' => 'Início',        'url' => BASE_URL],
    ['label' => 'Admin',         'url' => BASE_URL . '/admin'],
    ['label' => 'Cancelamentos'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Cancelamentos</h1>
            <p class="text-muted mb-0">Gerencie e acompanhe os cancelamentos do sistema.</p>
        </div>
        <div>
            <form method="POST" class="d-inline-block">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="acao" value="exportar_relatorio">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fas fa-download me-1"></i>Exportar CSV
                </button>
            </form>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="fas fa-times-circle" style="font-size: 2rem;"></i>
                    </div>
                    <div class="h4 mb-1"><?php echo number_format($estatisticas['total_cancelamentos'] ?? 0, 0, ',', '.'); ?></div>
                    <div class="small text-muted">Total de Cancelamentos</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-warning mb-2">
                        <i class="fas fa-handshake" style="font-size: 2rem;"></i>
                    </div>
                    <div class="h4 mb-1"><?php echo number_format($estatisticas['cancelamentos_assinaturas'] ?? 0, 0, ',', '.'); ?></div>
                    <div class="small text-muted">Assinaturas Canceladas</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-info mb-2">
                        <i class="fas fa-heart" style="font-size: 2rem;"></i>
                    </div>
                    <div class="h4 mb-1"><?php echo number_format($estatisticas['cancelamentos_doacoes'] ?? 0, 0, ',', '.'); ?></div>
                    <div class="small text-muted">Doações Canceladas</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-danger mb-2">
                        <i class="fas fa-calendar-alt" style="font-size: 2rem;"></i>
                    </div>
                    <div class="h4 mb-1"><?php echo number_format($estatisticas['ultimos_30_dias'] ?? 0, 0, ',', '.'); ?></div>
                    <div class="small text-muted">Últimos 30 dias</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Cancelamentos -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Histórico de Cancelamentos</h5>
        </div>
        <div class="card-body">
            <?php if (empty($cancelamentos)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Nenhum cancelamento registrado</h5>
                    <p class="text-muted">Não há registros de cancelamentos no momento.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Usuário</th>
                                <th>Tipo</th>
                                <th>Motivo</th>
                                <th>Responsável</th>
                                <th>Gateway</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cancelamentos as $item): ?>
                                <tr>
                                    <td>
                                        <small><?php echo formatDateBR($item['data_cancelamento'], true); ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($item['usuario_nome'])): ?>
                                            <div>
                                                <strong><?php echo sanitize($item['usuario_nome']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo sanitize($item['email']); ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Usuário não encontrado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($item['tipo'] === 'assinatura_parceiro'): ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-handshake me-1"></i>Assinatura
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-info">
                                                <i class="fas fa-heart me-1"></i>Doação
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-truncate d-block" style="max-width: 200px;" 
                                              title="<?php echo sanitize($item['motivo'] ?? ''); ?>">
                                            <?php echo sanitize(substr($item['motivo'] ?? '', 0, 50)) . (strlen($item['motivo'] ?? '') > 50 ? '...' : ''); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $responsavelClass = [
                                            'usuario' => 'bg-primary',
                                            'admin' => 'bg-danger',
                                            'sistema' => 'bg-secondary'
                                        ];
                                        $responsavelIcon = [
                                            'usuario' => 'fa-user',
                                            'admin' => 'fa-user-shield',
                                            'sistema' => 'fa-robot'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $responsavelClass[$item['responsavel']] ?? 'bg-secondary'; ?>">
                                            <i class="fas <?php echo $responsavelIcon[$item['responsavel']] ?? 'fa-question'; ?> me-1"></i>
                                            <?php echo ucfirst($item['responsavel']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($item['gateway_response'] === 'success'): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Sucesso
                                            </span>
                                        <?php elseif ($item['gateway_response'] === 'manual'): ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-hand me-1"></i>Manual
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-minus me-1"></i>N/A
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code class="small"><?php echo sanitize($item['ip_address'] ?? 'N/A'); ?></code>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <?php
                $totalRegistros = $estatisticas['total_cancelamentos'] ?? 0;
                $totalPaginas = ceil($totalRegistros / $limite);
                
                if ($totalPaginas > 1):
                ?>
                    <nav aria-label="Paginação">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagina > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $inicio = max(1, $pagina - 2);
                            $fim = min($totalPaginas, $pagina + 2);
                            
                            for ($i = $inicio; $i <= $fim; $i++):
                            ?>
                                <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($pagina < $totalPaginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
