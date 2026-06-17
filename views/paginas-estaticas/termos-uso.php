<?php
$pageTitle = 'Termos de Uso';
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h1 class="mb-4">Termos de Uso</h1>
            
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <p class="text-muted">Última atualização: <?= date('d/m/Y') ?></p>
                    
                    <h3 class="h5 mb-3">1. Aceitação dos Termos</h3>
                    <p>Ao acessar e utilizar o site Cadê Meu Pet?, você concorda em cumprir estes Termos de Uso e nossa Política de Privacidade. Se não concordar com estes termos, por favor, não utilize nosso site.</p>
                    
                    <h3 class="h5 mb-3 mt-4">2. Cadastro de Usuário</h3>
                    <p>Para acessar determinadas funcionalidades do site, será necessário realizar um cadastro, fornecendo informações precisas e atualizadas. Você é responsável por manter a confidencialidade de sua senha e por todas as atividades que ocorram em sua conta.</p>
                    
                    <h3 class="h5 mb-3 mt-4">3. Uso Adequado</h3>
                    <p>Você concorda em não utilizar o site para:</p>
                    <ul>
                        <li>Publicar conteúdo ilegal, ofensivo, difamatório ou enganoso</li>
                        <li>Violação de direitos autorais, marcas registradas ou outros direitos de propriedade intelectual</li>
                        <li>Transmitir vírus ou qualquer código malicioso</li>
                        <li>Coletar informações de outros usuários sem consentimento</li>
                        <li>Realizar atividades fraudulentas ou enganosas</li>
                    </ul>
                    
                    <h3 class="h5 mb-3 mt-4">4. Anúncios de Animais</h3>
                    <p>Ao publicar um anúncio no Cadê Meu Pet?, você concorda que:</p>
                    <ul>
                        <li>As informações fornecidas são verdadeiras e precisas</li>
                        <li>Você tem o direito de oferecer o animal em adoção ou de anunciar um animal perdido/encontrado</li>
                        <li>As imagens utilizadas são de sua autoria ou você tem permissão para utilizá-las</li>
                        <li>O Cadê Meu Pet? não se responsabiliza por acordos feitos entre os usuários</li>
                    </ul>
                    
                    <h3 class="h5 mb-3 mt-4">5. Doações</h3>
                    <p>As doações realizadas através do site estão sujeitas a termos específicos:</p>
                    <ul>
                        <li>As doações são voluntárias e não reembolsáveis</li>
                        <li>O valor da doação será utilizado conforme descrito na campanha</li>
                        <li>O doador receberá um comprovante de doação por e-mail</li>
                    </ul>
                    
                    <h3 class="h5 mb-3 mt-4">6. Propriedade Intelectual</h3>
                    <p>Todos os direitos de propriedade intelectual relacionados ao site, incluindo textos, gráficos, logotipos, ícones, imagens, clipes de áudio e software, são de propriedade do Cadê Meu Pet? ou de seus licenciadores.</p>
                    
                    <h3 class="h5 mb-3 mt-4">7. Limitação de Responsabilidade</h3>
                    <p>O Cadê Meu Pet? não se responsabiliza por:</p>
                    <ul>
                        <li>Danos diretos ou indiretos decorrentes do uso ou incapacidade de usar o site</li>
                        <li>Conteúdo de sites de terceiros vinculados ao nosso site</li>
                        <li>Conduta de outros usuários ou terceiros</li>
                        <li>Interrupções ou falhas técnicas no site</li>
                    </ul>
                    
                    <h3 class="h5 mb-3 mt-4">8. Modificações nos Termos</h3>
                    <p>Reservamo-nos o direito de modificar estes Termos a qualquer momento. As alterações entrarão em vigor imediatamente após a publicação no site. O uso contínuo do site após tais modificações constitui sua aceitação dos novos Termos.</p>
                    
                    <h3 class="h5 mb-3 mt-4">9. Lei Aplicável</h3>
                    <p>Estes Termos são regidos pelas leis brasileiras. Qualquer disputa relacionada a estes Termos será submetida à jurisdição exclusiva dos tribunais do Brasil.</p>
                    
                    <h3 class="h5 mb-3 mt-4">10. Contato</h3>
                    <p>Em caso de dúvidas sobre estes Termos de Uso, entre em contato conosco através da nossa <a href="<?php echo BASE_URL; ?>/contato-dpo">página de contato do DPO</a>.</p>
                </div>
            </div>
        </div>
        
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
