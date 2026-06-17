<?php
$pageTitle       = 'LGPD — Lei Geral de Proteção de Dados — Cadê Meu Pet?';
$dataAtualizacao = '17 de junho de 2026';
require_once __DIR__ . '/../../config.php';
include __DIR__ . '/../../includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">

        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm sticky-top" style="top:80px;">
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
                    <a href="<?php echo BASE_URL; ?>/politica-cookies" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-cookie-bite me-2"></i>Política de Cookies
                    </a>
                    <a href="<?php echo BASE_URL; ?>/lgpd" class="list-group-item list-group-item-action active">
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
                             style="width:52px;height:52px;background:#6c5ce7;color:#fff;">
                            <i class="fa-solid fa-scale-balanced fa-lg"></i>
                        </div>
                        <div>
                            <h1 class="h3 fw-bold mb-1">LGPD — Lei Geral de Proteção de Dados</h1>
                            <p class="text-muted small mb-0">
                                <i class="fa-regular fa-calendar me-1"></i>Última atualização: <?php echo $dataAtualizacao; ?>
                            </p>
                        </div>
                    </div>

                    <!-- 1 -->
                    <h2 class="h5 fw-bold mt-4 mb-3">
                        <span class="badge" style="background:#6c5ce7;">1</span> Sobre a Lei
                    </h2>
                    <p>
                        A <strong>Lei Geral de Proteção de Dados (Lei nº 13.709/2018 — LGPD)</strong>
                        é a legislação brasileira que regula como organizações públicas e privadas devem
                        coletar, armazenar, tratar e compartilhar dados pessoais de cidadãos brasileiros.
                    </p>
                    <p>
                        Ela foi inspirada no <em>Regulamento Geral de Proteção de Dados</em> europeu (GDPR),
                        entrou em vigor em setembro de 2020 e é fiscalizada pela
                        <strong>ANPD — Autoridade Nacional de Proteção de Dados</strong>.
                    </p>

                    <!-- 2 -->
                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge" style="background:#6c5ce7;">2</span> Nosso Papel como Controlador
                    </h2>
                    <p>
                        O <strong>Cadê Meu Pet?</strong> atua como <strong>Controlador de Dados</strong> —
                        ou seja, somos os responsáveis pelas decisões sobre o tratamento dos dados pessoais
                        de nossos usuários.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <tr><th style="width:35%">Controlador</th><td>Cadê Meu Pet? — PageUp Sistemas</td></tr>
                            <tr><th>Encarregado (DPO)</th><td>Disponível via <a href="<?php echo BASE_URL; ?>/contato-dpo">formulário de contato</a></td></tr>
                            <tr><th>E-mail de privacidade</th><td>privacidade@cademeupet.com.br</td></tr>
                            <tr><th>Prazo de resposta</th><td>Até 15 dias úteis</td></tr>
                        </table>
                    </div>

                    <!-- 3 -->
                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge" style="background:#6c5ce7;">3</span> Bases Legais que Utilizamos
                    </h2>
                    <p>A LGPD exige que o tratamento de dados tenha uma base legal. Utilizamos as seguintes (art. 7º):</p>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Base legal</th>
                                    <th>Artigo</th>
                                    <th>Quando aplicamos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Execução de contrato</strong></td>
                                    <td>Art. 7º, V</td>
                                    <td>Cadastro, publicação de anúncios, processamento de doações</td>
                                </tr>
                                <tr>
                                    <td><strong>Consentimento</strong></td>
                                    <td>Art. 7º, I</td>
                                    <td>Newsletters, comunicações de marketing, cookies não essenciais</td>
                                </tr>
                                <tr>
                                    <td><strong>Legítimo interesse</strong></td>
                                    <td>Art. 7º, IX</td>
                                    <td>Segurança do sistema, análise de uso, prevenção a fraudes</td>
                                </tr>
                                <tr>
                                    <td><strong>Obrigação legal</strong></td>
                                    <td>Art. 7º, II</td>
                                    <td>Retenção de registros fiscais e logs de acesso (Marco Civil)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 4 -->
                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge" style="background:#6c5ce7;">4</span> Seus Direitos como Titular (art. 18)
                    </h2>
                    <p>A LGPD garante a você os seguintes direitos, que podem ser exercidos a qualquer momento:</p>

                    <div class="row g-3 mb-4">
                        <?php
                        $direitos = [
                            ['fa-eye',               '#0984e3', 'I — Confirmação e Acesso',
                             'Confirmar se tratamos seus dados e receber uma cópia deles.'],
                            ['fa-pen-to-square',      '#00b894', 'II — Correção',
                             'Corrigir dados incompletos, inexatos ou desatualizados.'],
                            ['fa-eraser',             '#e17055', 'III — Anonimização ou Eliminação',
                             'Anonimizar, bloquear ou apagar dados desnecessários ou excessivos.'],
                            ['fa-file-export',        '#6c5ce7', 'IV — Portabilidade',
                             'Receber seus dados em formato interoperável para outro serviço.'],
                            ['fa-trash-can',          '#d63031', 'V — Eliminação por consentimento',
                             'Solicitar a exclusão de dados tratados com base no seu consentimento.'],
                            ['fa-circle-info',        '#fdcb6e', 'VI — Informação sobre compartilhamento',
                             'Saber com quais terceiros seus dados foram compartilhados.'],
                            ['fa-hand-point-up',      '#00cec9', 'VII — Informação sobre recusa',
                             'Ser informado sobre a possibilidade de não dar consentimento e as consequências.'],
                            ['fa-toggle-off',         '#636e72', 'VIII — Revogação do consentimento',
                             'Revogar o consentimento a qualquer momento, de forma gratuita e facilitada.'],
                            ['fa-robot',              '#74b9ff', 'IX — Revisão de decisões automatizadas',
                             'Questionar decisões tomadas exclusivamente por algoritmos sem revisão humana.'],
                        ];
                        foreach ($direitos as $d):
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="fa-solid <?php echo $d[0]; ?> fa-lg" style="color:<?php echo $d[1]; ?>;"></i>
                                        <span class="fw-semibold small"><?php echo $d[2]; ?></span>
                                    </div>
                                    <p class="text-muted mb-0" style="font-size:.75rem;"><?php echo $d[3]; ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- 5 -->
                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge" style="background:#6c5ce7;">5</span> Como Exercer Seus Direitos
                    </h2>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4 text-center">
                            <div class="card border-0 h-100 py-4" style="border-left:4px solid #6c5ce7 !important;">
                                <i class="fa-solid fa-envelope fa-2x mb-3" style="color:#6c5ce7;"></i>
                                <div class="fw-semibold">E-mail</div>
                                <div class="small text-muted">privacidade@cademeupet.com.br</div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="card border-0 h-100 py-4" style="border-left:4px solid #6c5ce7 !important;">
                                <i class="fa-solid fa-wpforms fa-2x mb-3" style="color:#6c5ce7;"></i>
                                <div class="fw-semibold">Formulário online</div>
                                <a href="<?php echo BASE_URL; ?>/contato-dpo" class="small">Acessar formulário</a>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="card border-0 h-100 py-4" style="border-left:4px solid #6c5ce7 !important;">
                                <i class="fa-solid fa-clock fa-2x mb-3" style="color:#6c5ce7;"></i>
                                <div class="fw-semibold">Prazo de resposta</div>
                                <div class="small text-muted">Até 15 dias úteis</div>
                            </div>
                        </div>
                    </div>
                    <p class="small text-muted">
                        Pode ser necessário verificar sua identidade antes de atender a solicitação,
                        para proteção dos próprios dados.
                    </p>

                    <!-- 6 -->
                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge" style="background:#6c5ce7;">6</span> Segurança e Medidas Adotadas
                    </h2>
                    <ul>
                        <li>Senhas armazenadas com <strong>bcrypt</strong> (sem possibilidade de reversão)</li>
                        <li>Comunicação criptografada via <strong>HTTPS/TLS 1.2+</strong></li>
                        <li>Tokens CSRF em todos os formulários</li>
                        <li>Separação de ambientes (desenvolvimento vs. produção)</li>
                        <li>Acesso ao banco de dados restrito por IP e credenciais separadas</li>
                        <li>Revisões periódicas de segurança do código</li>
                    </ul>

                    <!-- 7 -->
                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge" style="background:#6c5ce7;">7</span> Violação de Dados (Data Breach)
                    </h2>
                    <p>
                        Em caso de incidente de segurança que comprometa dados pessoais,
                        notificaremos a <strong>ANPD</strong> e os titulares afetados dentro de
                        <strong>72 horas</strong> da ciência do incidente, conforme art. 48 da LGPD,
                        descrevendo a natureza dos dados afetados, as medidas tomadas e os riscos envolvidos.
                    </p>

                    <!-- 8 -->
                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge" style="background:#6c5ce7;">8</span> ANPD — Autoridade Nacional
                    </h2>
                    <p>
                        Se entender que seus direitos não foram atendidos adequadamente pelo Cadê Meu Pet?,
                        você pode registrar uma reclamação junto à
                        <strong>Autoridade Nacional de Proteção de Dados (ANPD)</strong>:
                    </p>
                    <a href="https://www.gov.br/anpd" target="_blank" rel="noopener"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>
                        gov.br/anpd
                    </a>

                    <!-- 9 -->
                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge" style="background:#6c5ce7;">9</span> Falar com o DPO
                    </h2>
                    <p>Para qualquer solicitação relacionada à LGPD:</p>
                    <a href="<?php echo BASE_URL; ?>/contato-dpo" class="btn btn-primary">
                        <i class="fa-solid fa-envelope-circle-check me-2"></i>Falar com o DPO
                    </a>

                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
