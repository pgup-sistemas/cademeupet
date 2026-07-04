<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Matches Sugeridos | Cadê Meu Pet?';
$usuarioId = (int)getUserId();
$controller = new MatchController();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Falha na validação do formulário. Atualize a página e tente novamente.';
    } else {
        $acao = $_POST['action'] ?? '';
        $matchId = (int)($_POST['match_id'] ?? 0);

        if ($acao === 'confirmar') {
            $resultado = $controller->confirmar($matchId, $usuarioId);
            if (!empty($resultado['success'])) {
                setFlashMessage('Match confirmado! Uma conversa foi aberta para vocês combinarem os detalhes.', MSG_SUCCESS);
                redirect('/mensagens');
            } else {
                $errors[] = $resultado['error'] ?? 'Não foi possível confirmar este match.';
            }
        } elseif ($acao === 'rejeitar') {
            $resultado = $controller->rejeitar($matchId, $usuarioId);
            if (!empty($resultado['success'])) {
                setFlashMessage('Match descartado.', MSG_SUCCESS);
                redirect('/matches');
            } else {
                $errors[] = $resultado['error'] ?? 'Não foi possível descartar este match.';
            }
        } else {
            $errors[] = 'Ação inválida.';
        }
    }
}

$matches = $controller->listarParaUsuario($usuarioId);

$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL],
    ['label' => 'Matches Sugeridos'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <h1 class="h3 fw-bold mb-1">Matches sugeridos</h1>
    <p class="text-muted mb-4">
        Nosso sistema cruza anúncios de "perdido" e "achado" por atributos, localização, período e foto.
        Nenhum dado de contato é compartilhado até você confirmar.
    </p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $erro): ?>
                    <li><?php echo sanitize($erro); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (empty($matches)): ?>
        <div class="alert alert-info">Nenhum match sugerido no momento.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($matches as $m): ?>
                <?php
                    $ehDonoPerdido = (int)$m['perdido_usuario_id'] === $usuarioId;
                    $meuLado  = $ehDonoPerdido ? 'perdido' : 'achado';
                    $outroNome = $ehDonoPerdido ? ($m['achado_nome'] ?: 'Pet encontrado') : ($m['perdido_nome'] ?: 'Pet perdido');
                    $outraFoto = $ehDonoPerdido ? $m['achado_foto'] : $m['perdido_foto'];
                ?>
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-primary"><?php echo (int)$m['score_total']; ?>% de compatibilidade</span>
                                <span class="badge bg-light text-dark"><?php echo ucfirst($m['status']); ?></span>
                            </div>

                            <?php if ($outraFoto): ?>
                                <img src="<?php echo BASE_URL; ?>/uploads/anuncios/<?php echo sanitize($outraFoto); ?>" class="img-fluid rounded mb-2" style="max-height:180px;object-fit:cover;width:100%;" alt="">
                            <?php endif; ?>

                            <p class="mb-2">
                                Possível correspondência com o anúncio
                                "<strong><?php echo sanitize($outroNome); ?></strong>"
                                (<?php echo $ehDonoPerdido ? 'achado' : 'perdido'; ?>).
                            </p>

                            <ul class="small text-muted mb-3">
                                <?php if ($m['distancia_km'] !== null): ?><li>Distância: <?php echo $m['distancia_km']; ?> km</li><?php endif; ?>
                                <?php if ($m['dias_diferenca'] !== null): ?><li>Diferença de datas: <?php echo (int)$m['dias_diferenca']; ?> dia(s)</li><?php endif; ?>
                                <?php if ($m['score_visual'] !== null): ?><li>Similaridade visual: <?php echo (int)$m['score_visual']; ?>%</li><?php endif; ?>
                            </ul>

                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="confirmar">
                                <input type="hidden" name="match_id" value="<?php echo (int)$m['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-success">É o meu pet — abrir conversa</button>
                            </form>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Descartar este match?');">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="rejeitar">
                                <input type="hidden" name="match_id" value="<?php echo (int)$m['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Não é o meu pet</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
