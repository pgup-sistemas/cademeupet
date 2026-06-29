<?php

class ParceiroContrato
{
    const VERSAO_ATUAL = '1.0';

    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    public function findAceiteAtivo(int $usuarioId, string $plano, string $periodicidade)
    {
        return $this->db->fetchOne(
            'SELECT * FROM parceiro_contratos_aceites
             WHERE usuario_id = ? AND versao_contrato = ? AND plano = ? AND periodicidade = ?
             ORDER BY aceito_em DESC LIMIT 1',
            [$usuarioId, self::VERSAO_ATUAL, $plano, $periodicidade]
        );
    }

    public function registrarAceite(array $dados): int
    {
        return $this->db->insert('parceiro_contratos_aceites', [
            'usuario_id'      => (int)$dados['usuario_id'],
            'versao_contrato' => $dados['versao_contrato'] ?? self::VERSAO_ATUAL,
            'plano'           => (string)$dados['plano'],
            'periodicidade'   => (string)$dados['periodicidade'],
            'valor_mensal'    => (float)$dados['valor_mensal'],
            'ip_aceite'       => (string)($dados['ip_aceite'] ?? ''),
            'user_agent'      => (string)($dados['user_agent'] ?? ''),
            'hash_contrato'   => (string)($dados['hash_contrato'] ?? ''),
            'aceito_em'       => date('Y-m-d H:i:s'),
        ]);
    }

    public function listByUsuario(int $usuarioId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM parceiro_contratos_aceites WHERE usuario_id = ? ORDER BY aceito_em DESC',
            [$usuarioId]
        );
    }

    public function findById(int $id)
    {
        return $this->db->fetchOne(
            'SELECT c.*, u.nome AS usuario_nome, u.email
             FROM parceiro_contratos_aceites c
             JOIN usuarios u ON u.id = c.usuario_id
             WHERE c.id = ? LIMIT 1',
            [$id]
        );
    }
}
