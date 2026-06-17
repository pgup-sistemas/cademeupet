<?php
/**
 * Cadê Meu Pet? - Modelo de Alertas de Busca
 */
class Alerta
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    public function create(array $data)
    {
        return $this->db->insert('alertas', [
            'usuario_id' => $data['usuario_id'],
            'tipo' => $data['tipo'] ?? 'ambos',
            'especie' => $data['especie'] ?? null,
            'cidade' => $data['cidade'],
            'estado' => strtoupper($data['estado']),
            'raio_km' => $data['raio_km'] ?? 10,
            'ativo' => isset($data['ativo']) ? (int) (bool) $data['ativo'] : 1,
            'ultimo_envio' => $data['ultimo_envio'] ?? null,
            'data_criacao' => date('Y-m-d H:i:s')
        ]);
    }

    public function update(int $id, int $usuarioId, array $data)
    {
        $payload = [];

        $camposPermitidos = ['tipo', 'especie', 'cidade', 'estado', 'raio_km', 'ativo'];
        foreach ($camposPermitidos as $campo) {
            if (!array_key_exists($campo, $data)) {
                continue;
            }

            $valor = $data[$campo];
            if ($campo === 'estado') {
                $valor = strtoupper($valor);
            }
            if ($campo === 'ativo') {
                $valor = $valor ? 1 : 0;
            }

            $payload[$campo] = $valor;
        }

        if (empty($payload)) {
            return false;
        }

        return $this->db->update(
            'alertas',
            $payload,
            'id = ? AND usuario_id = ?',
            [$id, $usuarioId]
        );
    }

    public function delete(int $id, int $usuarioId)
    {
        return $this->db->delete('alertas', 'id = ? AND usuario_id = ?', [$id, $usuarioId]);
    }

    public function findById(int $id, int $usuarioId)
    {
        return $this->db->fetchOne(
            'SELECT * FROM alertas WHERE id = ? AND usuario_id = ?',
            [$id, $usuarioId]
        );
    }

    public function listByUser(int $usuarioId)
    {
        return $this->db->fetchAll(
            'SELECT * FROM alertas WHERE usuario_id = ? ORDER BY data_criacao DESC',
            [$usuarioId]
        );
    }

    public function countByUser(int $usuarioId)
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS total FROM alertas WHERE usuario_id = ? AND ativo = 1',
            [$usuarioId]
        );
        return (int)($row['total'] ?? 0);
    }

    public function listActiveAlerts()
    {
        return $this->db->fetchAll(
            'SELECT * FROM alertas WHERE ativo = 1'
        );
    }

    public function updateLastSent(int $id)
    {
        return $this->db->update(
            'alertas',
            ['ultimo_envio' => date('Y-m-d H:i:s')],
            'id = ?',
            [$id]
        );
    }

    public function listDueAlerts(int $limit = 100)
    {
        $interval = defined('ALERT_MIN_INTERVAL_SECONDS') ? ALERT_MIN_INTERVAL_SECONDS : 3600;

        $sql = 'SELECT *
                FROM alertas
                WHERE ativo = 1
                  AND (
                        ultimo_envio IS NULL
                        OR TIMESTAMPDIFF(SECOND, ultimo_envio, NOW()) >= ?
                      )
                ORDER BY ultimo_envio IS NULL DESC, ultimo_envio ASC
                LIMIT ?';

        return $this->db->fetchAll($sql, [$interval, $limit]);
    }
}
