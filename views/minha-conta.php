<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Minha Conta - Cadê Meu Pet?';
$usuarioId = (int)getUserId();

$db = getDB();

// Carrega dados do usuário
$usuario = $db->fetchOne('SELECT * FROM usuarios WHERE id = ?', [$usuarioId]);

// Aba ativa
$aba = $_GET['aba'] ?? 'anuncios';
$abasValidas = ['anuncios', 'favoritos', 'alertas', 'configuracoes'];
if (!in_array($aba, $abasValidas, true)) {
    $aba = 'anuncios';
}

// Processar atualizações de configurações
$erros = [];
$sucesso = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $aba === 'configuracoes') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $erros[] = 'Erro de validação. Recarregue a página.';
    } else {
        $acao = $_POST['acao'] ?? '';

        if ($acao === 'atualizar_perfil') {
            $nome  = trim($_POST['nome'] ?? '');
            $phone = trim($_POST['telefone'] ?? '');
            if ($nome === '') {
                $erros[] = 'O nome não pode ficar em branco.';
            } else {
                $db->update('usuarios', ['nome' => $nome, 'telefone' => $phone], 'id = ?', [$usuarioId]);
                $usuario['nome']     = $nome;
                $usuario['telefone'] = $phone;
                $sucesso = 'Perfil atualizado com sucesso.';
            }
        } elseif ($acao === 'alterar_senha') {
            $senhaAtual  = $_POST['senha_atual']  ?? '';
            $novaSenha   = $_POST['nova_senha']   ?? '';
            $confirma    = $_POST['confirma']     ?? '';

            if (!password_verify($senhaAtual, $usuario['senha'])) {
                $erros[] = 'Senha atual incorreta.';
            } elseif (strlen($novaSenha) < PASSWORD_MIN_LENGTH) {
                $erros[] = 'A nova senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres.';
            } elseif ($novaSenha !== $confirma) {
                $erros[] = 'As senhas não coincidem.';
            } else {
                $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
                $db->update('usuarios', ['senha' => $hash], 'id = ?', [$usuarioId]);
                $sucesso = 'Senha alterada com sucesso.';
            }
        }
    }
}

// Dados por aba
$anuncioModel       = new Anuncio();
$favoritoController = new FavoritoController();
$alertaController   = new AlertaController();

$meusAnuncios = $anuncioModel->findByUser($usuarioId, 50, 0);
$favoritos    = $favoritoController->listarDoUsuario();
$alertas      = $alertaController->listarPorUsuario($usuarioId);

// Stats rápidas
$totalAtivos    = count(array_filter($meusAnuncios, fn($a) => ($a['status'] ?? '') === STATUS_ATIVO));
$totalResolvidos = count(array_filter($meusAnuncios, fn($a) => ($a['status'] ?? '') === STATUS_RESOLVIDO));

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">

    <!-- Cabeçalho do perfil -->
    <div class="row align-items-center mb-4 g-3">
        <div class="col-auto">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 text-white"
                 style="width:72px;height:72px;background:var(--cmp-primary);">
                <?php echo strtoupper(mb_substr($usuario['nome'] ?? 'U', 0, 1)); ?>
            </div>
        </div>
        <div class="col">
            <h1 class="h4 fw-bold mb-0"><?php echo sanitize($usuario['nome'] ?? ''); ?></h1>
            <p class="text-muted mb-0"><?php echo sanitize($usuario['email'] ?? ''); ?></p>
        </div>
        <div class="col-auto d-none d-md-flex gap-3">
            <div class="text-center">
                <div class="fw-bold fs-5"><?php echo $totalAtivos; ?></div>
                <div class="text-muted small">Ativos</div>
            </div>
            <div class="text-center">
                <div class="fw-bold fs-5 text-success"><?php echo $totalResolvidos; ?></div>
                <div class="text-muted small">Resolvidos</div>
            </div>
            <div class="text-center">
                <div class="fw-bold fs-5 text-danger"><?php echo count($favoritos); ?></div>
                <div class="text-muted small">Favoritos</div>
            </div>
        </div>
    </div>

    <!-- Abas -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $aba === 'anuncios' ? 'active' : ''; ?>"
               href="<?php echo BASE_URL; ?>/minha-conta?aba=anuncios">
                <i class="fa-solid fa-list me-1"></i>Meus Anúncios
                <?php if ($totalAtivos > 0): ?>
                    <span class="badge bg-primary ms-1"><?php echo $totalAtivos; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $aba === 'favoritos' ? 'active' : ''; ?>"
               href="<?php echo BASE_URL; ?>/minha-conta?aba=favoritos">
                <i class="fa-solid fa-heart me-1"></i>Favoritos
                <?php if (!empty($favoritos)): ?>
                    <span class="badge bg-danger ms-1"><?php echo count($favoritos); ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $aba === 'alertas' ? 'active' : ''; ?>"
               href="<?php echo BASE_URL; ?>/minha-conta?aba=alertas">
                <i class="fa-solid fa-bell me-1"></i>Alertas
                <?php if (!empty($alertas)): ?>
                    <span class="badge bg-warning text-dark ms-1"><?php echo count($alertas); ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $aba === 'configuracoes' ? 'active' : ''; ?>"
               href="<?php echo BASE_URL; ?>/minha-conta?aba=configuracoes">
                <i class="fa-solid fa-gear me-1"></i>Configurações
            </a>
        </li>
    </ul>

    <?php if ($sucesso): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo sanitize($sucesso); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($erros)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($erros as $e): ?><li><?php echo sanitize($e); ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- ───── ABA ANÚNCIOS ───── -->
    <?php if ($aba === 'anuncios'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 fw-bold mb-0">Meus Anúncios</h2>
            <a href="<?php echo BASE_URL; ?>/novo-anuncio" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus me-1"></i>Publicar novo
            </a>
        </div>

        <?php if (empty($meusAnuncios)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fa-solid fa-list fa-3x mb-3 d-block opacity-25"></i>
                <p>Você ainda não publicou nenhum anúncio.</p>
                <a href="<?php echo BASE_URL; ?>/novo-anuncio" class="btn btn-primary">Publicar agora</a>
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($meusAnuncios as $a): ?>
                    <?php
                        $statusClass = [
                            STATUS_ATIVO      => 'success',
                            STATUS_RESOLVIDO  => 'primary',
                            STATUS_EXPIRADO   => 'warning',
                            STATUS_INATIVO    => 'secondary',
                            STATUS_BLOQUEADO  => 'danger',
                        ][$a['status'] ?? ''] ?? 'secondary';
                        $statusLabel = [
                            STATUS_ATIVO     => 'Ativo',
                            STATUS_RESOLVIDO => 'Resolvido',
                            STATUS_EXPIRADO  => 'Expirado',
                            STATUS_INATIVO   => 'Inativo',
                            STATUS_BLOQUEADO => 'Bloqueado',
                        ][$a['status'] ?? ''] ?? ucfirst($a['status'] ?? '');
                    ?>
                    <div class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-<?php echo $a['tipo'] === 'perdido' ? 'danger' : ($a['tipo'] === 'doacao' ? 'primary' : 'success'); ?>">
                                    <?php echo $a['tipo'] === 'perdido' ? 'Perdido' : ($a['tipo'] === 'doacao' ? 'Adoção' : 'Encontrado'); ?>
                                </span>
                                <span class="badge bg-<?php echo $statusClass; ?>-subtle text-<?php echo $statusClass; ?>-emphasis border border-<?php echo $statusClass; ?>-subtle">
                                    <?php echo $statusLabel; ?>
                                </span>
                                <?php if (($a['status'] ?? '') === STATUS_RESOLVIDO): ?>
                                    <span class="badge bg-success">
                                        <i class="fa-solid fa-heart-circle-check me-1"></i>Reunido!
                                    </span>
                                <?php endif; ?>
                            </div>
                            <strong><?php echo sanitize($a['nome_pet'] ?: 'Pet ' . ucfirst($a['especie'])); ?></strong>
                            <span class="text-muted small ms-2"><?php echo sanitize($a['cidade']); ?> - <?php echo sanitize($a['estado']); ?></span>
                            <div class="text-muted small"><?php echo timeAgo($a['data_publicacao']); ?></div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?php echo BASE_URL; ?>/anuncio/<?php echo (int)$a['id']; ?>/" class="btn btn-sm btn-outline-secondary">Ver</a>
                            <a href="<?php echo BASE_URL; ?>/editar-anuncio/<?php echo (int)$a['id']; ?>/" class="btn btn-sm btn-outline-primary">Editar</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <!-- ───── ABA FAVORITOS ───── -->
    <?php elseif ($aba === 'favoritos'): ?>
        <h2 class="h5 fw-bold mb-3">Meus Favoritos</h2>

        <?php if (empty($favoritos)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fa-regular fa-heart fa-3x mb-3 d-block opacity-25"></i>
                <p>Você ainda não favoritou nenhum pet.</p>
                <a href="<?php echo BASE_URL; ?>/busca" class="btn btn-outline-primary">Explorar anúncios</a>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($favoritos as $fav): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <?php if (!empty($fav['foto'])): ?>
                                <img src="<?php echo BASE_URL; ?>/uploads/anuncios/<?php echo sanitize($fav['foto']); ?>"
                                     class="card-img-top" style="height:160px;object-fit:cover;"
                                     alt="<?php echo sanitize($fav['nome_pet'] ?? ''); ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <span class="badge bg-<?php echo ($fav['tipo'] ?? '') === 'perdido' ? 'danger' : (($fav['tipo'] ?? '') === 'doacao' ? 'primary' : 'success'); ?> mb-1">
                                    <?php echo ($fav['tipo'] ?? '') === 'perdido' ? 'Perdido' : (($fav['tipo'] ?? '') === 'doacao' ? 'Adoção' : 'Encontrado'); ?>
                                </span>
                                <h6 class="fw-bold mb-1"><?php echo sanitize($fav['nome_pet'] ?: 'Pet'); ?></h6>
                                <p class="text-muted small mb-2">
                                    <i class="fa-solid fa-location-dot me-1"></i>
                                    <?php echo sanitize($fav['cidade'] ?? ''); ?> - <?php echo sanitize($fav['estado'] ?? ''); ?>
                                </p>
                                <a href="<?php echo BASE_URL; ?>/anuncio/<?php echo (int)($fav['id'] ?? 0); ?>/"
                                   class="btn btn-sm btn-outline-primary w-100">Ver anúncio</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <!-- ───── ABA ALERTAS ───── -->
    <?php elseif ($aba === 'alertas'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 fw-bold mb-0">Meus Alertas</h2>
            <a href="<?php echo BASE_URL; ?>/alertas" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus me-1"></i>Gerenciar alertas
            </a>
        </div>

        <?php if (empty($alertas)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fa-solid fa-bell-slash fa-3x mb-3 d-block opacity-25"></i>
                <p>Nenhum alerta configurado.</p>
                <a href="<?php echo BASE_URL; ?>/alertas" class="btn btn-outline-primary">Criar alerta</a>
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($alertas as $alerta): ?>
                    <div class="list-group-item d-flex align-items-center gap-3 py-3">
                        <i class="fa-solid fa-bell text-warning fa-lg"></i>
                        <div class="flex-grow-1">
                            <strong>
                                <?php echo sanitize($alerta['cidade']); ?> - <?php echo sanitize($alerta['estado']); ?>
                                (<?php echo (int)($alerta['raio_km'] ?? 10); ?> km)
                            </strong>
                            <div class="text-muted small">
                                <?php echo $alerta['especie'] ? 'Espécie: ' . sanitize($alerta['especie']) : 'Qualquer espécie'; ?>
                                <?php echo $alerta['tipo'] && $alerta['tipo'] !== 'ambos' ? ' · Tipo: ' . sanitize($alerta['tipo']) : ''; ?>
                            </div>
                        </div>
                        <span class="badge <?php echo $alerta['ativo'] ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo $alerta['ativo'] ? 'Ativo' : 'Pausado'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <!-- ───── ABA CONFIGURAÇÕES ───── -->
    <?php elseif ($aba === 'configuracoes'): ?>
        <div class="row g-4">
            <!-- Dados pessoais -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4"><i class="fa-solid fa-user me-2"></i>Dados pessoais</h5>
                        <form method="POST" action="?aba=configuracoes">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="acao" value="atualizar_perfil">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nome completo</label>
                                <input type="text" class="form-control" name="nome"
                                       value="<?php echo sanitize($usuario['nome'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">E-mail</label>
                                <input type="email" class="form-control" value="<?php echo sanitize($usuario['email'] ?? ''); ?>" disabled>
                                <div class="form-text">O e-mail não pode ser alterado.</div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Telefone</label>
                                <input type="tel" class="form-control" name="telefone"
                                       value="<?php echo sanitize($usuario['telefone'] ?? ''); ?>"
                                       placeholder="(00) 00000-0000">
                            </div>
                            <button type="submit" class="btn btn-primary">Salvar alterações</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Alterar senha -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4"><i class="fa-solid fa-lock me-2"></i>Alterar senha</h5>
                        <form method="POST" action="?aba=configuracoes">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="acao" value="alterar_senha">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Senha atual</label>
                                <input type="password" class="form-control" name="senha_atual" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nova senha</label>
                                <input type="password" class="form-control" name="nova_senha"
                                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Confirmar nova senha</label>
                                <input type="password" class="form-control" name="confirma" required>
                            </div>
                            <button type="submit" class="btn btn-danger">Alterar senha</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
