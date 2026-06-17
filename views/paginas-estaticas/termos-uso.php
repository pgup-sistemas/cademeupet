<?php
$pageTitle       = 'Termos de Uso — Cadê Meu Pet?';
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
                    <a href="<?php echo BASE_URL; ?>/termos-uso" class="list-group-item list-group-item-action active">
                        <i class="fa-solid fa-file-contract me-2"></i>Termos de Uso
                    </a>
                    <a href="<?php echo BASE_URL; ?>/politica-cookies" class="list-group-item list-group-item-action">
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
                             style="width:52px;height:52px;background:var(--cmp-secondary);color:#fff;">
                            <i class="fa-solid fa-file-contract fa-lg"></i>
                        </div>
                        <div>
                            <h1 class="h3 fw-bold mb-1">Termos de Uso</h1>
                            <p class="text-muted small mb-0">
                                <i class="fa-regular fa-calendar me-1"></i>Última atualização: <?php echo $dataAtualizacao; ?>
                            </p>
                        </div>
                    </div>

                    <div class="alert alert-warning border-0 mb-4">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        Ao utilizar o <strong>Cadê Meu Pet?</strong>, você declara que leu, compreendeu e concorda
                        integralmente com estes Termos de Uso. Caso não concorde, por favor, não utilize o serviço.
                    </div>

                    <!-- 1 -->
                    <h2 class="h5 fw-bold mt-4 mb-3"><span class="badge bg-secondary me-2">1</span>Sobre o Serviço</h2>
                    <p>
                        O <strong>Cadê Meu Pet?</strong> é uma plataforma gratuita destinada a ajudar tutores a
                        reencontrar animais de estimação perdidos, divulgar animais encontrados e facilitar
                        adoções responsáveis. O serviço também inclui o módulo <strong>Pet Love</strong>,
                        voltado à reprodução responsável de pets com tutores que buscam cruzamentos planejados.
                    </p>

                    <!-- 2 -->
                    <h2 class="h5 fw-bold mt-5 mb-3"><span class="badge bg-secondary me-2">2</span>Cadastro e Conta</h2>
                    <ul>
                        <li>Para publicar anúncios ou utilizar funcionalidades completas, é necessário criar uma conta com dados verídicos.</li>
                        <li>Você é responsável por manter a confidencialidade da sua senha e por todas as atividades realizadas na sua conta.</li>
                        <li>O e-mail informado deve ser válido e acessível — utilizamos para comunicações importantes.</li>
                        <li>É proibido criar múltiplas contas para burlar limites ou penalidades aplicadas.</li>
                        <li>O Cadê Meu Pet? pode suspender ou encerrar contas que violem estes Termos.</li>
                    </ul>

                    <!-- 3 -->
                    <h2 class="h5 fw-bold mt-5 mb-3"><span class="badge bg-secondary me-2">3</span>Publicação de Anúncios</h2>
                    <p>Ao publicar um anúncio, você declara e garante que:</p>
                    <ul>
                        <li>As informações são <strong>verdadeiras e precisas</strong> — dados falsos podem prejudicar outras pessoas.</li>
                        <li>Você tem o direito de publicar o anúncio (é o tutor, encontrou o animal ou está auxiliando o tutor).</li>
                        <li>As fotos enviadas são de sua autoria ou você tem autorização para utilizá-las.</li>
                        <li>O anúncio não contém informações de contato falsas ou números que possam induzir a golpes.</li>
                    </ul>
                    <p>O Cadê Meu Pet? reserva-se o direito de remover anúncios que violem estas regras,
                       sem aviso prévio.</p>

                    <!-- 4 -->
                    <h2 class="h5 fw-bold mt-5 mb-3"><span class="badge bg-secondary me-2">4</span>Módulo Pet Love (Cruzamentos)</h2>
                    <p>O Pet Love conecta tutores interessados em cruzamentos responsáveis. Ao cadastrar um pet neste módulo, você concorda que:</p>
                    <ul>
                        <li>O pet possui saúde comprovada, vacinas em dia e está apto ao cruzamento.</li>
                        <li>Você agirá com responsabilidade na seleção de parceiros e nos acordos com outros tutores.</li>
                        <li>O Cadê Meu Pet? atua apenas como intermediário — não é parte nos acordos entre tutores e
                            não se responsabiliza por ninhadas, custos veterinários ou desentendimentos entre as partes.</li>
                        <li>É proibido o uso do Pet Love para fins comerciais ilegais, venda irregular de animais
                            ou qualquer prática que contrarie a Lei de Crimes Ambientais (Lei nº 9.605/98).</li>
                    </ul>

                    <!-- 5 -->
                    <h2 class="h5 fw-bold mt-5 mb-3"><span class="badge bg-secondary me-2">5</span>Doações</h2>
                    <ul>
                        <li>As doações são voluntárias, realizadas via PIX ou cartão de crédito, e processadas pela <strong>Efí Bank (Gerencianet)</strong>.</li>
                        <li>Doações confirmadas <strong>não são reembolsáveis</strong>, salvo erro comprovado no processamento.</li>
                        <li>O valor arrecadado é destinado à manutenção da plataforma.</li>
                        <li>Um comprovante é enviado por e-mail ao doador após a confirmação do pagamento.</li>
                        <li>O relatório de transparência financeira está disponível em <a href="<?php echo BASE_URL; ?>/transparencia">Transparência</a>.</li>
                    </ul>

                    <!-- 6 -->
                    <h2 class="h5 fw-bold mt-5 mb-3"><span class="badge bg-secondary me-2">6</span>Conteúdo Proibido</h2>
                    <p>É estritamente proibido utilizar o Cadê Meu Pet? para:</p>
                    <div class="row g-2">
                        <?php
                        $proibidos = [
                            'Publicar anúncios falsos ou enganosos',
                            'Comercializar animais de forma irregular',
                            'Usar o sistema para spam ou phishing',
                            'Carregar malware ou código malicioso',
                            'Coletar dados de outros usuários sem consentimento',
                            'Assediar, ameaçar ou discriminar outros usuários',
                            'Contornar medidas de segurança do sistema',
                            'Publicar conteúdo que viole direitos de terceiros',
                        ];
                        foreach ($proibidos as $p):
                        ?>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start gap-2 p-2 bg-light rounded">
                                <i class="fa-solid fa-circle-xmark text-danger mt-1 flex-shrink-0"></i>
                                <span class="small"><?php echo $p; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- 7 -->
                    <h2 class="h5 fw-bold mt-5 mb-3"><span class="badge bg-secondary me-2">7</span>Propriedade Intelectual</h2>
                    <p>
                        Todo o conteúdo criado pelo Cadê Meu Pet? — textos, design, código, marca, logotipo e ícones —
                        é protegido por direitos autorais e de marca. Você pode reproduzir anúncios específicos
                        para fins de busca de pets, desde que cite a fonte e preserve os créditos.
                        Qualquer outro uso comercial requer autorização prévia por escrito.
                    </p>
                    <p>
                        Os conteúdos publicados pelos usuários (fotos, descrições) permanecem de propriedade do usuário.
                        Ao publicar, você nos concede licença não exclusiva, gratuita e mundial para exibir e
                        reproduzir esse conteúdo no contexto do serviço.
                    </p>

                    <!-- 8 -->
                    <h2 class="h5 fw-bold mt-5 mb-3"><span class="badge bg-secondary me-2">8</span>Limitação de Responsabilidade</h2>
                    <p>O Cadê Meu Pet? <strong>não se responsabiliza</strong> por:</p>
                    <ul>
                        <li>Veracidade das informações publicadas pelos usuários.</li>
                        <li>Acordos, negociações ou encontros realizados entre usuários fora da plataforma.</li>
                        <li>Saúde, comportamento ou destino dos animais após o contato entre tutores.</li>
                        <li>Danos indiretos, lucros cessantes ou danos consequentes decorrentes do uso do serviço.</li>
                        <li>Indisponibilidade temporária do serviço por manutenção ou falhas técnicas.</li>
                    </ul>

                    <!-- 9 -->
                    <h2 class="h5 fw-bold mt-5 mb-3"><span class="badge bg-secondary me-2">9</span>Encerramento de Conta</h2>
                    <p>
                        Você pode encerrar sua conta a qualquer momento pela seção "Minha Conta".
                        O encerramento implica a remoção dos seus anúncios ativos e dados pessoais,
                        ressalvados os dados que devem ser retidos por obrigação legal (ex.: registros financeiros).
                    </p>

                    <!-- 10 -->
                    <h2 class="h5 fw-bold mt-5 mb-3"><span class="badge bg-secondary me-2">10</span>Modificações dos Termos</h2>
                    <p>
                        Reservamo-nos o direito de modificar estes Termos a qualquer momento.
                        Alterações relevantes serão comunicadas por e-mail ou aviso no site com antecedência mínima de 15 dias.
                        O uso continuado após a vigência da nova versão constitui aceitação.
                    </p>

                    <!-- 11 -->
                    <h2 class="h5 fw-bold mt-5 mb-3"><span class="badge bg-secondary me-2">11</span>Lei Aplicável e Foro</h2>
                    <p>
                        Estes Termos são regidos pelas leis da <strong>República Federativa do Brasil</strong>,
                        notadamente o Código de Defesa do Consumidor (Lei nº 8.078/90), o Marco Civil da Internet
                        (Lei nº 12.965/14) e a LGPD (Lei nº 13.709/18).
                        Fica eleito o foro da comarca de <strong>Porto Velho — RO</strong> para dirimir eventuais controvérsias.
                    </p>

                    <!-- 12 -->
                    <h2 class="h5 fw-bold mt-5 mb-3"><span class="badge bg-secondary me-2">12</span>Contato</h2>
                    <p>Dúvidas sobre estes Termos? Entre em contato:</p>
                    <a href="<?php echo BASE_URL; ?>/contato-dpo" class="btn btn-secondary">
                        <i class="fa-solid fa-envelope me-2"></i>Falar com o DPO
                    </a>

                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
