<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$pageTitle = 'Admin - Usuários - Cadê Meu Pet?';

$usuarioModel = new Usuario();

$search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

$currentUserId = (int)(getUserId() ?? 0);

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max(1, $pagina);
$limite = 20;
$offset = ($pagina - 1) * $limite;

$alertEmailFail = '';
if (!empty($_SESSION['alert_email_fail'])) {
    $alertEmailFail = $_SESSION['alert_email_fail'];
    unset($_SESSION['alert_email_fail']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Falha na validação do formulário. Recarregue a página.', MSG_ERROR);
        redirect('/admin/usuarios');
    }

    $acao = (string)($_POST['acao'] ?? '');

    if ($acao === 'create_user') {
        $nome            = trim((string)($_POST['nome']            ?? ''));
        $email           = trim((string)($_POST['email']           ?? ''));
        $telefone        = trim((string)($_POST['telefone']        ?? ''));
        $senha           = (string)($_POST['senha']                ?? '');
        $tipoUsuario     = (string)($_POST['tipo_usuario']         ?? 'comum');
        $ativo           = !empty($_POST['ativo'])           ? 1 : 0;
        $isAdminFlag     = !empty($_POST['is_admin'])        ? 1 : 0;
        $emailConfirmado = !empty($_POST['email_confirmado']) ? 1 : 0;

        $errors = [];
        if ($nome === '')                              $errors[] = 'Informe o nome.';
        if ($email === '' || !isValidEmail($email))   $errors[] = 'Informe um email válido.';
        if ($telefone === '')                          $errors[] = 'Informe o telefone.';
        if (strlen($senha) < PASSWORD_MIN_LENGTH)      $errors[] = 'Senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres.';
        if (!in_array($tipoUsuario, ['comum', 'parceiro'], true)) $errors[] = 'Tipo inválido.';
        if ($email !== '' && $usuarioModel->findByEmail($email))  $errors[] = 'E-mail já cadastrado.';

        if (!empty($errors)) { setFlashMessage(implode(' ', $errors), MSG_ERROR); redirect('/admin/usuarios'); }

        try {
            $usuarioModel->create([
                'nome' => $nome, 'email' => $email, 'telefone' => $telefone,
                'senha' => hashPassword($senha), 'tipo_usuario' => $tipoUsuario,
                'notificacoes_email' => 1, 'email_confirmado' => $emailConfirmado,
                'token_confirmacao' => null, 'tentativas_login' => 0,
                'bloqueado_ate' => null, 'ultimo_acesso' => null,
                'ativo' => $ativo, 'is_admin' => $isAdminFlag,
            ]);
            setFlashMessage('Usuário criado.', MSG_SUCCESS);
        } catch (Throwable $e) {
            error_log('[Admin Usuarios] create_user: ' . $e->getMessage());
            setFlashMessage('Erro ao criar usuário.', MSG_ERROR);
        }
        redirect('/admin/usuarios');
    }

    $id   = (int)($_POST['id'] ?? 0);
    $alvo = $id > 0 ? $usuarioModel->findById($id) : null;

    if ($id <= 0 || !$alvo) {
        setFlashMessage('Usuário inválido.', MSG_ERROR);
        redirect('/admin/usuarios');
    }

    if ($acao === 'toggle_active') {
        if ($currentUserId === $id) { setFlashMessage('Você não pode desativar seu próprio usuário.', MSG_ERROR); redirect('/admin/usuarios'); }
        try {
            $novoAtivo = !(bool)$alvo['ativo'];
            $usuarioModel->setActive($id, $novoAtivo);
            auditLog($novoAtivo ? 'ativar_usuario' : 'desativar_usuario', 'usuarios', $id,
                ['ativo' => (int)$alvo['ativo']], ['ativo' => (int)$novoAtivo]);
            setFlashMessage('Status atualizado.', MSG_SUCCESS);
        }
        catch (Throwable $e) { error_log('[Admin Usuarios] toggle_active: ' . $e->getMessage()); setFlashMessage('Erro ao atualizar.', MSG_ERROR); }
        redirect('/admin/usuarios');
    }

    if ($acao === 'toggle_admin') {
        if ($currentUserId === $id) { setFlashMessage('Você não pode remover seu próprio acesso admin.', MSG_ERROR); redirect('/admin/usuarios'); }
        try {
            $novoAdmin = !(bool)$alvo['is_admin'];
            $usuarioModel->setAdmin($id, $novoAdmin);
            auditLog($novoAdmin ? 'promover_admin' : 'revogar_admin', 'usuarios', $id,
                ['is_admin' => (int)$alvo['is_admin']], ['is_admin' => (int)$novoAdmin]);
            setFlashMessage('Permissão atualizada.', MSG_SUCCESS);
        }
        catch (Throwable $e) { error_log('[Admin Usuarios] toggle_admin: ' . $e->getMessage()); setFlashMessage('Erro ao atualizar.', MSG_ERROR); }
        redirect('/admin/usuarios');
    }

    if ($acao === 'update_user') {
        $nome            = trim((string)($_POST['nome']            ?? ''));
        $email           = trim((string)($_POST['email']           ?? ''));
        $telefone        = trim((string)($_POST['telefone']        ?? ''));
        $tipoUsuario     = (string)($_POST['tipo_usuario']         ?? ($alvo['tipo_usuario'] ?? 'comum'));
        $ativo           = !empty($_POST['ativo'])           ? 1 : 0;
        $isAdminFlag     = !empty($_POST['is_admin'])        ? 1 : 0;
        $emailConfirmado = !empty($_POST['email_confirmado']) ? 1 : 0;

        $errors = [];
        if ($nome === '')                              $errors[] = 'Informe o nome.';
        if ($email === '' || !isValidEmail($email))   $errors[] = 'Informe um email válido.';
        if ($telefone === '')                          $errors[] = 'Informe o telefone.';
        if (!in_array($tipoUsuario, ['comum', 'parceiro'], true)) $errors[] = 'Tipo inválido.';

        $existing = $usuarioModel->findByEmail($email);
        if (!empty($existing) && (int)($existing['id'] ?? 0) !== $id) $errors[] = 'E-mail já cadastrado.';
        if ($currentUserId === $id) { $ativo = 1; $isAdminFlag = 1; }

        if (!empty($errors)) { setFlashMessage(implode(' ', $errors), MSG_ERROR); redirect('/admin/usuarios'); }

        try {
            $dadosNovos = [
                'nome' => $nome, 'email' => $email, 'telefone' => $telefone,
                'tipo_usuario' => $tipoUsuario, 'ativo' => $ativo,
                'is_admin' => $isAdminFlag, 'email_confirmado' => $emailConfirmado,
            ];
            $usuarioModel->update($id, $dadosNovos);
            auditLog('editar_usuario', 'usuarios', $id,
                array_intersect_key($alvo, $dadosNovos), $dadosNovos);
            setFlashMessage('Usuário atualizado.', MSG_SUCCESS);
        } catch (Throwable $e) {
            error_log('[Admin Usuarios] update_user: ' . $e->getMessage());
            setFlashMessage('Erro ao atualizar.', MSG_ERROR);
        }
        redirect('/admin/usuarios');
    }

    if ($acao === 'reset_password') {
        $senha = (string)($_POST['senha'] ?? '');
        if (strlen($senha) < PASSWORD_MIN_LENGTH) { setFlashMessage('Senha muito curta.', MSG_ERROR); redirect('/admin/usuarios'); }
        try {
            $usuarioModel->updatePassword($id, $senha);
            auditLog('reset_senha_usuario', 'usuarios', $id, null, ['senha_redefinida' => true]);
            setFlashMessage('Senha atualizada.', MSG_SUCCESS);
        }
        catch (Throwable $e) { error_log('[Admin Usuarios] reset_password: ' . $e->getMessage()); setFlashMessage('Erro ao atualizar senha.', MSG_ERROR); }
        redirect('/admin/usuarios');
    }

    setFlashMessage('Ação inválida.', MSG_ERROR);
    redirect('/admin/usuarios');
}

$total       = $usuarioModel->countAll($search);
$usuarios    = $usuarioModel->findAll($limite, $offset, $search);
$totalPaginas = (int)ceil($total / $limite);

$breadcrumbs = [
    ['label' => 'Início',   'url' => BASE_URL],
    ['label' => 'Admin',    'url' => BASE_URL . '/admin'],
    ['label' => 'Usuários'],
];
$suppressBreadcrumbBar = true;
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">

    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

    <!-- Conteúdo -->
    <div class="admin-main py-3 px-3 px-lg-4">

            <!-- Topbar mobile -->
            <div class="d-flex d-lg-none gap-1 mb-3 flex-wrap">
                <a href="<?php echo BASE_URL; ?>/admin"            class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-gauge"></i></a>
                <a href="<?php echo BASE_URL; ?>/admin/usuarios"   class="btn btn-sm btn-primary"><i class="fa-solid fa-users"></i></a>
                <a href="<?php echo BASE_URL; ?>/admin/anuncios"   class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-list"></i></a>
                <a href="<?php echo BASE_URL; ?>/admin/moderacao"  class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-shield-halved"></i></a>
                <a href="<?php echo BASE_URL; ?>/admin/financeiro" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-chart-line"></i></a>
                <a href="<?php echo BASE_URL; ?>/admin/config"     class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-gear"></i></a>
            </div>

            <?php include __DIR__ . '/../includes/admin-breadcrumb.php'; ?>

            <!-- Header da página -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h1 class="h5 fw-bold mb-0">Usuários</h1>
                    <p class="text-muted small mb-0"><?php echo number_format($total); ?> cadastrados</p>
                </div>
                <button class="btn btn-sm btn-primary" type="button"
                        data-bs-toggle="collapse" data-bs-target="#formCriarUsuario">
                    <i class="fa-solid fa-plus me-1"></i>Novo usuário
                </button>
            </div>

            <?php if (!empty($alertEmailFail)): ?>
                <div class="alert alert-warning alert-dismissible small py-2 mb-3">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i><?php echo sanitize($alertEmailFail); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php $flash = getFlashMessage(); if ($flash): ?>
                <div class="alert alert-<?php echo sanitize($flash['type']); ?> alert-dismissible small py-2 mb-3">
                    <?php echo sanitize($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Form criar usuário (colapsável) -->
            <div class="collapse mb-3" id="formCriarUsuario">
                <div class="card border shadow-sm">
                    <div class="card-body p-3">
                        <h2 class="h6 fw-bold mb-3">Novo usuário</h2>
                        <form method="POST" class="row g-2">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="acao" value="create_user">
                            <div class="col-sm-6 col-lg-3">
                                <input type="text" name="nome" class="form-control form-control-sm" placeholder="Nome" required>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <input type="email" name="email" class="form-control form-control-sm" placeholder="E-mail" required>
                            </div>
                            <div class="col-sm-6 col-lg-2">
                                <input type="text" name="telefone" class="form-control form-control-sm" placeholder="Telefone" required>
                            </div>
                            <div class="col-sm-6 col-lg-2">
                                <input type="password" name="senha" class="form-control form-control-sm" placeholder="Senha" required>
                            </div>
                            <div class="col-sm-6 col-lg-2">
                                <select name="tipo_usuario" class="form-select form-select-sm">
                                    <option value="comum">Comum</option>
                                    <option value="parceiro">Parceiro</option>
                                </select>
                            </div>
                            <div class="col-12 d-flex align-items-center gap-3 flex-wrap">
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="checkbox" name="ativo" id="c_ativo" checked>
                                    <label class="form-check-label small" for="c_ativo">Ativo</label>
                                </div>
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="checkbox" name="is_admin" id="c_admin">
                                    <label class="form-check-label small" for="c_admin">Admin</label>
                                </div>
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="checkbox" name="email_confirmado" id="c_emailconf">
                                    <label class="form-check-label small" for="c_emailconf">E-mail confirmado</label>
                                </div>
                                <button class="btn btn-sm btn-primary ms-auto" type="submit">
                                    <i class="fa-solid fa-plus me-1"></i>Criar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Busca -->
            <form method="GET" class="d-flex gap-2 mb-3">
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="Buscar por nome ou e-mail"
                       value="<?php echo sanitize($search); ?>">
                <button class="btn btn-sm btn-outline-secondary" type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <?php if ($search !== ''): ?>
                    <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="btn btn-sm btn-outline-danger">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                <?php endif; ?>
            </form>

            <!-- Tabela -->
            <div class="card border shadow-sm">
                <div class="table-responsive">
                    <?php if (empty($usuarios)): ?>
                        <div class="text-muted small p-4 text-center">Nenhum usuário encontrado.</div>
                    <?php else: ?>
                        <?php $modals = ''; ?>
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-muted">
                                    <th class="ps-3" style="width:48px">#</th>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th class="text-center" style="width:60px">Ativo</th>
                                    <th class="text-center" style="width:60px">Admin</th>
                                    <th style="width:80px">Tipo</th>
                                    <th class="text-center" style="width:80px">E-mail conf.</th>
                                    <th class="text-end pe-3" style="width:160px">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $u): ?>
                                    <tr>
                                        <td class="ps-3 text-muted small"><?php echo (int)$u['id']; ?></td>
                                        <td>
                                            <span class="fw-semibold small"><?php echo sanitize($u['nome'] ?? ''); ?></span>
                                        </td>
                                        <td class="small text-muted"><?php echo sanitize($u['email'] ?? ''); ?></td>
                                        <td class="text-center">
                                            <?php if (!empty($u['ativo'])): ?>
                                                <i class="fa-solid fa-circle-check text-success" title="Ativo"></i>
                                            <?php else: ?>
                                                <i class="fa-solid fa-circle-xmark text-secondary" title="Inativo"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($u['is_admin'])): ?>
                                                <i class="fa-solid fa-shield-halved text-primary" title="Admin"></i>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border" style="font-size:.68rem;">
                                                <?php echo sanitize($u['tipo_usuario'] ?? ''); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($u['email_confirmado'])): ?>
                                                <i class="fa-solid fa-envelope-circle-check text-success small" title="Confirmado"></i>
                                            <?php else: ?>
                                                <i class="fa-solid fa-envelope text-warning small" title="Não confirmado"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="d-flex justify-content-end gap-1">
                                                <!-- Editar -->
                                                <button class="btn btn-xs btn-outline-secondary"
                                                        title="Editar"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEditUser<?php echo (int)$u['id']; ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <!-- Senha -->
                                                <button class="btn btn-xs btn-outline-secondary"
                                                        title="Redefinir senha"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalResetPass<?php echo (int)$u['id']; ?>">
                                                    <i class="fa-solid fa-key"></i>
                                                </button>
                                                <!-- Ativar/Bloquear -->
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                                    <input type="hidden" name="acao" value="toggle_active">
                                                    <button class="btn btn-xs <?php echo !empty($u['ativo']) ? 'btn-outline-danger' : 'btn-outline-success'; ?>"
                                                            title="<?php echo !empty($u['ativo']) ? 'Bloquear' : 'Ativar'; ?>"
                                                            <?php echo ($currentUserId === (int)$u['id']) ? 'disabled' : ''; ?>>
                                                        <i class="fa-solid <?php echo !empty($u['ativo']) ? 'fa-ban' : 'fa-circle-check'; ?>"></i>
                                                    </button>
                                                </form>
                                                <!-- Toggle admin -->
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                                    <input type="hidden" name="acao" value="toggle_admin">
                                                    <button class="btn btn-xs <?php echo !empty($u['is_admin']) ? 'btn-outline-warning' : 'btn-outline-primary'; ?>"
                                                            title="<?php echo !empty($u['is_admin']) ? 'Remover admin' : 'Tornar admin'; ?>"
                                                            <?php echo ($currentUserId === (int)$u['id']) ? 'disabled' : ''; ?>>
                                                        <i class="fa-solid fa-shield-halved"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>

                                    <?php ob_start(); ?>

                                    <!-- Modal Editar -->
                                    <div class="modal fade" id="modalEditUser<?php echo (int)$u['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header py-2 px-3">
                                                    <h5 class="modal-title small fw-bold">
                                                        <i class="fa-solid fa-pen-to-square me-1 text-primary"></i>
                                                        Editar #<?php echo (int)$u['id']; ?> — <?php echo sanitize($u['nome'] ?? ''); ?>
                                                    </h5>
                                                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body p-3">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="acao" value="update_user">
                                                        <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                                        <div class="row g-2">
                                                            <div class="col-sm-4">
                                                                <label class="form-label small fw-semibold mb-1">Nome</label>
                                                                <input type="text" name="nome" class="form-control form-control-sm"
                                                                       value="<?php echo sanitize($u['nome'] ?? ''); ?>" required>
                                                            </div>
                                                            <div class="col-sm-4">
                                                                <label class="form-label small fw-semibold mb-1">E-mail</label>
                                                                <input type="email" name="email" class="form-control form-control-sm"
                                                                       value="<?php echo sanitize($u['email'] ?? ''); ?>" required>
                                                            </div>
                                                            <div class="col-sm-4">
                                                                <label class="form-label small fw-semibold mb-1">Telefone</label>
                                                                <input type="text" name="telefone" class="form-control form-control-sm"
                                                                       value="<?php echo sanitize($u['telefone'] ?? ''); ?>" required>
                                                            </div>
                                                            <div class="col-sm-4">
                                                                <label class="form-label small fw-semibold mb-1">Tipo</label>
                                                                <select name="tipo_usuario" class="form-select form-select-sm">
                                                                    <option value="comum"     <?php echo (($u['tipo_usuario'] ?? '') === 'comum')     ? 'selected' : ''; ?>>Comum</option>
                                                                    <option value="parceiro"  <?php echo (($u['tipo_usuario'] ?? '') === 'parceiro')  ? 'selected' : ''; ?>>Parceiro</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-sm-8 d-flex align-items-end">
                                                                <div class="d-flex flex-wrap gap-3">
                                                                    <div class="form-check mb-0">
                                                                        <input class="form-check-input" type="checkbox" name="ativo"
                                                                               id="e_ativo_<?php echo (int)$u['id']; ?>"
                                                                               <?php echo !empty($u['ativo']) ? 'checked' : ''; ?>
                                                                               <?php echo ($currentUserId === (int)$u['id']) ? 'disabled' : ''; ?>>
                                                                        <label class="form-check-label small" for="e_ativo_<?php echo (int)$u['id']; ?>">Ativo</label>
                                                                    </div>
                                                                    <div class="form-check mb-0">
                                                                        <input class="form-check-input" type="checkbox" name="is_admin"
                                                                               id="e_admin_<?php echo (int)$u['id']; ?>"
                                                                               <?php echo !empty($u['is_admin']) ? 'checked' : ''; ?>
                                                                               <?php echo ($currentUserId === (int)$u['id']) ? 'disabled' : ''; ?>>
                                                                        <label class="form-check-label small" for="e_admin_<?php echo (int)$u['id']; ?>">Admin</label>
                                                                    </div>
                                                                    <div class="form-check mb-0">
                                                                        <input class="form-check-input" type="checkbox" name="email_confirmado"
                                                                               id="e_emailconf_<?php echo (int)$u['id']; ?>"
                                                                               <?php echo !empty($u['email_confirmado']) ? 'checked' : ''; ?>>
                                                                        <label class="form-check-label small" for="e_emailconf_<?php echo (int)$u['id']; ?>">E-mail confirmado</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php if ($currentUserId === (int)$u['id']): ?>
                                                            <p class="text-muted small mt-2 mb-0">
                                                                <i class="fa-solid fa-circle-info me-1"></i>
                                                                Seu próprio usuário não pode ser desativado ou perder permissão admin.
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer py-2 px-3 gap-2">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            <i class="fa-solid fa-floppy-disk me-1"></i>Salvar
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Redefinir Senha -->
                                    <div class="modal fade" id="modalResetPass<?php echo (int)$u['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header py-2 px-3">
                                                    <h5 class="modal-title small fw-bold">
                                                        <i class="fa-solid fa-key me-1 text-warning"></i>
                                                        Redefinir senha #<?php echo (int)$u['id']; ?>
                                                    </h5>
                                                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body p-3">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="acao" value="reset_password">
                                                        <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                                        <label class="form-label small fw-semibold mb-1">Nova senha</label>
                                                        <input type="password" name="senha" class="form-control form-control-sm" required
                                                               placeholder="Mín. <?php echo (int)PASSWORD_MIN_LENGTH; ?> caracteres">
                                                    </div>
                                                    <div class="modal-footer py-2 px-3 gap-2">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-sm btn-warning">
                                                            <i class="fa-solid fa-key me-1"></i>Atualizar
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <?php $modals .= ob_get_clean(); ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php echo $modals; ?>

                        <?php if ($totalPaginas > 1): ?>
                            <?php
                                $prev = max(1, $pagina - 1);
                                $next = min($totalPaginas, $pagina + 1);
                                $qs   = $search !== '' ? '&q=' . urlencode($search) : '';
                            ?>
                            <div class="d-flex justify-content-center align-items-center gap-2 py-3">
                                <a class="btn btn-xs btn-outline-secondary <?php echo $pagina <= 1 ? 'disabled' : ''; ?>"
                                   href="<?php echo BASE_URL . '/admin/usuarios?pagina=' . $prev . $qs; ?>">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                                <span class="small text-muted"><?php echo $pagina; ?> / <?php echo $totalPaginas; ?></span>
                                <a class="btn btn-xs btn-outline-secondary <?php echo $pagina >= $totalPaginas ? 'disabled' : ''; ?>"
                                   href="<?php echo BASE_URL . '/admin/usuarios?pagina=' . $next . $qs; ?>">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

    </div><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
