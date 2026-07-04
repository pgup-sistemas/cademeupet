<?php
/**
 * Cadê Meu Pet? - Solicitações de triagem/direcionamento veterinário emergencial.
 * Suporta tutor anônimo (usuario_id nulo) — em emergência, exigir cadastro
 * pode atrasar quem mais precisa de orientação rápida.
 */
class TriagemSolicitacao
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    public function criar(array $dados): int
    {
        $id = $this->db->insert('triagem_solicitacoes', [
            'usuario_id'                 => $dados['usuario_id'] ?? null,
            'nome_contato'               => $dados['nome_contato'] ?? null,
            'telefone_contato'           => $dados['telefone_contato'] ?? null,
            'especie'                    => $dados['especie'],
            'sintomas'                   => json_encode($dados['sintomas'] ?? [], JSON_UNESCAPED_UNICODE),
            'nivel_urgencia'              => $dados['nivel_urgencia'],
            'renda_baixa_declarada'       => isset($dados['renda_baixa_declarada']) ? (int)(bool)$dados['renda_baixa_declarada'] : null,
            'cidade'                     => $dados['cidade'] ?? null,
            'estado'                     => $dados['estado'] ?? null,
            'latitude'                   => isset($dados['latitude']) && $dados['latitude'] !== '' ? (float)$dados['latitude'] : null,
            'longitude'                  => isset($dados['longitude']) && $dados['longitude'] !== '' ? (float)$dados['longitude'] : null,
            'direcionamento_sugerido'     => $dados['direcionamento_sugerido'],
            'triagem_locais_publicos_id' => $dados['triagem_locais_publicos_id'] ?? null,
            'parceiro_perfil_id'         => $dados['parceiro_perfil_id'] ?? null,
            'disclaimer_aceito'          => (int)!empty($dados['disclaimer_aceito']),
            'status'                     => 'orientado',
        ]);
        return (int)$id;
    }

    public function buscarPorId(int $id): ?array
    {
        $row = $this->db->fetchOne('SELECT * FROM triagem_solicitacoes WHERE id = ?', [$id]);
        if (!$row) return null;
        $row['sintomas'] = json_decode($row['sintomas'] ?? '[]', true) ?: [];
        return $row;
    }

    public function buscarPorUsuario(int $usuarioId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT s.*, pp.nome_fantasia AS parceiro_nome, tlp.nome AS local_publico_nome
             FROM triagem_solicitacoes s
             LEFT JOIN parceiro_perfis pp ON pp.id = s.parceiro_perfil_id
             LEFT JOIN triagem_locais_publicos tlp ON tlp.id = s.triagem_locais_publicos_id
             WHERE s.usuario_id = ?
             ORDER BY s.criado_em DESC',
            [$usuarioId]
        ) ?: [];
        foreach ($rows as &$row) {
            $row['sintomas'] = json_decode($row['sintomas'] ?? '[]', true) ?: [];
        }
        unset($row);
        return $rows;
    }

    public function buscarPorParceiro(int $parceiroPerfilId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT s.*, u.nome AS tutor_nome
             FROM triagem_solicitacoes s
             LEFT JOIN usuarios u ON u.id = s.usuario_id
             WHERE s.parceiro_perfil_id = ?
             ORDER BY FIELD(s.nivel_urgencia, 'critica','alta','moderada','baixa'), s.criado_em DESC",
            [$parceiroPerfilId]
        ) ?: [];
        foreach ($rows as &$row) {
            $row['sintomas'] = json_decode($row['sintomas'] ?? '[]', true) ?: [];
        }
        unset($row);
        return $rows;
    }

    public function vincularConversa(int $id, int $conversaId): bool
    {
        return $this->db->update(
            'triagem_solicitacoes',
            ['conversa_id' => $conversaId, 'status' => 'em_contato'],
            'id = ?',
            [$id]
        ) !== false;
    }

    public function atualizarStatus(int $id, string $status): bool
    {
        $validos = ['orientado', 'em_contato', 'encerrado', 'abandonado'];
        if (!in_array($status, $validos, true)) return false;
        return $this->db->update('triagem_solicitacoes', ['status' => $status], 'id = ?', [$id]) !== false;
    }

    public function pertenceAoUsuario(int $id, int $usuarioId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT id FROM triagem_solicitacoes WHERE id = ? AND usuario_id = ?',
            [$id, $usuarioId]
        );
        return (bool)$row;
    }
}
