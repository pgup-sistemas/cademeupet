<?php
/**
 * Cadê Meu Pet? - Laudo/atestado/receituário veterinário assinado.
 * Vínculo fino entre um atendimento e o núcleo genérico de documento
 * assinável (Documento + DocumentoAssinatura). Ver
 * docs/modulo-atendimento-veterinario-laudo.md (Fase 3)
 */
class Laudo
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    public function vincular(int $atendimentoId, int $documentoId): int
    {
        return (int)$this->db->insert('laudos', [
            'atendimento_id' => $atendimentoId,
            'documento_id'   => $documentoId,
        ]);
    }

    public function buscarPorId(int $id): ?array
    {
        $row = $this->db->fetchOne(
            'SELECT l.*, d.tipo, d.status AS documento_status, d.codigo_verificacao, d.pdf_path,
                    d.conteudo_html, d.criado_por_usuario_id, d.retifica_documento_id
             FROM laudos l
             JOIN documentos d ON d.id = l.documento_id
             WHERE l.id = ?',
            [$id]
        );
        return $row ?: null;
    }

    public function buscarPorAtendimento(int $atendimentoId): array
    {
        return $this->db->fetchAll(
            'SELECT l.*, d.tipo, d.status AS documento_status, d.codigo_verificacao, d.pdf_path, d.criado_em AS documento_criado_em
             FROM laudos l
             JOIN documentos d ON d.id = l.documento_id
             WHERE l.atendimento_id = ?
             ORDER BY d.criado_em DESC',
            [$atendimentoId]
        ) ?: [];
    }

    /** Histórico de laudos assinados de um pet (via atendimentos do pet). */
    public function buscarPorPet(int $petId): array
    {
        return $this->db->fetchAll(
            "SELECT l.*, d.tipo, d.status AS documento_status, d.codigo_verificacao, d.pdf_path, d.criado_em AS documento_criado_em,
                    a.motivo_consulta, a.pet_id, v.nome_completo AS veterinario_nome, pp.nome_fantasia AS clinica_nome
             FROM laudos l
             JOIN documentos d ON d.id = l.documento_id
             JOIN atendimentos a ON a.id = l.atendimento_id
             JOIN parceiro_veterinarios v ON v.id = a.veterinario_id
             JOIN parceiro_perfis pp ON pp.id = a.parceiro_perfil_id
             WHERE a.pet_id = ? AND d.status = 'assinado'
             ORDER BY d.criado_em DESC",
            [$petId]
        ) ?: [];
    }

    public function pertenceAoVeterinario(int $laudoId, int $criadoPorUsuarioId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT l.id FROM laudos l JOIN documentos d ON d.id = l.documento_id
             WHERE l.id = ? AND d.criado_por_usuario_id = ?',
            [$laudoId, $criadoPorUsuarioId]
        );
        return (bool)$row;
    }
}
