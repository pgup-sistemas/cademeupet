<?php
/**
 * Cadê Meu Pet? - Modelo de Doação
 * Gerencia as operações do módulo de doações, incluindo métricas e histórico do usuário.
 */

class Doacao
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * Registra doação (única ou recorrente).
     */
    public function create(array $data): int
    {
        return $this->db->insert('doacoes', $data);
    }

    public function update(int $id, array $data)
    {
        return $this->db->update('doacoes', $data, 'id = ?', [$id]);
    }

    public function findById(int $id)
    {
        return $this->db->fetchOne('SELECT * FROM doacoes WHERE id = ? LIMIT 1', [$id]);
    }

    public function findByTransactionId(string $transactionId)
    {
        return $this->db->fetchOne('SELECT * FROM doacoes WHERE transaction_id = ? LIMIT 1', [$transactionId]);
    }

    public function findByEfiChargeId($chargeId)
    {
        $chargeId = (string)$chargeId;
        $chargeId = preg_replace('/[^0-9]/', '', $chargeId);
        if ($chargeId === '') {
            return null;
        }

        return $this->db->fetchOne('SELECT * FROM doacoes WHERE efi_charge_id = ? LIMIT 1', [$chargeId]);
    }

    public function findLastBySubscriptionId($subscriptionId)
    {
        $subscriptionId = (string)$subscriptionId;
        $subscriptionId = preg_replace('/[^0-9]/', '', $subscriptionId);
        if ($subscriptionId === '') {
            return null;
        }

        return $this->db->fetchOne(
            'SELECT *
             FROM doacoes
             WHERE efi_subscription_id = ?
             ORDER BY data_doacao DESC
             LIMIT 1',
            [$subscriptionId]
        );
    }

    /**
     * Atualiza status após retorno do gateway.
     */
    public function updateStatus(int $id, string $status, array $extras = [])
    {
        $payload = array_merge($extras, ['status' => $status]);
        return $this->db->update('doacoes', $payload, 'id = ?', [$id]);
    }

    public function countApprovedDonations(): int
    {
        $row = $this->db->fetchOne('SELECT COUNT(*) AS total FROM doacoes WHERE status IN ("aprovado", "aprovada")');
        return (int)($row['total'] ?? 0);
    }

    public function sumApprovedDonations(): float
    {
        $row = $this->db->fetchOne('SELECT COALESCE(SUM(valor), 0) AS total FROM doacoes WHERE status IN ("aprovado", "aprovada")');
        return (float)($row['total'] ?? 0);
    }

    public function getApprovedDonationsPublic(int $limit = 20, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT id, valor, nome_doador, mensagem, data_doacao
             FROM doacoes
             WHERE status IN ("aprovado", "aprovada") AND exibir_mural = 1
             ORDER BY data_doacao DESC
             LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    public function countApprovedDonationsPublic(): int
    {
        $row = $this->db->fetchOne('SELECT COUNT(*) AS total FROM doacoes WHERE status IN ("aprovado", "aprovada") AND exibir_mural = 1');
        return (int)($row['total'] ?? 0);
    }

    public function findAll(int $limit = 50, int $offset = 0, ?string $status = null): array
    {
        $status = $status !== null ? trim($status) : null;
        $params = [];

        $sql = 'SELECT * FROM doacoes';
        if ($status !== null && $status !== '') {
            $sql .= ' WHERE status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY data_doacao DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    public function countAll(?string $status = null): int
    {
        $status = $status !== null ? trim($status) : null;
        if ($status !== null && $status !== '') {
            $row = $this->db->fetchOne('SELECT COUNT(*) AS total FROM doacoes WHERE status = ?', [$status]);
            return (int)($row['total'] ?? 0);
        }

        $row = $this->db->fetchOne('SELECT COUNT(*) AS total FROM doacoes');
        return (int)($row['total'] ?? 0);
    }

    /**
     * Busca doações por usuário (histórico com filtros básicos).
     */
    public function findByUser(int $usuarioId, int $limit = 20, int $offset = 0)
    {
        return $this->db->fetchAll(
            'SELECT * FROM doacoes WHERE usuario_id = ? ORDER BY data_doacao DESC LIMIT ? OFFSET ?',
            [$usuarioId, $limit, $offset]
        );
    }

    /**
     * Obtém sumário para dashboard.
     */
    public function getDashboardSummary()
    {
        return $this->db->fetchOne(
            'SELECT 
                COUNT(*) AS total_doacoes,
                SUM(CASE WHEN status IN ("aprovado", "aprovada") THEN valor ELSE 0 END) AS total_aprovado,
                SUM(CASE WHEN tipo = "mensal" AND status IN ("aprovado", "aprovada") THEN valor ELSE 0 END) AS recorrente,
                SUM(CASE WHEN DATE_FORMAT(data_doacao, "%Y-%m") = DATE_FORMAT(CURDATE(), "%Y-%m") AND status IN ("aprovado", "aprovada") THEN valor ELSE 0 END) AS mes_atual
             FROM doacoes'
        );
    }

    /**
     * Busca mural de doadores (com permissão de exibição).
     */
    public function getMural(int $limit = 20)
    {
        return $this->db->fetchAll(
            'SELECT nome_doador, mensagem, valor, data_doacao 
             FROM doacoes 
             WHERE status IN ("aprovado", "aprovada") AND exibir_mural = 1 
             ORDER BY data_doacao DESC 
             LIMIT ?',
            [$limit]
        );
    }

    /**
     * Calcula progresso da meta financeira atual.
     */
    public function getCurrentGoalProgress()
    {
        return $this->db->fetchOne(
            'SELECT m.id, m.mes_referencia, m.valor_meta,
                    COALESCE((
                        SELECT SUM(d.valor) FROM doacoes d
                        WHERE d.status = "aprovada"
                          AND MONTH(d.data_doacao) = MONTH(CURDATE())
                          AND YEAR(d.data_doacao)  = YEAR(CURDATE())
                    ), 0) AS valor_arrecadado,
                    m.custos_servidor, m.custos_manutencao, m.custos_outros, m.descricao
             FROM metas_financeiras m
             WHERE m.ativo = 1
             ORDER BY m.mes_referencia DESC
             LIMIT 1'
        );
    }

    /**
     * Atualiza valor arrecadado da meta ativa.
     */
    public function updateGoalProgress(float $valor)
    {
        return $this->db->query(
            'UPDATE metas_financeiras 
             SET valor_arrecadado = valor_arrecadado + ?
             WHERE ativo = 1
             ORDER BY mes_referencia DESC
             LIMIT 1',
            [$valor]
        );
    }
}

