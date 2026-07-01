<?php
/**
 * Cadê Meu Pet? - Modelo de Anúncio
 * Centraliza operações de leitura e escrita na tabela `anuncios`,
 * incluindo regras auxiliares para limitações e filtros de busca.
 */

class Anuncio
{
    private $db;

    private static $columnsCache = null;

    public function __construct()
    {
        $this->db = getDB();
    }

    private function getColumns(): array
    {
        if (self::$columnsCache !== null) {
            return self::$columnsCache;
        }

        try {
            $rows = $this->db->fetchAll('SHOW COLUMNS FROM anuncios');
            $cols = [];
            foreach ($rows as $row) {
                if (!empty($row['Field'])) {
                    $cols[] = (string)$row['Field'];
                }
            }

            self::$columnsCache = $cols;
            return $cols;
        } catch (Throwable $e) {
            error_log('[Anuncio] Falha ao obter colunas da tabela anuncios: ' . $e->getMessage());
            self::$columnsCache = [];
            return [];
        }
    }

    private function filterDataToExistingColumns(array $data): array
    {
        $columns = $this->getColumns();
        if (empty($columns)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($columns));
    }

    /**
     * Cria anúncio e retorna ID.
     */
    public function create(array $data): int
    {
        $data = $this->filterDataToExistingColumns($data);
        return $this->db->insert('anuncios', $data);
    }

    /**
     * Atualiza anúncio.
     */
    public function update(int $id, array $data)
    {
        $data = $this->filterDataToExistingColumns($data);
        if (empty($data)) {
            return false;
        }

        return $this->db->update('anuncios', $data, 'id = ?', [$id]);
    }

    /**
     * Soft delete: altera status para inativo e registra data de atualização.
     */
    public function softDelete(int $id, int $usuarioId)
    {
        return $this->db->update(
            'anuncios',
            [
                'status' => STATUS_INATIVO,
                'data_atualizacao' => date('Y-m-d H:i:s')
            ],
            'id = ? AND usuario_id = ?',
            [$id, $usuarioId]
        );
    }

    /**
     * Soft delete administrativo: altera status para inativo.
     */
    public function softDeleteAsAdmin(int $id)
    {
        return $this->db->update(
            'anuncios',
            [
                'status' => STATUS_INATIVO,
                'data_atualizacao' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$id]
        );
    }

    /**
     * Marcar como resolvido, com data de reunião e história opcional.
     */
    public function markAsResolved(int $id, string $historia = '')
    {
        $data = [
            'status'       => STATUS_RESOLVIDO,
            'resolvido_em' => date('Y-m-d H:i:s'),
            'data_atualizacao' => date('Y-m-d H:i:s')
        ];
        if ($historia !== '') {
            $data['historia_reuniao'] = $historia;
        }
        return $this->update($id, $data);
    }

    /**
     * Reativar anúncio (marcar como ativo).
     */
    public function markAsActive(int $id)
    {
        return $this->update($id, [
            'status' => STATUS_ATIVO,
            'data_atualizacao' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Soft delete administrativo (bloqueado).
     */
    public function block(int $id)
    {
        return $this->db->update(
            'anuncios',
            [
                'status' => STATUS_BLOQUEADO,
                'data_atualizacao' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$id]
        );
    }

    /**
     * Busca anúncio por ID (inclui usuário).
     */
    public function findById(int $id)
    {
        return $this->db->fetchOne(
            'SELECT a.*, u.nome as usuario_nome, u.telefone, u.email
             FROM anuncios a
             JOIN usuarios u ON a.usuario_id = u.id
             WHERE a.id = ? AND a.status IN (?, ?)',
            [$id, STATUS_ATIVO, STATUS_RESOLVIDO]
        );
    }

    /**
     * Busca anúncio por ID (incluindo status inativo/bloqueado) para uso interno.
     */
    public function findByIdAnyStatus(int $id)
    {
        return $this->db->fetchOne(
            'SELECT a.*, u.nome as usuario_nome, u.telefone, u.email
             FROM anuncios a
             JOIN usuarios u ON a.usuario_id = u.id
             WHERE a.id = ?',
            [$id]
        );
    }

    /**
     * Lista anúncios do usuário (inclui foto principal).
     */
    public function findByUser(int $usuarioId, int $limit = 50, int $offset = 0, ?string $status = null)
    {
        $where = ['a.usuario_id = ?'];
        $params = [$usuarioId];

        if (!empty($status)) {
            $where[] = 'a.status = ?';
            $params[] = $status;
        }

        return $this->db->fetchAll(
            "SELECT a.*,
                    (SELECT nome_arquivo FROM fotos_anuncios f WHERE f.anuncio_id = a.id ORDER BY ordem LIMIT 1) AS foto
             FROM anuncios a
             WHERE " . implode(' AND ', $where) . "
             ORDER BY a.data_publicacao DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );
    }

    /**
     * Incrementa contador de visualizações aplicando limite diário por IP.
     */
    public function incrementViews(int $id, string $clientIp)
    {
        $cacheKey = "view_{$id}_{$clientIp}_" . date('Ymd');

        if (!isset($_SESSION[$cacheKey])) {
            $_SESSION[$cacheKey] = true;
            return $this->db->query('UPDATE anuncios SET visualizacoes = visualizacoes + 1 WHERE id = ?', [$id]);
        }

        return false;
    }

    /**
     * Conta anúncios ativos por usuário (já garantido no modelo de usuário).
     */
    public function countActiveByUser(int $usuarioId): int
    {
        $result = $this->db->fetchOne(
            'SELECT COUNT(*) AS total FROM anuncios WHERE usuario_id = ? AND status = ?',
            [$usuarioId, STATUS_ATIVO]
        );

        return (int)($result['total'] ?? 0);
    }

    /**
     * Verifica intervalo entre publicações (último anúncio).
     */
    public function canPublishNewAd(int $usuarioId): bool
    {
        $lastAd = $this->db->fetchOne(
            'SELECT data_publicacao FROM anuncios WHERE usuario_id = ? ORDER BY data_publicacao DESC LIMIT 1',
            [$usuarioId]
        );

        if (!$lastAd || empty($lastAd['data_publicacao'])) {
            return true;
        }

        $lastTimestamp = strtotime($lastAd['data_publicacao']);
        return (time() - $lastTimestamp) >= MIN_PUBLISH_INTERVAL;
    }

    /**
     * Atualiza status para expirado quando necessário.
     */
    public function expireOldAds()
    {
        return $this->db->query(
            'UPDATE anuncios 
             SET status = ?, data_atualizacao = NOW()
             WHERE status = ? AND data_expiracao < CURDATE()',
            [STATUS_EXPIRADO, STATUS_ATIVO]
        );
    }

    /**
     * Busca paginada com filtros diversos.
     */
    public function search(array $filtros, int $limit = RESULTS_PER_PAGE, int $offset = 0): array
    {
        // Cache apenas para buscas sem geolocalização e sem filtro de usuário específico
        $isGeo     = !empty($filtros['lat']) && !empty($filtros['lng']);
        $hasUserId = !empty($filtros['usuario_id']);
        if (!$isGeo && !$hasUserId) {
            $cacheKey = 'search_' . md5(serialize($filtros) . $limit . '_' . $offset);
            $cached   = cacheGet($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $query = [
            'SELECT a.*, u.nome AS usuario_nome,',
            '       (SELECT nome_arquivo FROM fotos_anuncios f WHERE f.anuncio_id = a.id ORDER BY ordem LIMIT 1) AS foto',
            'FROM anuncios a',
            'JOIN usuarios u ON a.usuario_id = u.id'
        ];

        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['status'])) {
            $where[] = 'a.status = ?';
            $params[] = $filtros['status'];
        } else {
            $where[] = 'a.status = ?';
            $params[] = STATUS_ATIVO;
        }

        if (!empty($filtros['tipo'])) {
            $where[] = 'a.tipo = ?';
            $params[] = $filtros['tipo'];
        }

        if (!empty($filtros['especie'])) {
            $where[] = 'a.especie = ?';
            $params[] = $filtros['especie'];
        }

        if (!empty($filtros['cidade'])) {
            $where[] = 'a.cidade = ?';
            $params[] = $filtros['cidade'];
        }

        if (!empty($filtros['estado'])) {
            $where[] = 'a.estado = ?';
            $params[] = $filtros['estado'];
        }

        if (!empty($filtros['bairro'])) {
            $where[] = 'a.bairro LIKE ?';
            $params[] = '%' . $filtros['bairro'] . '%';
        }

        if (!empty($filtros['has_photo'])) {
            $where[] = 'EXISTS (SELECT 1 FROM fotos_anuncios f WHERE f.anuncio_id = a.id)';
        }

        if (!empty($filtros['q'])) {
            $where[] = 'MATCH(a.nome_pet, a.raca, a.cor, a.descricao) AGAINST (? IN BOOLEAN MODE)';
            $params[] = $this->buildFulltextQuery($filtros['q']);
        }

        if (!empty($filtros['data_desde'])) {
            $where[] = 'a.data_ocorrido >= ?';
            $params[] = $filtros['data_desde'];
        }

        if (!empty($filtros['data_ate'])) {
            $where[] = 'a.data_ocorrido <= ?';
            $params[] = $filtros['data_ate'];
        }

        if (!empty($filtros['tamanho'])) {
            $where[] = 'a.tamanho = ?';
            $params[] = $filtros['tamanho'];
        }

        if (!empty($filtros['lat']) && !empty($filtros['lng']) && !empty($filtros['raio'])) {
            $lat   = (float)$filtros['lat'];
            $lng   = (float)$filtros['lng'];
            $raio  = (float)$filtros['raio'];
            // 1° latitude ≈ 111 km; 1° longitude ≈ 111 km * cos(lat)
            $deltaLat = $raio / 111.0;
            $deltaLng = $raio / (111.0 * max(cos(deg2rad($lat)), 0.0001));
            $where[] = 'a.latitude IS NOT NULL AND a.longitude IS NOT NULL';
            $where[] = 'a.latitude  BETWEEN ? AND ?';
            $where[] = 'a.longitude BETWEEN ? AND ?';
            $params[] = $lat - $deltaLat;
            $params[] = $lat + $deltaLat;
            $params[] = $lng - $deltaLng;
            $params[] = $lng + $deltaLng;
        }

        // Captura WHERE params antes de injetar Haversine (para a query de COUNT)
        $countParams = $params;
        $whereSQL    = 'WHERE ' . implode(' AND ', $where);

        $query[] = $whereSQL;

        $hasGeo = !empty($filtros['lat']) && !empty($filtros['lng']) && !empty($filtros['raio']);

        if ($hasGeo) {
            $query[] = 'HAVING distancia <= ?';
            $params[] = (int)$filtros['raio'];

            $query[0] .= ' (6371 * acos(cos(radians(?)) * cos(radians(a.latitude)) * cos(radians(a.longitude) - radians(?)) + sin(radians(?)) * sin(radians(a.latitude)))) AS distancia,';
            array_splice($params, 0, 0, [(float)$filtros['lat'], (float)$filtros['lng'], (float)$filtros['lat']]);
        }

        // COUNT separado (bounding box já filtra; pequeno sobre-arredondamento aceitável)
        $countSQL = 'SELECT COUNT(*) AS total FROM anuncios a JOIN usuarios u ON a.usuario_id = u.id ' . $whereSQL;
        $totalRow = $this->db->fetchOne($countSQL, $countParams);
        $total    = (int)($totalRow['total'] ?? 0);

        $order = 'a.data_publicacao DESC';

        if (!empty($filtros['ordenacao'])) {
            $ordenacao = (string)$filtros['ordenacao'];
            if ($ordenacao === 'proximo' && !$hasGeo) {
                $order = 'a.data_publicacao DESC';
            } else {
                $order = $this->resolveOrderClause($ordenacao);
            }
        }

        $query[] = 'ORDER BY ' . $order;
        $query[] = 'LIMIT ? OFFSET ?';

        $params[] = $limit;
        $params[] = $offset;

        $results = $this->db->fetchAll(implode("\n", $query), $params);

        $output = ['results' => $results, 'total' => $total];

        if (!$isGeo && !$hasUserId) {
            cacheSet($cacheKey, $output, CACHE_TIME_SEARCH);
        }

        return $output;
    }

    private function buildFulltextQuery(string $termo): string
    {
        $termo = preg_replace('/\s+/', ' ', trim($termo));
        $palavras = array_filter(explode(' ', $termo));

        if (empty($palavras)) {
            return '';
        }

        $formatted = array_map(function ($palavra) {
            return $palavra . '*';
        }, $palavras);

        return implode(' ', $formatted);
    }

    private function resolveOrderClause(string $ordenacao): string
    {
        switch ($ordenacao) {
            case 'recente':
                return 'a.data_publicacao DESC';
            case 'antigo':
                return 'a.data_publicacao ASC';
            case 'popular':
                return 'a.visualizacoes DESC';
            case 'proximo':
                return 'distancia ASC';
            default:
                return 'a.data_publicacao DESC';
        }
    }

    /**
     * Retorna anúncios compatíveis com um alerta de busca.
     */
    public function findByAlert(array $alerta, int $limit = 10)
    {
        $query = [
            'SELECT a.*, u.nome AS usuario_nome,',
            '       (SELECT nome_arquivo FROM fotos_anuncios f WHERE f.anuncio_id = a.id ORDER BY ordem LIMIT 1) AS foto',
            'FROM anuncios a',
            'JOIN usuarios u ON a.usuario_id = u.id'
        ];

        $where = ['a.status = ?'];
        $params = [STATUS_ATIVO];

        $tipo = $alerta['tipo'] ?? 'ambos';
        if ($tipo && $tipo !== 'ambos') {
            $where[] = 'a.tipo = ?';
            $params[] = $tipo;
        }

        if (!empty($alerta['especie'])) {
            $where[] = 'a.especie = ?';
            $params[] = $alerta['especie'];
        }

        if (!empty($alerta['estado'])) {
            $where[] = 'a.estado = ?';
            $params[] = strtoupper($alerta['estado']);
        }

        if (!empty($alerta['cidade'])) {
            $where[] = 'a.cidade = ?';
            $params[] = $alerta['cidade'];
        }

        $query[] = 'WHERE ' . implode(' AND ', $where);
        $query[] = 'ORDER BY a.data_publicacao DESC';
        $query[] = 'LIMIT ?';
        $params[] = $limit;

        return $this->db->fetchAll(implode(' ', $query), $params);
    }
}

