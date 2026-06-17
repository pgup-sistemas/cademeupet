<?php
/**
 * Cadê Meu Pet? - Modelo de Favoritos
 */
class Favorito
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    public function isFavorited(int $usuarioId, int $anuncioId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT id FROM favoritos WHERE usuario_id = ? AND anuncio_id = ?',
            [$usuarioId, $anuncioId]
        );
        return !empty($row);
    }

    public function add(int $usuarioId, int $anuncioId)
    {
        if ($this->isFavorited($usuarioId, $anuncioId)) {
            return true;
        }

        return $this->db->insert('favoritos', [
            'usuario_id' => $usuarioId,
            'anuncio_id' => $anuncioId,
            'data_favoritado' => date('Y-m-d H:i:s')
        ]);
    }

    public function remove(int $usuarioId, int $anuncioId)
    {
        return $this->db->delete('favoritos', 'usuario_id = ? AND anuncio_id = ?', [$usuarioId, $anuncioId]);
    }

    public function listarPorUsuario(int $usuarioId)
    {
        return $this->db->fetchAll(
            'SELECT a.*, f.data_favoritado,
                    (SELECT nome_arquivo FROM fotos_anuncios WHERE anuncio_id = a.id ORDER BY ordem LIMIT 1) AS foto
             FROM favoritos f
             JOIN anuncios a ON a.id = f.anuncio_id
             WHERE f.usuario_id = ?
             ORDER BY f.data_favoritado DESC',
            [$usuarioId]
        );
    }
}
