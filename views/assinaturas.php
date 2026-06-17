<?php
// views/assinaturas.php - Painel de Gerenciamento de Assinaturas do Doador

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$usuarioId = (int)$_SESSION['user_id'];
$doacaoModel = new Doacao();
$usuarioModel = new Usuario();
$usuario = $usuarioModel->findById($usuarioId);

// Obter todas as assinaturas ativas do usuário
$query = "SELECT * FROM doacoes WHERE usuario_id = ? AND status IN ('pendente', 'aprovada', 'ativa') AND efi_subscription_id IS NOT NULL AND efi_subscription_id != '' ORDER BY criada_em DESC";
$stmt = $GLOBALS['pdo']->prepare($query);
$stmt->execute([$usuarioId]);
$assinaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter histórico de pagamentos de assinaturas
$queryHistorico = "SELECT d.*, CASE WHEN d.status = 'aprovada' THEN 'Pago' WHEN d.status = 'pendente' THEN 'Aguardando' WHEN d.status = 'recusado' THEN 'Recusado' ELSE d.status END as status_label 
                    FROM doacoes d 
                    WHERE d.usuario_id = ? AND d.efi_subscription_id IS NOT NULL AND d.efi_subscription_id != '' 
                    ORDER BY d.ultimo_pagamento_em DESC LIMIT 20";
$stmtHistorico = $GLOBALS['pdo']->prepare($queryHistorico);
$stmtHistorico->execute([$usuarioId]);
$historico = $stmtHistorico->fetchAll(PDO::FETCH_ASSOC);

// Calcular estatísticas
$totalAssinado = 0;
$totalMensal = 0;
foreach ($assinaturas as $assinatura) {
    if (in_array($assinatura['status'], ['ativa', 'aprovada'])) {
        $valor = (float)$assinatura['valor'];
        $totalAssinado += $valor;
        $totalMensal += $valor;
    }
}

// Processar ações (cancelar, pausar, reativar)
$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $assinaturaId = (int)($_POST['assinatura_id'] ?? 0);
    
    if ($acao && $assinaturaId) {
        $assinatura = $doacaoModel->findById($assinaturaId);
        
        // Verificar se a assinatura pertence ao usuário
        if (!empty($assinatura) && (int)$assinatura['usuario_id'] === $usuarioId) {
            if ($acao === 'cancelar') {
                // Cancelar assinatura
                $update = ['status' => 'cancelada', 'cancelada_em' => date('Y-m-d H:i:s')];
                $doacaoModel->update($assinaturaId, $update);
                $mensagem = '<i class="fa-solid fa-circle-check text-success"></i> Assinatura cancelada com sucesso!';
                $tipoMensagem = 'success';
                // Log
                error_log("[assinaturas] Usuário $usuarioId cancelou assinatura $assinaturaId");
            } elseif ($acao === 'pausar' && $assinatura['status'] === 'ativa') {
                $update = ['status' => 'pausada', 'pausada_em' => date('Y-m-d H:i:s')];
                $doacaoModel->update($assinaturaId, $update);
                $mensagem = '<i class="fa-solid fa-circle-check text-success"></i> Assinatura pausada com sucesso!';
                $tipoMensagem = 'success';
                error_log("[assinaturas] Usuário $usuarioId pausou assinatura $assinaturaId");
            } elseif ($acao === 'reativar' && $assinatura['status'] === 'pausada') {
                $update = ['status' => 'ativa', 'pausada_em' => NULL];
                $doacaoModel->update($assinaturaId, $update);
                $mensagem = '<i class="fa-solid fa-circle-check text-success"></i> Assinatura reativada com sucesso!';
                $tipoMensagem = 'success';
                error_log("[assinaturas] Usuário $usuarioId reativou assinatura $assinaturaId");
            }
            
            // Atualizar exibição
            header('Location: /assinaturas.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Assinaturas - Cadê Meu Pet?</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .assinaturas-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .assinatura-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .assinatura-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 15px;
        }
        
        .assinatura-titulo {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-ativa {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pausada {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-cancelada {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-pendente {
            background: #cce5ff;
            color: #004085;
        }
        
        .assinatura-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .info-item {
            font-size: 14px;
        }
        
        .info-label {
            color: #666;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #333;
            font-size: 16px;
            font-weight: 500;
        }
        
        .valor-grande {
            color: #28a745;
            font-size: 20px;
            font-weight: bold;
        }
        
        .assinatura-acoes {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-acao {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-pausar {
            background: #ffc107;
            color: white;
        }
        
        .btn-pausar:hover {
            background: #e0a800;
        }
        
        .btn-reativar {
            background: #17a2b8;
            color: white;
        }
        
        .btn-reativar:hover {
            background: #138496;
        }
        
        .btn-cancelar {
            background: #dc3545;
            color: white;
        }
        
        .btn-cancelar:hover {
            background: #c82333;
        }
        
        .historico-container {
            margin-top: 40px;
        }
        
        .historico-titulo {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }
        
        .historico-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .historico-table thead {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        .historico-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }
        
        .historico-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .historico-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .mensagem {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .mensagem.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .mensagem.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .statsbox {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state img {
            max-width: 100px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state p {
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .btn-primary {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    
    <div class="assinaturas-container">
        <h1 style="margin-bottom: 30px; color: #333;">Minhas Assinaturas e Doações Recorrentes</h1>
        
        <?php if (!empty($mensagem)) { ?>
            <div class="mensagem <?php echo $tipoMensagem; ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php } ?>
        
        <!-- Estatísticas -->
        <div class="statsbox">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="stat-label">Total Assinado</div>
                <div class="stat-value">R$ <?php echo number_format($totalAssinado, 2, ',', '.'); ?></div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="stat-label">Assinaturas Ativas</div>
                <div class="stat-value"><?php echo count(array_filter($assinaturas, fn($a) => $a['status'] === 'ativa')); ?></div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="stat-label">Compromisso Mensal</div>
                <div class="stat-value">R$ <?php echo number_format($totalMensal, 2, ',', '.'); ?></div>
            </div>
        </div>
        
        <!-- Assinaturas Ativas -->
        <h2 style="margin-top: 30px; margin-bottom: 20px; color: #333;">Suas Assinaturas</h2>
        
        <?php if (empty($assinaturas)) { ?>
            <div class="empty-state">
                <p style="font-size: 18px; margin-bottom: 20px;">Você não tem assinaturas ativas no momento.</p>
                <a href="/novo-anuncio.php" class="btn-primary">Criar uma Nova Doação</a>
            </div>
        <?php } else { ?>
            <?php foreach ($assinaturas as $assinatura) { ?>
                <div class="assinatura-card">
                    <div class="assinatura-header">
                        <div>
                            <div class="assinatura-titulo">
                                <?php 
                                    $titulo = $assinatura['titulo'] ?? 'Doação Anônima';
                                    echo htmlspecialchars(strlen($titulo) > 50 ? substr($titulo, 0, 50) . '...' : $titulo);
                                ?>
                            </div>
                        </div>
                        <span class="status-badge status-<?php echo $assinatura['status']; ?>">
                            <?php 
                                $statusLabel = [
                                    'ativa' => 'Ativa',
                                    'pausada' => 'Pausada',
                                    'cancelada' => 'Cancelada',
                                    'pendente' => 'Pendente',
                                    'aprovada' => 'Aprovada',
                                    'recusado' => 'Recusado'
                                ];
                                echo $statusLabel[$assinatura['status']] ?? $assinatura['status'];
                            ?>
                        </span>
                    </div>
                    
                    <div class="assinatura-info">
                        <div class="info-item">
                            <div class="info-label">Valor da Doação</div>
                            <div class="info-value valor-grande">
                                R$ <?php echo number_format((float)$assinatura['valor'], 2, ',', '.'); ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Método de Pagamento</div>
                            <div class="info-value">
                                <?php 
                                    $metodo = $assinatura['metodo_pagamento'] ?? 'Não especificado';
                                    $metodoLabel = [
                                        'pix' => '<i class="fa-solid fa-credit-card"></i> PIX',
                                        'cartao' => '<i class="fa-solid fa-credit-card"></i> Cartão',
                                        'gateway' => '<i class="fa-solid fa-credit-card"></i> Cartão Recorrente',
                                        'boleto' => '<i class="fa-solid fa-file"></i> Boleto'
                                    ];
                                    echo $metodoLabel[$metodo] ?? ucfirst($metodo);
                                ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Próxima Cobrança</div>
                            <div class="info-value">
                                <?php 
                                    if (!empty($assinatura['proxima_cobranca'])) {
                                        $data = new DateTime($assinatura['proxima_cobranca']);
                                        echo $data->format('d/m/Y');
                                    } else {
                                        echo 'A definir';
                                    }
                                ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Último Pagamento</div>
                            <div class="info-value">
                                <?php 
                                    if (!empty($assinatura['ultimo_pagamento_em'])) {
                                        $data = new DateTime($assinatura['ultimo_pagamento_em']);
                                        echo $data->format('d/m/Y H:i');
                                    } else {
                                        echo 'Pendente';
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="assinatura-acoes">
                        <?php if ($assinatura['status'] === 'ativa') { ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="acao" value="pausar">
                                <input type="hidden" name="assinatura_id" value="<?php echo $assinatura['id']; ?>">
                                <button type="submit" class="btn-acao btn-pausar" onclick="return confirm('Tem certeza que deseja pausar esta assinatura?');">
                                    <i class="fa-solid fa-pause"></i> Pausar Assinatura
                                </button>
                            </form>
                        <?php } ?>
                        
                        <?php if ($assinatura['status'] === 'pausada') { ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="acao" value="reativar">
                                <input type="hidden" name="assinatura_id" value="<?php echo $assinatura['id']; ?>">
                                <button type="submit" class="btn-acao btn-reativar">
                                    <i class="fa-solid fa-circle-check text-success"></i> Reativar Assinatura
                                </button>
                            </form>
                        <?php } ?>
                        
                        <?php if ($assinatura['status'] !== 'cancelada') { ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="acao" value="cancelar">
                                <input type="hidden" name="assinatura_id" value="<?php echo $assinatura['id']; ?>">
                                <button type="submit" class="btn-acao btn-cancelar" onclick="return confirm('Tem certeza que deseja cancelar esta assinatura? Esta ação não pode ser desfeita.');"><strong><i class="fa-solid fa-circle-xmark text-danger"></i> Cancelar</strong></button>
                            </form>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
        
        <!-- Histórico de Pagamentos -->
        <div class="historico-container">
            <h2 class="historico-titulo">Histórico de Pagamentos</h2>
            
            <?php if (empty($historico)) { ?>
                <div style="padding: 20px; text-align: center; color: #666;">
                    Nenhum histórico de pagamento disponível.
                </div>
            <?php } else { ?>
                <table class="historico-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $item) { ?>
                            <tr>
                                <td><?php echo (new DateTime($item['ultimo_pagamento_em'] ?? $item['criada_em']))->format('d/m/Y H:i'); ?></td>
                                <td><?php echo htmlspecialchars(substr($item['titulo'] ?? 'Doação', 0, 40)); ?></td>
                                <td><strong>R$ <?php echo number_format((float)$item['valor'], 2, ',', '.'); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $item['status']; ?>">
                                        <?php 
                                            $statusLabel = [
                                                'ativa' => 'Ativa',
                                                'aprovada' => 'Pago',
                                                'pendente' => 'Pendente',
                                                'cancelada' => 'Cancelado',
                                                'recusado' => 'Recusado'
                                            ];
                                            echo $statusLabel[$item['status']] ?? $item['status'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/doacao.php?id=<?php echo $item['id']; ?>" style="color: #007bff; text-decoration: none; font-weight: 600;">Ver Detalhes →</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>
    </div>
    
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>
