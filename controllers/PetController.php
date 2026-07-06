<?php
/**
 * Cadê Meu Pet? - Controller da ficha permanente do pet ("Meus Pets").
 * Fundação do módulo de Atendimento Veterinário / Laudo / Termo de Adoção.
 */
class PetController
{
    private $petModel;

    public function __construct($petModel = null)
    {
        $this->petModel = $petModel ?: new Pet();
    }

    public function listarPorTutor(int $tutorUsuarioId): array
    {
        return $this->petModel->buscarPorTutor($tutorUsuarioId);
    }

    public function buscarSeForDoTutor(int $petId, int $tutorUsuarioId): ?array
    {
        if (!$this->petModel->pertenceAoTutor($petId, $tutorUsuarioId)) {
            return null;
        }
        return $this->petModel->buscarPorId($petId);
    }

    public function criar(int $tutorUsuarioId, array $dados, array $arquivoFoto = []): array
    {
        $dados = sanitize($dados);
        $erros = $this->validar($dados);
        if (!empty($erros)) {
            return ['success' => false, 'errors' => $erros];
        }

        $nomeArquivo = null;
        if (!empty($arquivoFoto['tmp_name'])) {
            $upload = uploadImage($arquivoFoto, BASE_PATH . '/uploads/pets');
            if (empty($upload['success'])) {
                return ['success' => false, 'errors' => $upload['errors'] ?? ['Erro ao enviar a foto.']];
            }
            $nomeArquivo = $upload['filename'];
        }

        $id = $this->petModel->criar([
            'tutor_usuario_id'       => $tutorUsuarioId,
            'nome'                   => $dados['nome'],
            'especie'                => $dados['especie'],
            'raca'                   => $dados['raca'] ?? null,
            'sexo'                   => $dados['sexo'] ?? null,
            'data_nascimento'        => $dados['data_nascimento'] ?? null,
            'idade_aproximada_meses' => $dados['idade_aproximada_meses'] ?? null,
            'cor'                    => $dados['cor'] ?? null,
            'foto'                   => $nomeArquivo,
            'microchip_numero'       => $dados['microchip_numero'] ?? null,
            'origem_anuncio_id'      => !empty($dados['origem_anuncio_id']) ? (int)$dados['origem_anuncio_id'] : null,
        ]);

        auditLog('criar_pet', 'pets', $id);

        return ['success' => true, 'id' => $id];
    }

    public function atualizar(int $petId, int $tutorUsuarioId, array $dados, array $arquivoFoto = []): array
    {
        if (!$this->petModel->pertenceAoTutor($petId, $tutorUsuarioId)) {
            return ['success' => false, 'errors' => ['Pet não encontrado.']];
        }

        $dados = sanitize($dados);
        $erros = $this->validar($dados, false);
        if (!empty($erros)) {
            return ['success' => false, 'errors' => $erros];
        }

        if (!empty($arquivoFoto['tmp_name'])) {
            $upload = uploadImage($arquivoFoto, BASE_PATH . '/uploads/pets');
            if (empty($upload['success'])) {
                return ['success' => false, 'errors' => $upload['errors'] ?? ['Erro ao enviar a foto.']];
            }
            $dados['foto'] = $upload['filename'];
        }

        $this->petModel->atualizar($petId, $tutorUsuarioId, $dados);
        auditLog('atualizar_pet', 'pets', $petId);

        return ['success' => true];
    }

    /**
     * Edição de dados cadastrais do pet feita por um veterinário aprovado
     * (qualquer clínica) durante um atendimento — não exige que o pet
     * "pertença" à clínica atual, consistente com o histórico clínico
     * compartilhado entre clínicas (ver docs/modulo-atendimento-veterinario-laudo.md).
     */
    public function atualizarComoVeterinario(int $petId, int $veterinarioUsuarioId, array $dados): array
    {
        $veterinarioModel = new ParceiroVeterinario();
        if (!$veterinarioModel->buscarAprovadoPorUsuarioId($veterinarioUsuarioId)) {
            return ['success' => false, 'errors' => ['Você não está aprovado como veterinário.']];
        }

        $pet = $this->petModel->buscarPorId($petId);
        if (!$pet) {
            return ['success' => false, 'errors' => ['Pet não encontrado.']];
        }

        $dados = sanitize($dados);
        $erros = $this->validar($dados, false);
        if (!empty($erros)) {
            return ['success' => false, 'errors' => $erros];
        }
        if (empty($dados['nome']) && array_key_exists('nome', $dados)) {
            return ['success' => false, 'errors' => ['O nome do pet não pode ficar em branco.']];
        }

        $this->petModel->atualizarCampos($petId, $dados);
        auditLog('atualizar_pet_pelo_veterinario', 'pets', $petId);

        return ['success' => true];
    }

    public function desativar(int $petId, int $tutorUsuarioId): bool
    {
        $ok = $this->petModel->desativar($petId, $tutorUsuarioId);
        if ($ok) {
            auditLog('desativar_pet', 'pets', $petId);
        }
        return $ok;
    }

    private function validar(array $dados, bool $exigirCamposObrigatorios = true): array
    {
        $erros = [];

        if ($exigirCamposObrigatorios && empty($dados['nome'])) {
            $erros[] = 'Informe o nome do pet.';
        }
        if ($exigirCamposObrigatorios && empty($dados['especie'])) {
            $erros[] = 'Informe a espécie do pet.';
        }
        if (!empty($dados['sexo']) && !in_array($dados['sexo'], ['macho', 'femea'], true)) {
            $erros[] = 'Sexo inválido.';
        }
        if (!empty($dados['data_nascimento']) && !strtotime($dados['data_nascimento'])) {
            $erros[] = 'Data de nascimento inválida.';
        }

        return $erros;
    }
}
