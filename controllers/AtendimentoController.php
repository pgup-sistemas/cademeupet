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

    /** Busca tutores (usuários) por nome/telefone — usado para localizar quem cadastrar o pet novo. */
    public function buscarTutoresPorTermo(string $termo): array
    {
        return $this->usuarioModel->buscarPorNomeOuTelefone($termo);
    }

    public function buscarTutorPorId(int $tutorUsuarioId): ?array
    {
        $tutor = $this->usuarioModel->findById($tutorUsuarioId);
        return $tutor ?: null;
    }

    /**
     * Cadastra um tutor novo (primeira vez na clínica, sem conta ainda) e o
     * pet vinculado a ele, tudo em uma única ação. Evita duplicidade: se já
     * existir uma conta com o mesmo telefone (ou e-mail, quando informado),
     * retorna erro orientando a buscar o tutor existente em vez de duplicar.
     *
     * Se o tutor não informar e-mail, gera um e-mail interno único (o tutor
     * não conseguirá logar até atualizar o e-mail depois) — a conta serve
     * para guardar a ficha do pet e o histórico clínico desde já.
     */
    public function criarTutorEPet(int $veterinarioUsuarioId, array $dadosTutor, array $dadosPet): array
    {
        $veterinario = $this->veterinarioModel->buscarAprovadoPorUsuarioId($veterinarioUsuarioId);
        if (!$veterinario) {
            return ['success' => false, 'errors' => ['Veterinário não aprovado.']];
        }

        $dadosTutor = sanitize($dadosTutor);
        $nomeTutor = trim($dadosTutor['nome'] ?? '');
        $telefoneTutor = preg_replace('/\D/', '', (string)($dadosTutor['telefone'] ?? ''));
        $emailTutor = trim($dadosTutor['email'] ?? '');

        if ($nomeTutor === '' || strlen($nomeTutor) < 3) {
            return ['success' => false, 'errors' => ['Informe o nome completo do tutor.']];
        }
        if (strlen($telefoneTutor) < 10) {
            return ['success' => false, 'errors' => ['Informe um telefone válido do tutor (com DDD).']];
        }

        if ($this->usuarioModel->findByTelefone($telefoneTutor)) {
            return ['success' => false, 'errors' => ['Já existe um tutor cadastrado com esse telefone. Use a busca por tutor para localizá-lo, em vez de cadastrar de novo.']];
        }
        if ($emailTutor !== '' && $this->usuarioModel->findByEmail($emailTutor)) {
            return ['success' => false, 'errors' => ['Já existe uma conta cadastrada com esse e-mail. Use a busca por tutor para localizá-lo.']];
        }
        if ($emailTutor !== '' && !isValidEmail($emailTutor)) {
            return ['success' => false, 'errors' => ['E-mail do tutor inválido.']];
        }

        if ($emailTutor === '') {
            $emailTutor = 'tutor.' . $telefoneTutor . '@sememail.cademeupet.local';
        }

        $tutorId = $this->usuarioModel->create([
            'nome'               => $nomeTutor,
            'email'              => $emailTutor,
            'telefone'           => $telefoneTutor,
            'senha'              => hashPassword(bin2hex(random_bytes(16))),
            'tipo_usuario'       => 'comum',
            'email_confirmado'   => 0,
            'ativo'              => 1,
            'notificacoes_email' => 0,
        ]);
        auditLog('criar_tutor_pelo_veterinario', 'usuarios', (int)$tutorId);

        $resultadoPet = $this->criarPetDuranteAtendimento($veterinarioUsuarioId, (int)$tutorId, $dadosPet);
        if (empty($resultadoPet['success'])) {
            return $resultadoPet;
        }

        return ['success' => true, 'tutor_id' => (int)$tutorId, 'pet_id' => $resultadoPet['pet_id']];
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

    public function abrir(
        int $veterinarioUsuarioId,
        int $petId,
        string $motivoConsulta,
        ?int $triagemSolicitacaoId = null,
        string $tipoAtendimento = 'consulta'
    ): array {
        $veterinario = $this->veterinarioModel->buscarAprovadoPorUsuarioId($veterinarioUsuarioId);
        if (!$veterinario) {
            return ['success' => false, 'errors' => ['Você ainda não está aprovado como veterinário nesta plataforma.']];
        }

        $pet = $this->petModel->buscarPorId($petId);
        if (!$pet) {
            return ['success' => false, 'errors' => ['Pet não encontrado.']];
        }

        if (!in_array($tipoAtendimento, Atendimento::TIPOS_ATENDIMENTO, true)) {
            return ['success' => false, 'errors' => ['Tipo de atendimento inválido.']];
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
            'tipo_atendimento'       => $tipoAtendimento,
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

    /**
     * Acesso de leitura ao atendimento: o veterinário responsável pode editar;
     * qualquer outro veterinário ou o dono da mesma clínica pode só visualizar.
     */
    public function buscarParaVisualizacao(int $atendimentoId, int $usuarioId): array
    {
        $atendimento = $this->atendimentoModel->buscarPorId($atendimentoId);
        if (!$atendimento) {
            return ['atendimento' => null, 'souOTratante' => false];
        }

        $veterinario = $this->veterinarioModel->buscarPorUsuarioId($usuarioId);
        $souOTratante = $veterinario && $this->atendimentoModel->pertenceAoVeterinario($atendimentoId, (int)$veterinario['id']);
        if ($souOTratante) {
            return ['atendimento' => $atendimento, 'souOTratante' => true];
        }

        $souDaClinica = $veterinario && (int)$veterinario['parceiro_perfil_id'] === (int)$atendimento['parceiro_perfil_id'];
        if (!$souDaClinica) {
            $perfil = $this->parceiroPerfilModel->findByUserId($usuarioId);
            $souDaClinica = $perfil && (int)$perfil['id'] === (int)$atendimento['parceiro_perfil_id'];
        }
        if ($souDaClinica) {
            return ['atendimento' => $atendimento, 'souOTratante' => false];
        }

        return ['atendimento' => null, 'souOTratante' => false];
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
        if (isset($dados['exame_nome']) && is_array($dados['exame_nome'])) {
            $dados['exames_solicitados'] = json_encode($this->normalizarExames($dados), JSON_UNESCAPED_UNICODE);
        }
        if (isset($dados['medicamento_nome']) && is_array($dados['medicamento_nome'])) {
            $dados['medicamentos_prescritos'] = json_encode($this->normalizarMedicamentos($dados), JSON_UNESCAPED_UNICODE);
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

    /** Monta o array estruturado de exames solicitados a partir dos campos repetidos do formulário. */
    private function normalizarExames(array $dados): array
    {
        $nomes = (array)($dados['exame_nome'] ?? []);
        $observacoes = (array)($dados['exame_observacao'] ?? []);

        $exames = [];
        foreach ($nomes as $indice => $nome) {
            $nome = trim((string)$nome);
            if ($nome === '') {
                continue;
            }
            $exames[] = [
                'nome' => $nome,
                'observacao' => !empty($observacoes[$indice]) ? $observacoes[$indice] : null,
            ];
        }
        return $exames;
    }

    /** Monta o array estruturado de medicamentos prescritos a partir dos campos repetidos do formulário. */
    private function normalizarMedicamentos(array $dados): array
    {
        $nomes = (array)($dados['medicamento_nome'] ?? []);
        $dosagens = (array)($dados['medicamento_dosagem'] ?? []);
        $vias = (array)($dados['medicamento_via'] ?? []);
        $frequencias = (array)($dados['medicamento_frequencia'] ?? []);
        $duracoes = (array)($dados['medicamento_duracao'] ?? []);

        $medicamentos = [];
        foreach ($nomes as $indice => $nome) {
            $nome = trim((string)$nome);
            if ($nome === '') {
                continue;
            }
            $medicamentos[] = [
                'nome'       => $nome,
                'dosagem'    => !empty($dosagens[$indice]) ? $dosagens[$indice] : null,
                'via'        => !empty($vias[$indice]) ? $vias[$indice] : null,
                'frequencia' => !empty($frequencias[$indice]) ? $frequencias[$indice] : null,
                'duracao'    => !empty($duracoes[$indice]) ? $duracoes[$indice] : null,
            ];
        }
        return $medicamentos;
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

        $faltantes = $this->requisitosFaltantes($atendimento);
        if (!empty($faltantes)) {
            return ['success' => false, 'errors' => $faltantes];
        }

        $this->atendimentoModel->finalizar($atendimentoId);
        auditLog('finalizar_atendimento', 'atendimentos', $atendimentoId);

        return ['success' => true];
    }

    /**
     * Retorna o que falta preencher para poder finalizar, de acordo com o
     * tipo de atendimento — cada tipo tem seu próprio critério mínimo,
     * evitando forçar o formato de consulta completa em procedimentos
     * rápidos (ex.: vacinação isolada não tem diagnóstico).
     */
    public function requisitosFaltantes(array $atendimento): array
    {
        $tipo = $atendimento['tipo_atendimento'] ?? 'consulta';
        $faltantes = [];

        switch ($tipo) {
            case 'vacinacao':
                if (empty($atendimento['peso_kg'])) {
                    $faltantes[] = 'Registre o peso do pet (aba Exame Físico).';
                }
                if (empty(Atendimento::decodificarVacinas($atendimento['vacinas_aplicadas'] ?? null))) {
                    $faltantes[] = 'Registre ao menos uma vacina aplicada (aba Exame Físico).';
                }
                break;

            case 'exame':
                if (empty(Atendimento::decodificarExames($atendimento['exames_solicitados'] ?? null))) {
                    $faltantes[] = 'Registre ao menos um exame solicitado (aba Exames).';
                }
                break;

            case 'retorno':
                if (empty($atendimento['conduta']) && empty($atendimento['anamnese'])) {
                    $faltantes[] = 'Registre uma breve nota de acompanhamento (aba Anamnese ou Diagnóstico e Conduta).';
                }
                break;

            case 'consulta':
            default:
                if (empty($atendimento['diagnostico']) && empty($atendimento['conduta'])) {
                    $faltantes[] = 'Preencha ao menos diagnóstico ou conduta antes de finalizar (aba Diagnóstico e Conduta).';
                }
                break;
        }

        return $faltantes;
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
