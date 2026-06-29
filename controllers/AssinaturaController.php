<?php

class AssinaturaController
{
    private $db;
    private $doacaoModel;

    public function __construct($db = null)
    {
        $this->db       = $db ?? getDB();
        $this->doacaoModel = new Doacao();
    }

    public function getAssinaturas(int $usuarioId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM doacoes
             WHERE usuario_id = ?
               AND status IN ('pendente', 'aprovada', 'ativa')
               AND efi_subscription_id IS NOT NULL
               AND efi_subscription_id != ''
             ORDER BY criada_em DESC",
            [$usuarioId]
        ) ?: [];
    }

    public function getHistorico(int $usuarioId): array
    {
        return $this->db->fetchAll(
            "SELECT d.*,
                    CASE
                        WHEN d.status = 'aprovada' THEN 'Pago'
                        WHEN d.status = 'pendente' THEN 'Aguardando'
                        WHEN d.status = 'recusado' THEN 'Recusado'
                        ELSE d.status
                    END as status_label
             FROM doacoes d
             WHERE d.usuario_id = ?
               AND d.efi_subscription_id IS NOT NULL
               AND d.efi_subscription_id != ''
             ORDER BY d.ultimo_pagamento_em DESC
             LIMIT 20",
            [$usuarioId]
        ) ?: [];
    }

    public function processarAcao(int $usuarioId, int $assinaturaId, string $acao): bool
    {
        $assinatura = $this->doacaoModel->findById($assinaturaId);

        if (empty($assinatura) || (int)$assinatura['usuario_id'] !== $usuarioId) {
            return false;
        }

        $agora = date('Y-m-d H:i:s');

        if ($acao === 'cancelar' && $assinatura['status'] !== 'cancelada') {
            $this->doacaoModel->update($assinaturaId, ['status' => 'cancelada', 'cancelada_em' => $agora]);
            error_log("[AssinaturaController] Usuário $usuarioId cancelou assinatura $assinaturaId");
            return true;
        }

        if ($acao === 'pausar' && $assinatura['status'] === 'ativa') {
            $this->doacaoModel->update($assinaturaId, ['status' => 'pausada', 'pausada_em' => $agora]);
            error_log("[AssinaturaController] Usuário $usuarioId pausou assinatura $assinaturaId");
            return true;
        }

        if ($acao === 'reativar' && $assinatura['status'] === 'pausada') {
            $this->doacaoModel->update($assinaturaId, ['status' => 'ativa', 'pausada_em' => null]);
            error_log("[AssinaturaController] Usuário $usuarioId reativou assinatura $assinaturaId");
            return true;
        }

        return false;
    }
}
