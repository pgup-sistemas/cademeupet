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
        $cacheKey = 'home_data';
        $cached = cacheGet($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

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

        $depoimentos = $this->db->fetchAll("
            SELECT d.texto, d.criado_em, u.nome AS usuario_nome, a.nome_pet, a.tipo AS anuncio_tipo,
                   (SELECT nome_arquivo FROM fotos_anuncios WHERE anuncio_id = a.id ORDER BY ordem LIMIT 1) AS foto
            FROM depoimentos d
            JOIN usuarios u ON u.id = d.usuario_id
            LEFT JOIN anuncios a ON a.id = d.anuncio_id
            WHERE d.aprovado = 1
            ORDER BY d.criado_em DESC
            LIMIT 6
        ") ?: [];

        $result = ['anunciosRecentes' => $anunciosRecentes, 'stats' => $stats, 'depoimentos' => $depoimentos];
        cacheSet($cacheKey, $result, CACHE_TIME_HOME);
        return $result;
    }
}
