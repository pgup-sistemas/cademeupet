<?php

class PetLoveInteresseController {

    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function manifestar(array $dados): array {
        requireLogin();
        if (!validateCSRFToken($dados['csrf_token'] ?? '')) {
            return ['ok' => false, 'erro' => 'Token inválido.'];
        }

        $petloveId      = (int)($dados['petlove_id']      ?? 0);
        $petInteresseId = (int)($dados['pet_interessado_id'] ?? 0) ?: null;
        $mensagem       = trim((string)($dados['mensagem'] ?? ''));
        $usuarioId      = getUserId();

        if ($petloveId <= 0) {
            return ['ok' => false, 'erro' => 'Pet não encontrado.'];
        }

        // O dono do pet não pode demonstrar interesse no próprio pet
        $pet = $this->db->fetchOne(
            "SELECT id, usuario_id, nome FROM petlove_pets WHERE id = ? AND status = 'ativo'",
            [$petloveId]
        );
        if (!$pet) {
            return ['ok' => false, 'erro' => 'Pet não disponível.'];
        }
        if ((int)$pet['usuario_id'] === $usuarioId) {
            return ['ok' => false, 'erro' => 'Você não pode demonstrar interesse no seu próprio pet.'];
        }

        // Verificar duplicidade
        $existente = $this->db->fetchOne(
            "SELECT id, status FROM petlove_interesses WHERE petlove_id = ? AND interessado_id = ?",
            [$petloveId, $usuarioId]
        );
        if ($existente) {
            return ['ok' => false, 'erro' => 'Você já demonstrou interesse neste pet.'];
        }

        $this->db->insert('petlove_interesses', [
            'petlove_id'         => $petloveId,
            'interessado_id'     => $usuarioId,
            'pet_interessado_id' => $petInteresseId,
            'mensagem'           => $mensagem ?: null,
            'status'             => 'pendente',
        ]);

        $this->notificarDono((int)$pet['usuario_id'], $pet['nome'], $usuarioId);

        return ['ok' => true, 'msg' => 'Interesse enviado! O tutor será notificado.'];
    }

    public function responder(array $dados): array {
        requireLogin();
        if (!validateCSRFToken($dados['csrf_token'] ?? '')) {
            return ['ok' => false, 'erro' => 'Token inválido.'];
        }

        $interesseId = (int)($dados['interesse_id'] ?? 0);
        $acao        = $dados['acao'] ?? '';
        $usuarioId   = getUserId();

        if (!in_array($acao, ['aceitar', 'recusar'], true)) {
            return ['ok' => false, 'erro' => 'Ação inválida.'];
        }

        // Confirmar que o usuário logado é dono do pet alvo
        $interesse = $this->db->fetchOne(
            "SELECT i.*, p.usuario_id AS dono_id, p.nome AS pet_nome
             FROM petlove_interesses i
             JOIN petlove_pets p ON p.id = i.petlove_id
             WHERE i.id = ?",
            [$interesseId]
        );
        if (!$interesse || (int)$interesse['dono_id'] !== $usuarioId) {
            return ['ok' => false, 'erro' => 'Interesse não encontrado.'];
        }
        if ($interesse['status'] !== 'pendente') {
            return ['ok' => false, 'erro' => 'Este interesse já foi respondido.'];
        }

        $novoStatus = $acao === 'aceitar' ? 'aceito' : 'recusado';
        $this->db->update('petlove_interesses', ['status' => $novoStatus], 'id = ?', [$interesseId]);

        if ($novoStatus === 'aceito') {
            $this->notificarInteressadoAceito((int)$interesse['interessado_id'], $interesse['pet_nome'], $usuarioId);
        }

        $msg = $novoStatus === 'aceito' ? 'Interesse aceito! Os contatos foram liberados.' : 'Interesse recusado.';
        return ['ok' => true, 'msg' => $msg, 'status' => $novoStatus];
    }

    public function listarRecebidos(int $usuarioId): array {
        return $this->db->fetchAll(
            "SELECT i.*,
                    p.nome  AS pet_nome,  p.especie AS pet_especie, p.raca AS pet_raca,
                    u.nome  AS interessado_nome, u.telefone AS interessado_tel,
                    u.email AS interessado_email,
                    (SELECT caminho FROM petlove_fotos WHERE petlove_id = p.id AND principal = 1 LIMIT 1) AS pet_foto,
                    pi.nome AS pet_interessado_nome
             FROM petlove_interesses i
             JOIN petlove_pets  p  ON p.id  = i.petlove_id
             JOIN usuarios      u  ON u.id  = i.interessado_id
             LEFT JOIN petlove_pets pi ON pi.id = i.pet_interessado_id
             WHERE p.usuario_id = ?
             ORDER BY i.criado_em DESC",
            [$usuarioId]
        ) ?: [];
    }

    public function listarEnviados(int $usuarioId): array {
        return $this->db->fetchAll(
            "SELECT i.*,
                    p.nome  AS pet_nome,  p.especie AS pet_especie, p.raca AS pet_raca,
                    u.nome  AS dono_nome,
                    (SELECT caminho FROM petlove_fotos WHERE petlove_id = p.id AND principal = 1 LIMIT 1) AS pet_foto
             FROM petlove_interesses i
             JOIN petlove_pets p ON p.id = i.petlove_id
             JOIN usuarios    u ON u.id = p.usuario_id
             WHERE i.interessado_id = ?
             ORDER BY i.criado_em DESC",
            [$usuarioId]
        ) ?: [];
    }

    // ─────────────────────────────────────────────
    // Notificações (best-effort)
    // ─────────────────────────────────────────────

    private function notificarDono(int $donoId, string $petNome, int $interessadoId): void {
        if (!function_exists('sendEmail')) return;
        $dono       = $this->db->fetchOne("SELECT nome, email FROM usuarios WHERE id = ?", [$donoId]);
        $interessado = $this->db->fetchOne("SELECT nome FROM usuarios WHERE id = ?", [$interessadoId]);
        if (!$dono || !$dono['email']) return;
        $assunto = "Novo interesse no seu pet {$petNome} — Cadê Meu Pet?";
        $corpo   = "Olá {$dono['nome']},\n\n"
                 . "{$interessado['nome']} demonstrou interesse no cruzamento com {$petNome}.\n\n"
                 . "Acesse sua conta para aceitar ou recusar: " . BASE_URL . "/minha-conta/petlove\n\n"
                 . "Equipe Cadê Meu Pet?";
        @sendEmail($dono['email'], $dono['nome'], $assunto, nl2br(htmlspecialchars($corpo)));
    }

    private function notificarInteressadoAceito(int $interessadoId, string $petNome, int $donoId): void {
        if (!function_exists('sendEmail')) return;
        $interessado = $this->db->fetchOne("SELECT nome, email FROM usuarios WHERE id = ?", [$interessadoId]);
        $dono        = $this->db->fetchOne("SELECT nome, telefone FROM usuarios WHERE id = ?", [$donoId]);
        if (!$interessado || !$interessado['email']) return;
        $assunto = "Seu interesse no pet {$petNome} foi aceito! — Cadê Meu Pet?";
        $corpo   = "Olá {$interessado['nome']},\n\n"
                 . "Boa notícia! O tutor {$dono['nome']} aceitou seu interesse no pet {$petNome}.\n\n"
                 . "Contato do tutor: {$dono['telefone']}\n\n"
                 . "Acesse sua conta para ver os detalhes: " . BASE_URL . "/minha-conta/petlove\n\n"
                 . "Equipe Cadê Meu Pet?";
        @sendEmail($interessado['email'], $interessado['nome'], $assunto, nl2br(htmlspecialchars($corpo)));
    }
}
