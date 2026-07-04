<?php
/**
 * Cadê Meu Pet? - Motor de score do match automático (perdido ↔ achado).
 *
 * Segue o mesmo padrão de PetLove::buscarCompativeis() — pré-filtro em SQL
 * (espécie, tipo oposto, status ativo, janela de tempo) seguido de cálculo
 * de score em PHP e ordenação por score. A diferença é que aqui o
 * componente visual (pHash) é OPCIONAL e pesa menos que geo/tempo/atributos,
 * já que é um sinal grátis mas fraco (ver services/ImagemMatchService.php).
 */
class MatchEngine
{
    private $db;
    private FotoEmbedding $fotoEmbeddingModel;
    private ImagemMatchService $imagemMatchService;

    /** Pesos relativos de cada componente do score (somam 100 quando todos presentes). */
    private const PESOS = [
        'atributos' => 35,
        'geo'       => 30,
        'tempo'     => 20,
        'visual'    => 15,
    ];

    public function __construct($db = null, ?FotoEmbedding $fotoEmbeddingModel = null, ?ImagemMatchService $imagemMatchService = null)
    {
        $this->db = $db ?: getDB();
        $this->fotoEmbeddingModel = $fotoEmbeddingModel ?: new FotoEmbedding($this->db);
        $this->imagemMatchService = $imagemMatchService ?: new ImagemMatchService();
    }

    /**
     * Busca candidatos do tipo oposto (perdido↔achado) para o anúncio dado,
     * dentro da janela de tempo e por proximidade geográfica/cidade.
     */
    public function buscarCandidatos(array $anuncio): array
    {
        $tipoOposto = $anuncio['tipo'] === 'perdido' ? 'encontrado' : 'perdido';
        $janelaDias = defined('MATCHING_JANELA_DIAS') ? (int)MATCHING_JANELA_DIAS : 60;

        $where = [
            "a.tipo = ?",
            "a.status = 'ativo'",
            "a.especie = ?",
            "a.id != ?",
            "a.usuario_id != ?",
            "ABS(DATEDIFF(a.data_ocorrido, ?)) <= ?",
        ];
        $params = [
            $tipoOposto,
            $anuncio['especie'],
            $anuncio['id'],
            $anuncio['usuario_id'],
            $anuncio['data_ocorrido'],
            $janelaDias,
        ];

        // Pré-filtro geográfico: mesma cidade/estado OU dentro de uma janela ampla
        // de latitude/longitude (refinamento fino é feito depois em PHP via Haversine).
        if (!empty($anuncio['latitude']) && !empty($anuncio['longitude'])) {
            $delta = 1.0; // graus, filtro grosseiro só para reduzir candidatos antes do Haversine
            $where[] = "((a.latitude IS NOT NULL AND a.longitude IS NOT NULL
                          AND a.latitude BETWEEN ? AND ? AND a.longitude BETWEEN ? AND ?)
                         OR (a.cidade = ? AND a.estado = ?))";
            $params[] = (float)$anuncio['latitude'] - $delta;
            $params[] = (float)$anuncio['latitude'] + $delta;
            $params[] = (float)$anuncio['longitude'] - $delta;
            $params[] = (float)$anuncio['longitude'] + $delta;
            $params[] = $anuncio['cidade'];
            $params[] = $anuncio['estado'];
        } else {
            $where[] = "a.cidade = ? AND a.estado = ?";
            $params[] = $anuncio['cidade'];
            $params[] = $anuncio['estado'];
        }

        return $this->db->fetchAll(
            "SELECT a.* FROM anuncios a WHERE " . implode(' AND ', $where) . "
             ORDER BY a.data_publicacao DESC
             LIMIT 100",
            $params
        ) ?: [];
    }

    /**
     * Calcula o score combinado entre dois anúncios (um perdido, um achado).
     * Retorna null se não for possível calcular nenhum componente.
     */
    public function calcularScore(array $perdido, array $achado): ?array
    {
        $componentes = [];

        $componentes['atributos'] = $this->scoreAtributos($perdido, $achado);
        $geo = $this->scoreGeo($perdido, $achado);
        $componentes['geo'] = $geo['sub_score'];
        $tempo = $this->scoreTempo($perdido, $achado);
        $componentes['tempo'] = $tempo['sub_score'];
        $componentes['visual'] = $this->scoreVisual($perdido, $achado);

        $somaPesos = 0;
        $somaPonderada = 0;
        foreach ($componentes as $chave => $subScore) {
            if ($subScore === null) {
                continue;
            }
            $peso = self::PESOS[$chave];
            $somaPesos += $peso;
            $somaPonderada += $peso * $subScore;
        }

        if ($somaPesos === 0) {
            return null;
        }

        $scoreTotal = round(($somaPonderada / $somaPesos) * 100, 2);

        return [
            'score_total'     => min(100, $scoreTotal),
            'score_atributos' => $componentes['atributos'] !== null ? round($componentes['atributos'] * 100, 2) : null,
            'score_geo'       => $componentes['geo'] !== null ? round($componentes['geo'] * 100, 2) : null,
            'score_tempo'     => $componentes['tempo'] !== null ? round($componentes['tempo'] * 100, 2) : null,
            'score_visual'    => $componentes['visual'] !== null ? round($componentes['visual'] * 100, 2) : null,
            'distancia_km'    => $geo['distancia_km'],
            'dias_diferenca'  => $tempo['dias_diferenca'],
        ];
    }

    /** Proporção (0-1) de atributos coincidentes entre raça, cor e porte/tamanho. */
    private function scoreAtributos(array $a, array $b): ?float
    {
        $comparaveis = 0;
        $coincidentes = 0;

        foreach (['raca', 'cor', 'tamanho'] as $campo) {
            $valA = trim((string)($a[$campo] ?? ''));
            $valB = trim((string)($b[$campo] ?? ''));
            if ($valA === '' || $valB === '') {
                continue;
            }
            $comparaveis++;
            if (mb_strtolower($valA) === mb_strtolower($valB)) {
                $coincidentes++;
            }
        }

        if ($comparaveis === 0) {
            return null;
        }
        return $coincidentes / $comparaveis;
    }

    /** @return array{sub_score: ?float, distancia_km: ?float} */
    private function scoreGeo(array $a, array $b): array
    {
        $raioKm = defined('MATCHING_RAIO_KM') ? (float)MATCHING_RAIO_KM : 50.0;

        if (!empty($a['latitude']) && !empty($a['longitude']) && !empty($b['latitude']) && !empty($b['longitude'])) {
            $km = $this->calcularDistanciaKm(
                (float)$a['latitude'], (float)$a['longitude'],
                (float)$b['latitude'], (float)$b['longitude']
            );
            $sub = max(0.0, 1 - ($km / $raioKm));
            return ['sub_score' => min(1.0, $sub), 'distancia_km' => $km];
        }

        if (!empty($a['cidade']) && !empty($b['cidade'])
            && mb_strtolower(trim($a['cidade'])) === mb_strtolower(trim($b['cidade']))
            && strtoupper((string)($a['estado'] ?? '')) === strtoupper((string)($b['estado'] ?? ''))
        ) {
            // Sem coordenadas, mas mesma cidade: crédito parcial.
            return ['sub_score' => 0.6, 'distancia_km' => null];
        }

        return ['sub_score' => null, 'distancia_km' => null];
    }

    /** @return array{sub_score: ?float, dias_diferenca: ?int} */
    private function scoreTempo(array $perdido, array $achado): array
    {
        if (empty($perdido['data_ocorrido']) || empty($achado['data_ocorrido'])) {
            return ['sub_score' => null, 'dias_diferenca' => null];
        }

        $janelaDias = defined('MATCHING_JANELA_DIAS') ? (int)MATCHING_JANELA_DIAS : 60;
        $dias = (int)abs((strtotime($achado['data_ocorrido']) - strtotime($perdido['data_ocorrido'])) / 86400);
        $sub = max(0.0, 1 - ($dias / max(1, $janelaDias)));

        return ['sub_score' => $sub, 'dias_diferenca' => $dias];
    }

    private function scoreVisual(array $perdido, array $achado): ?float
    {
        $hashPerdido = $this->fotoEmbeddingModel->hashPrincipalDoAnuncio((int)$perdido['id']);
        $hashAchado  = $this->fotoEmbeddingModel->hashPrincipalDoAnuncio((int)$achado['id']);

        if ($hashPerdido === null || $hashAchado === null) {
            return null;
        }

        $score = $this->imagemMatchService->scoreSimilaridade($hashPerdido, $hashAchado);
        return $score !== null ? $score / 100 : null;
    }

    public function calcularDistanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $raioTerra = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return round($raioTerra * 2 * asin(sqrt($a)), 1);
    }
}
