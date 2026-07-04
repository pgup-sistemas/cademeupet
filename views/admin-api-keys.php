<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$pageTitle = 'Admin - API Pública | Cadê Meu Pet?';
$db = getDB();
$apiKeyModel = new ApiKey();

$novaChaveGerada = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Falha na validação do formulário. Recarregue a página.', MSG_ERROR);
        redirect('/admin/api-keys');
    }

    $acao = $_POST['action'] ?? '';

    if ($acao === 'criar_consumidor_e_key') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email_contato'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $escopos = (array)($_POST['escopos'] ?? []);
        $rateLimite = max(1, (int)($_POST['rate_limit_por_minuto'] ?? 60));

        if ($nome === '') {
            setFlashMessage('Informe o nome do consumidor.', MSG_ERROR);
            redirect('/admin/api-keys');
        }

        $consumidorId = $db->insert('api_consumidores', [
            'nome' => $nome,
            'email_contato' => $email ?: null,
            'descricao' => $descricao ?: null,
            'ativo' => 1,
        ]);

        $novaChave = $apiKeyModel->gerar((int)$consumidorId, $escopos, $rateLimite);
        $novaChaveGerada = $novaChave;
        auditLog('criar_api_key', 'api_keys', $novaChave['id'], null, ['consumidor' => $nome]);
        setFlashMessage('Consumidor e API key criados com sucesso. Copie a chave agora — ela não será mostrada novamente.', MSG_SUCCESS);
    } elseif ($acao === 'revogar') {
        $keyId = (int)($_POST['key_id'] ?? 0);
        if ($keyId > 0 && $apiKeyModel->revogar($keyId)) {
            auditLog('revogar_api_key', 'api_keys', $keyId);
            setFlashMessage('API key revogada.', MSG_SUCCESS);
        } else {
            setFlashMessage('Não foi possível revogar esta key.', MSG_ERROR);
        }
        redirect('/admin/api-keys');
    }
}

$keys = $apiKeyModel->listarTodas();

$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL],
    ['label' => 'Admin',  'url' => BASE_URL . '/admin'],
    ['label' => 'API Pública'],
];
$suppressBreadcrumbBar = true;
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

    <main class="admin-content p-4">
        <h1 class="h3 fw-bold mb-4">API Pública — Consumidores e Chaves</h1>
        <p class="text-muted">Acesso liberado apenas por aprovação manual — não há self-service. Gere uma chave por consumidor externo aprovado.</p>

        <?php if ($novaChaveGerada): ?>
            <div class="alert alert-warning">
                <strong>Copie a chave agora, ela não será exibida novamente:</strong>
                <code class="d-block mt-2 p-2 bg-light border rounded"><?php echo sanitize($novaChaveGerada['chave_plaintext']); ?></code>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Novo consumidor + API key</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="criar_consumidor_e_key">

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Nome do consumidor</label>
                            <input type="text" class="form-control" name="nome" required placeholder="Ex: Prefeitura de Porto Velho - CCZ">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">E-mail de contato</label>
                            <input type="email" class="form-control" name="email_contato">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Limite de requisições/minuto</label>
                            <input type="number" class="form-control" name="rate_limit_por_minuto" value="60" min="1">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descrição</label>
                        <input type="text" class="form-control" name="descricao" placeholder="Contexto do uso desta integração">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block">Escopos</label>
                        <?php foreach (ApiKey::ESCOPOS_VALIDOS as $escopo): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="escopos[]" value="<?php echo $escopo; ?>" id="esc_<?php echo $escopo; ?>" <?php echo in_array($escopo, ['anuncios_leitura', 'parceiros_leitura'], true) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="esc_<?php echo $escopo; ?>"><?php echo $escopo; ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">Gerar API key</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Chaves existentes</h2>
                <?php if (empty($keys)): ?>
                    <div class="alert alert-info mb-0">Nenhuma API key criada até o momento.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Consumidor</th>
                                    <th>Prefixo</th>
                                    <th>Escopos</th>
                                    <th>Limite/min</th>
                                    <th>Status</th>
                                    <th>Último uso</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($keys as $k): ?>
                                    <tr>
                                        <td><?php echo sanitize($k['consumidor_nome']); ?></td>
                                        <td><code><?php echo sanitize($k['prefixo']); ?>…</code></td>
                                        <td><small><?php echo sanitize($k['escopos']); ?></small></td>
                                        <td><?php echo (int)$k['rate_limit_por_minuto']; ?></td>
                                        <td>
                                            <?php if ($k['ativo']): ?>
                                                <span class="badge bg-success">Ativa</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Revogada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $k['ultimo_uso_em'] ? formatDateTimeBR($k['ultimo_uso_em']) : 'Nunca'; ?></td>
                                        <td>
                                            <?php if ($k['ativo']): ?>
                                                <form method="POST" onsubmit="return confirm('Revogar esta API key?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="action" value="revogar">
                                                    <input type="hidden" name="key_id" value="<?php echo (int)$k['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Revogar</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
