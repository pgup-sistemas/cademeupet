<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$pageTitle = 'Admin - Configurações - Cadê Meu Pet?';
$db = getDB();

// Processar salvamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Erro de validação. Recarregue a página.', MSG_ERROR);
        redirect('/admin/config');
    }

    $campos = [
        'site_nome'        => ['label' => 'Nome do site',              'type' => 'text',    'max' => 100],
        'site_descricao'   => ['label' => 'Descrição do site',         'type' => 'text',    'max' => 300],
        'max_anuncios'     => ['label' => 'Máx. anúncios por usuário', 'type' => 'int',     'min' => 1, 'max_val' => 50],
        'min_intervalo'    => ['label' => 'Intervalo mínimo (horas)',   'type' => 'int',     'min' => 0, 'max_val' => 168],
        'expiracao_dias'   => ['label' => 'Expiração (dias)',           'type' => 'int',     'min' => 1, 'max_val' => 365],
        'max_fotos'        => ['label' => 'Máx. fotos por anúncio',    'type' => 'int',     'min' => 1, 'max_val' => 20],
        'moderacao_ativa'  => ['label' => 'Moderação ativa',           'type' => 'bool'],
        'email_contato'    => ['label' => 'E-mail de contato',         'type' => 'email',   'max' => 150],
        'meta_keywords'    => ['label' => 'Meta keywords',             'type' => 'text',    'max' => 300],
        'rodape_texto'     => ['label' => 'Texto do rodapé',           'type' => 'text',    'max' => 300],
    ];

    $erros = [];
    $valores = [];

    foreach ($campos as $chave => $cfg) {
        if ($cfg['type'] === 'bool') {
            $valores[$chave] = isset($_POST[$chave]) ? '1' : '0';
        } elseif ($cfg['type'] === 'int') {
            $v = (int)($_POST[$chave] ?? 0);
            if (isset($cfg['min']) && $v < $cfg['min']) {
                $erros[] = $cfg['label'] . " deve ser pelo menos {$cfg['min']}.";
                continue;
            }
            if (isset($cfg['max_val']) && $v > $cfg['max_val']) {
                $erros[] = $cfg['label'] . " não pode exceder {$cfg['max_val']}.";
                continue;
            }
            $valores[$chave] = (string)$v;
        } elseif ($cfg['type'] === 'email') {
            $v = trim($_POST[$chave] ?? '');
            if (!empty($v) && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                $erros[] = $cfg['label'] . ' deve ser um e-mail válido.';
                continue;
            }
            $valores[$chave] = $v;
        } else {
            $v = trim($_POST[$chave] ?? '');
            if (isset($cfg['max']) && mb_strlen($v) > $cfg['max']) {
                $v = mb_substr($v, 0, $cfg['max']);
            }
            $valores[$chave] = $v;
        }
    }

    if ($erros) {
        setFlashMessage(implode(' ', $erros), MSG_ERROR);
    } else {
        foreach ($valores as $chave => $valor) {
            $exists = $db->fetchOne('SELECT chave FROM configuracoes WHERE chave = ?', [$chave]);
            if ($exists) {
                $db->update('configuracoes', ['valor' => $valor], 'chave = ?', [$chave]);
            } else {
                $db->insert('configuracoes', ['chave' => $chave, 'valor' => $valor]);
            }
        }
        setFlashMessage('Configurações salvas com sucesso.', MSG_SUCCESS);
    }

    redirect('/admin/config');
}

// Carregar configurações atuais
$rows = $db->fetchAll('SELECT chave, valor FROM configuracoes');
$config = [];
foreach ($rows as $row) {
    $config[$row['chave']] = $row['valor'];
}

// Valores padrão
$defaults = [
    'site_nome'       => 'Cadê Meu Pet?',
    'site_descricao'  => 'Plataforma de anúncios para pets perdidos e encontrados.',
    'max_anuncios'    => '5',
    'min_intervalo'   => '1',
    'expiracao_dias'  => '30',
    'max_fotos'       => '5',
    'moderacao_ativa' => '0',
    'email_contato'   => '',
    'meta_keywords'   => 'pet perdido, pet encontrado, adoção de animais',
    'rodape_texto'    => '',
];
foreach ($defaults as $k => $v) {
    if (!isset($config[$k])) {
        $config[$k] = $v;
    }
}

function cfg(array $config, string $key): string {
    return htmlspecialchars($config[$key] ?? '', ENT_QUOTES, 'UTF-8');
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0"><i class="fa-solid fa-gear me-2"></i>Configurações do Sistema</h1>
        <a href="<?php echo BASE_URL; ?>/admin" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>Voltar ao Admin
        </a>
    </div>

    <?php $flash = getFlashMessage(); if ($flash): ?>
        <div class="alert alert-<?php echo sanitize($flash['type']); ?> alert-dismissible">
            <?php echo sanitize($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

        <div class="row g-4">

            <!-- Coluna esquerda -->
            <div class="col-lg-6">

                <!-- Informações do site -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-0">
                        <h5 class="fw-bold mb-0"><i class="fa-solid fa-globe me-2 text-primary"></i>Identidade do Site</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nome do site</label>
                            <input type="text" name="site_nome" class="form-control"
                                   value="<?php echo cfg($config, 'site_nome'); ?>"
                                   maxlength="100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descrição</label>
                            <textarea name="site_descricao" class="form-control" rows="2"
                                      maxlength="300"><?php echo cfg($config, 'site_descricao'); ?></textarea>
                            <div class="form-text">Usada nas meta tags de SEO.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Meta keywords</label>
                            <input type="text" name="meta_keywords" class="form-control"
                                   value="<?php echo cfg($config, 'meta_keywords'); ?>"
                                   maxlength="300">
                            <div class="form-text">Separe por vírgulas.</div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Texto do rodapé</label>
                            <input type="text" name="rodape_texto" class="form-control"
                                   value="<?php echo cfg($config, 'rodape_texto'); ?>"
                                   maxlength="300"
                                   placeholder="Ex: Todos os direitos reservados.">
                        </div>
                    </div>
                </div>

                <!-- Contato -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-0">
                        <h5 class="fw-bold mb-0"><i class="fa-solid fa-envelope me-2 text-primary"></i>Contato</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-0">
                            <label class="form-label fw-semibold">E-mail de contato</label>
                            <input type="email" name="email_contato" class="form-control"
                                   value="<?php echo cfg($config, 'email_contato'); ?>"
                                   maxlength="150"
                                   placeholder="contato@cademeupet.com.br">
                            <div class="form-text">Exibido nas páginas institucionais e respostas automáticas.</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Coluna direita -->
            <div class="col-lg-6">

                <!-- Limites de anúncios -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-0">
                        <h5 class="fw-bold mb-0"><i class="fa-solid fa-sliders me-2 text-primary"></i>Limites de Anúncios</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Máx. anúncios por usuário</label>
                                <input type="number" name="max_anuncios" class="form-control"
                                       value="<?php echo cfg($config, 'max_anuncios'); ?>"
                                       min="1" max="50" required>
                                <div class="form-text">Por período de 30 dias.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Intervalo mínimo (horas)</label>
                                <input type="number" name="min_intervalo" class="form-control"
                                       value="<?php echo cfg($config, 'min_intervalo'); ?>"
                                       min="0" max="168" required>
                                <div class="form-text">Entre novos anúncios do mesmo usuário.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Expiração (dias)</label>
                                <input type="number" name="expiracao_dias" class="form-control"
                                       value="<?php echo cfg($config, 'expiracao_dias'); ?>"
                                       min="1" max="365" required>
                                <div class="form-text">Após quantos dias o anúncio expira.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Máx. fotos por anúncio</label>
                                <input type="number" name="max_fotos" class="form-control"
                                       value="<?php echo cfg($config, 'max_fotos'); ?>"
                                       min="1" max="20" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Moderação -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-0">
                        <h5 class="fw-bold mb-0"><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Moderação</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="moderacao_ativa" id="swModeracao"
                                   <?php echo ($config['moderacao_ativa'] ?? '0') === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="swModeracao">
                                Ativar moderação de anúncios
                            </label>
                        </div>
                        <p class="text-muted small mb-0">
                            Quando ativa, novos anúncios ficam com status <strong>pendente</strong> até serem aprovados por um administrador.
                            Quando desativada, anúncios são publicados diretamente como <strong>aprovado</strong>.
                        </p>
                    </div>
                </div>

                <!-- Preview configurações atuais -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pt-4 pb-0">
                        <h5 class="fw-bold mb-0"><i class="fa-solid fa-circle-info me-2 text-muted"></i>Resumo atual</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-1"><i class="fa-solid fa-check text-success me-2"></i>
                                Até <strong><?php echo cfg($config, 'max_anuncios'); ?></strong> anúncios por usuário
                            </li>
                            <li class="mb-1"><i class="fa-solid fa-check text-success me-2"></i>
                                Expiram em <strong><?php echo cfg($config, 'expiracao_dias'); ?></strong> dias
                            </li>
                            <li class="mb-1"><i class="fa-solid fa-check text-success me-2"></i>
                                Máximo de <strong><?php echo cfg($config, 'max_fotos'); ?></strong> fotos por anúncio
                            </li>
                            <li class="mb-0"><i class="fa-solid fa-<?php echo ($config['moderacao_ativa'] ?? '0') === '1' ? 'shield-halved text-warning' : 'circle-check text-success'; ?> me-2"></i>
                                Moderação: <strong><?php echo ($config['moderacao_ativa'] ?? '0') === '1' ? 'Ativada' : 'Desativada'; ?></strong>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>

        <!-- Botões de ação -->
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-2"></i>Salvar Configurações
            </button>
            <a href="<?php echo BASE_URL; ?>/admin" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
