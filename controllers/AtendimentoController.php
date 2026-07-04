<?php
/**
 * Cadê Meu Pet? - Controller de atendimento veterinário presencial.
 * Ver docs/modulo-atendimento-veterinario-laudo.md (Fase 2)
 *
 * MVP: o pet buscado/criado durante o atendimento precisa estar vinculado
 * a um tutor com conta já existente (busca por telefone/nome). Criar uma
 * conta "mínima" para tutor sem cadastro fica para uma fase futura.
 */
class AtendimentoController
{
    private $atendimentoModel;
    private $petModel;
    private $veterinarioModel;
    private $parceiroPerfilModel;
    private $usuarioModel;

    public function __construct(
        $atendimentoModel = null,
        $petModel = null,
        $veterinarioModel = null,
        $parceiroPerfilModel = null,
        $usuarioModel = null
    ) {
        $this->atendimentoModel   = $atendimentoModel ?: new Atendimento();
        $this->petModel           = $petModel ?: new Pet();
        $this->veterinarioModel   = $veterinarioModel ?: new ParceiroVeterinario();
        $this->parceiroPerfilModel = $parceiroPerfilModel ?: new ParceiroPerfil();
        $this->usuarioModel       = $usuarioModel ?: new Usuario();
    }

    /** Confere se o usuário logado é um veterinário aprovado e retorna seu registro. */
    public function veterinarioAprovadoOuNull(int $usuarioId): ?array
    {
        return $this->veterinarioModel->buscarAprovadoPorUsuarioId($usuarioId);
    }

    public function buscarPetsPorTermo(string $termo): array
    {
        return $this->petModel->buscarPorTutorTelefoneOuNome($termo);
    }

    public function criarPetDuranteAtendimento(int $veterinarioUsuarioId, int $tutorUsuarioId, array $dados): array
    {
        if (!$this->veterinarioModel->buscarAprovadoPorUsuarioId($veterinarioUsuarioId)) {
            return ['success' => false, 'errors' => ['Veterinário não aprovado.']];
        }
        $tutor = $this->usuarioModel->findById($tutorUsuarioId);
        if (!$tutor) {
            return ['success' => false, 'errors' => ['Tutor não encontrado.']];
        }

        $dados = sanitize($dados);
        if (empty($dados['nome']) || empty($dados['especie'])) {
            return ['success' => false, 'errors' => ['Informe nome e espécie do pet.']];
        }

        $petId = $this->petModel->criar([
            'tutor_usuario_id' => $tutorUsuarioId,
            'nome'             => $dados['nome'],
            'especie'          => $dados['especie'],
            'raca'             => $dados['raca'] ?? null,
            'sexo'             => $dados['sexo'] ?? null,
            'cor'              => $dados['cor'] ?? null,
        ]);

        return ['success' => true, 'pet_id' => $petId];
    }

    public function abrir(int $veterinarioUsuarioId, int $petId, string $motivoConsulta, ?int $triagemSolicitacaoId = null): array
    {
        $veterinario = $this->veterinarioModel->buscarAprovadoPorUsuarioId($veterinarioUsuarioId);
        if (!$veterinario) {
            return ['success' => false, 'errors' => ['Você ainda não está aprovado como veterinário nesta plataforma.']];
        }

        $pet = $this->petModel->buscarPorId($petId);
        if (!$pet) {
            return ['success' => false, 'errors' => ['Pet não encontrado.']];
        }

        $motivoConsulta = trim(sanitize($motivoConsulta));
        if ($motivoConsulta === '') {
            return ['success' => false, 'errors' => ['Informe o motivo da consulta.']];
        }

        $atendimentoId = $this->atendimentoModel->abrir([
            'pet_id'                 => $petId,
            'parceiro_perfil_id'     => $veterinario['parceiro_perfil_id'],
            'veterinario_id'         => $veterinario['id'],
            'triagem_solicitacao_id' => $triagemSolicitacaoId,
            'motivo_consulta'        => $motivoConsulta,
        ]);

        auditLog('abrir_atendimento', 'atendimentos', $atendimentoId);

        return ['success' => true, 'id' => $atendimentoId];
    }

    public function buscarSeForDoVeterinario(int $atendimentoId, int $veterinarioUsuarioId): ?array
    {
        $veterinario = $this->veterinarioModel->buscarPorUsuarioId($veterinarioUsuarioId);
        if (!$veterinario || !$this->atendimentoModel->pertenceAoVeterinario($atendimentoId, (int)$veterinario['id'])) {
            return null;
        }
        return $this->atendimentoModel->buscarPorId($atendimentoId);
    }

    public function atualizarCampos(int $atendimentoId, int $veterinarioUsuarioId, array $dados): array
    {
        $atendimento = $this->buscarSeForDoVeterinario($atendimentoId, $veterinarioUsuarioId);
        if (!$atendimento) {
            return ['success' => false, 'errors' => ['Atendimento não encontrado.']];
        }
        if ($atendimento['status'] !== 'em_andamento') {
            return ['success' => false, 'errors' => ['Este atendimento já foi finalizado/cancelado.']];
        }

        $dados = sanitize($dados);
        if (isset($dados['vacina_nome']) && is_array($dados['vacina_nome'])) {
            $dados['vacinas_aplicadas'] = json_encode($this->normalizarVacinas($dados), JSON_UNESCAPED_UNICODE);
        }
        $this->atendimentoModel->atualizarCampos($atendimentoId, $dados);

        return ['success' => true];
    }

    /** Monta o array estruturado de vacinas a partir dos campos repetidos do formulário. */
    private function normalizarVacinas(array $dados): array
    {
        $nomes = (array)($dados['vacina_nome'] ?? []);
        $datas = (array)($dados['vacina_data'] ?? []);
        $lotes = (array)($dados['vacina_lote'] ?? []);

        $vacinas = [];
        foreach ($nomes as $indice => $nome) {
            $nome = trim((string)$nome);
            if ($nome === '') {
                continue;
            }
            $vacinas[] = [
                'nome' => $nome,
                'data' => !empty($datas[$indice]) ? $datas[$indice] : null,
                'lote' => !empty($lotes[$indice]) ? $lotes[$indice] : null,
            ];
        }
        return $vacinas;
    }

    public function carteiraDeVacinacao(int $petId): array
    {
        return $this->atendimentoModel->carteiraDeVacinacao($petId);
    }

    public function finalizar(int $atendimentoId, int $veterinarioUsuarioId): array
    {
        $atendimento = $this->buscarSeForDoVeterinario($atendimentoId, $veterinarioUsuarioId);
        if (!$atendimento) {
            return ['success' => false, 'errors' => ['Atendimento não encontrado.']];
        }
        if (empty($atendimento['diagnostico']) && empty($atendimento['conduta'])) {
            return ['success' => false, 'errors' => ['Preencha ao menos diagnóstico ou conduta antes de finalizar.']];
        }

        $this->atendimentoModel->finalizar($atendimentoId);
        auditLog('finalizar_atendimento', 'atendimentos', $atendimentoId);

        return ['success' => true];
    }

    public function historicoDoPet(int $petId): array
    {
        return $this->atendimentoModel->historicoDoPet($petId);
    }

    public function listarEmAndamento(int $veterinarioUsuarioId): array
    {
        $veterinario = $this->veterinarioModel->buscarPorUsuarioId($veterinarioUsuarioId);
        if (!$veterinario) {
            return [];
        }
        return $this->atendimentoModel->listarEmAndamentoPorVeterinario((int)$veterinario['id']);
    }

    public function listarDaClinica(int $parceiroUsuarioId): array
    {
        $perfil = $this->parceiroPerfilModel->findByUserId($parceiroUsuarioId);
        if (!$perfil) {
            return [];
        }
        return $this->atendimentoModel->listarPorClinica((int)$perfil['id']);
    }
}
