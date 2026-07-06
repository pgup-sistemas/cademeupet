<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Documento Veterinário | Cadê Meu Pet?';
$usuarioId = (int)getUserId();
$controller = new LaudoController();
$errors = [];

$laudoId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Falha na validação do formulário. Atualize a página e tente novamente.';
    } else {
        $acao = $_POST['action'] ?? '';

        if ($acao === 'assinar') {
            $resultado = $controller->assinar(
                $laudoId,
                $usuarioId,
                $_POST['senha'] ?? '',
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );
            if (!empty($resultado['success'])) {
                setFlashMessage('Documento assinado com sucesso.', MSG_SUCCESS);
            } else {
                $errors[] = $resultado['error'] ?? 'Não foi possível assinar.';
            }
        } elseif ($acao === 'retificar') {
            $resultado = $controller->retificar($laudoId, $usuarioId, $_POST['novo_conteudo'] ?? '');
            if (!empty($resultado['success'])) {
                setFlashMessage('Retificação criada. Assine o novo documento para torná-lo definitivo.', MSG_SUCCESS);
                redirect('/laudo?id=' . $resultado['id']);
            } else {
                $errors = $resultado['errors'] ?? ['Não foi possível retificar.'];
            }
        }
    }
}

$laudo = $controller->buscarPorId($laudoId);
if (!$laudo) {
    setFlashMessage('Documento não encontrado.', MSG_ERROR);
    redirect('/parceiro/atendimentos');
}

$souOAutor = (int)$laudo['criado_por_usuario_id'] === $usuarioId;
$retificacoes = (new Documento())->buscarRetificacoes((int)$laudo['documento_id']);

$tipoLabel = ['laudo' => 'Laudo', 'atestado' => 'Atestado', 'receituario' => 'Receituário', 'pedido_exame' => 'Pedido de Exames'];

$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL],
    ['label' => $tipoLabel[$laudo['tipo']] ?? 'Documento'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <h1 class="h4 fw-bold mb-0"><?php echo $tipoLabel[$laudo['tipo']] ?? ucfirst($laudo['tipo']); ?></h1>
        <span class="badge bg-<?php echo $laudo['documento_status'] === 'assinado' ? 'success' : ($laudo['documento_status'] === 'revogado' ? 'secondary' : 'warning text-dark'); ?>">
            <?php echo ucfirst(str_replace('_', ' ', $laudo['documento_status'])); ?>
        </span>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?php echo sanitize($e); ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <?php if (!empty($retificacoes)): ?>
        <div class="alert alert-warning">
            Este documento foi retificado. Veja a versão mais recente:
            <?php foreach ($retificacoes as $r): ?>
                <a href="<?php echo BASE_URL; ?>/laudo?id=<?php
                    // O id de laudo é diferente do id de documento — buscamos pelo vínculo.
                    $lRetif = getDB()->fetchOne('SELECT id FROM laudos WHERE documento_id = ?', [$r['id']]);
                    echo (int)($lRetif['id'] ?? 0);
                ?>"><?php echo sanitize($r['codigo_verificacao']); ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-4">
            <div class="border rounded p-3 mb-3">
                <?php echo $laudo['conteudo_html']; ?>
            </div>

            <?php if ($laudo['documento_status'] === 'assinado'): ?>
                <?php
                    $urlVerificacao = rtrim((string)BASE_URL, '/') . '/verificar?codigo=' . urlencode($laudo['codigo_verificacao']);
                    $textoWhats = rawurlencode('Documento veterinário (' . ($tipoLabel[$laudo['tipo']] ?? $laudo['tipo']) . ') — confira a autenticidade: ' . $urlVerificacao);
                ?>
                <div class="alert alert-success">
                    Código de verificação: <strong><?php echo sanitize($laudo['codigo_verificacao']); ?></strong>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <?php if (!empty($laudo['pdf_path'])): ?>
                            <a href="<?php echo BASE_URL; ?>/uploads/documentos/<?php echo sanitize($laudo['pdf_path']); ?>" target="_blank" class="btn btn-sm btn-outline-success">Baixar PDF</a>
                        <?php endif; ?>
                        <a href="<?php echo $urlVerificacao; ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Página de verificação</a>
                        <a href="https://wa.me/?text=<?php echo $textoWhats; ?>" target="_blank" class="btn btn-sm btn-success">
                            <i class="fa-brands fa-whatsapp me-1"></i>Compartilhar no WhatsApp
                        </a>
                    </div>
                </div>

                <?php if ($souOAutor && empty($retificacoes)): ?>
                    <details class="mt-3">
                        <summary class="text-danger" style="cursor:pointer;">Encontrei um erro — retificar este documento</summary>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="retificar">
                            <p class="text-muted small">O documento original permanece intacto e auditável — a retificação cria um novo documento vinculado a ele.</p>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Novo conteúdo (corrigido)</label>
                                <textarea class="form-control" name="novo_conteudo" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-danger">Criar retificação</button>
                        </form>
                    </details>
                <?php endif; ?>

            <?php elseif ($souOAutor && $laudo['documento_status'] !== 'revogado'): ?>
                <form method="POST" class="border rounded p-3">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="assinar">
                    <p class="mb-2"><strong>Assinar este documento é um ato deliberado.</strong> Digite sua senha para confirmar — a assinatura é eletrônica e auditável (hash + IP + data/hora), sem validade de assinatura digital certificada (ICP-Brasil).</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sua senha</label>
                        <input type="password" class="form-control" name="senha" required>
                    </div>
                    <button type="submit" class="btn btn-success">Assinar documento</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
