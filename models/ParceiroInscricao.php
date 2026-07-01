<?php

class ParceiroInscricao
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    public function findByUserId(int $usuarioId)
    {
        return $this->db->fetchOne('SELECT * FROM parceiro_inscricoes WHERE usuario_id = ? LIMIT 1', [$usuarioId]);
    }

    public function create(array $data): int
    {
        return $this->db->insert('parceiro_inscricoes', $data);
    }

    public function listByStatus(string $status, int $limit = 20, int $offset = 0): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        return $this->db->fetchAll(
            'SELECT pi.*, u.nome as usuario_nome, u.email,
                    COALESCE(NULLIF(pi.telefone, ""), NULLIF(u.telefone, ""), "") AS telefone
             FROM parceiro_inscricoes pi
             JOIN usuarios u ON u.id = pi.usuario_id
             WHERE pi.status = ?
             ORDER BY pi.data_criacao DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset,
            [$status]
        );
    }

    public function countByStatus(string $status): int
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS total FROM parceiro_inscricoes WHERE status = ?',
            [$status]
        );

        return (int)($row['total'] ?? 0);
    }

    public function approve(int $inscricaoId, int $adminId): void
    {
        $this->db->update(
            'parceiro_inscricoes',
            [
                'status' => 'aprovada',
                'aprovada_em' => date('Y-m-d H:i:s'),
                'recusada_em' => null,
                'analisada_por' => $adminId,
            ],
            'id = ?',
            [$inscricaoId]
        );
    }

    public function reject(int $inscricaoId, int $adminId): void
    {
        $this->db->update(
            'parceiro_inscricoes',
            [
                'status' => 'recusada',
                'recusada_em' => date('Y-m-d H:i:s'),
                'aprovada_em' => null,
                'analisada_por' => $adminId,
            ],
            'id = ?',
            [$inscricaoId]
        );
    }

    public function findById(int $id)
    {
        return $this->db->fetchOne(
            'SELECT pi.*, u.email, u.nome as usuario_nome
             FROM parceiro_inscricoes pi
             JOIN usuarios u ON u.id = pi.usuario_id
             WHERE pi.id = ? LIMIT 1',
            [$id]
        );
    }

    public function reopen(int $inscricaoId, int $adminId): void
    {
        $this->db->update(
            'parceiro_inscricoes',
            [
                'status'       => 'pendente',
                'recusada_em'  => null,
                'aprovada_em'  => null,
                'analisada_por' => $adminId,
            ],
            'id = ?',
            [$inscricaoId]
        );
    }

    public function delete(int $inscricaoId): void
    {
        $this->db->fetchAll('DELETE FROM parceiro_inscricoes WHERE id = ?', [$inscricaoId]);
    }
}
