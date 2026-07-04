<?php
/**
 * Cadê Meu Pet? - Verificação pública de autenticidade de documentos.
 * Ver docs/modulo-atendimento-veterinario-laudo.md (Fase 4)
 *
 * Página SEM login — qualquer um com o código pode conferir autenticidade
 * (hash + assinaturas). NUNCA expõe o conteúdo clínico completo (diagnóstico,
 * texto do laudo/atestado) — isso é dado sensível do pet/tutor. Mostra
 * apenas metadados suficientes para confirmar que o documento é genuíno.
 */
class VerificacaoController
{
    private $db;
    private Documento $documentoModel;
    private DocumentoAssinatura $assinaturaModel;

    public function __construct($db = null, ?Documento $documentoModel = null, ?DocumentoAssinatura $assinaturaModel = null)
    {
        $this->db              = $db ?: getDB();
        $this->documentoModel  = $documentoModel ?: new Documento($this->db);
        $this->assinaturaModel = $assinaturaModel ?: new DocumentoAssinatura($this->db, $this->documentoModel);
    }

    public function buscarPorCodigo(string $codigo): ?array
    {
        $codigo = strtoupper(trim($codigo));
        $documento = $this->documentoModel->buscarPorCodigoVerificacao($codigo);
        if (!$documento) {
            return null;
        }

        $assinaturas = array_map(function (array $a): array {
            return [
                'usuario_nome' => $a['usuario_nome'],
                'papel'        => $a['papel'],
                'identificacao_extra' => $a['identificacao_extra'],
                'assinado_em'  => $a['assinado_em'],
            ];
        }, $this->assinaturaModel->listarPorDocumento((int)$documento['id']));

        $referencia = $this->resolverReferencia($documento);

        $retificacoes = $this->documentoModel->buscarRetificacoes((int)$documento['id']);
        $codigoSubstituto = null;
        if (!empty($retificacoes)) {
            $ultima = end($retificacoes);
            $codigoSubstituto = $ultima['codigo_verificacao'];
        }

        return [
            'tipo'               => $documento['tipo'],
            'status'             => $documento['status'],
            'codigo_verificacao' => $documento['codigo_verificacao'],
            'criado_em'          => $documento['criado_em'],
            'assinaturas'        => $assinaturas,
            'referencia'         => $referencia,
            'foi_substituido'    => $codigoSubstituto !== null,
            'codigo_substituto'  => $codigoSubstituto,
            'eh_retificacao'     => !empty($documento['retifica_documento_id']),
        ];
    }

    private function resolverReferencia(array $documento): array
    {
        if ($documento['referencia_tipo'] === 'atendimento') {
            $row = $this->db->fetchOne(
                "SELECT p.nome AS pet_nome, p.especie AS pet_especie, pp.nome_fantasia AS clinica_nome, pp.cidade AS clinica_cidade
                 FROM atendimentos a
                 JOIN pets p ON p.id = a.pet_id
                 JOIN parceiro_perfis pp ON pp.id = a.parceiro_perfil_id
                 WHERE a.id = ?",
                [$documento['referencia_id']]
            );
            return [
                'pet_nome'      => $row['pet_nome'] ?? null,
                'pet_especie'   => $row['pet_especie'] ?? null,
                'emissor_nome'  => $row['clinica_nome'] ?? null,
                'emissor_local' => $row['clinica_cidade'] ?? null,
            ];
        }

        if ($documento['referencia_tipo'] === 'anuncio') {
            $row = $this->db->fetchOne(
                'SELECT nome_pet, especie, cidade FROM anuncios WHERE id = ?',
                [$documento['referencia_id']]
            );
            return [
                'pet_nome'      => $row['nome_pet'] ?? null,
                'pet_especie'   => $row['especie'] ?? null,
                'emissor_nome'  => 'Cadê Meu Pet?',
                'emissor_local' => $row['cidade'] ?? null,
            ];
        }

        return [];
    }
}
