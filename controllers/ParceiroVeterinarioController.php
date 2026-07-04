<?php
/**
 * Cadê Meu Pet? - Controller de cadastro/validação de veterinários parceiros.
 * Ver docs/modulo-atendimento-veterinario-laudo.md (Fase 2)
 *
 * Só clínicas (parceiro_perfis.categoria='clinica') podem cadastrar
 * veterinários. A aprovação do CRMV é sempre manual pelo admin — não há
 * API pública/gratuita para validar automaticamente o registro no CFMV.
 */
class ParceiroVeterinarioController
{
    private $veterinarioModel;
    private $parceiroPerfilModel;

    public function __construct($veterinarioModel = null, $parceiroPerfilModel = null)
    {
        $this->veterinarioModel   = $veterinarioModel ?: new ParceiroVeterinario();
        $this->parceiroPerfilModel = $parceiroPerfilModel ?: new ParceiroPerfil();
    }

    public function cadastrar(int $parceiroUsuarioId, int $veterinarioUsuarioId, array $dados): array
    {
        $perfil = $this->parceiroPerfilModel->findByUserId($parceiroUsuarioId);
        if (!$perfil || $perfil['categoria'] !== 'clinica') {
            return ['success' => false, 'errors' => ['Só parceiros da categoria Clínica podem cadastrar veterinários.']];
        }

        $dados = sanitize($dados);
        $erros = $this->validar($dados);
        if (!empty($erros)) {
            return ['success' => false, 'errors' => $erros];
        }

        if ($this->veterinarioModel->crmvJaExiste($dados['crmv_numero'], $dados['crmv_uf'])) {
            return ['success' => false, 'errors' => ['Este CRMV já está cadastrado no sistema (possivelmente em outra clínica).']];
        }

        $id = $this->veterinarioModel->criar([
            'parceiro_perfil_id' => $perfil['id'],
            'usuario_id'         => $veterinarioUsuarioId,
            'nome_completo'      => $dados['nome_completo'],
            'crmv_numero'        => $dados['crmv_numero'],
            'crmv_uf'            => $dados['crmv_uf'],
        ]);

        auditLog('cadastrar_veterinario', 'parceiro_veterinarios', $id);

        return ['success' => true, 'id' => $id];
    }

    public function listarDaClinica(int $parceiroUsuarioId): array
    {
        $perfil = $this->parceiroPerfilModel->findByUserId($parceiroUsuarioId);
        if (!$perfil) {
            return [];
        }
        return $this->veterinarioModel->listarPorParceiro((int)$perfil['id']);
    }

    public function meuCadastro(int $usuarioId): ?array
    {
        return $this->veterinarioModel->buscarPorUsuarioId($usuarioId);
    }

    public function estaAprovado(int $usuarioId): bool
    {
        return (bool)$this->veterinarioModel->buscarAprovadoPorUsuarioId($usuarioId);
    }

    public function listarFilaAdmin(): array
    {
        return $this->veterinarioModel->listarPendentes();
    }

    public function listarTodosAdmin(): array
    {
        return $this->veterinarioModel->listarTodos();
    }

    public function aprovar(int $veterinarioId, int $adminUsuarioId): array
    {
        $ok = $this->veterinarioModel->aprovar($veterinarioId, $adminUsuarioId);
        if (!$ok) {
            return ['success' => false, 'error' => 'Não foi possível aprovar (talvez já processado).'];
        }
        auditLog('aprovar_veterinario', 'parceiro_veterinarios', $veterinarioId);
        $this->notificar($veterinarioId, true, null);
        return ['success' => true];
    }

    public function rejeitar(int $veterinarioId, int $adminUsuarioId, string $motivo): array
    {
        $ok = $this->veterinarioModel->rejeitar($veterinarioId, $adminUsuarioId, $motivo);
        if (!$ok) {
            return ['success' => false, 'error' => 'Não foi possível rejeitar (talvez já processado).'];
        }
        auditLog('rejeitar_veterinario', 'parceiro_veterinarios', $veterinarioId);
        $this->notificar($veterinarioId, false, $motivo);
        return ['success' => true];
    }

    private function notificar(int $veterinarioId, bool $aprovado, ?string $motivo): void
    {
        $veterinario = $this->veterinarioModel->buscarPorId($veterinarioId);
        if (!$veterinario) {
            return;
        }
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->findById((int)$veterinario['usuario_id']);
        if (!$usuario || empty($usuario['email'])) {
            return;
        }

        if ($aprovado) {
            $assunto = 'Seu cadastro de veterinário foi aprovado — Cadê Meu Pet?';
            $corpo = '<p>Olá, ' . sanitize($usuario['nome']) . '!</p><p>Seu CRMV foi validado e você já pode abrir atendimentos pela plataforma.</p>';
        } else {
            $assunto = 'Seu cadastro de veterinário não foi aprovado — Cadê Meu Pet?';
            $corpo = '<p>Olá, ' . sanitize($usuario['nome']) . '.</p><p>Seu cadastro não foi aprovado. Motivo: ' . sanitize((string)$motivo) . '</p>';
        }
        @sendEmail($usuario['email'], $assunto, $corpo);
    }

    private function validar(array $dados): array
    {
        $erros = [];

        if (empty($dados['nome_completo'])) {
            $erros[] = 'Informe o nome completo do veterinário.';
        }
        if (empty($dados['crmv_numero'])) {
            $erros[] = 'Informe o número do CRMV.';
        }
        if (empty($dados['crmv_uf']) || strlen($dados['crmv_uf']) !== 2) {
            $erros[] = 'Informe a UF do CRMV (2 letras).';
        }

        return $erros;
    }
}
