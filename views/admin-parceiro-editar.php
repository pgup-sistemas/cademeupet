<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$pageTitle = 'Admin — Editar Parceiro | Cadê Meu Pet?';

$inscricaoId    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$inscricaoModel = new ParceiroInscricao();
$perfilModel    = new ParceiroPerfil();
$usuarioModel   = new Usuario();
$adminId        = (int)(getUserId() ?? 0);

$inscricao = $inscricaoId > 0 ? $inscricaoModel->findById($inscricaoId) : null;
if (!$inscricao) {
    setFlashMessage('Inscrição não encontrada.', MSG_ERROR);
    redirect('/admin/parceiros?tab=inscricoes');
}

$usuarioId = (int)$inscricao['usuario_id'];
$usuario   = $usuarioModel->findById($usuarioId);
$perfil    = $perfilModel->findByUserId($usuarioId);

$categorias = ['petshop', 'clinica', 'hotel', 'adestrador', 'outro'];
$estados    = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG',
               'PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Falha na validação. Recarregue e tente novamente.', MSG_ERROR);
        redirect("/admin/parceiro-editar?id={$inscricaoId}");
    }

    $secao = (string)($_POST['secao'] ?? '');

    // ── Salvar dados do usuário ──────────────────────────────────────────────
    if ($secao === 'usuario') {
        $nome     = trim($_POST['nome'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $telefone = preg_replace('/\D/', '', (string)($_POST['telefone'] ?? ''));

        if ($nome === '')                             $erros[] = 'Nome é obrigatório.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';

        if (!$erros) {
            $dadosUsuario = ['nome' => $nome, 'email' => $email];
            if ($telefone !== '') $dadosUsuario['telefone'] = $telefone;
            $usuarioModel->update($usuarioId, $dadosUsuario);
            setFlashMessage('Dados do usuário atualizados.', MSG_SUCCESS);
            redirect("/admin/parceiro-editar?id={$inscricaoId}");
        }
    }

    // ── Salvar dados da inscrição ────────────────────────────────────────────
    if ($secao === 'inscricao') {
        $nomeFantasia = trim($_POST['nome_fantasia'] ?? '');
        $categoria    = (string)($_POST['categoria'] ?? '');
        $cidade       = trim($_POST['cidade'] ?? '');
        $estado       = strtoupper(trim($_POST['estado'] ?? ''));
        $telefone     = preg_replace('/\D/', '', (string)($_POST['telefone'] ?? ''));
        $mensagem     = trim($_POST['mensagem'] ?? '');

        if ($nomeFantasia === '')                             $erros[] = 'Nome fantasia é obrigatório.';
        if (!in_array($categoria, $categorias, true))        $erros[] = 'Categoria inválida.';
        if ($cidade === '')                                   $erros[] = 'Cidade é obrigatória.';
        if (!in_array($estado, $estados, true))              $erros[] = 'Estado inválido.';
        if ($telefone !== '' && strlen($telefone) < 10)      $erros[] = 'Telefone deve ter ao menos 10 dígitos.';

        if (!$erros) {
            $db = getDB();
            $db->update('parceiro_inscricoes', [
                'nome_fantasia' => $nomeFantasia,
                'categoria'     => $categoria,
                'cidade'        => $cidade,
                'estado'        => $estado,
                'telefone'      => $telefone !== '' ? $telefone : null,
                'mensagem'      => $mensagem !== '' ? $mensagem : null,
            ], 'id = ?', [$inscricaoId]);
            setFlashMessage('Inscrição atualizada.', MSG_SUCCESS);
            redirect("/admin/parceiro-editar?id={$inscricaoId}");
        }
    }

    // ── Salvar dados do perfil ───────────────────────────────────────────────
    if ($secao === 'perfil') {
        $nomeFantasia  = trim($_POST['nome_fantasia'] ?? '');
        $categoria     = (string)($_POST['categoria'] ?? '');
        $descricao     = trim($_POST['descricao'] ?? '');
        $telefone      = preg_replace('/\D/', '', (string)($_POST['telefone'] ?? ''));
        $whatsapp      = preg_replace('/\D/', '', (string)($_POST['whatsapp'] ?? ''));
        $emailContato  = trim($_POST['email_contato'] ?? '');
        $site          = trim($_POST['site'] ?? '');
        $instagram     = trim($_POST['instagram'] ?? '');
        $endereco      = trim($_POST['endereco'] ?? '');
        $bairro        = trim($_POST['bairro'] ?? '');
        $cidade        = trim($_POST['cidade'] ?? '');
        $estado        = strtoupper(trim($_POST['estado'] ?? ''));
        $publicado     = isset($_POST['publicado']) ? 1 : 0;
        $verificado    = isset($_POST['verificado']) ? 1 : 0;
        $destaque      = isset($_POST['destaque']) ? 1 : 0;

        if ($nomeFantasia === '')                               $erros[] = 'Nome fantasia é obrigatório.';
        if (!in_array($categoria, $categorias, true))          $erros[] = 'Categoria inválida.';
        if ($cidade === '')                                     $erros[] = 'Cidade é obrigatória.';
        if (!in_array($estado, $estados, true))                $erros[] = 'Estado inválido.';
        if ($emailContato !== '' && !filter_var($emailContato, FILTER_VALIDATE_EMAIL))
                                                               $erros[] = 'E-mail de contato inválido.';

        if (!$erros) {
            $dados = [
                'nome_fantasia' => $nomeFantasia,
                'categoria'     => $categoria,
                'descricao'     => $descricao !== '' ? $descricao : null,
                'telefone'      => $telefone !== '' ? $telefone : null,
                'whatsapp'      => $whatsapp !== '' ? $whatsapp : null,
                'email_contato' => $emailContato !== '' ? $emailContato : null,
                'site'          => $site !== '' ? $site : null,
                'instagram'     => $instagram !== '' ? $instagram : null,
                'endereco'      => $endereco !== '' ? $endereco : null,
                'bairro'        => $bairro !== '' ? $bairro : null,
                'cidade'        => $cidade,
                'estado'        => $estado,
                'publicado'     => $publicado,
                'verificado'    => $verificado,
                'destaque'      => $destaque,
            ];
            if ($perfil) {
                $perfilModel->updateForUser($usuarioId, $dados);
            } else {
                $slug = $perfilModel->generateUniqueSlug($nomeFantasia, $cidade, $estado);
                $perfilModel->create(array_merge($dados, ['usuario_id' => $usuarioId, 'slug' => $slug]));
            }
            setFlashMessage('Perfil atualizado.', MSG_SUCCESS);
            redirect("/admin/parceiro-editar?id={$inscricaoId}");
        }
    }

    // Recarrega dados atualizados após erro
    $inscricao = $inscricaoModel->findById($inscricaoId);
    $usuario   = $usuarioModel->findById($usuarioId);
    $perfil    = $perfilModel->findByUserId($usuarioId);
}

// ── Limpa telefone zerado para exibição ──────────────────────────────────────
function cleanTel(?string $t): string {
    if ($t === null) return '';
    $digits = preg_replace('/\D/', '', $t);
    return (preg_match('/^0+$/', $digits) || strlen($digits) < 10) ? '' : $digits;
}

$breadcrumbs = [
    ['label' => 'Início',    'url' => BASE_URL],
    ['label' => 'Admin',     'url' => BASE_URL . '/admin'],
    ['label' => 'Parceiros', 'url' => BASE_URL . '/admin/parceiros?tab=inscricoes'],
    ['label' => 'Editar'],
];
$suppressBreadcrumbBar = true;
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">

    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

    <div class="admin-main py-4 px-4">

    <!-- Topbar mobile -->
    <div class="d-flex d-lg-none align-items-center gap-2 mb-3 flex-wrap">
        <a href="<?php echo BASE_URL; ?>/admin"            class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-gauge"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/usuarios"   class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-users"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/anuncios"   class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-list"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/moderacao"  class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-shield-halved"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/financeiro" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-chart-line"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/parceiros"  class="btn btn-sm btn-primary"><i class="fa-solid fa-handshake"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/config"     class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-gear"></i></a>
    </div>

    <div class="mx-auto" style="max-width: 860px;">

    <?php include __DIR__ . '/../includes/admin-breadcrumb.php'; ?>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">Editar parceiro</h1>
            <p class="text-muted mb-0">
                <i class="bi bi-building me-1"></i>
                <?php echo sanitize((string)($inscricao['nome_fantasia'] ?? '—')); ?>
                <span class="badge bg-<?php echo $inscricao['status'] === 'aprovada' ? 'success' : ($inscricao['status'] === 'pendente' ? 'warning' : 'danger'); ?> ms-2">
                    <?php echo ucfirst(sanitize($inscricao['status'])); ?>
                </span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($perfil && !empty($perfil['slug'])): ?>
            <a class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
               href="<?php echo url('/parceiro/' . $perfil['slug']); ?>" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i> Ver perfil
            </a>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
               href="<?php echo url('/admin/parceiros?tab=inscricoes'); ?>">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <?php if ($erros): ?>
    <div class="alert alert-danger mb-3">
        <ul class="mb-0">
            <?php foreach ($erros as $e): ?>
                <li><?php echo sanitize($e); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php /* ── 1. Dados do usuário ── */ ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-transparent border-0 px-4 pt-4 pb-2">
            <h2 class="h6 fw-bold mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-person-circle text-primary"></i> Dados do usuário (conta)
            </h2>
        </div>
        <div class="card-body px-4 pb-4">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="secao" value="usuario">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="u-nome" class="form-label">Nome completo</label>
                        <input type="text" id="u-nome" name="nome" class="form-control"
                               value="<?php echo sanitize((string)($usuario['nome'] ?? '')); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="u-email" class="form-label">E-mail</label>
                        <input type="email" id="u-email" name="email" class="form-control"
                               value="<?php echo sanitize((string)($usuario['email'] ?? '')); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="u-telefone" class="form-label">Telefone</label>
                        <input type="text" id="u-telefone" name="telefone" class="form-control"
                               value="<?php echo sanitize(cleanTel($usuario['telefone'] ?? null)); ?>"
                               placeholder="DDD + número">
                    </div>
                    <div class="col-md-4">
                        <label for="u-cidade" class="form-label">Cidade</label>
                        <input type="text" id="u-cidade" class="form-control" value="<?php echo sanitize((string)($usuario['cidade'] ?? '')); ?>" readonly disabled>
                    </div>
                    <div class="col-md-4">
                        <label for="u-tipo" class="form-label">Tipo</label>
                        <input type="text" id="u-tipo" class="form-control" value="<?php echo sanitize((string)($usuario['tipo_usuario'] ?? '')); ?>" readonly disabled>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <i class="bi bi-floppy"></i> Salvar dados do usuário
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php /* ── 2. Dados da inscrição ── */ ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-transparent border-0 px-4 pt-4 pb-2">
            <h2 class="h6 fw-bold mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-text text-warning"></i> Dados da inscrição
            </h2>
        </div>
        <div class="card-body px-4 pb-4">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="secao" value="inscricao">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="insc-nome-fantasia" class="form-label">Nome fantasia / Razão social</label>
                        <input type="text" id="insc-nome-fantasia" name="nome_fantasia" class="form-control"
                               value="<?php echo sanitize((string)($inscricao['nome_fantasia'] ?? '')); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="insc-categoria" class="form-label">Categoria</label>
                        <select id="insc-categoria" name="categoria" class="form-select">
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo ($inscricao['categoria'] ?? '') === $cat ? 'selected' : ''; ?>>
                                <?php echo ucfirst($cat); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="insc-telefone" class="form-label">Telefone de contato</label>
                        <input type="text" id="insc-telefone" name="telefone" class="form-control"
                               value="<?php echo sanitize(cleanTel($inscricao['telefone'] ?? null)); ?>"
                               placeholder="DDD + número">
                    </div>
                    <div class="col-md-5">
                        <label for="insc-cidade" class="form-label">Cidade</label>
                        <input type="text" id="insc-cidade" name="cidade" class="form-control"
                               value="<?php echo sanitize((string)($inscricao['cidade'] ?? '')); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="insc-estado" class="form-label">Estado</label>
                        <select id="insc-estado" name="estado" class="form-select">
                            <?php foreach ($estados as $uf): ?>
                            <option value="<?php echo $uf; ?>" <?php echo ($inscricao['estado'] ?? '') === $uf ? 'selected' : ''; ?>>
                                <?php echo $uf; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="insc-mensagem" class="form-label">Mensagem / Observação</label>
                        <textarea id="insc-mensagem" name="mensagem" class="form-control" rows="3"><?php echo sanitize((string)($inscricao['mensagem'] ?? '')); ?></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-warning d-inline-flex align-items-center gap-2">
                        <i class="bi bi-floppy"></i> Salvar inscrição
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php /* ── 3. Perfil público ── */ ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-transparent border-0 px-4 pt-4 pb-2">
            <h2 class="h6 fw-bold mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-shop text-success"></i> Perfil público
                <?php if (!$perfil): ?>
                    <span class="badge bg-secondary">Não criado ainda</span>
                <?php elseif ($perfil['publicado']): ?>
                    <span class="badge bg-success">Publicado</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Despublicado</span>
                <?php endif; ?>
            </h2>
        </div>
        <div class="card-body px-4 pb-4">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="secao" value="perfil">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="perf-nome-fantasia" class="form-label">Nome fantasia (público)</label>
                        <input type="text" id="perf-nome-fantasia" name="nome_fantasia" class="form-control"
                               value="<?php echo sanitize((string)($perfil['nome_fantasia'] ?? $inscricao['nome_fantasia'] ?? '')); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="perf-categoria" class="form-label">Categoria</label>
                        <select id="perf-categoria" name="categoria" class="form-select">
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat; ?>"
                                <?php echo ($perfil['categoria'] ?? $inscricao['categoria'] ?? '') === $cat ? 'selected' : ''; ?>>
                                <?php echo ucfirst($cat); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="perf-descricao" class="form-label">Descrição / Apresentação</label>
                        <textarea id="perf-descricao" name="descricao" class="form-control" rows="4"
                                  placeholder="Descreva os serviços, diferenciais..."><?php echo sanitize((string)($perfil['descricao'] ?? '')); ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label for="perf-telefone" class="form-label">Telefone</label>
                        <input type="text" id="perf-telefone" name="telefone" class="form-control"
                               value="<?php echo sanitize(cleanTel($perfil['telefone'] ?? null)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="perf-whatsapp" class="form-label">WhatsApp</label>
                        <input type="text" id="perf-whatsapp" name="whatsapp" class="form-control"
                               value="<?php echo sanitize(cleanTel($perfil['whatsapp'] ?? null)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="perf-email-contato" class="form-label">E-mail de contato</label>
                        <input type="email" id="perf-email-contato" name="email_contato" class="form-control"
                               value="<?php echo sanitize((string)($perfil['email_contato'] ?? '')); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="perf-site" class="form-label">Site</label>
                        <input type="url" id="perf-site" name="site" class="form-control"
                               value="<?php echo sanitize((string)($perfil['site'] ?? '')); ?>"
                               placeholder="https://...">
                    </div>
                    <div class="col-md-6">
                        <label for="perf-instagram" class="form-label">Instagram</label>
                        <input type="text" id="perf-instagram" name="instagram" class="form-control"
                               value="<?php echo sanitize((string)($perfil['instagram'] ?? '')); ?>"
                               placeholder="@usuario ou URL">
                    </div>
                    <div class="col-12">
                        <label for="perf-endereco" class="form-label">Endereço</label>
                        <input type="text" id="perf-endereco" name="endereco" class="form-control"
                               value="<?php echo sanitize((string)($perfil['endereco'] ?? '')); ?>"
                               placeholder="Rua, número">
                    </div>
                    <div class="col-md-4">
                        <label for="perf-bairro" class="form-label">Bairro</label>
                        <input type="text" id="perf-bairro" name="bairro" class="form-control"
                               value="<?php echo sanitize((string)($perfil['bairro'] ?? '')); ?>">
                    </div>
                    <div class="col-md-5">
                        <label for="perf-cidade" class="form-label">Cidade</label>
                        <input type="text" id="perf-cidade" name="cidade" class="form-control"
                               value="<?php echo sanitize((string)($perfil['cidade'] ?? $inscricao['cidade'] ?? '')); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="perf-estado" class="form-label">Estado</label>
                        <select id="perf-estado" name="estado" class="form-select">
                            <?php foreach ($estados as $uf): ?>
                            <option value="<?php echo $uf; ?>"
                                <?php echo ($perfil['estado'] ?? $inscricao['estado'] ?? '') === $uf ? 'selected' : ''; ?>>
                                <?php echo $uf; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="d-flex gap-4 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="publicado" id="publicado" value="1"
                                       <?php echo ($perfil['publicado'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="publicado">
                                    <strong>Publicado</strong> — visível na lista de parceiros
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="verificado" id="verificado" value="1"
                                       <?php echo ($perfil['verificado'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="verificado">
                                    <i class="bi bi-patch-check-fill text-primary"></i> <strong>Verificado</strong>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="destaque" id="destaque" value="1"
                                       <?php echo ($perfil['destaque'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="destaque">
                                    <i class="bi bi-star-fill text-warning"></i> <strong>Destaque</strong>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2 flex-wrap align-items-center">
                    <button type="submit" class="btn btn-success d-inline-flex align-items-center gap-2">
                        <i class="bi bi-floppy"></i> Salvar perfil
                    </button>
                    <?php if ($perfil && !empty($perfil['slug'])): ?>
                    <span class="text-muted small">
                        <i class="bi bi-link-45deg"></i>
                        Slug: <code><?php echo sanitize($perfil['slug']); ?></code>
                    </span>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php /* ── Histórico ── */ ?>
    <div class="card shadow-sm border-0">
        <div class="card-body px-4 py-3">
            <div class="row g-3 text-muted small">
                <div class="col-md-4">
                    <strong>Inscrição criada:</strong><br>
                    <?php echo sanitize((string)($inscricao['data_criacao'] ?? '—')); ?>
                </div>
                <?php if (!empty($inscricao['aprovada_em'])): ?>
                <div class="col-md-4">
                    <strong>Aprovada em:</strong><br>
                    <?php echo sanitize((string)$inscricao['aprovada_em']); ?>
                </div>
                <?php endif; ?>
                <div class="col-md-4">
                    <strong>ID Inscrição / Usuário:</strong><br>
                    #<?php echo $inscricaoId; ?> / #<?php echo $usuarioId; ?>
                </div>
            </div>
        </div>
    </div>

    </div><!-- /.mx-auto -->

    </div><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
