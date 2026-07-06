<?php
/**
 * Cadê Meu Pet? - Veterinários habilitados por clínica parceira (CRMV).
 * Ver docs/modulo-atendimento-veterinario-laudo.md (Fase 2)
 */
class ParceiroVeterinario
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    public function criar(array $dados): int
    {
        return (int)$this->db->insert('parceiro_veterinarios', [
            'parceiro_perfil_id' => $dados['parceiro_perfil_id'],
            'usuario_id'         => $dados['usuario_id'],
            'nome_completo'      => $dados['nome_completo'],
            'crmv_numero'        => $dados['crmv_numero'],
            'crmv_uf'            => strtoupper($dados['crmv_uf']),
            'status'             => 'pendente_validacao',
        ]);
    }

    public function buscarPorId(int $id): ?array
    {
        $row = $this->db->fetchOne('SELECT * FROM parceiro_veterinarios WHERE id = ?', [$id]);
        return $row ?: null;
    }

    public function buscarPorUsuarioId(int $usuarioId): ?array
    {
        $row = $this->db->fetchOne('SELECT * FROM parceiro_veterinarios WHERE usuario_id = ? ORDER BY id DESC LIMIT 1', [$usuarioId]);
        return $row ?: null;
    }

    /** Só retorna se estiver aprovado — usado para autorizar abrir atendimento/laudo. */
    public function buscarAprovadoPorUsuarioId(int $usuarioId): ?array
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM parceiro_veterinarios WHERE usuario_id = ? AND status = 'aprovado' ORDER BY id DESC LIMIT 1",
            [$usuarioId]
        );
        return $row ?: null;
    }

    public function crmvJaExiste(string $numero, string $uf, ?int $ignorarId = null): bool
    {
        $sql = 'SELECT id FROM parceiro_veterinarios WHERE crmv_numero = ? AND crmv_uf = ?';
        $params = [$numero, strtoupper($uf)];
        if ($ignorarId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $ignorarId;
        }
        $row = $this->db->fetchOne($sql, $params);
        return (bool)$row;
    }

    public function pertenceAClinica(int $veterinarioId, int $parceiroPerfilId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT id FROM parceiro_veterinarios WHERE id = ? AND parceiro_perfil_id = ?',
            [$veterinarioId, $parceiroPerfilId]
        );
        return (bool)$row;
    }

    /**
     * Atualiza dados cadastrais. Se o CRMV (número ou UF) mudar, o registro
     * volta para 'pendente_validacao' — a validação anterior era sobre o
     * CRMV antigo, então uma mudança exige nova conferência manual.
     */
    public function atualizar(int $id, array $dados): bool
    {
        $atual = $this->buscarPorId($id);
        if (!$atual) {
            return false;
        }

        $novoNumero = $dados['crmv_numero'];
        $novaUf = strtoupper($dados['crmv_uf']);
        $crmvMudou = $novoNumero !== $atual['crmv_numero'] || $novaUf !== $atual['crmv_uf'];

        $campos = [
            'nome_completo' => $dados['nome_completo'],
            'crmv_numero'   => $novoNumero,
            'crmv_uf'       => $novaUf,
        ];

        if ($crmvMudou && $atual['status'] !== 'pendente_validacao') {
            $campos['status'] = 'pendente_validacao';
            $campos['validado_por'] = null;
            $campos['validado_em'] = null;
            $campos['motivo_rejeicao'] = null;
        }

        return $this->db->update('parceiro_veterinarios', $campos, 'id = ?', [$id]) !== false;
    }

    public function temAtendimentos(int $id): bool
    {
        $row = $this->db->fetchOne('SELECT id FROM atendimentos WHERE veterinario_id = ? LIMIT 1', [$id]);
        return (bool)$row;
    }

    /** Exclusão definitiva — só deve ser chamada quando não há atendimentos vinculados (ver temAtendimentos). */
    public function remover(int $id): bool
    {
        return $this->db->delete('parceiro_veterinarios', 'id = ?', [$id]) !== false;
    }

    /** Remoção "suave": mantém o histórico de atendimentos intacto, apenas revoga o acesso. */
    public function desativarPelaClinica(int $id): bool
    {
        return $this->db->update('parceiro_veterinarios', [
            'status'          => 'suspenso',
            'motivo_rejeicao' => 'Removido da equipe pela clínica.',
        ], 'id = ?', [$id]) !== false;
    }

    public function listarPorParceiro(int $parceiroPerfilId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM parceiro_veterinarios WHERE parceiro_perfil_id = ? ORDER BY criado_em DESC',
            [$parceiroPerfilId]
        ) ?: [];
    }

    public function listarPendentes(): array
    {
        return $this->db->fetchAll(
            "SELECT v.*, pp.nome_fantasia AS clinica_nome, pp.cidade AS clinica_cidade, pp.estado AS clinica_estado
             FROM parceiro_veterinarios v
             JOIN parceiro_perfis pp ON pp.id = v.parceiro_perfil_id
             WHERE v.status = 'pendente_validacao'
             ORDER BY v.criado_em ASC"
        ) ?: [];
    }

    public function listarTodos(): array
    {
        return $this->db->fetchAll(
            "SELECT v.*, pp.nome_fantasia AS clinica_nome
             FROM parceiro_veterinarios v
             JOIN parceiro_perfis pp ON pp.id = v.parceiro_perfil_id
             ORDER BY FIELD(v.status, 'pendente_validacao','aprovado','suspenso','rejeitado'), v.criado_em DESC"
        ) ?: [];
    }

    public function aprovar(int $id, int $adminUsuarioId): bool
    {
        return $this->db->update('parceiro_veterinarios', [
            'status'       => 'aprovado',
            'validado_por' => $adminUsuarioId,
            'validado_em'  => date('Y-m-d H:i:s'),
            'motivo_rejeicao' => null,
        ], "id = ? AND status = 'pendente_validacao'", [$id]) !== false;
    }

    public function rejeitar(int $id, int $adminUsuarioId, string $motivo): bool
    {
        return $this->db->update('parceiro_veterinarios', [
            'status'          => 'rejeitado',
            'validado_por'    => $adminUsuarioId,
            'validado_em'     => date('Y-m-d H:i:s'),
            'motivo_rejeicao' => $motivo,
        ], "id = ? AND status = 'pendente_validacao'", [$id]) !== false;
    }

    public function suspender(int $id, int $adminUsuarioId, string $motivo): bool
    {
        return $this->db->update('parceiro_veterinarios', [
            'status'          => 'suspenso',
            'validado_por'    => $adminUsuarioId,
            'validado_em'     => date('Y-m-d H:i:s'),
            'motivo_rejeicao' => $motivo,
        ], 'id = ?', [$id]) !== false;
    }
}
