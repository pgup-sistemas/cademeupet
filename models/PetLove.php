<?php

class PetLove {

    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    // ─────────────────────────────────────────────
    // CRUD
    // ─────────────────────────────────────────────

    public function criar(array $dados): int {
        $stmt = $this->db->insert('petlove_pets', [
            'usuario_id'          => $dados['usuario_id'],
            'nome'                => $dados['nome'],
            'especie'             => $dados['especie'],
            'raca'                => $dados['raca'],
            'porte'               => $dados['porte'],
            'sexo'                => $dados['sexo'],
            'idade_meses'         => (int)$dados['idade_meses'],
            'cor'                 => $dados['cor'] ?? null,
            'peso_kg'             => isset($dados['peso_kg']) && $dados['peso_kg'] !== '' ? (float)$dados['peso_kg'] : null,
            'tem_pedigree'        => (int)!empty($dados['tem_pedigree']),
            'pedigree_num'        => $dados['pedigree_num'] ?? null,
            'vacinado'            => (int)!empty($dados['vacinado']),
            'vermifugado'         => (int)!empty($dados['vermifugado']),
            'castrado'            => (int)!empty($dados['castrado']),
            'descricao'           => $dados['descricao'] ?? null,
            'objetivo'            => $dados['objetivo'] ?? 'cruzamento',
            'latitude'            => isset($dados['latitude'])  && $dados['latitude']  !== '' ? (float)$dados['latitude']  : null,
            'longitude'           => isset($dados['longitude']) && $dados['longitude'] !== '' ? (float)$dados['longitude'] : null,
            'cidade'              => $dados['cidade'] ?? null,
            'estado'              => $dados['estado'] ?? null,
            'disponivel'          => 1,
            'criacao_responsavel' => (int)!empty($dados['criacao_responsavel']),
            'status'              => 'ativo',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function atualizar(int $id, array $dados): bool {
        $campos = [
            'nome'                => $dados['nome'],
            'raca'                => $dados['raca'],
            'porte'               => $dados['porte'],
            'sexo'                => $dados['sexo'],
            'idade_meses'         => (int)$dados['idade_meses'],
            'cor'                 => $dados['cor'] ?? null,
            'peso_kg'             => isset($dados['peso_kg']) && $dados['peso_kg'] !== '' ? (float)$dados['peso_kg'] : null,
            'tem_pedigree'        => (int)!empty($dados['tem_pedigree']),
            'pedigree_num'        => $dados['pedigree_num'] ?? null,
            'vacinado'            => (int)!empty($dados['vacinado']),
            'vermifugado'         => (int)!empty($dados['vermifugado']),
            'descricao'           => $dados['descricao'] ?? null,
            'objetivo'            => $dados['objetivo'] ?? 'cruzamento',
            'latitude'            => isset($dados['latitude'])  && $dados['latitude']  !== '' ? (float)$dados['latitude']  : null,
            'longitude'           => isset($dados['longitude']) && $dados['longitude'] !== '' ? (float)$dados['longitude'] : null,
            'cidade'              => $dados['cidade'] ?? null,
            'estado'              => $dados['estado'] ?? null,
        ];
        return $this->db->update('petlove_pets', $campos, 'id = ?', [$id]) !== false;
    }

    public function buscarPorId(int $id): ?array {
        $row = $this->db->fetchOne(
            "SELECT p.*, u.nome AS tutor_nome, u.email AS tutor_email, u.telefone AS tutor_telefone,
                    u.cidade AS tutor_cidade, u.estado AS tutor_estado
             FROM petlove_pets p
             JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.id = ? AND p.status != 'removido'",
            [$id]
        );
        if (!$row) return null;
        $row['fotos'] = $this->buscarFotos((int)$row['id']);
        return $row;
    }

    public function buscarPorUsuario(int $usuarioId): array {
        $rows = $this->db->fetchAll(
            "SELECT p.*,
                    (SELECT caminho FROM petlove_fotos WHERE petlove_id = p.id AND principal = 1 LIMIT 1) AS foto_principal,
                    (SELECT COUNT(*) FROM petlove_interesses WHERE petlove_id = p.id AND status = 'pendente') AS interesses_pendentes
             FROM petlove_pets p
             WHERE p.usuario_id = ? AND p.status != 'removido'
             ORDER BY p.criado_em DESC",
            [$usuarioId]
        );
        return $rows ?: [];
    }

    // ─────────────────────────────────────────────
    // VITRINE
    // ─────────────────────────────────────────────

    public function listarVitrine(array $filtros = [], int $pagina = 1, int $porPagina = 12): array {
        $where  = ["p.status = 'ativo'", "p.disponivel = 1"];
        $params = [];

        if (!empty($filtros['especie'])) {
            $where[]  = 'p.especie = ?';
            $params[] = $filtros['especie'];
        }
        if (!empty($filtros['sexo'])) {
            $where[]  = 'p.sexo = ?';
            $params[] = $filtros['sexo'];
        }
        if (!empty($filtros['porte'])) {
            $where[]  = 'p.porte = ?';
            $params[] = $filtros['porte'];
        }
        if (!empty($filtros['raca'])) {
            $where[]  = 'p.raca LIKE ?';
            $params[] = '%' . $filtros['raca'] . '%';
        }
        if (!empty($filtros['cidade'])) {
            $where[]  = 'p.cidade LIKE ?';
            $params[] = '%' . $filtros['cidade'] . '%';
        }
        if (!empty($filtros['estado'])) {
            $where[]  = 'p.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (isset($filtros['tem_pedigree']) && $filtros['tem_pedigree'] !== '') {
            $where[]  = 'p.tem_pedigree = ?';
            $params[] = (int)$filtros['tem_pedigree'];
        }
        if (!empty($filtros['objetivo'])) {
            $where[]  = 'p.objetivo = ?';
            $params[] = $filtros['objetivo'];
        }

        $whereSql = implode(' AND ', $where);
        $offset   = ($pagina - 1) * $porPagina;

        $total = (int)($this->db->fetchOne(
            "SELECT COUNT(*) AS total FROM petlove_pets p WHERE $whereSql",
            $params
        )['total'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT p.*,
                    (SELECT caminho FROM petlove_fotos WHERE petlove_id = p.id AND principal = 1 LIMIT 1) AS foto_principal,
                    u.nome AS tutor_nome
             FROM petlove_pets p
             JOIN usuarios u ON u.id = p.usuario_id
             WHERE $whereSql
             ORDER BY p.tem_pedigree DESC, p.criado_em DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$porPagina, $offset])
        );

        return [
            'pets'       => $rows ?: [],
            'total'      => $total,
            'pagina'     => $pagina,
            'por_pagina' => $porPagina,
            'paginas'    => (int)ceil($total / $porPagina),
        ];
    }

    // ─────────────────────────────────────────────
    // MATCHING / COMPATIBILIDADE
    // ─────────────────────────────────────────────

    public function buscarCompativeis(int $petloveId, array $filtros = []): array {
        $pet = $this->buscarPorId($petloveId);
        if (!$pet) return [];

        $sexoOposto = $pet['sexo'] === 'macho' ? 'femea' : 'macho';

        // Portes adjacentes: mini ↔ pequeno, pequeno ↔ médio, médio ↔ grande, grande ↔ gigante
        $portesAdj = [
            'mini'    => ['mini',    'pequeno'],
            'pequeno' => ['mini',    'pequeno', 'medio'],
            'medio'   => ['pequeno', 'medio',   'grande'],
            'grande'  => ['medio',   'grande',  'gigante'],
            'gigante' => ['grande',  'gigante'],
        ];
        $portesOk = $portesAdj[$pet['porte']] ?? [$pet['porte']];
        $portesIn = implode(',', array_fill(0, count($portesOk), '?'));

        $params = array_merge(
            [$pet['especie'], $sexoOposto],
            $portesOk,
            [$petloveId, $pet['usuario_id']]
        );

        $rows = $this->db->fetchAll(
            "SELECT p.*,
                    (SELECT caminho FROM petlove_fotos WHERE petlove_id = p.id AND principal = 1 LIMIT 1) AS foto_principal,
                    u.nome AS tutor_nome
             FROM petlove_pets p
             JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.especie    = ?
               AND p.sexo       = ?
               AND p.porte      IN ($portesIn)
               AND p.status     = 'ativo'
               AND p.disponivel = 1
               AND p.id         != ?
               AND p.usuario_id != ?
             ORDER BY p.tem_pedigree DESC, p.criado_em DESC
             LIMIT 20",
            $params
        );
        if (!$rows) return [];

        // Calcular score e distância para cada match
        foreach ($rows as &$match) {
            $match['score']      = $this->pontuacaoCompatibilidade($pet, $match);
            $match['distancia_km'] = ($pet['latitude'] && $pet['longitude'] && $match['latitude'] && $match['longitude'])
                ? $this->calcularDistanciaKm(
                    (float)$pet['latitude'],  (float)$pet['longitude'],
                    (float)$match['latitude'], (float)$match['longitude']
                )
                : null;
        }
        unset($match);

        // Ordenar: score desc, distância asc
        usort($rows, function ($a, $b) {
            if ($b['score'] !== $a['score']) return $b['score'] - $a['score'];
            if ($a['distancia_km'] === null && $b['distancia_km'] === null) return 0;
            if ($a['distancia_km'] === null) return 1;
            if ($b['distancia_km'] === null) return -1;
            return $a['distancia_km'] <=> $b['distancia_km'];
        });

        return $rows;
    }

    public function calcularDistanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float {
        $raioTerra = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return round($raioTerra * 2 * asin(sqrt($a)), 1);
    }

    public function pontuacaoCompatibilidade(array $petA, array $petB): int {
        $score = 0;

        // Raça idêntica: +40 pts
        if (mb_strtolower(trim($petA['raca'])) === mb_strtolower(trim($petB['raca']))) {
            $score += 40;
        }

        // Porte idêntico: +20 pts; adjacente já incluído na query, mas adiciona menos
        if ($petA['porte'] === $petB['porte']) {
            $score += 20;
        } else {
            $score += 10;
        }

        // Ambos com pedigree: +20 pts; apenas um: +5
        if ($petA['tem_pedigree'] && $petB['tem_pedigree']) {
            $score += 20;
        } elseif ($petA['tem_pedigree'] || $petB['tem_pedigree']) {
            $score += 5;
        }

        // Distância: até 50 km = +20; até 100 km = +10; > 100 km = +0
        if ($petA['latitude'] && $petA['longitude'] && $petB['latitude'] && $petB['longitude']) {
            $km = $this->calcularDistanciaKm(
                (float)$petA['latitude'],  (float)$petA['longitude'],
                (float)$petB['latitude'],  (float)$petB['longitude']
            );
            if ($km <= 50)       $score += 20;
            elseif ($km <= 100)  $score += 10;
        }

        return min(100, $score);
    }

    // ─────────────────────────────────────────────
    // FOTOS
    // ─────────────────────────────────────────────

    public function adicionarFoto(int $petloveId, string $caminho, bool $principal = false): int {
        if ($principal) {
            $this->db->query(
                "UPDATE petlove_fotos SET principal = 0 WHERE petlove_id = ?",
                [$petloveId]
            );
        }
        $this->db->insert('petlove_fotos', [
            'petlove_id' => $petloveId,
            'caminho'    => $caminho,
            'principal'  => (int)$principal,
            'ordem'      => $this->proximaOrdemFoto($petloveId),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function buscarFotos(int $petloveId): array {
        return $this->db->fetchAll(
            "SELECT * FROM petlove_fotos WHERE petlove_id = ? ORDER BY principal DESC, ordem ASC",
            [$petloveId]
        ) ?: [];
    }

    private function proximaOrdemFoto(int $petloveId): int {
        $max = $this->db->fetchOne(
            "SELECT MAX(ordem) AS max_ordem FROM petlove_fotos WHERE petlove_id = ?",
            [$petloveId]
        );
        return (int)($max['max_ordem'] ?? 0) + 1;
    }

    public function removerFoto(int $fotoId, int $petloveId): bool {
        $foto = $this->db->fetchOne(
            "SELECT caminho FROM petlove_fotos WHERE id = ? AND petlove_id = ?",
            [$fotoId, $petloveId]
        );
        if (!$foto) return false;
        $this->db->query("DELETE FROM petlove_fotos WHERE id = ?", [$fotoId]);
        $path = UPLOAD_PATH . '/petlove/' . $foto['caminho'];
        if (file_exists($path)) @unlink($path);
        return true;
    }

    // ─────────────────────────────────────────────
    // STATUS
    // ─────────────────────────────────────────────

    public function alterarStatus(int $id, string $status): bool {
        $validos = ['ativo', 'pausado', 'removido'];
        if (!in_array($status, $validos, true)) return false;
        return $this->db->update('petlove_pets', ['status' => $status], 'id = ?', [$id]) !== false;
    }

    public function pertenceAoUsuario(int $id, int $usuarioId): bool {
        $row = $this->db->fetchOne(
            "SELECT id FROM petlove_pets WHERE id = ? AND usuario_id = ?",
            [$id, $usuarioId]
        );
        return (bool)$row;
    }

    // ─────────────────────────────────────────────
    // HELPERS DE EXIBIÇÃO
    // ─────────────────────────────────────────────

    public static function labelEspecie(string $especie): string {
        return ['cachorro' => 'Cachorro', 'gato' => 'Gato', 'outro' => 'Outro'][$especie] ?? ucfirst($especie);
    }

    public static function labelPorte(string $porte): string {
        return [
            'mini'    => 'Miniatura',
            'pequeno' => 'Pequeno',
            'medio'   => 'Médio',
            'grande'  => 'Grande',
            'gigante' => 'Gigante',
        ][$porte] ?? ucfirst($porte);
    }

    public static function labelIdade(int $meses): string {
        if ($meses < 12) return $meses . ' ' . ($meses === 1 ? 'mês' : 'meses');
        $anos = intdiv($meses, 12);
        $rest = $meses % 12;
        $str  = $anos . ' ' . ($anos === 1 ? 'ano' : 'anos');
        if ($rest > 0) $str .= ' e ' . $rest . ' ' . ($rest === 1 ? 'mês' : 'meses');
        return $str;
    }
}
