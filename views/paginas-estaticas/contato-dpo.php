<?php
$pageTitle = 'Contato do Encarregado de Proteção de Dados (DPO)';
$breadcrumbs = [
    ['label' => 'Início',        'url' => BASE_URL],
    ['label' => 'Falar com DPO'],
];
include __DIR__ . '/../../includes/header.php';

// Processamento do formulário
$mensagemEnviada = false;
$erroEnvio = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING) ?? '';
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '';
    $assunto = filter_input(INPUT_POST, 'assunto', FILTER_SANITIZE_STRING) ?? '';
    $mensagem = filter_input(INPUT_POST, 'mensagem', FILTER_SANITIZE_STRING) ?? '';
    
    // Validação básica
    if (empty($nome) || empty($email) || empty($assunto) || empty($mensagem)) {
        $erroEnvio = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erroEnvio = 'Por favor, insira um endereço de e-mail válido.';
    } else {
            // Enviar e-mail usando a função sendEmail
        $para = 'dpo@cademeupet.com.br';
        $assuntoEmail = "[Contato DPO] $assunto";
        
        // Corpo do e-mail em HTML
        $corpoHTML = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .content { padding: 15px 0; }
                .footer { margin-top: 30px; font-size: 12px; color: #6c757d; border-top: 1px solid #eee; padding-top: 15px; }
                .label { font-weight: bold; color: #495057; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2 style='margin: 0; color: #0d6efd;'>Contato do DPO - Cadê Meu Pet?</h2>
                </div>
                
                <div class='content'>
                    <p>Você recebeu uma nova mensagem através do formulário de contato do DPO:</p>
                    
                    <p><span class='label'>Nome:</span> " . htmlspecialchars($nome) . "</p>
                    <p><span class='label'>E-mail:</span> " . htmlspecialchars($email) . "</p>
                    <p><span class='label'>Assunto:</span> " . htmlspecialchars($assunto) . "</p>
                    
                    <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <p style='margin: 0; white-space: pre-line;'>" . nl2br(htmlspecialchars($mensagem)) . "</p>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>Esta é uma mensagem automática enviada pelo sistema Cadê Meu Pet?. Por favor, não responda este e-mail.</p>
                    <p>&copy; " . date('Y') . " Cadê Meu Pet?. Todos os direitos reservados.</p>
                </div>
            </div>
        </body>
        </html>";
        
        // Versão em texto simples para clientes de e-mail que não suportam HTML
        $corpoTexto = "Contato do DPO - Cadê Meu Pet?\n\n";
        $corpoTexto .= "Nome: $nome\n";
        $corpoTexto .= "E-mail: $email\n";
        $corpoTexto .= "Assunto: $assunto\n\n";
        $corpoTexto .= "Mensagem:\n$mensagem\n\n";
        $corpoTexto .= "---\n";
        $corpoTexto .= "Esta é uma mensagem automática enviada pelo sistema Cadê Meu Pet?.\n";
        $corpoTexto .= "© " . date('Y') . " Cadê Meu Pet?. Todos os direitos reservados.";
        
        // Enviar e-mail
        try {
            // Envia para o DPO
            $enviado = sendEmail(
                $para,
                $assuntoEmail,
                $corpoHTML,
                $email // Usar o e-mail do remetente como from
            );
            
            if ($enviado) {
                // Envia cópia para o remetente
                $assuntoCopia = "[Cópia] $assuntoEmail";
                $mensagemCopia = "Olá " . htmlspecialchars($nome) . ",\n\n";
                $mensagemCopia .= "Recebemos sua mensagem através do formulário de contato do DPO. Abaixo está uma cópia da sua mensagem:\n\n";
                $mensagemCopia .= "----------------------------------------\n";
                $mensagemCopia .= "Assunto: $assunto\n";
                $mensagemCopia .= "Enviado em: " . date('d/m/Y H:i:s') . "\n";
                $mensagemCopia .= "----------------------------------------\n\n";
                $mensagemCopia .= "$mensagem\n\n";
                $mensagemCopia .= "----------------------------------------\n";
                $mensagemCopia .= "Agradecemos pelo seu contato. Nossa equipe irá analisar sua solicitação e retornar o mais breve possível.\n\n";
                $mensagemCopia .= "Atenciosamente,\nEquipe Cadê Meu Pet?\n";
                
                sendEmail(
                    $email,
                    $assuntoCopia,
                    $mensagemCopia,
                    $para // Usar o e-mail do DPO como remetente
                );
                
                $mensagemEnviada = true;
            } else {
                throw new Exception('Falha ao enviar e-mail');
            }
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail do DPO: " . $e->getMessage());
            $erroEnvio = 'Ocorreu um erro ao enviar sua mensagem. Por favor, tente novamente mais tarde ou entre em contato diretamente por e-mail.';
        }
    }
}
?>

<style>
    .contact-form {
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }
    
    .contact-form h1 {
        color: #2c3e50;
        font-weight: 700;
        margin-bottom: 1.5rem;
        text-align: center;
        position: relative;
        padding-bottom: 1rem;
    }
    
    .contact-form h1:after {
        content: '';
        display: block;
        width: 80px;
        height: 4px;
        background: #3498db;
        margin: 10px auto 0;
        border-radius: 2px;
    }
    
    .form-label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }
    
    .form-control, .form-select, .form-textarea {
        border-radius: 8px;
        padding: 12px 15px;
        border: 1px solid #ddd;
        transition: all 0.3s ease;
        margin-bottom: 1.25rem;
    }
    
    .form-control:focus, .form-select:focus, .form-textarea:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
    }
    
    .btn-primary {
        background-color: #3498db;
        border: none;
        padding: 12px 30px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .btn-primary:hover {
        background-color: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .alert {
        border-radius: 8px;
        padding: 15px 20px;
        margin-bottom: 1.5rem;
    }
    
    .contact-info {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 2rem;
    }
    
    .contact-info h3 {
        color: #2c3e50;
        font-size: 1.25rem;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #3498db;
        display: inline-block;
    }
    
    .contact-info p {
        color: #555;
        margin-bottom: 0.75rem;
    }
    
    .contact-info i {
        color: #3498db;
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }
    
    @media (max-width: 768px) {
        .contact-form {
            padding: 1.5rem;
        }
        
        .contact-form h1 {
            font-size: 1.75rem;
        }
    }
</style>

<div class="container py-5">
    <div class="contact-form">
        <h1>Contato do Encarregado de Proteção de Dados (DPO)</h1>
        
        <?php if ($mensagemEnviada): ?>
            <div class="alert alert-success" role="alert">
                <h4 class="alert-heading">Mensagem enviada com sucesso!</h4>
                <p>Obrigado por entrar em contato com nosso Encarregado de Proteção de Dados. Retornaremos o mais breve possível.</p>
                <hr>
                <p class="mb-0"><a href="/" class="alert-link">Voltar para a página inicial</a></p>
            </div>
        <?php else: ?>
            <?php if ($erroEnvio): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($erroEnvio) ?>
                </div>
            <?php endif; ?>

            <p class="mb-4">Utilize o formulário abaixo para entrar em contato com nosso Encarregado de Proteção de Dados (DPO) para questões relacionadas à proteção de dados pessoais e privacidade, incluindo o exercício dos seus direitos previstos na LGPD.</p>

            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nome" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="nome" name="nome" required 
                                   placeholder="Seu nome completo" value="<?= isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : '' ?>">
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required
                                   placeholder="seu@email.com" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="assunto" class="form-label">Assunto <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                        <select class="form-select" id="assunto" name="assunto" required>
                            <option value="" selected disabled>Selecione um assunto</option>
                            <option value="Exercício de Direitos LGPD" <?= (isset($_POST['assunto']) && $_POST['assunto'] === 'Exercício de Direitos LGPD') ? 'selected' : '' ?>>Exercício de Direitos LGPD</option>
                            <option value="Dúvidas sobre Privacidade" <?= (isset($_POST['assunto']) && $_POST['assunto'] === 'Dúvidas sobre Privacidade') ? 'selected' : '' ?>>Dúvidas sobre Privacidade</option>
                            <option value="Solicitação de Exclusão de Dados" <?= (isset($_POST['assunto']) && $_POST['assunto'] === 'Solicitação de Exclusão de Dados') ? 'selected' : '' ?>>Solicitação de Exclusão de Dados</option>
                            <option value="Outro" <?= (isset($_POST['assunto']) && $_POST['assunto'] === 'Outro') ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="mensagem" class="form-label">Mensagem <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text align-items-start pt-2"><i class="fas fa-comment"></i></span>
                        <textarea class="form-control form-textarea" id="mensagem" name="mensagem" 
                                  rows="6" required placeholder="Descreva sua solicitação aqui..."><?= isset($_POST['mensagem']) ? htmlspecialchars($_POST['mensagem']) : '' ?></textarea>
                    </div>
                </div>

                <div class="form-group form-check mb-4">
                    <input type="checkbox" class="form-check-input" id="lgpd-consent" required>
                    <label class="form-check-label" for="lgpd-consent">
                        Concordo com a coleta e tratamento dos meus dados pessoais conforme a <a href="<?php echo BASE_URL; ?>/politica-privacidade" target="_blank">Política de Privacidade</a>.
                    </label>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="fas fa-paper-plane me-2"></i> Enviar Mensagem
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="contact-info">
    <h3><i class="fas fa-info-circle"></i> Informações de Contato</h3>
    <div class="row">
        <div class="col-md-6">
            <p class="mb-3">
                <i class="fas fa-clock"></i> 
                <strong>Horário de Atendimento:</strong><br>
                <span class="ms-4">Segunda a Sexta, das 9h às 18h</span>
            </p>
            <p class="mb-3">
                <i class="fas fa-envelope"></i> 
                <strong>E-mail:</strong><br>
                <a href="mailto:dpo@cademeupet.com.br" class="ms-4">dpo@cademeupet.com.br</a>
            </p>
        </div>
        <div class="col-md-6">
            <p class="mb-3">
                <i class="fas fa-map-marker-alt"></i> 
                <strong>Endereço:</strong><br>
                <span class="ms-4">Rua Exemplo, 123 - Centro<br>
                Cidade - Estado, CEP 12345-678</span>
            </p>
            <p class="mb-3">
                <i class="fas fa-phone"></i> 
                <strong>Telefone:</strong><br>
                <a href="tel:00000000000" class="ms-4">(00) 1234-5678</a>
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validação do formulário com feedback personalizado
    const form = document.querySelector('form.needs-validation');
    
    if (form) {
        // Adiciona classes de validação ao campo quando o usuário sai dele
        form.querySelectorAll('.form-control, .form-select, .form-textarea').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.checkValidity()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            });
        });
        
        // Validação no envio do formulário
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Rola até o primeiro campo inválido
                const firstInvalid = form.querySelector('.is-invalid, :invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus();
                }
            }
            
            form.classList.add('was-validated');
        }, false);
    }
    
    // Adiciona máscara para telefone se houver campo de telefone
    const telefoneInput = document.getElementById('telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.substring(0, 11);
            
            if (value.length > 10) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length > 5) {
                value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
            } else if (value.length > 0) {
                value = value.replace(/(\d*)/, '($1');
            }
            
            e.target.value = value;
        });
    }
});
</script>

<!-- Adiciona o Font Awesome para os ícones -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
