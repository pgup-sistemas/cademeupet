<?php
$pageTitle       = 'Política de Cookies — Cadê Meu Pet?';
$dataAtualizacao = '17 de junho de 2026';
require_once __DIR__ . '/../../config.php';
$breadcrumbs = [
    ['label' => 'Início',              'url' => BASE_URL],
    ['label' => 'Política de Cookies'],
];
include __DIR__ . '/../../includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">

        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm legal-sidebar">
                <div class="card-header bg-transparent border-0 pt-3 pb-1">
                    <span class="small fw-bold text-muted text-uppercase">Páginas legais</span>
                </div>
                <div class="list-group list-group-flush rounded-bottom">
                    <a href="<?php echo BASE_URL; ?>/politica-privacidade" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-shield-halved me-2"></i>Política de Privacidade
                    </a>
                    <a href="<?php echo BASE_URL; ?>/termos-uso" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-file-contract me-2"></i>Termos de Uso
                    </a>
                    <a href="<?php echo BASE_URL; ?>/politica-cookies" class="list-group-item list-group-item-action active">
                        <i class="fa-solid fa-cookie-bite me-2"></i>Política de Cookies
                    </a>
                    <a href="<?php echo BASE_URL; ?>/lgpd" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-scale-balanced me-2"></i>LGPD
                    </a>
                    <a href="<?php echo BASE_URL; ?>/contato-dpo" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-envelope-circle-check me-2"></i>Falar com DPO
                    </a>
                </div>
            </div>
        </div>

        <!-- Conteúdo -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-lg-5">

                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:52px;height:52px;background:#f39c12;color:#fff;">
                            <i class="fa-solid fa-cookie-bite fa-lg"></i>
                        </div>
                        <div>
                            <h1 class="h3 fw-bold mb-1">Política de Cookies</h1>
                            <p class="text-muted small mb-0">
                                <i class="fa-regular fa-calendar me-1"></i>Última atualização: <?php echo $dataAtualizacao; ?>
                            </p>
                        </div>
                    </div>

                    <!-- 1 -->
                    <h2 class="h5 fw-bold mt-4 mb-3"><span class="badge" style="background:#f39c12;">1</span> O que são Cookies?</h2>
                    <p>
                        Cookies são pequenos arquivos de texto que um site armazena no seu navegador quando você o visita.
                        Eles permitem que o site lembre suas ações e preferências ao longo do tempo, evitando que você
                        precise informar os mesmos dados repetidamente.
                    </p>

                    <!-- 2 -->
                    <h2 class="h5 fw-bold mt-5 mb-3"><span class="badge" style="background:#f39c12;">2</span> Cookies que Utilizamos</h2>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Categoria</th>
                                    <th>Nome / Origem</th>
                                    <th>Finalidade</th>
                                    <th>Validade</th>
                                    <th>Necessário?</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge bg-danger">Essencial</span></td>
                                    <td><code>PHPSESSID</code></td>
                                    <td>Mantém sua sessão de login ativa</td>
                                    <td>Sessão</td>
                                    <td><i class="fa-solid fa-check text-success"></i> Sim</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-danger">Essencial</span></td>
                                    <td><code>csrf_token</code></td>
                                    <td>Protege formulários contra ataques CSRF</td>
                                    <td>Sessão</td>
                                    <td><i class="fa-solid fa-check text-success"></i> Sim</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-warning text-dark">Preferência</span></td>
                                    <td><code>cookie_consent</code></td>
                                    <td>Lembra que você aceitou os cookies</td>
                                    <td>1 ano</td>
                                    <td><i class="fa-solid fa-xmark text-danger"></i> Não</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-warning text-dark">Preferência</span></td>
                                    <td><code>donation_dismissed</code></td>
                                    <td>Lembra que você dispensou o modal de doação</td>
                                    <td>30 dias</td>
                                    <td><i class="fa-solid fa-xmark text-danger"></i> Não</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-info text-dark">Funcional</span></td>
                                    <td>Leaflet / OSM</td>
                                    <td>Exibe mapas interativos (tiles do OpenStreetMap)</td>
                                    <td>Sessão</td>
                                    <td><i class="fa-solid fa-xmark text-danger"></i> Não</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-secondary">CDN</span></td>
                                    <td>Bootstrap / FontAwesome CDN</td>
                                    <td>Carrega recursos de estilo e ícones via CDN</td>
                                    <td>Cache navegador</td>
                                    <td><i class="fa-solid fa-xmark text-danger"></i> Não</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Não utilizamos cookies de rastreamento de terceiros para publicidade (Google Ads, Facebook Pixel, etc.).
                    </p>

                    <!-- 3 -->
                    <h2 class="h5 fw-bold mt-5 mb-3"><span class="badge" style="background:#f39c12;">3</span> Cookies Essenciais</h2>
                    <p>
                        Os cookies essenciais são <strong>indispensáveis para o funcionamento do site</strong>.
                        Sem eles, funcionalidades como login, formulários seguros e navegação entre páginas
                        autenticadas não funcionam. Esses cookies <strong>não requerem consentimento</strong>
                        (base legal: legítimo interesse / execução de contrato — LGPD art. 7º, V e IX).
                    </p>

                    <!-- 4 -->
                    <h2 class="h5 fw-bold mt-5 mb-3"><span class="badge" style="background:#f39c12;">4</span> Como Gerenciar Cookies</h2>
                    <p>Você pode controlar cookies não essenciais de três formas:</p>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card border-0 bg-light h-100 text-center p-3">
                                <i class="fa-solid fa-toggle-off fa-2x text-warning mb-2"></i>
                                <div class="fw-semibold small mb-1">Banner de consentimento</div>
                                <div class="text-muted" style="font-size:.75rem;">
                                    Ao acessar o site pela primeira vez, um banner permite aceitar ou recusar cookies não essenciais.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 bg-light h-100 text-center p-3">
                                <i class="fa-solid fa-gear fa-2x text-warning mb-2"></i>
                                <div class="fw-semibold small mb-1">Configurações do navegador</div>
                                <div class="text-muted" style="font-size:.75rem;">
                                    Você pode bloquear ou excluir cookies diretamente nas configurações do seu navegador.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 bg-light h-100 text-center p-3">
                                <i class="fa-solid fa-trash-can fa-2x text-warning mb-2"></i>
                                <div class="fw-semibold small mb-1">Revogar consentimento</div>
                                <div class="text-muted" style="font-size:.75rem;">
                                    Clique no botão abaixo para redefinir suas preferências de cookies a qualquer momento.
                                </div>
                                <button id="btn-resetar-cookies" class="btn btn-outline-warning btn-sm mt-2">
                                    <i class="fa-solid fa-rotate-left me-1"></i>Redefinir preferências
                                </button>
                            </div>
                        </div>
                    </div>

                    <h5 class="fw-semibold">Gerenciar nos navegadores</h5>
                    <ul>
                        <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Google Chrome</a></li>
                        <li><a href="https://support.mozilla.org/pt-BR/kb/gerencie-configuracoes-de-armazenamento-local-de-s" target="_blank" rel="noopener">Mozilla Firefox</a></li>
                        <li><a href="https://support.apple.com/pt-br/guide/safari/sfri11471/mac" target="_blank" rel="noopener">Apple Safari</a></li>
                        <li><a href="https://support.microsoft.com/pt-br/microsoft-edge/excluir-cookies-no-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" rel="noopener">Microsoft Edge</a></li>
                    </ul>

                    <!-- 5 -->
                    <h2 class="h5 fw-bold mt-5 mb-3"><span class="badge" style="background:#f39c12;">5</span> Alterações nesta Política</h2>
                    <p>
                        Podemos atualizar esta Política de Cookies periodicamente.
                        Mudanças serão comunicadas pelo banner de consentimento e publicadas nesta página.
                    </p>

                    <!-- 6 -->
                    <h2 class="h5 fw-bold mt-5 mb-3"><span class="badge" style="background:#f39c12;">6</span> Contato</h2>
                    <p>Dúvidas sobre o uso de cookies? Fale com nosso DPO:</p>
                    <a href="<?php echo BASE_URL; ?>/contato-dpo" class="btn btn-warning text-dark fw-semibold">
                        <i class="fa-solid fa-envelope-circle-check me-2"></i>Falar com o DPO
                    </a>

                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.getElementById('btn-resetar-cookies').addEventListener('click', function () {
    localStorage.removeItem('cookie_consent');
    location.reload();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
