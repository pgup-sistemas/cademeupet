<?php
/**
 * Cadê Meu Pet? - Trilha de auditoria de assinatura (multi-signatário).
 * Ver docs/modulo-atendimento-veterinario-laudo.md
 *
 * Assinatura eletrônica SIMPLES e auditável (hash + timestamp + IP),
 * sem validade jurídica ICP-Brasil. Isso deve ficar explícito para quem
 * assina, na camada de apresentação (controller/view), não só aqui.
 */
class DocumentoAssinatura
{
    private $db;
    private Documento $documentoModel;

    public function __construct($db = null, ?Documento $documentoModel = null)
    {
        $this->db = $db ?: getDB();
        $this->documentoModel = $documentoModel ?: new Documento($this->db);
    }

    /**
     * Registra uma assinatura. Move o documento de 'rascunho' para
     * 'aguardando_assinaturas' na primeira assinatura (travando o conteúdo).
     * Retorna ['success' => bool, 'error' => ?string].
     */
    public function assinar(
        int $documentoId,
        int $usuarioId,
        string $papel,
        ?string $identificacaoExtra,
        ?string $ipAddress,
        ?string $userAgent
    ): array {
        $documento = $this->documentoModel->buscarPorId($documentoId);
        if (!$documento) {
            return ['success' => false, 'error' => 'Documento não encontrado.'];
        }
        if ($documento['status'] === 'revogado') {
            return ['success' => false, 'error' => 'Este documento foi revogado.'];
        }
        if ($documento['status'] === 'assinado') {
            return ['success' => false, 'error' => 'Este documento já está totalmente assinado.'];
        }

        $jaAssinouEssePapel = $this->db->fetchOne(
            'SELECT id FROM documento_assinaturas WHERE documento_id = ? AND usuario_id = ? AND papel = ?',
            [$documentoId, $usuarioId, $papel]
        );
        if ($jaAssinouEssePapel) {
            return ['success' => false, 'error' => 'Você já assinou este documento neste papel.'];
        }

        // Trava o conteúdo na primeira assinatura (rascunho -> aguardando_assinaturas).
        if ($documento['status'] === 'rascunho') {
            $this->documentoModel->marcarAguardandoAssinaturas($documentoId);
            $documento = $this->documentoModel->buscarPorId($documentoId);
        }

        $this->db->insert('documento_assinaturas', [
            'documento_id'        => $documentoId,
            'usuario_id'          => $usuarioId,
            'papel'               => $papel,
            'identificacao_extra' => $identificacaoExtra,
            'hash_no_momento'     => $documento['hash_conteudo'],
            'ip_address'          => $ipAddress,
            'user_agent'          => $userAgent ? mb_substr($userAgent, 0, 255) : null,
        ]);

        return ['success' => true];
    }

    public function listarPorDocumento(int $documentoId): array
    {
        return $this->db->fetchAll(
            'SELECT da.*, u.nome AS usuario_nome
             FROM documento_assinaturas da
             JOIN usuarios u ON u.id = da.usuario_id
             WHERE da.documento_id = ?
             ORDER BY da.assinado_em ASC',
            [$documentoId]
        ) ?: [];
    }

    public function papeisJaAssinados(int $documentoId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT DISTINCT papel FROM documento_assinaturas WHERE documento_id = ?',
            [$documentoId]
        ) ?: [];
        return array_column($rows, 'papel');
    }

    /** Verifica se todas as assinaturas obrigatórias (papéis) já existem para o documento. */
    public function todosPapeisObrigatoriosAssinados(int $documentoId, array $papeisObrigatorios): bool
    {
        $assinados = $this->papeisJaAssinados($documentoId);
        foreach ($papeisObrigatorios as $papel) {
            if (!in_array($papel, $assinados, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Verifica a integridade de todas as assinaturas de um documento —
     * confirma que o hash registrado em cada assinatura bate com o
     * hash_conteudo atual do documento (nenhuma adulteração pós-assinatura).
     */
    public function verificarIntegridade(int $documentoId): bool
    {
        $documento = $this->documentoModel->buscarPorId($documentoId);
        if (!$documento) {
            return false;
        }
        $assinaturas = $this->listarPorDocumento($documentoId);
        foreach ($assinaturas as $assinatura) {
            if ($assinatura['hash_no_momento'] !== $documento['hash_conteudo']) {
                return false;
            }
        }
        return true;
    }
}
