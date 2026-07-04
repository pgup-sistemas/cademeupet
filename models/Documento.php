<?php
/**
 * Cadê Meu Pet? - Núcleo genérico de documento assinável.
 * Reutilizado por laudo veterinário (Fase 3) e termo de adoção (Fase 1).
 * Ver docs/modulo-atendimento-veterinario-laudo.md
 *
 * Regra de imutabilidade: o conteúdo só pode ser alterado enquanto o
 * documento está em 'rascunho' (nenhuma assinatura ainda). A partir da
 * primeira assinatura, o documento vira 'aguardando_assinaturas' e o
 * conteúdo fica travado — qualquer correção exige um novo documento.
 */
class Documento
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    public function criar(array $dados): int
    {
        $conteudo = $dados['conteudo_html'];
        $codigo = $this->gerarCodigoVerificacaoUnico();

        return (int)$this->db->insert('documentos', [
            'tipo'                  => $dados['tipo'],
            'referencia_tipo'       => $dados['referencia_tipo'],
            'referencia_id'         => $dados['referencia_id'],
            'conteudo_html'         => $conteudo,
            'hash_conteudo'         => hash('sha256', $conteudo),
            'status'                => 'rascunho',
            'codigo_verificacao'    => $codigo,
            'criado_por_usuario_id' => $dados['criado_por_usuario_id'],
        ]);
    }

    public function buscarPorId(int $id): ?array
    {
        $row = $this->db->fetchOne('SELECT * FROM documentos WHERE id = ?', [$id]);
        return $row ?: null;
    }

    public function buscarPorCodigoVerificacao(string $codigo): ?array
    {
        $row = $this->db->fetchOne('SELECT * FROM documentos WHERE codigo_verificacao = ?', [$codigo]);
        return $row ?: null;
    }

    /**
     * Atualiza o conteúdo — só permitido em 'rascunho' (antes da 1ª assinatura).
     * Recalcula o hash_conteudo. Retorna false se o documento já não estiver
     * mais em rascunho (assinado/aguardando/revogado).
     */
    public function atualizarConteudo(int $id, string $novoConteudoHtml): bool
    {
        $documento = $this->buscarPorId($id);
        if (!$documento || $documento['status'] !== 'rascunho') {
            return false;
        }

        return $this->db->update('documentos', [
            'conteudo_html' => $novoConteudoHtml,
            'hash_conteudo' => hash('sha256', $novoConteudoHtml),
        ], 'id = ?', [$id]) !== false;
    }

    public function marcarAguardandoAssinaturas(int $id): bool
    {
        return $this->db->update(
            'documentos',
            ['status' => 'aguardando_assinaturas'],
            "id = ? AND status = 'rascunho'",
            [$id]
        ) !== false;
    }

    public function marcarAssinado(int $id, ?string $pdfPath = null): bool
    {
        $campos = ['status' => 'assinado'];
        if ($pdfPath !== null) {
            $campos['pdf_path'] = $pdfPath;
        }
        return $this->db->update(
            'documentos',
            $campos,
            "id = ? AND status = 'aguardando_assinaturas'",
            [$id]
        ) !== false;
    }

    public function definirPdf(int $id, string $pdfPath): bool
    {
        return $this->db->update('documentos', ['pdf_path' => $pdfPath], 'id = ?', [$id]) !== false;
    }

    public function revogar(int $id, string $motivo): bool
    {
        return $this->db->update('documentos', [
            'status'           => 'revogado',
            'revogado_em'      => date('Y-m-d H:i:s'),
            'motivo_revogacao' => $motivo,
        ], 'id = ?', [$id]) !== false;
    }

    public function estaImutavel(array $documento): bool
    {
        return in_array($documento['status'], ['aguardando_assinaturas', 'assinado', 'revogado'], true);
    }

    private function gerarCodigoVerificacaoUnico(): string
    {
        do {
            $codigo = strtoupper(bin2hex(random_bytes(5))); // 10 caracteres hex
            $existe = $this->db->fetchOne('SELECT id FROM documentos WHERE codigo_verificacao = ?', [$codigo]);
        } while ($existe);

        return $codigo;
    }
}
