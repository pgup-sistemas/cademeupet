<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Termo de Adoção | Cadê Meu Pet?';
$usuarioId = (int)getUserId();
$controller = new TermoAdocaoController();
$errors = [];

$termoId = !empty($_GET['id']) ? (int)$_GET['id'] : 0;
$anuncioIdIniciar = !empty($_GET['anuncio_id']) ? (int)$_GET['anuncio_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Falha na validação do formulário. Atualize a página e tente novamente.';
    } else {
        $acao = $_POST['action'] ?? '';

        if ($acao === 'iniciar') {
            $resultado = $controller->iniciar((int)$_POST['anuncio_id'], $usuarioId, [
                'nome' => $_POST['adotante_nome'] ?? '',
                'telefone' => $_POST['adotante_telefone'] ?? '',
            ]);
            if (!empty($resultado['success'])) {
                setFlashMessage('Termo de adoção iniciado! O adotante foi notificado (se tiver conta) para assinar.', MSG_SUCCESS);
                redirect('/termo-adocao?id=' . $resultado['id']);
            } else {
                $errors[] = $resultado['error'] ?? 'Não foi possível iniciar o termo.';
                $anuncioIdIniciar = (int)$_POST['anuncio_id'];
            }
        } elseif ($acao === 'assinar') {
            $resultado = $controller->assinarComoAdotante(
                (int)$_POST['termo_id'],
                $usuarioId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );
            if (!empty($resultado['success'])) {
                setFlashMessage('Termo assinado com sucesso! Você é responsável pelo bem-estar do pet a partir de agora.', MSG_SUCCESS);
            } else {
                $errors[] = $resultado['error'] ?? 'Não foi possível assinar o termo.';
            }
            $termoId = (int)$_POST['termo_id'];
        } elseif ($acao === 'recusar') {
            $resultado = $controller->recusar((int)$_POST['termo_id'], $usuarioId);
            if (!empty($resultado['success'])) {
                setFlashMessage('Você recusou este termo.', MSG_SUCCESS);
                redirect('/termos-adocao');
            } else {
                $errors[] = $resultado['error'] ?? 'Não foi possível recusar.';
            }
            $termoId = (int)$_POST['termo_id'];
        }
    }
}

$termo = $termoId ? $controller->buscarPorId($termoId) : null;

$anuncioParaIniciar = null;
if (!$termo && $anuncioIdIniciar) {
    $anuncioModel = new Anuncio();
    $anuncioParaIniciar = $anuncioModel->findByIdAnyStatus($anuncioIdIniciar);
    if (!$anuncioParaIniciar || (int)$anuncioParaIniciar['usuario_id'] !== $usuarioId
        || $anuncioParaIniciar['tipo'] !== 'doacao' || $anuncioParaIniciar['status'] !== 'resolvido') {
        $anuncioParaIniciar = null;
        $errors[] = 'Este anúncio não está disponível para gerar um termo de adoção.';
    }
}

$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL],
    ['label' => 'Termo de Adoção'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $erro): ?>
                    <li><?php echo sanitize($erro); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($termo): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h1 class="h4 fw-bold mb-0">Termo de Responsabilidade de Adoção</h1>
                    <span class="badge bg-<?php echo $termo['status'] === 'assinado' ? 'success' : ($termo['status'] === 'recusado' || $termo['status'] === 'expirado' ? 'secondary' : 'warning text-dark'); ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $termo['status'])); ?>
                    </span>
                </div>

                <div class="border rounded p-3 mb-3">
                    <?php echo $termo['conteudo_html']; ?>
                </div>

                <?php if ($termo['status'] === 'assinado'): ?>
                    <div class="alert alert-success">
                        Termo assinado. Código de verificação: <strong><?php echo sanitize($termo['codigo_verificacao']); ?></strong>
                        <?php if (!empty($termo['pdf_path'])): ?>
                            <br><a href="<?php echo BASE_URL; ?>/uploads/documentos/<?php echo sanitize($termo['pdf_path']); ?>" target="_blank" class="btn btn-sm btn-outline-success mt-2">Baixar PDF</a>
                        <?php endif; ?>
                    </div>
                <?php elseif ($termo['status'] === 'aguardando_adotante' && (int)($termo['adotante_usuario_id'] ?? 0) === $usuarioId): ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="assinar">
                        <input type="hidden" name="termo_id" value="<?php echo (int)$termo['id']; ?>">
                        <button type="submit" class="btn btn-success">Assinar e me responsabilizar</button>
                    </form>
                    <form method="POST" class="d-inline ms-2" onsubmit="return confirm('Tem certeza que deseja recusar este termo?');">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="recusar">
                        <input type="hidden" name="termo_id" value="<?php echo (int)$termo['id']; ?>">
                        <button type="submit" class="btn btn-outline-danger">Recusar</button>
                    </form>
                <?php elseif ($termo['status'] === 'aguardando_adotante' && empty($termo['adotante_usuario_id'])): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="assinar">
                        <input type="hidden" name="termo_id" value="<?php echo (int)$termo['id']; ?>">
                        <p class="text-muted small">Este termo foi endereçado ao telefone <?php echo sanitize($termo['adotante_telefone_informado'] ?? ''); ?>. Se este é o seu número cadastrado, você pode assinar.</p>
                        <button type="submit" class="btn btn-success">Assinar e me responsabilizar</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($anuncioParaIniciar): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h1 class="h4 fw-bold mb-1">Iniciar Termo de Adoção</h1>
                <p class="text-muted mb-4">Pet: <strong><?php echo sanitize($anuncioParaIniciar['nome_pet'] ?: ucfirst($anuncioParaIniciar['especie'])); ?></strong></p>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="iniciar">
                    <input type="hidden" name="anuncio_id" value="<?php echo (int)$anuncioParaIniciar['id']; ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nome de quem adotou</label>
                        <input type="text" class="form-control" name="adotante_nome" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Telefone de quem adotou</label>
                        <input type="text" class="form-control" name="adotante_telefone" required>
                        <small class="text-muted">Se essa pessoa já tem conta com esse telefone, o termo já entra vinculado à conta dela.</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Gerar termo e notificar adotante</button>
                </form>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-info">Nenhum termo para exibir. Volte em <a href="<?php echo BASE_URL; ?>/meus-anuncios">Meus Anúncios</a> para iniciar um termo de adoção a partir de um anúncio de doação resolvido.</div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
