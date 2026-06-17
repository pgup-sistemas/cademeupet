<?php
class MapController
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * Retorna até 200 anúncios ativos com coordenadas para exibição no mapa.
     */
    public function getPins(): array
    {
        $rows = $this->db->fetchAll("
            SELECT
                a.id,
                a.nome_pet,
                a.tipo,
                a.especie,
                a.latitude,
                a.longitude,
                a.cidade,
                a.bairro,
                a.data_publicacao,
                (SELECT nome_arquivo
                   FROM fotos_anuncios
                  WHERE anuncio_id = a.id
                  ORDER BY ordem
                  LIMIT 1) AS foto
            FROM anuncios a
            WHERE a.status = 'ativo'
              AND a.latitude  IS NOT NULL
              AND a.longitude IS NOT NULL
            ORDER BY a.data_publicacao DESC
            LIMIT 200
        ");

        $pins = [];
        foreach ($rows as $row) {
            $pins[] = [
                'id'             => (int)$row['id'],
                'nome_pet'       => $row['nome_pet'] ?: ucfirst((string)$row['especie']),
                'tipo'           => $row['tipo'],
                'lat'            => (float)$row['latitude'],
                'lng'            => (float)$row['longitude'],
                'cidade'         => (string)($row['cidade'] ?? ''),
                'bairro'         => (string)($row['bairro'] ?? ''),
                'data_publicacao'=> $row['data_publicacao'],
                'foto_thumb'     => $row['foto']
                    ? BASE_URL . '/uploads/anuncios/' . $row['foto']
                    : null,
                'url'            => BASE_URL . '/anuncio/' . (int)$row['id'] . '/',
            ];
        }

        return $pins;
    }
}
