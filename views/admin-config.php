<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$pageTitle = 'Admin - Configurações - Cadê Meu Pet?';
$adminCtrl = new AdminController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Erro de validação. Recarregue a página.', MSG_ERROR);
        redirect('/admin/config');
    }
    $erros = $adminCtrl->salvarConfiguracoes($_POST);
    if ($erros) {
        setFlashMessage(implode(' ', $erros), MSG_ERROR);
    } else {
        setFlashMessage('Configurações salvas com sucesso.', MSG_SUCCESS);
    }
    redirect('/admin/config');
}

$config = $adminCtrl->getConfiguracoes();

// Valores padrão
$defaults = [
    'site_nome'                      => 'Cadê Meu Pet?',
    'site_descricao'                 => 'Plataforma de anúncios para pets perdidos e encontrados.',
    'max_anuncios'                   => '5',
    'min_intervalo'                  => '1',
    'expiracao_dias'                 => '30',
    'max_fotos'                      => '5',
    'moderacao_ativa'                => '0',
    'email_contato'                  => '',
    'meta_keywords'                  => 'pet perdido, pet encontrado, adoção de animais',
    'rodape_texto'                   => '',
    'parceiro_plano_basico_mensal'   => '79.90',
    'parceiro_plano_destaque_mensal' => '129.90',
];
foreach ($defaults as $k => $v) {
    if (!isset($config[$k])) {
        $config[$k] = $v;
    }
}

function cfg(array $config, string $key): string {
    return htmlspecialchars($config[$key] ?? '', ENT_QUOTES, 'UTF-8');
}

$breadcrumbs = [
    ['label' => 'Início',        'url' => BASE_URL],
    ['label' => 'Admin',         'url' => BASE_URL . '/admin'],
    ['label' => 'Configurações'],
];
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
        <a href="<?php echo BASE_URL; ?>/admin/parceiros"  class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-handshake"></i></a>
        <a href="<?php echo BASE_URL; ?>/admin/config"     class="btn btn-sm btn-primary"><i class="fa-solid fa-gear"></i></a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0"><i class="fa-solid fa-gear me-2"></i>Configurações do Sistema</h1>
        <a href="<?php echo BASE_URL; ?>/admin" class="btn btn-outline-secondary btn-sm d-none d-lg-inline-flex">
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

                <!-- Planos de Parceiros -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0 pt-4 pb-0">
                        <h5 class="fw-bold mb-0"><i class="fa-solid fa-handshake me-2 text-primary"></i>Planos de Parceiros</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Valores mensais cobrados por plano. O anual é calculado automaticamente (× 12).</p>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Plano Básico (R$/mês)</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" name="parceiro_plano_basico_mensal" class="form-control"
                                           value="<?php echo cfg($config, 'parceiro_plano_basico_mensal'); ?>"
                                           min="0.01" step="0.01" required>
                                </div>
                                <div class="form-text">Anual: R$ <?php echo number_format((float)($config['parceiro_plano_basico_mensal'] ?? 79.90) * 12, 2, ',', '.'); ?></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Plano Destaque (R$/mês)</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" name="parceiro_plano_destaque_mensal" class="form-control"
                                           value="<?php echo cfg($config, 'parceiro_plano_destaque_mensal'); ?>"
                                           min="0.01" step="0.01" required>
                                </div>
                                <div class="form-text">Anual: R$ <?php echo number_format((float)($config['parceiro_plano_destaque_mensal'] ?? 129.90) * 12, 2, ',', '.'); ?></div>
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

    <!-- ═══════════════════════════════════════════════════════
         PAINEL DE WEBHOOK PIX (fora do form de config)
    ══════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-sm mt-5">
        <div class="card-header bg-white d-flex align-items-center gap-2 py-3">
            <i class="fa-solid fa-webhook text-warning fs-5"></i>
            <h5 class="mb-0 fw-bold">Webhook PIX — EFI</h5>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                O EFI precisa saber para qual URL enviar as notificações de pagamento PIX.
                Use os botões abaixo para registrar, consultar ou remover o webhook.<br>
                <strong>URL configurada:</strong>
                <code><?php echo htmlspecialchars((string)EFI_PIX_NOTIFICATION_URL, ENT_QUOTES); ?></code><br>
                <strong>Chave PIX:</strong>
                <code><?php echo htmlspecialchars((string)EFI_PIX_KEY, ENT_QUOTES); ?></code>
            </p>

            <?php if (IS_LOCAL): ?>
            <div class="alert alert-warning py-2 small mb-3">
                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                Ambiente <strong>local</strong>: o EFI não consegue alcançar <code>localhost</code>.
                O botão <em>Registrar</em> falhará. Use um túnel público (ngrok, localtunnel) e atualize
                <code>EFI_PIX_NOTIFICATION_URL</code> no <code>.env</code> antes de registrar.
                Em local, o <strong>polling automático</strong> da página de doação-pix já garante confirmação.
            </div>
            <?php endif; ?>

            <div id="webhookResult" class="mb-3" style="display:none;"></div>

            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-success btn-sm" onclick="webhookAcao('registrar')">
                    <i class="fa-solid fa-plug me-1"></i>Registrar webhook
                </button>
                <button class="btn btn-outline-primary btn-sm" onclick="webhookAcao('consultar')">
                    <i class="fa-solid fa-magnifying-glass me-1"></i>Consultar status
                </button>
                <button class="btn btn-outline-danger btn-sm" onclick="webhookAcao('remover')">
                    <i class="fa-solid fa-trash me-1"></i>Remover webhook
                </button>
            </div>
        </div>
    </div>

    </div><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<script>
const csrfToken = '<?php echo generateCSRFToken(); ?>';
const webhookApiUrl = '<?php echo BASE_URL; ?>/api/webhook-pix-admin';

async function webhookAcao(acao) {
    const resultDiv = document.getElementById('webhookResult');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<div class="alert alert-info py-2 small"><span class="spinner-border spinner-border-sm me-2"></span>Aguarde...</div>';

    try {
        const resp = await fetch(webhookApiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf_token: csrfToken, acao: acao })
        });
        const data = await resp.json();

        if (data.ok) {
            const webhookInfo = data.resposta_efi ? '<pre class="mt-2 mb-0 small bg-light p-2 rounded">' + JSON.stringify(data.resposta_efi, null, 2) + '</pre>' : '';
            resultDiv.innerHTML = '<div class="alert alert-success py-2 small"><i class="fa-solid fa-check me-1"></i><strong>' + acao.charAt(0).toUpperCase() + acao.slice(1) + '</strong> executado com sucesso.' + webhookInfo + '</div>';
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger py-2 small"><i class="fa-solid fa-xmark me-1"></i><strong>Erro:</strong> ' + (data.error || 'desconhecido') + (data.dica ? '<br><em>' + data.dica + '</em>' : '') + '</div>';
        }
    } catch (e) {
        resultDiv.innerHTML = '<div class="alert alert-danger py-2 small"><strong>Erro de rede:</strong> ' + e.message + '</div>';
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
