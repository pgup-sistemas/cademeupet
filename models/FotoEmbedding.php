<?php
/**
 * Cadê Meu Pet? - Assinaturas visuais de fotos de anúncios (para matching).
 */
class FotoEmbedding
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    /** Fotos de anúncios perdido/achado ativos que ainda não têm assinatura processada. */
    public function buscarPendentes(int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT f.id AS foto_id, f.anuncio_id, f.nome_arquivo
             FROM fotos_anuncios f
             JOIN anuncios a ON a.id = f.anuncio_id
             LEFT JOIN foto_embeddings fe ON fe.foto_id = f.id
             WHERE a.tipo IN ('perdido','encontrado')
               AND a.status = 'ativo'
               AND (fe.id IS NULL OR (fe.status = 'falha' AND fe.tentativas < 3))
             ORDER BY f.id ASC
             LIMIT ?",
            [$limit]
        ) ?: [];
    }

    public function registrarPendente(int $fotoId, int $anuncioId): int
    {
        $existente = $this->db->fetchOne('SELECT id FROM foto_embeddings WHERE foto_id = ?', [$fotoId]);
        if ($existente) {
            return (int)$existente['id'];
        }
        return (int)$this->db->insert('foto_embeddings', [
            'foto_id'    => $fotoId,
            'anuncio_id' => $anuncioId,
            'provedor'   => 'phash_local',
            'status'     => 'pendente',
        ]);
    }

    public function marcarProcessado(int $fotoId, string $hashPerceptual): void
    {
        $this->db->update(
            'foto_embeddings',
            [
                'status'          => 'processado',
                'hash_perceptual' => $hashPerceptual,
                'processado_em'   => date('Y-m-d H:i:s'),
            ],
            'foto_id = ?',
            [$fotoId]
        );
    }

    public function marcarFalha(int $fotoId, string $mensagem): void
    {
        $row = $this->db->fetchOne('SELECT tentativas FROM foto_embeddings WHERE foto_id = ?', [$fotoId]);
        $tentativas = (int)($row['tentativas'] ?? 0) + 1;
        $this->db->update(
            'foto_embeddings',
            [
                'status'        => 'falha',
                'tentativas'    => $tentativas,
                'erro_mensagem' => mb_substr($mensagem, 0, 500),
                'processado_em' => date('Y-m-d H:i:s'),
            ],
            'foto_id = ?',
            [$fotoId]
        );
    }

    /** Hash perceptual da foto principal (menor `ordem`) de um anúncio, se já processada. */
    public function hashPrincipalDoAnuncio(int $anuncioId): ?string
    {
        $row = $this->db->fetchOne(
            "SELECT fe.hash_perceptual
             FROM fotos_anuncios f
             JOIN foto_embeddings fe ON fe.foto_id = f.id AND fe.status = 'processado'
             WHERE f.anuncio_id = ?
             ORDER BY f.ordem ASC
             LIMIT 1",
            [$anuncioId]
        );
        return $row['hash_perceptual'] ?? null;
    }
}
