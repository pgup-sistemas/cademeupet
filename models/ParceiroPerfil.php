<?php

class ParceiroPerfil
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    public function findByUserId(int $usuarioId)
    {
        return $this->db->fetchOne('SELECT * FROM parceiro_perfis WHERE usuario_id = ? LIMIT 1', [$usuarioId]);
    }

    public function findBySlug(string $slug)
    {
        return $this->db->fetchOne(
            'SELECT pp.*, u.email as usuario_email
             FROM parceiro_perfis pp
             JOIN usuarios u ON u.id = pp.usuario_id
             WHERE pp.slug = ? AND pp.publicado = 1
             LIMIT 1',
            [$slug]
        );
    }

    public function listPublic(?string $cidade = null, ?string $categoria = null, int $limit = 12, int $offset = 0): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        [$whereSql, $params] = $this->buildPublicFilter($cidade, $categoria);

        $sql =
            'SELECT pp.*
             FROM parceiro_perfis pp
             WHERE ' . $whereSql .
            ' ORDER BY pp.destaque DESC, pp.verificado DESC, pp.nome_fantasia ASC
             LIMIT ' . $limit . ' OFFSET ' . $offset;

        return $this->db->fetchAll($sql, $params);
    }

    public function countPublic(?string $cidade = null, ?string $categoria = null): int
    {
        [$whereSql, $params] = $this->buildPublicFilter($cidade, $categoria);

        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS total FROM parceiro_perfis pp WHERE ' . $whereSql,
            $params
        );

        return (int)($row['total'] ?? 0);
    }

    private function buildPublicFilter(?string $cidade, ?string $categoria): array
    {
        $where = ['pp.publicado = 1'];
        $params = [];

        if ($cidade !== null && trim($cidade) !== '') {
            $where[] = 'LOWER(pp.cidade) LIKE LOWER(?)';
            $params[] = '%' . trim($cidade) . '%';
        }

        if ($categoria !== null && trim($categoria) !== '') {
            $where[] = 'pp.categoria = ?';
            $params[] = trim($categoria);
        }

        return [implode(' AND ', $where), $params];
    }

    public function create(array $data): int
    {
        return $this->db->insert('parceiro_perfis', $data);
    }

    public function update(int $id, array $data)
    {
        return $this->db->update('parceiro_perfis', $data, 'id = ?', [$id]);
    }

    public function updateForUser(int $usuarioId, array $data)
    {
        return $this->db->update('parceiro_perfis', $data, 'usuario_id = ?', [$usuarioId]);
    }

    public function publishForUser(int $usuarioId, bool $publish): void
    {
        $this->db->update('parceiro_perfis', ['publicado' => $publish ? 1 : 0], 'usuario_id = ?', [$usuarioId]);
    }

    public function setVerifiedForUser(int $usuarioId, bool $verified): void
    {
        $this->db->update('parceiro_perfis', ['verificado' => $verified ? 1 : 0], 'usuario_id = ?', [$usuarioId]);
    }

    public function setHighlightForUser(int $usuarioId, bool $highlight): void
    {
        $this->db->update('parceiro_perfis', ['destaque' => $highlight ? 1 : 0], 'usuario_id = ?', [$usuarioId]);
    }

    public function generateUniqueSlug(string $nomeFantasia, string $cidade, string $estado): string
    {
        $base = strtolower(trim($nomeFantasia . '-' . $cidade . '-' . $estado));
        $base = iconv('UTF-8', 'ASCII//TRANSLIT', $base);
        $base = preg_replace('/[^a-z0-9\s-]/', '', $base);
        $base = preg_replace('/\s+/', '-', $base);
        $base = preg_replace('/-+/', '-', $base);
        $base = trim($base, '-');

        if ($base === '') {
            $base = 'parceiro';
        }

        $slug = $base;
        $i = 2;
        while ($this->db->fetchOne('SELECT id FROM parceiro_perfis WHERE slug = ? LIMIT 1', [$slug])) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
