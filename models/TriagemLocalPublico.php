<?php
/**
 * Cadê Meu Pet? - Locais públicos/institucionais de atendimento veterinário
 * (ex.: Clínica de Bem-Estar Animal Municipal). Sem integração digital real
 * com a fila presencial — apenas informação para orientar o tutor.
 */
class TriagemLocalPublico
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    public function buscarAtivosPorCidade(string $cidade, string $estado): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM triagem_locais_publicos WHERE ativo = 1 AND cidade = ? AND estado = ? ORDER BY nome',
            [$cidade, $estado]
        ) ?: [];
    }

    public function buscarPorId(int $id): ?array
    {
        $row = $this->db->fetchOne('SELECT * FROM triagem_locais_publicos WHERE id = ?', [$id]);
        return $row ?: null;
    }

    public function listarAtivos(): array
    {
        return $this->db->fetchAll('SELECT * FROM triagem_locais_publicos WHERE ativo = 1 ORDER BY cidade, nome') ?: [];
    }
}
