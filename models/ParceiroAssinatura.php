<?php

class ParceiroAssinatura
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    public function findByUserId(int $usuarioId)
    {
        return $this->db->fetchOne('SELECT * FROM parceiro_assinaturas WHERE usuario_id = ? LIMIT 1', [$usuarioId]);
    }

    public function create(array $data): int
    {
        return $this->db->insert('parceiro_assinaturas', $data);
    }

    public function setStatus(int $usuarioId, string $status): void
    {
        $this->db->update('parceiro_assinaturas', ['status' => $status], 'usuario_id = ?', [$usuarioId]);
    }

    public function updateForUser(int $usuarioId, array $data)
    {
        return $this->db->update('parceiro_assinaturas', $data, 'usuario_id = ?', [$usuarioId]);
    }

    public function countByStatus(string $status): int
    {
        $status = trim($status);
        if ($status === '') {
            return 0;
        }

        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS total
             FROM parceiro_assinaturas
             WHERE status = ?',
            [$status]
        );

        return (int)($row['total'] ?? 0);
    }

    public function countExpiringSoon(int $days = 7): int
    {
        $days = max(1, (int)$days);
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS total
             FROM parceiro_assinaturas
             WHERE status = "ativa" AND pago_ate IS NOT NULL AND pago_ate <= DATE_ADD(CURDATE(), INTERVAL ? DAY)',
            [$days]
        );
        return (int)($row['total'] ?? 0);
    }

    public function getDashboardSummary(int $expiringDays = 7): array
    {
        $expiringDays = max(1, (int)$expiringDays);
        $row = $this->db->fetchOne(
            'SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = "ativa" THEN 1 ELSE 0 END) AS ativas,
                SUM(CASE WHEN status = "pendente_pagamento" THEN 1 ELSE 0 END) AS pendentes,
                SUM(CASE WHEN status = "suspensa" THEN 1 ELSE 0 END) AS suspensas,
                SUM(CASE WHEN status = "cancelada" THEN 1 ELSE 0 END) AS canceladas,
                SUM(CASE WHEN status = "ativa" AND pago_ate IS NOT NULL AND pago_ate <= DATE_ADD(CURDATE(), INTERVAL ? DAY) THEN 1 ELSE 0 END) AS expirando
             FROM parceiro_assinaturas',
            [$expiringDays]
        );

        return is_array($row) ? $row : [];
    }

    public function listExpiringSoon(int $days = 7, int $limit = 10): array
    {
        $days = max(1, (int)$days);
        $limit = max(1, min(100, (int)$limit));

        return $this->db->fetchAll(
            'SELECT a.*, u.nome AS usuario_nome, u.email
             FROM parceiro_assinaturas a
             JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.status = "ativa" AND a.pago_ate IS NOT NULL AND a.pago_ate <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY a.pago_ate ASC
             LIMIT ' . $limit,
            [$days]
        );
    }

    public function listAll(int $limit = 20, int $offset = 0): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        return $this->db->fetchAll(
            'SELECT a.*,
                    u.nome AS usuario_nome, u.email,
                    pp.efi_subscription_id AS pagamento_subscription_id,
                    pp.gateway_tipo,
                    pp.id AS pagamento_id
             FROM parceiro_assinaturas a
             JOIN usuarios u ON u.id = a.usuario_id
             LEFT JOIN parceiro_pagamentos pp ON pp.usuario_id = a.usuario_id
                 AND pp.status = "aprovado"
                 AND pp.efi_subscription_id IS NOT NULL
             ORDER BY FIELD(a.status,"ativa","pendente_pagamento","suspensa","cancelada"), u.nome ASC
             LIMIT ' . $limit . ' OFFSET ' . $offset
        );
    }

    public function countAll(): int
    {
        $row = $this->db->fetchOne('SELECT COUNT(*) AS total FROM parceiro_assinaturas');

        return (int)($row['total'] ?? 0);
    }

    public function cancelar(int $usuarioId, string $canceladaEm = ''): void
    {
        if ($canceladaEm === '') {
            $canceladaEm = date('Y-m-d H:i:s');
        }
        $this->db->update(
            'parceiro_assinaturas',
            ['status' => 'cancelada', 'cancelada_em' => $canceladaEm],
            'usuario_id = ?',
            [$usuarioId]
        );
    }
}
