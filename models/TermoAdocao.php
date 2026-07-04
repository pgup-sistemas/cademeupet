<?php
/**
 * Cadê Meu Pet? - Termo de Responsabilidade de Adoção/Doação.
 * Ver docs/modulo-atendimento-veterinario-laudo.md (Fase 1)
 */
class TermoAdocao
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    public function criar(array $dados): int
    {
        $expiraEm = date('Y-m-d H:i:s', strtotime('+30 days'));

        return (int)$this->db->insert('termos_adocao', [
            'anuncio_id'                  => $dados['anuncio_id'],
            'pet_id'                      => $dados['pet_id'] ?? null,
            'documento_id'                => $dados['documento_id'],
            'doador_usuario_id'           => $dados['doador_usuario_id'],
            'adotante_usuario_id'         => $dados['adotante_usuario_id'] ?? null,
            'adotante_nome_informado'     => $dados['adotante_nome_informado'] ?? null,
            'adotante_telefone_informado' => $dados['adotante_telefone_informado'] ?? null,
            'parceiro_testemunha_id'      => $dados['parceiro_testemunha_id'] ?? null,
            'status'                      => 'aguardando_adotante',
            'expira_em'                   => $expiraEm,
        ]);
    }

    public function buscarPorId(int $id): ?array
    {
        $row = $this->db->fetchOne(
            'SELECT t.*, d.status AS documento_status, d.codigo_verificacao, d.pdf_path, d.conteudo_html
             FROM termos_adocao t
             JOIN documentos d ON d.id = t.documento_id
             WHERE t.id = ?',
            [$id]
        );
        return $row ?: null;
    }

    public function buscarPorAnuncio(int $anuncioId): ?array
    {
        $row = $this->db->fetchOne('SELECT * FROM termos_adocao WHERE anuncio_id = ? ORDER BY id DESC LIMIT 1', [$anuncioId]);
        return $row ?: null;
    }

    public function buscarComoDoador(int $doadorUsuarioId): array
    {
        return $this->db->fetchAll(
            "SELECT t.*, a.nome_pet, a.especie, d.status AS documento_status
             FROM termos_adocao t
             JOIN anuncios a ON a.id = t.anuncio_id
             JOIN documentos d ON d.id = t.documento_id
             WHERE t.doador_usuario_id = ?
             ORDER BY t.criado_em DESC",
            [$doadorUsuarioId]
        ) ?: [];
    }

    public function buscarComoAdotante(int $adotanteUsuarioId): array
    {
        return $this->db->fetchAll(
            "SELECT t.*, a.nome_pet, a.especie, d.status AS documento_status
             FROM termos_adocao t
             JOIN anuncios a ON a.id = t.anuncio_id
             JOIN documentos d ON d.id = t.documento_id
             WHERE t.adotante_usuario_id = ?
             ORDER BY t.criado_em DESC",
            [$adotanteUsuarioId]
        ) ?: [];
    }

    /** Termos endereçados a alguém sem conta ainda, buscando por nome+telefone batendo com a conta recém criada/logada. */
    public function buscarPendentesPorTelefone(string $telefone): array
    {
        return $this->db->fetchAll(
            "SELECT t.* FROM termos_adocao t
             WHERE t.adotante_usuario_id IS NULL
               AND t.adotante_telefone_informado = ?
               AND t.status = 'aguardando_adotante'",
            [$telefone]
        ) ?: [];
    }

    public function vincularAdotanteUsuario(int $termoId, int $usuarioId): bool
    {
        return $this->db->update('termos_adocao', ['adotante_usuario_id' => $usuarioId], 'id = ?', [$termoId]) !== false;
    }

    public function atualizarStatus(int $id, string $status): bool
    {
        $validos = ['aguardando_adotante', 'assinado', 'recusado', 'expirado'];
        if (!in_array($status, $validos, true)) {
            return false;
        }
        return $this->db->update('termos_adocao', ['status' => $status], 'id = ?', [$id]) !== false;
    }

    public function definirTestemunha(int $id, int $parceiroPerfilId): bool
    {
        return $this->db->update('termos_adocao', ['parceiro_testemunha_id' => $parceiroPerfilId], 'id = ?', [$id]) !== false;
    }

    public function marcarExpiradosVencidos(): int
    {
        $stmt = $this->db->query(
            "UPDATE termos_adocao SET status = 'expirado'
             WHERE status = 'aguardando_adotante' AND expira_em IS NOT NULL AND expira_em < NOW()"
        );
        return $stmt->rowCount();
    }
}
