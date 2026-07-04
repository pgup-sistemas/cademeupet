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
