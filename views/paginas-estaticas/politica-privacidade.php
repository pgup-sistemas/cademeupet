<?php
$pageTitle = 'Política de Privacidade';
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h1 class="mb-4">Política de Privacidade</h1>
            
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <p class="text-muted">Última atualização: <?= date('d/m/Y') ?></p>
                    
                    <h3 class="h5 mb-3">1. Introdução</h3>
                    <p>O Cadê Meu Pet? está comprometido com a proteção da sua privacidade e dos seus dados pessoais. Esta Política de Privacidade explica como coletamos, usamos, divulgamos e protegemos suas informações quando você utiliza nosso site e serviços.</p>
                    
                    <h3 class="h5 mb-3 mt-4">2. Dados que Coletamos</h3>
                    <p>Podemos coletar as seguintes categorias de informações:</p>
                    <ul>
                        <li>Informações de cadastro (nome, e-mail, telefone, endereço)</li>
                        <li>Dados de localização (quando você permite o acesso à sua localização)</li>
                        <li>Informações sobre os animais de estimação cadastrados</li>
                        <li>Dados de navegação e interação com o site</li>
                        <li>Informações de pagamento (processadas de forma segura por processadores de pagamento terceirizados)</li>
                    </ul>
                    
                    <h3 class="h5 mb-3 mt-4">3. Como Utilizamos Seus Dados</h3>
                    <p>Utilizamos seus dados para:</p>
                    <ul>
                        <li>Fornecer e melhorar nossos serviços</li>
                        <li>Processar doações e pagamentos</li>
                        <li>Enviar comunicações importantes sobre sua conta</li>
                        <li>Personalizar sua experiência no site</li>
                        <li>Enviar atualizações e notificações sobre animais perdidos/encontrados</li>
                        <li>Cumprir obrigações legais</li>
                    </ul>
                    
                    <h3 class="h5 mb-3 mt-4">4. Compartilhamento de Dados</h3>
                    <p>Não vendemos ou alugamos suas informações pessoais. Podemos compartilhar seus dados apenas nas seguintes situações:</p>
                    <ul>
                        <li>Com prestadores de serviços que nos auxiliam na operação do site</li>
                        <li>Quando exigido por lei ou processo legal</li>
                        <li>Para proteger direitos, propriedade ou segurança do Cadê Meu Pet?, nossos usuários ou terceiros</li>
                        <li>Com seu consentimento explícito</li>
                    </ul>
                    
                    <h3 class="h5 mb-3 mt-4">5. Seus Direitos</h3>
                    <p>De acordo com a LGPD, você tem o direito de:</p>
                    <ul>
                        <li>Acessar seus dados pessoais</li>
                        <li>Corrigir dados incompletos, inexatos ou desatualizados</li>
                        <li>Solicitar a anonimização, bloqueio ou eliminação de dados desnecessários</li>
                        <li>Revogar seu consentimento</li>
                        <li>Excluir sua conta e todos os dados associados a ela</li>
                    </ul>
                    
                    <h3 class="h5 mb-3 mt-4">6. Segurança dos Dados</h3>
                    <p>Implementamos medidas de segurança técnicas e organizacionais para proteger seus dados contra acesso não autorizado, alteração, divulgação ou destruição não autorizada.</p>
                    
                    <h3 class="h5 mb-3 mt-4">7. Alterações nesta Política</h3>
                    <p>Podemos atualizar nossa Política de Privacidade periodicamente. Notificaremos você sobre quaisquer alterações publicando a nova política nesta página.</p>
                    
                    <h3 class="h5 mb-3 mt-4">8. Contato</h3>
                    <p>Se tiver dúvidas sobre esta Política de Privacidade ou sobre como tratamos seus dados pessoais, entre em contato conosco através da nossa <a href="<?php echo BASE_URL; ?>/contato-dpo">página de contato do DPO</a>.</p>
                </div>
            </div>
        </div>
        
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
