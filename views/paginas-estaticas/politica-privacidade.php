<?php
$pageTitle      = 'Política de Privacidade — Cadê Meu Pet?';
$dataAtualizacao = '17 de junho de 2026';
require_once __DIR__ . '/../../config.php';
include __DIR__ . '/../../includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">

        <!-- Sidebar de navegação legal -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm legal-sidebar">
                <div class="card-header bg-transparent border-0 pt-3 pb-1">
                    <span class="small fw-bold text-muted text-uppercase">Páginas legais</span>
                </div>
                <div class="list-group list-group-flush rounded-bottom">
                    <a href="<?php echo BASE_URL; ?>/politica-privacidade"
                       class="list-group-item list-group-item-action active">
                        <i class="fa-solid fa-shield-halved me-2"></i>Política de Privacidade
                    </a>
                    <a href="<?php echo BASE_URL; ?>/termos-uso"
                       class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-file-contract me-2"></i>Termos de Uso
                    </a>
                    <a href="<?php echo BASE_URL; ?>/politica-cookies"
                       class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-cookie-bite me-2"></i>Política de Cookies
                    </a>
                    <a href="<?php echo BASE_URL; ?>/lgpd"
                       class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-scale-balanced me-2"></i>LGPD
                    </a>
                    <a href="<?php echo BASE_URL; ?>/contato-dpo"
                       class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-envelope-circle-check me-2"></i>Falar com DPO
                    </a>
                </div>
            </div>
        </div>

        <!-- Conteúdo principal -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-lg-5">

                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:52px;height:52px;background:var(--cmp-primary);color:#fff;">
                            <i class="fa-solid fa-shield-halved fa-lg"></i>
                        </div>
                        <div>
                            <h1 class="h3 fw-bold mb-1">Política de Privacidade</h1>
                            <p class="text-muted small mb-0">
                                <i class="fa-regular fa-calendar me-1"></i>Última atualização: <?php echo $dataAtualizacao; ?>
                            </p>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 mb-4" role="alert">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        Esta política explica como o <strong>Cadê Meu Pet?</strong> coleta, usa, armazena e protege
                        seus dados pessoais, em conformidade com a
                        <strong>Lei Geral de Proteção de Dados (Lei nº 13.709/2018 — LGPD)</strong>.
                    </div>

                    <h2 class="h5 fw-bold mt-4 mb-3">
                        <span class="badge bg-primary me-2">1</span>Controlador dos Dados
                    </h2>
                    <p>O responsável pelo tratamento dos seus dados pessoais é:</p>
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm">
                            <tr><th style="width:35%">Nome</th><td>Cadê Meu Pet? — PageUp Sistemas</td></tr>
                            <tr><th>E-mail</th><td>privacidade@cademeupet.com.br</td></tr>
                            <tr><th>Encarregado (DPO)</th><td>Disponível em <a href="<?php echo BASE_URL; ?>/contato-dpo">Contato DPO</a></td></tr>
                        </table>
                    </div>

                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge bg-primary me-2">2</span>Quais Dados Coletamos
                    </h2>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold"><i class="fa-solid fa-user text-primary me-2"></i>Dados de Cadastro</h6>
                                    <ul class="mb-0 small">
                                        <li>Nome completo</li>
                                        <li>Endereço de e-mail</li>
                                        <li>Número de telefone/WhatsApp</li>
                                        <li>Cidade e estado</li>
                                        <li>Senha (armazenada com hash bcrypt)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold"><i class="fa-solid fa-paw text-primary me-2"></i>Dados dos Pets</h6>
                                    <ul class="mb-0 small">
                                        <li>Nome, espécie, raça, porte</li>
                                        <li>Fotos dos animais</li>
                                        <li>Coordenadas geográficas (localização do avistamento)</li>
                                        <li>Descrição e características</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold"><i class="fa-solid fa-credit-card text-primary me-2"></i>Dados Financeiros</h6>
                                    <ul class="mb-0 small">
                                        <li>Valor e data das doações realizadas</li>
                                        <li>Dados de pagamento <strong>não</strong> são armazenados por nós — processados pela Efí Bank</li>
                                        <li>ID de transação para comprovante</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold"><i class="fa-solid fa-globe text-primary me-2"></i>Dados de Navegação</h6>
                                    <ul class="mb-0 small">
                                        <li>Endereço IP (anonimizado)</li>
                                        <li>Tipo e versão do navegador</li>
                                        <li>Páginas visitadas e tempo de sessão</li>
                                        <li>Cookies de sessão e preferências</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge bg-primary me-2">3</span>Base Legal para o Tratamento (LGPD, art. 7º)
                    </h2>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm align-middle">
                            <thead class="table-dark">
                                <tr><th>Finalidade</th><th>Base Legal</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Criação e gerenciamento de conta</td><td>Execução de contrato (art. 7º, V)</td></tr>
                                <tr><td>Exibição de anúncios de pets perdidos/encontrados</td><td>Execução de contrato (art. 7º, V)</td></tr>
                                <tr><td>Envio de e-mails transacionais (confirmação, alerta)</td><td>Execução de contrato (art. 7º, V)</td></tr>
                                <tr><td>Processamento de doações</td><td>Execução de contrato (art. 7º, V)</td></tr>
                                <tr><td>Envio de newsletters e comunicações de marketing</td><td>Consentimento (art. 7º, I)</td></tr>
                                <tr><td>Melhoria do serviço via análise de uso</td><td>Legítimo interesse (art. 7º, IX)</td></tr>
                                <tr><td>Cumprimento de obrigações legais</td><td>Obrigação legal (art. 7º, II)</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge bg-primary me-2">4</span>Compartilhamento de Dados
                    </h2>
                    <p>Não vendemos, alugamos ou negociamos seus dados pessoais. Podemos compartilhá-los somente nas seguintes situações:</p>
                    <ul>
                        <li><strong>Operadores de pagamento:</strong> Efí Bank (Gerencianet) para processar doações.</li>
                        <li><strong>Infraestrutura de hospedagem:</strong> servidor onde o site está hospedado, com cláusulas de confidencialidade.</li>
                        <li><strong>Obrigação legal:</strong> quando exigido por lei, mandado judicial ou autoridade competente.</li>
                        <li><strong>Proteção de direitos:</strong> para prevenir fraudes ou proteger a segurança de usuários.</li>
                    </ul>

                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge bg-primary me-2">5</span>Período de Retenção dos Dados
                    </h2>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr><th>Tipo de dado</th><th>Período de retenção</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Conta de usuário ativa</td><td>Enquanto a conta estiver ativa</td></tr>
                                <tr><td>Conta excluída pelo titular</td><td>Excluída em até 30 dias (exceto obrigações legais)</td></tr>
                                <tr><td>Anúncios expirados ou removidos</td><td>30 dias (para auditoria interna)</td></tr>
                                <tr><td>Registros de doações</td><td>5 anos (obrigação fiscal — Lei nº 6.404/76)</td></tr>
                                <tr><td>Logs de acesso</td><td>6 meses (Marco Civil da Internet, art. 15)</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge bg-primary me-2">6</span>Segurança dos Dados
                    </h2>
                    <ul>
                        <li>Senhas armazenadas com algoritmo <strong>bcrypt</strong> (custo 12)</li>
                        <li>Comunicação criptografada via <strong>HTTPS/TLS</strong></li>
                        <li>Proteção contra CSRF em todos os formulários</li>
                        <li>Sanitização de saída para prevenir XSS</li>
                        <li>Consultas parametrizadas (PDO) para prevenir SQL injection</li>
                        <li>Backup periódico do banco de dados</li>
                    </ul>

                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge bg-primary me-2">7</span>Transferência Internacional de Dados
                    </h2>
                    <p>
                        O Cadê Meu Pet? é hospedado em servidores localizados no <strong>Brasil</strong>.
                        Serviços de CDN externos (Bootstrap, Font Awesome) podem processar dados fora do Brasil.
                        Nesses casos, adotamos cláusulas contratuais padrão conforme art. 33 da LGPD.
                    </p>

                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge bg-primary me-2">8</span>Menores de Idade
                    </h2>
                    <p>
                        Nossos serviços são destinados a pessoas com <strong>18 anos ou mais</strong>.
                        Não coletamos intencionalmente dados de menores. Se tomarmos conhecimento disso,
                        eliminaremos essas informações imediatamente.
                    </p>

                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge bg-primary me-2">9</span>Seus Direitos como Titular
                    </h2>
                    <p>Nos termos da LGPD (art. 18), você pode a qualquer momento solicitar:</p>
                    <div class="row g-2 mb-3">
                        <?php
                        $direitos = [
                            ['fa-eye',           'Acesso',             'Confirmar se tratamos seus dados e receber cópia deles.'],
                            ['fa-pen-to-square', 'Correção',           'Corrigir dados incompletos ou desatualizados.'],
                            ['fa-trash-can',     'Eliminação',         'Apagar dados desnecessários ou tratados com consentimento.'],
                            ['fa-ban',           'Bloqueio',           'Suspender temporariamente o tratamento.'],
                            ['fa-file-export',   'Portabilidade',      'Receber seus dados em formato legível por máquina.'],
                            ['fa-hand',          'Revogação',          'Revogar o consentimento a qualquer momento.'],
                            ['fa-circle-info',   'Informação',         'Saber com quem seus dados foram compartilhados.'],
                            ['fa-robot',         'Revisão automatiz.', 'Questionar decisões tomadas exclusivamente por algoritmos.'],
                        ];
                        foreach ($direitos as $d):
                        ?>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border-0 bg-light text-center h-100 py-3">
                                <i class="fa-solid <?php echo $d[0]; ?> fa-lg text-primary mb-2"></i>
                                <div class="fw-semibold small"><?php echo $d[1]; ?></div>
                                <div class="text-muted" style="font-size:.7rem;margin-top:4px;"><?php echo $d[2]; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p>
                        Para exercer qualquer um desses direitos, acesse nossa
                        <a href="<?php echo BASE_URL; ?>/contato-dpo"><strong>página de contato do DPO</strong></a>.
                        Respondemos em até <strong>15 dias úteis</strong>.
                    </p>

                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge bg-primary me-2">10</span>Cookies
                    </h2>
                    <p>
                        Utilizamos cookies para manter sua sessão e melhorar o serviço.
                        Veja nossa <a href="<?php echo BASE_URL; ?>/politica-cookies">Política de Cookies</a> para detalhes.
                    </p>

                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge bg-primary me-2">11</span>Alterações nesta Política
                    </h2>
                    <p>
                        Podemos atualizar esta Política periodicamente. Quando houver alterações relevantes,
                        notificaremos você por e-mail ou aviso no site.
                    </p>

                    <h2 class="h5 fw-bold mt-5 mb-3">
                        <span class="badge bg-primary me-2">12</span>Contato e DPO
                    </h2>
                    <p>Para dúvidas, solicitações ou reclamações sobre privacidade:</p>
                    <a href="<?php echo BASE_URL; ?>/contato-dpo" class="btn btn-primary">
                        <i class="fa-solid fa-envelope-circle-check me-2"></i>Falar com o DPO
                    </a>
                    <p class="text-muted small mt-3 mb-0">
                        Você também pode registrar reclamação junto à
                        <a href="https://www.gov.br/anpd" target="_blank" rel="noopener">ANPD — Autoridade Nacional de Proteção de Dados</a>.
                    </p>

                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
