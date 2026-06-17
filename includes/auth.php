<?php
/**
 * Cadê Meu Pet? - Sistema de Autenticação
 * Gerencia login, logout, registro e recuperação de senha
 */

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Registra novo usuário
     */
    public function register($data) {
        // Validações
        $errors = [];
        
        if (empty($data['nome']) || strlen($data['nome']) < 3) {
            $errors[] = 'Nome deve ter no mínimo 3 caracteres';
        }
        
        if (!isValidEmail($data['email'])) {
            $errors[] = 'Email inválido';
        }
        
        if (!isValidPhone($data['telefone'])) {
            $errors[] = 'Telefone inválido';
        }
        
        if (!isStrongPassword($data['senha'])) {
            $errors[] = 'Senha deve ter no mínimo 8 caracteres, incluindo letras e números';
        }
        
        if ($data['senha'] !== $data['confirma_senha']) {
            $errors[] = 'As senhas não conferem';
        }
        
        // Verifica se email já existe
        $existingUser = $this->db->fetchOne(
            "SELECT id FROM usuarios WHERE email = ?",
            [$data['email']]
        );
        
        if ($existingUser) {
            $errors[] = 'Este email já está cadastrado';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Cria usuário
        try {
            $token = bin2hex(random_bytes(32));
            
            $userId = $this->db->insert('usuarios', [
                'nome' => $data['nome'],
                'email' => $data['email'],
                'telefone' => preg_replace('/[^0-9]/', '', $data['telefone']),
                'senha' => hashPassword($data['senha']),
                'cidade' => $data['cidade'] ?? null,
                'estado' => $data['estado'] ?? null,
                'tipo_usuario' => 'comum',
                'token_confirmacao' => $token,
                'data_cadastro' => date('Y-m-d H:i:s')
            ]);
            
            // Envia email de confirmação
            $this->sendConfirmationEmail($data['email'], $data['nome'], $token);
            
            return [
                'success' => true,
                'user_id' => $userId,
                'message' => 'Cadastro realizado! Verifique seu email para confirmar.'
            ];
            
        } catch (Exception $e) {
            error_log("Erro no registro: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erro ao criar conta. Tente novamente.']];
        }
    }
    
    /**
     * Faz login do usuário
     */
    public function login($email, $senha, $lembrar = false) {
        // Validações básicas
        if (!isValidEmail($email)) {
            return ['success' => false, 'error' => 'Email inválido'];
        }
        
        if (empty($senha)) {
            return ['success' => false, 'error' => 'Senha é obrigatória'];
        }
        
        // Busca usuário
        $user = $this->db->fetchOne(
            "SELECT * FROM usuarios WHERE email = ? AND ativo = 1",
            [$email]
        );
        
        if (!$user) {
            $this->logLoginAttempt($email, false);
            return ['success' => false, 'error' => 'Email ou senha incorretos'];
        }
        
        // Verifica se está bloqueado
        if ($user['bloqueado_ate'] && strtotime($user['bloqueado_ate']) > time()) {
            $minutos = ceil((strtotime($user['bloqueado_ate']) - time()) / 60);
            return [
                'success' => false, 
                'error' => "Conta bloqueada. Tente novamente em {$minutos} minutos."
            ];
        }
        
        // Verifica senha
        if (!verifyPassword($senha, $user['senha'])) {
            $this->handleFailedLogin($user['id'], $user['tentativas_login']);
            return ['success' => false, 'error' => 'Email ou senha incorretos'];
        }
        
        // Verifica se email foi confirmado
        if (!$user['email_confirmado']) {
            return [
                'success' => false, 
                'error' => 'Por favor, confirme seu email antes de fazer login.',
                'need_confirmation' => true
            ];
        }
        
        // Login bem-sucedido
        $this->createSession($user);
        
        // Reseta tentativas de login
        $this->db->update('usuarios', 
            ['tentativas_login' => 0, 'bloqueado_ate' => null, 'ultimo_acesso' => date('Y-m-d H:i:s')],
            'id = ?',
            [$user['id']]
        );
        
        $this->logLoginAttempt($email, true);
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Cria sessão do usuário
     */
    private function createSession($user) {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Trata falha de login
     */
    private function handleFailedLogin($userId, $tentativasAtuais) {
        $novasTentativas = $tentativasAtuais + 1;
        
        $updateData = ['tentativas_login' => $novasTentativas];
        
        // Bloqueia após 3 tentativas
        if ($novasTentativas >= MAX_LOGIN_ATTEMPTS) {
            $bloqueioAte = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $updateData['bloqueado_ate'] = $bloqueioAte;
            $updateData['tentativas_login'] = 0;
        }
        
        $this->db->update('usuarios', $updateData, 'id = ?', [$userId]);
    }
    
    /**
     * Faz logout
     */
    public function logout() {
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
        return true;
    }
    
    /**
     * Confirma email do usuário
     */
    public function confirmEmail($token) {
        if (empty($token)) {
            return ['success' => false, 'error' => 'Token inválido'];
        }
        
        $user = $this->db->fetchOne(
            "SELECT id, email FROM usuarios WHERE token_confirmacao = ? AND email_confirmado = 0",
            [$token]
        );
        
        if (!$user) {
            return ['success' => false, 'error' => 'Token inválido ou já utilizado'];
        }
        
        $this->db->update('usuarios',
            ['email_confirmado' => 1, 'token_confirmacao' => null],
            'id = ?',
            [$user['id']]
        );
        
        return ['success' => true, 'message' => 'Email confirmado com sucesso!'];
    }
    
    /**
     * Solicita recuperação de senha
     */
    public function requestPasswordReset($email) {
        if (!isValidEmail($email)) {
            return ['success' => false, 'error' => 'Email inválido'];
        }
        
        $user = $this->db->fetchOne(
            "SELECT id, nome FROM usuarios WHERE email = ? AND ativo = 1",
            [$email]
        );
        
        if (!$user) {
            // Por segurança, sempre retorna sucesso (não revela se email existe)
            return ['success' => true, 'message' => 'Se o email existir, você receberá instruções.'];
        }
        
        // Gera token
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $this->db->update('usuarios',
            ['token_recuperacao' => $token, 'token_expira' => $expira],
            'id = ?',
            [$user['id']]
        );
        
        // Envia email
        $this->sendPasswordResetEmail($email, $user['nome'], $token);
        
        return ['success' => true, 'message' => 'Instruções enviadas para seu email.'];
    }
    
    /**
     * Reseta senha
     */
    public function resetPassword($token, $novaSenha, $confirmaSenha) {
        if (empty($token)) {
            return ['success' => false, 'error' => 'Token inválido'];
        }
        
        if (!isStrongPassword($novaSenha)) {
            return ['success' => false, 'error' => 'Senha muito fraca'];
        }
        
        if ($novaSenha !== $confirmaSenha) {
            return ['success' => false, 'error' => 'As senhas não conferem'];
        }
        
        $user = $this->db->fetchOne(
            "SELECT id, email, nome, email_confirmado, token_confirmacao FROM usuarios 
             WHERE token_recuperacao = ? 
             AND token_expira > NOW()",
            [$token]
        );
        
        if (!$user) {
            return ['success' => false, 'error' => 'Token inválido ou expirado'];
        }
        
        $this->db->update('usuarios',
            [
                'senha' => hashPassword($novaSenha),
                'token_recuperacao' => null,
                'token_expira' => null
            ],
            'id = ?',
            [$user['id']]
        );
        
        $confirmationSent = false;
        if (empty($user['email_confirmado'])) {
            $confirmationToken = $user['token_confirmacao'] ?? '';
            if (empty($confirmationToken)) {
                $confirmationToken = bin2hex(random_bytes(32));
                $this->db->update('usuarios',
                    ['token_confirmacao' => $confirmationToken],
                    'id = ?',
                    [$user['id']]
                );
            }
            
            $this->sendConfirmationEmail($user['email'], $user['nome'], $confirmationToken);
            $confirmationSent = true;
        }
        
        return [
            'success' => true,
            'message' => 'Senha alterada com sucesso!',
            'need_confirmation' => $confirmationSent,
            'confirmation_sent' => $confirmationSent
        ];
    }
    
    /**
     * Envia email de confirmação
     */
    public function sendConfirmationEmail($email, $nome, $token) {
        $link = BASE_URL . "/confirmar-email.php?token=" . $token;
        
        $subject = "Confirme seu email - Cadê Meu Pet?";
        $message = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #2196F3;'>Bem-vindo ao Cadê Meu Pet?, {$nome}!</h2>
                    <p>Obrigado por se cadastrar. Para ativar sua conta, clique no link abaixo:</p>
                    <p style='margin: 30px 0;'>
                        <a href='{$link}' 
                           style='background: #2196F3; color: white; padding: 12px 30px; 
                                  text-decoration: none; border-radius: 5px; display: inline-block;'>
                            Confirmar Email
                        </a>
                    </p>
                    <p>Ou copie e cole este link no navegador:</p>
                    <p style='color: #666; font-size: 12px;'>{$link}</p>
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                    <p style='color: #999; font-size: 12px;'>
                        Se você não se cadastrou no Cadê Meu Pet?, ignore este email.
                    </p>
                </div>
            </body>
            </html>
        ";
        
        $sent = sendEmail($email, $subject, $message);
        if (!$sent) {
            error_log('[Auth] Falha ao enviar email de confirmação para: ' . $email . ' | SMTP_HOST=' . (defined('SMTP_HOST') ? SMTP_HOST : 'N/A'));
            // Salva flag em sessão para exibir alerta ao admin
            if (isAdmin()) {
                $_SESSION['alert_email_fail'] = 'Falha ao enviar e-mail de confirmação para: ' . $email;
            }
        }
    }
    
    /**
     * Envia email de recuperação de senha
     */
    private function sendPasswordResetEmail($email, $nome, $token) {
        $link = BASE_URL . "/resetar-senha.php?token=" . $token;
        
        $subject = "Recuperação de Senha - Cadê Meu Pet?";
        $message = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #2196F3;'>Recuperação de Senha</h2>
                    <p>Olá {$nome},</p>
                    <p>Recebemos uma solicitação para resetar sua senha. Clique no link abaixo:</p>
                    <p style='margin: 30px 0;'>
                        <a href='{$link}' 
                           style='background: #FF9800; color: white; padding: 12px 30px; 
                                  text-decoration: none; border-radius: 5px; display: inline-block;'>
                            Resetar Senha
                        </a>
                    </p>
                    <p>Este link expira em 1 hora.</p>
                    <p style='color: #666; font-size: 12px;'>{$link}</p>
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                    <p style='color: #999; font-size: 12px;'>
                        Se você não solicitou isso, ignore este email.
                    </p>
                </div>
            </body>
            </html>
        ";
        
        $sent = sendEmail($email, $subject, $message);
        if (!$sent) {
            error_log('[Auth] Falha ao enviar email de recuperação de senha para: ' . $email);
        }
    }
    
    /**
     * Registra tentativa de login
     */
    private function logLoginAttempt($email, $success) {
        // Log para auditoria (implementar se necessário)
        $ip = getClientIP();
        error_log("Login attempt - Email: {$email}, Success: " . ($success ? 'Yes' : 'No') . ", IP: {$ip}");
    }
    
    /**
     * Verifica se sessão está válida
     */
    public function checkSession() {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return false;
        }
        
        // Verifica timeout de sessão
        if (isset($_SESSION['last_activity'])) {
            $elapsed = time() - $_SESSION['last_activity'];
            if ($elapsed > SESSION_TIMEOUT) {
                $this->logout();
                return false;
            }
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
}

?>