<?php
/**
 * Cadê Meu Pet? - Ficha permanente do pet (fundação do módulo de
 * Atendimento Veterinário / Laudo / Termo de Adoção).
 * Ver docs/modulo-atendimento-veterinario-laudo.md
 */
class Pet
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    public function criar(array $dados): int
    {
        return (int)$this->db->insert('pets', [
            'tutor_usuario_id'       => $dados['tutor_usuario_id'],
            'nome'                   => $dados['nome'],
            'especie'                => $dados['especie'],
            'raca'                   => $dados['raca'] ?? null,
            'sexo'                   => $dados['sexo'] ?? null,
            'data_nascimento'        => !empty($dados['data_nascimento']) ? $dados['data_nascimento'] : null,
            'idade_aproximada_meses' => isset($dados['idade_aproximada_meses']) && $dados['idade_aproximada_meses'] !== ''
                ? (int)$dados['idade_aproximada_meses'] : null,
            'cor'                    => $dados['cor'] ?? null,
            'foto'                   => $dados['foto'] ?? null,
            'microchip_numero'       => $dados['microchip_numero'] ?? null,
            'origem_anuncio_id'      => $dados['origem_anuncio_id'] ?? null,
            'ativo'                  => 1,
        ]);
    }

    public function atualizar(int $id, int $tutorUsuarioId, array $dados): bool
    {
        $campos = [];
        foreach (['nome', 'raca', 'sexo', 'cor', 'microchip_numero'] as $campo) {
            if (array_key_exists($campo, $dados)) {
                $campos[$campo] = $dados[$campo] ?: null;
            }
        }
        if (array_key_exists('data_nascimento', $dados)) {
            $campos['data_nascimento'] = $dados['data_nascimento'] ?: null;
        }
        if (array_key_exists('idade_aproximada_meses', $dados)) {
            $campos['idade_aproximada_meses'] = $dados['idade_aproximada_meses'] !== ''
                ? (int)$dados['idade_aproximada_meses'] : null;
        }
        if (array_key_exists('foto', $dados)) {
            $campos['foto'] = $dados['foto'];
        }
        if (empty($campos)) {
            return false;
        }
        return $this->db->update('pets', $campos, 'id = ? AND tutor_usuario_id = ?', [$id, $tutorUsuarioId]) !== false;
    }

    public function buscarPorId(int $id): ?array
    {
        $row = $this->db->fetchOne('SELECT * FROM pets WHERE id = ? AND ativo = 1', [$id]);
        return $row ?: null;
    }

    /**
     * Atualiza campos cadastrais por ID, sem exigir posse do tutor — uso
     * exclusivo de veterinário aprovado (checado na camada de controller),
     * já que o pet pode estar sendo atendido numa clínica diferente da que
     * ele "pertence".
     */
    public function atualizarCampos(int $id, array $dados): bool
    {
        $campos = [];
        foreach (['nome', 'especie', 'raca', 'sexo', 'cor', 'microchip_numero'] as $campo) {
            if (array_key_exists($campo, $dados)) {
                $campos[$campo] = $dados[$campo] ?: null;
            }
        }
        if (array_key_exists('data_nascimento', $dados)) {
            $campos['data_nascimento'] = $dados['data_nascimento'] ?: null;
        }
        if (array_key_exists('idade_aproximada_meses', $dados)) {
            $campos['idade_aproximada_meses'] = $dados['idade_aproximada_meses'] !== ''
                ? (int)$dados['idade_aproximada_meses'] : null;
        }
        if (empty($campos)) {
            return false;
        }
        return $this->db->update('pets', $campos, 'id = ?', [$id]) !== false;
    }

    public function pertenceAoTutor(int $id, int $tutorUsuarioId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT id FROM pets WHERE id = ? AND tutor_usuario_id = ? AND ativo = 1',
            [$id, $tutorUsuarioId]
        );
        return (bool)$row;
    }

    public function buscarPorTutor(int $tutorUsuarioId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM pets WHERE tutor_usuario_id = ? AND ativo = 1 ORDER BY criado_em DESC',
            [$tutorUsuarioId]
        ) ?: [];
    }

    /** Soft delete — nunca apaga histórico clínico associado. */
    public function desativar(int $id, int $tutorUsuarioId): bool
    {
        return $this->db->update('pets', ['ativo' => 0], 'id = ? AND tutor_usuario_id = ?', [$id, $tutorUsuarioId]) !== false;
    }

    /** Busca por nome do pet, ou nome/telefone do tutor — usado pelo veterinário para localizar o pet na recepção. */
    public function buscarPorTutorTelefoneOuNome(string $termo): array
    {
        $termo = '%' . trim($termo) . '%';
        return $this->db->fetchAll(
            "SELECT p.*, u.nome AS tutor_nome, u.telefone AS tutor_telefone
             FROM pets p
             JOIN usuarios u ON u.id = p.tutor_usuario_id
             WHERE p.ativo = 1 AND (p.nome LIKE ? OR u.nome LIKE ? OR u.telefone LIKE ?)
             ORDER BY p.nome
             LIMIT 20",
            [$termo, $termo, $termo]
        ) ?: [];
    }
}
