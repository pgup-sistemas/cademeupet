<?php

class HomeController
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? getDB();
    }

    public function getHomeData(): array
    {
        $anunciosRecentes = $this->db->fetchAll("
            SELECT a.*, u.nome as autor_nome,
                   (SELECT nome_arquivo FROM fotos_anuncios
                    WHERE anuncio_id = a.id ORDER BY ordem LIMIT 1) as foto
            FROM anuncios a
            JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.status = 'ativo'
            ORDER BY a.data_publicacao DESC
            LIMIT 8
        ");

        $stats = $this->db->fetchOne("SELECT * FROM view_estatisticas") ?? [];

        return ['anunciosRecentes' => $anunciosRecentes, 'stats' => $stats];
    }
}
