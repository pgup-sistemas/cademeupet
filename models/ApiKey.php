<?php
/**
 * Cadê Meu Pet? - API keys para consumidores externos (API pública /api/v1).
 * Acesso por aprovação manual do admin — a chave em texto puro só existe
 * no momento da criação; a partir daí só o hash SHA-256 é armazenado.
 */
class ApiKey
{
    private $db;

    public const ESCOPOS_VALIDOS = [
        'anuncios_leitura',
        'parceiros_leitura',
        'ingestao_denuncias',
        'ingestao_animais',
    ];

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    /**
     * Gera uma nova API key para um consumidor. Retorna a chave em texto
     * puro (mostrada uma única vez ao admin) junto com os metadados salvos.
     */
    public function gerar(int $consumidorId, array $escopos, int $rateLimitPorMinuto = 60, ?string $expiraEm = null): array
    {
        $escopos = array_values(array_intersect($escopos, self::ESCOPOS_VALIDOS));
        if (empty($escopos)) {
            $escopos = ['anuncios_leitura'];
        }

        $chavePlaintext = 'cmp_live_' . bin2hex(random_bytes(24));
        $prefixo = substr($chavePlaintext, 0, 12);
        $hash = hash('sha256', $chavePlaintext);

        $id = $this->db->insert('api_keys', [
            'consumidor_id'         => $consumidorId,
            'chave_hash'            => $hash,
            'prefixo'               => $prefixo,
            'escopos'               => implode(',', $escopos),
            'rate_limit_por_minuto' => $rateLimitPorMinuto,
            'ativo'                 => 1,
            'expira_em'             => $expiraEm,
        ]);

        return [
            'id'               => (int)$id,
            'chave_plaintext'  => $chavePlaintext,
            'prefixo'          => $prefixo,
        ];
    }

    public function buscarPorChavePlaintext(string $chavePlaintext): ?array
    {
        $hash = hash('sha256', $chavePlaintext);
        $row = $this->db->fetchOne(
            'SELECT k.*, c.ativo AS consumidor_ativo
             FROM api_keys k
             JOIN api_consumidores c ON c.id = k.consumidor_id
             WHERE k.chave_hash = ?',
            [$hash]
        );
        return $row ?: null;
    }

    public function valida(array $chaveRow): bool
    {
        if (empty($chaveRow['ativo']) || empty($chaveRow['consumidor_ativo'])) {
            return false;
        }
        if (!empty($chaveRow['expira_em']) && strtotime($chaveRow['expira_em']) < time()) {
            return false;
        }
        return true;
    }

    public function temEscopo(array $chaveRow, string $escopo): bool
    {
        $escopos = array_map('trim', explode(',', (string)($chaveRow['escopos'] ?? '')));
        return in_array($escopo, $escopos, true);
    }

    public function registrarUso(int $id): void
    {
        $this->db->update('api_keys', ['ultimo_uso_em' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    public function revogar(int $id): bool
    {
        return $this->db->update('api_keys', [
            'ativo'        => 0,
            'revogada_em'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]) !== false;
    }

    public function listarTodas(): array
    {
        return $this->db->fetchAll(
            'SELECT k.*, c.nome AS consumidor_nome
             FROM api_keys k
             JOIN api_consumidores c ON c.id = k.consumidor_id
             ORDER BY k.criado_em DESC'
        ) ?: [];
    }
}
