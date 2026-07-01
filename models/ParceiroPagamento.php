<?php

class ParceiroPagamento
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    public function create(array $data): int
    {
        return $this->db->insert('parceiro_pagamentos', $data);
    }

    public function closePendingForUser(int $usuarioId): void
    {
        $usuarioId = (int)$usuarioId;
        if ($usuarioId <= 0) {
            return;
        }

        $this->db->query(
            "UPDATE parceiro_pagamentos
             SET status = 'recusado', recusado_em = ?
             WHERE usuario_id = ? AND status = 'pendente'",
            [date('Y-m-d H:i:s'), $usuarioId]
        );
    }

    public function update(int $id, array $data)
    {
        return $this->db->update('parceiro_pagamentos', $data, 'id = ?', [$id]);
    }

    public function findByReferencia(string $referencia)
    {
        $referencia = trim($referencia);
        if ($referencia === '') {
            return null;
        }

        return $this->db->fetchOne(
            'SELECT p.*, u.email, u.nome as usuario_nome
             FROM parceiro_pagamentos p
             JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.referencia = ?
             LIMIT 1',
            [$referencia]
        );
    }

    public function findByEfiChargeId($chargeId)
    {
        $chargeId = (string)$chargeId;
        $chargeId = preg_replace('/[^0-9]/', '', $chargeId);
        if ($chargeId === '') {
            return null;
        }

        return $this->db->fetchOne(
            'SELECT p.*, u.email, u.nome as usuario_nome
             FROM parceiro_pagamentos p
             JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.efi_charge_id = ?
             LIMIT 1',
            [$chargeId]
        );
    }

    public function findLastBySubscriptionId($subscriptionId)
    {
        $subscriptionId = (string)$subscriptionId;
        $subscriptionId = preg_replace('/[^0-9]/', '', $subscriptionId);
        if ($subscriptionId === '') {
            return null;
        }

        return $this->db->fetchOne(
            'SELECT p.*, u.email, u.nome as usuario_nome
             FROM parceiro_pagamentos p
             JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.efi_subscription_id = ?
             ORDER BY p.data_criacao DESC
             LIMIT 1',
            [$subscriptionId]
        );
    }

    public function listByStatus(string $status, int $limit = 20, int $offset = 0): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        return $this->db->fetchAll(
            'SELECT p.*, u.email, u.nome as usuario_nome
             FROM parceiro_pagamentos p
             JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.status = ?
             ORDER BY p.data_criacao DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset,
            [$status]
        );
    }

    public function sumByStatus(string $status): float
    {
        $status = trim($status);
        if ($status === '') {
            return 0.0;
        }

        $row = $this->db->fetchOne(
            'SELECT COALESCE(SUM(valor), 0) AS total
             FROM parceiro_pagamentos
             WHERE status = ?',
            [$status]
        );

        return (float)($row['total'] ?? 0);
    }

    public function sumByStatusInMonth(string $status, string $mes): float
    {
        $status = trim($status);
        $mes = trim($mes);
        if ($status === '' || $mes === '' || !preg_match('/^\d{4}-\d{2}$/', $mes)) {
            return 0.0;
        }

        $row = $this->db->fetchOne(
            'SELECT COALESCE(SUM(valor), 0) AS total
             FROM parceiro_pagamentos
             WHERE status = ? AND DATE_FORMAT(data_criacao, "%Y-%m") = ?',
            [$status, $mes]
        );

        return (float)($row['total'] ?? 0);
    }

    public function countByStatus(string $status): int
    {
        $status = trim($status);
        if ($status === '') {
            return 0;
        }

        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS total
             FROM parceiro_pagamentos
             WHERE status = ?',
            [$status]
        );

        return (int)($row['total'] ?? 0);
    }

    public function getDashboardSummary(): array
    {
        $row = $this->db->fetchOne(
            'SELECT
                COUNT(*) AS total_pagamentos,
                SUM(CASE WHEN status = "aprovado" THEN valor ELSE 0 END) AS total_aprovado,
                SUM(CASE WHEN DATE_FORMAT(data_criacao, "%Y-%m") = DATE_FORMAT(CURDATE(), "%Y-%m") AND status = "aprovado" THEN valor ELSE 0 END) AS mes_atual,
                SUM(CASE WHEN status = "pendente" THEN 1 ELSE 0 END) AS pendentes,
                SUM(CASE WHEN status = "aprovado" THEN 1 ELSE 0 END) AS aprovados,
                SUM(CASE WHEN status = "recusado" THEN 1 ELSE 0 END) AS recusados
             FROM parceiro_pagamentos'
        );

        return is_array($row) ? $row : [];
    }

    public function countAll(?string $status = null, ?string $mes = null): int
    {
        $where = [];
        $params = [];

        if ($status !== null && $status !== '') {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }

        if ($mes !== null && $mes !== '') {
            $dt = DateTime::createFromFormat('Y-m', $mes);
            if ($dt instanceof DateTime) {
                $start = $dt->format('Y-m-01 00:00:00');
                $end = $dt->modify('+1 month')->format('Y-m-01 00:00:00');
                $where[] = 'p.data_criacao >= ? AND p.data_criacao < ?';
                $params[] = $start;
                $params[] = $end;
            }
        }

        $sql = 'SELECT COUNT(*) as total FROM parceiro_pagamentos p';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $row = $this->db->fetchOne($sql, $params);
        return (int)($row['total'] ?? 0);
    }

    public function findAll(int $limit = 30, int $offset = 0, ?string $status = null, ?string $mes = null): array
    {
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);

        $where = [];
        $params = [];

        if ($status !== null && $status !== '') {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }

        if ($mes !== null && $mes !== '') {
            $dt = DateTime::createFromFormat('Y-m', $mes);
            if ($dt instanceof DateTime) {
                $start = $dt->format('Y-m-01 00:00:00');
                $end = $dt->modify('+1 month')->format('Y-m-01 00:00:00');
                $where[] = 'p.data_criacao >= ? AND p.data_criacao < ?';
                $params[] = $start;
                $params[] = $end;
            }
        }

        $sql = 'SELECT p.*, u.email, u.nome as usuario_nome
                FROM parceiro_pagamentos p
                JOIN usuarios u ON u.id = p.usuario_id';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY p.data_criacao DESC LIMIT ' . $limit . ' OFFSET ' . $offset;

        return $this->db->fetchAll($sql, $params);
    }

    public function approve(int $pagamentoId, int $adminId): void
    {
        $this->db->update(
            'parceiro_pagamentos',
            [
                'status' => 'aprovado',
                'aprovado_em' => date('Y-m-d H:i:s'),
                'aprovado_por' => $adminId,
            ],
            'id = ?',
            [$pagamentoId]
        );
    }

    public function reject(int $pagamentoId, int $adminId): void
    {
        $this->db->update(
            'parceiro_pagamentos',
            [
                'status' => 'recusado',
                'recusado_em' => date('Y-m-d H:i:s'),
                'aprovado_por' => $adminId,
            ],
            'id = ?',
            [$pagamentoId]
        );
    }

    public function findById(int $id)
    {
        return $this->db->fetchOne(
            'SELECT p.*, u.email, u.nome as usuario_nome
             FROM parceiro_pagamentos p
             JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.id = ? LIMIT 1',
            [$id]
        );
    }
}
