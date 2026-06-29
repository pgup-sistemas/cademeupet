<?php
require_once __DIR__ . '/../includes/auth.php';
/**
 * PetFinder - Controller de Usuários
 * Responsável por orquestrar autenticação, cadastro e gerenciamento de perfil.
 */

class UsuarioController
{
    private $auth;
    private $usuarioModel;

    public function __construct()
    {
        $this->auth = new Auth();
        $this->usuarioModel = new Usuario();
    }

    /**
     * Solicita recuperação de senha.
     */
    public function solicitarResetSenha(string $email): array
    {
        $auth = new Auth();
        return $auth->requestPasswordReset($email);
    }

    /**
     * Reenvia e-mail de confirmação para um usuário.
     */
    public function reenviarConfirmacaoEmail(string $email): array
    {
        $usuario = $this->usuarioModel->findByEmail($email);

        if (!$usuario) {
            return ['success' => true]; // Não revela se o e-mail existe
        }

        if (!empty($usuario['email_confirmado'])) {
            return ['errors' => ['Este e-mail já foi confirmado.']];
        }

        $token = bin2hex(random_bytes(32));
        $this->usuarioModel->update((int)$usuario['id'], ['token_confirmacao' => $token]);

        $this->auth->sendConfirmationEmail($email, $usuario['nome'], $token);

        return ['success' => true];
    }

    /**
     * Realiza cadastro de usuário aplicando validações complementares.
     */
    public function registrar(array $dados)
    {
        $dados = sanitize($dados);

        $erros = $this->validarCadastro($dados);
        if (!empty($erros)) {
            return ['success' => false, 'errors' => $erros];
        }

        $payload = [
            'nome' => trim($dados['nome']),
            'email' => strtolower(trim($dados['email'])),
            'telefone' => preg_replace('/[^0-9]/', '', $dados['telefone']),
            'senha' => $dados['senha'],
            'confirma_senha' => $dados['confirma_senha'],
            'cidade' => $dados['cidade'] ?? null,
            'estado' => isset($dados['estado']) ? strtoupper($dados['estado']) : null
        ];

        return $this->auth->register($payload);
    }

    /**
     * Realiza login respeitando regras de bloqueio e confirmações.
     */
    public function login(string $email, string $senha, bool $lembrar = false)
    {
        return $this->auth->login(sanitize($email), $senha, $lembrar);
    }

    /**
     * Finaliza sessão do usuário.
     */
    public function logout()
    {
        return $this->auth->logout();
    }

    /**
     * Atualiza dados de perfil permitidos.
     */
    public function atualizarPerfil(int $usuarioId, array $dados)
    {
        $dados = sanitize($dados);
        $permitidos = ['nome', 'telefone', 'cidade', 'estado', 'notificacoes_email'];
        $payload = [];
        $erros = [];

        foreach ($permitidos as $campo) {
            if (!array_key_exists($campo, $dados)) {
                continue;
            }

            $valor = $dados[$campo];

            switch ($campo) {
                case 'nome':
                    if (strlen($valor) < 3) {
                        $erros[] = 'Nome deve ter ao menos 3 caracteres.';
                    } else {
                        $payload['nome'] = $valor;
                    }
                    break;

                case 'telefone':
                    if (!empty($valor) && !isValidPhone($valor)) {
                        $erros[] = 'Telefone inválido.';
                    } else {
                        $payload['telefone'] = preg_replace('/[^0-9]/', '', $valor);
                    }
                    break;

                case 'cidade':
                    $payload['cidade'] = !empty($valor) ? $valor : null;
                    break;

                case 'estado':
                    if (!empty($valor) && strlen($valor) !== 2) {
                        $erros[] = 'Estado deve ter 2 caracteres (UF).';
                    } else {
                        $payload['estado'] = !empty($valor) ? strtoupper($valor) : null;
                    }
                    break;

                case 'notificacoes_email':
                    $payload['notificacoes_email'] = $valor ? 1 : 0;
                    break;
            }
        }

        if (!empty($erros)) {
            return ['success' => false, 'errors' => $erros];
        }

        if (empty($payload)) {
            return ['success' => false, 'errors' => ['Nenhuma alteração informada.']];
        }

        $this->usuarioModel->update($usuarioId, $payload);
        return ['success' => true, 'message' => 'Perfil atualizado com sucesso.'];
    }

    /**
     * Processa upload de foto de perfil respeitando regras de segurança.
     */
    public function atualizarFotoPerfil(int $usuarioId, array $arquivo)
    {
        $resultado = uploadImage($arquivo, UPLOAD_PATH . '/perfil');

        if (!$resultado['success']) {
            return ['success' => false, 'errors' => $resultado['errors'] ?? ['Erro ao enviar imagem.']];
        }

        $this->usuarioModel->updateProfilePhoto($usuarioId, $resultado['filename']);
        return ['success' => true, 'filename' => $resultado['filename']];
    }

    /**
     * Inicia fluxo de recuperação de senha.
     */
    public function solicitarRecuperacaoSenha(string $email)
    {
        return $this->auth->requestPasswordReset($email);
    }

    /**
     * Finaliza recuperação de senha.
     */
    public function resetarSenha(string $token, string $novaSenha, string $confirmaSenha)
    {
        return $this->auth->resetPassword($token, $novaSenha, $confirmaSenha);
    }

    private function validarCadastro(array $dados): array
    {
        $erros = [];

        if (empty($dados['nome']) || strlen($dados['nome']) < 3) {
            $erros[] = 'Nome deve ter ao menos 3 caracteres.';
        }

        if (empty($dados['email']) || !isValidEmail($dados['email'])) {
            $erros[] = 'Informe um email válido.';
        }

        if (empty($dados['telefone']) || !isValidPhone($dados['telefone'])) {
            $erros[] = 'Telefone inválido. Use o formato (XX) XXXXX-XXXX.';
        }

        if (empty($dados['senha']) || !isStrongPassword($dados['senha'])) {
            $erros[] = 'Senha deve ter ao menos 8 caracteres com letras e números.';
        }

        if (($dados['senha'] ?? '') !== ($dados['confirma_senha'] ?? '')) {
            $erros[] = 'As senhas informadas não conferem.';
        }

        if (!empty($dados['estado']) && strlen($dados['estado']) !== 2) {
            $erros[] = 'Estado deve conter apenas a sigla (2 caracteres).';
        }

        return $erros;
    }
}

