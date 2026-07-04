<?php
/**
 * Cadê Meu Pet? - Rate limiting da API pública por janela de minuto.
 * Implementado em MySQL puro (sem Redis), condizente com a infra atual do
 * projeto: um contador por (api_key_id, minuto), incrementado via
 * INSERT ... ON DUPLICATE KEY UPDATE.
 */
class ApiRateLimit
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    /**
     * Incrementa o contador da janela atual e retorna true se ainda está
     * dentro do limite, false se estourou (o request que estourou também
     * conta, então o limite é "no máximo N por minuto").
     */
    public function verificarERegistrar(int $apiKeyId, int $limitePorMinuto): bool
    {
        $janelaInicio = date('Y-m-d H:i:00');

        $this->db->query(
            'INSERT INTO api_rate_limit_janelas (api_key_id, janela_inicio, contador)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE contador = contador + 1',
            [$apiKeyId, $janelaInicio]
        );

        $row = $this->db->fetchOne(
            'SELECT contador FROM api_rate_limit_janelas WHERE api_key_id = ? AND janela_inicio = ?',
            [$apiKeyId, $janelaInicio]
        );

        return (int)($row['contador'] ?? 0) <= max(1, $limitePorMinuto);
    }

    /** Limpa janelas com mais de 1 hora — chamada oportunista, sem precisar de cron dedicado. */
    public function limparJanelasAntigas(): void
    {
        $this->db->query(
            'DELETE FROM api_rate_limit_janelas WHERE janela_inicio < DATE_SUB(NOW(), INTERVAL 1 HOUR)'
        );
    }
}
