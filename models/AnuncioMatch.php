<?php
/**
 * Cadê Meu Pet? - Matches sugeridos entre anúncios "perdido" e "achado".
 */
class AnuncioMatch
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    public function existeParaPar(int $perdidoId, int $achadoId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT id FROM anuncio_matches WHERE anuncio_perdido_id = ? AND anuncio_achado_id = ?',
            [$perdidoId, $achadoId]
        );
        return (bool)$row;
    }

    public function criar(array $dados): int
    {
        return (int)$this->db->insert('anuncio_matches', [
            'anuncio_perdido_id' => $dados['anuncio_perdido_id'],
            'anuncio_achado_id'  => $dados['anuncio_achado_id'],
            'score_total'        => $dados['score_total'],
            'score_visual'       => $dados['score_visual'] ?? null,
            'score_geo'          => $dados['score_geo'] ?? null,
            'score_atributos'    => $dados['score_atributos'] ?? null,
            'score_tempo'        => $dados['score_tempo'] ?? null,
            'distancia_km'       => $dados['distancia_km'] ?? null,
            'dias_diferenca'     => $dados['dias_diferenca'] ?? null,
            'status'             => 'pendente',
        ]);
    }

    public function buscarPorId(int $id): ?array
    {
        $row = $this->db->fetchOne('SELECT * FROM anuncio_matches WHERE id = ?', [$id]);
        return $row ?: null;
    }

    /** Match com os usuario_id dos dois anúncios envolvidos (para checagem de posse). */
    public function buscarComDonosPorId(int $id): ?array
    {
        $row = $this->db->fetchOne(
            'SELECT m.*, ap.usuario_id AS perdido_usuario_id, aa.usuario_id AS achado_usuario_id
             FROM anuncio_matches m
             JOIN anuncios ap ON ap.id = m.anuncio_perdido_id
             JOIN anuncios aa ON aa.id = m.anuncio_achado_id
             WHERE m.id = ?',
            [$id]
        );
        return $row ?: null;
    }

    /** Matches pendentes/notificados envolvendo anúncios do usuário (como perdido ou achado). */
    public function buscarParaUsuario(int $usuarioId): array
    {
        return $this->db->fetchAll(
            "SELECT m.*,
                    ap.nome_pet AS perdido_nome, ap.usuario_id AS perdido_usuario_id,
                    aa.nome_pet AS achado_nome, aa.usuario_id AS achado_usuario_id,
                    (SELECT nome_arquivo FROM fotos_anuncios WHERE anuncio_id = ap.id ORDER BY ordem LIMIT 1) AS perdido_foto,
                    (SELECT nome_arquivo FROM fotos_anuncios WHERE anuncio_id = aa.id ORDER BY ordem LIMIT 1) AS achado_foto
             FROM anuncio_matches m
             JOIN anuncios ap ON ap.id = m.anuncio_perdido_id
             JOIN anuncios aa ON aa.id = m.anuncio_achado_id
             WHERE (ap.usuario_id = ? OR aa.usuario_id = ?)
               AND m.status IN ('pendente','notificado')
             ORDER BY m.score_total DESC, m.criado_em DESC",
            [$usuarioId, $usuarioId]
        ) ?: [];
    }

    public function marcarNotificado(int $id): void
    {
        $this->db->update('anuncio_matches', [
            'status'        => 'notificado',
            'notificado_em' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    public function confirmar(int $id, int $usuarioId, int $conversaId): bool
    {
        return $this->db->update('anuncio_matches', [
            'status'                    => 'confirmado',
            'conversa_id'               => $conversaId,
            'confirmado_por_usuario_id' => $usuarioId,
            'resolvido_em'              => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]) !== false;
    }

    public function rejeitar(int $id): bool
    {
        return $this->db->update('anuncio_matches', [
            'status'       => 'rejeitado',
            'resolvido_em' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]) !== false;
    }

    public function pertenceAoUsuario(array $match, int $usuarioId): bool
    {
        return (int)$match['perdido_usuario_id'] === $usuarioId
            || (int)$match['achado_usuario_id'] === $usuarioId;
    }
}
