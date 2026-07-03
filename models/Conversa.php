<?php
/**
 * Cadê Meu Pet? - Modelo de Conversas (chat interno)
 * Usado tanto por anúncios (perdido/encontrado/doação) quanto pelo Pet Love.
 */
class Conversa
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    /**
     * Retorna a conversa existente entre o interessado e o dono do
     * anúncio/pet, ou cria uma nova. Uma conversa por par (tipo, referência,
     * interessado) — a mesma pessoa não abre duas conversas para o mesmo item.
     */
    public function obterOuCriar(string $tipo, int $referenciaId, int $donoId, int $interessadoId): array
    {
        $existente = $this->db->fetchOne(
            'SELECT * FROM conversas WHERE tipo = ? AND referencia_id = ? AND usuario_interessado_id = ?',
            [$tipo, $referenciaId, $interessadoId]
        );
        if ($existente) {
            return $existente;
        }

        $id = $this->db->insert('conversas', [
            'tipo'                   => $tipo,
            'referencia_id'          => $referenciaId,
            'usuario_dono_id'        => $donoId,
            'usuario_interessado_id' => $interessadoId,
            'status'                 => 'aberta',
        ]);

        return $this->db->fetchOne('SELECT * FROM conversas WHERE id = ?', [$id]);
    }

    public function buscarPorId(int $id): ?array
    {
        $row = $this->db->fetchOne('SELECT * FROM conversas WHERE id = ?', [$id]);
        return $row ?: null;
    }

    /** Conversa do usuário (dono ou interessado) sobre um item específico — usada pra linkar "continuar conversa" na página do anúncio/pet. */
    public function buscarDoUsuarioPorReferencia(string $tipo, int $referenciaId, int $usuarioId): ?array
    {
        $row = $this->db->fetchOne(
            'SELECT * FROM conversas WHERE tipo = ? AND referencia_id = ? AND (usuario_dono_id = ? OR usuario_interessado_id = ?)
             ORDER BY COALESCE(ultima_mensagem_em, criado_em) DESC LIMIT 1',
            [$tipo, $referenciaId, $usuarioId, $usuarioId]
        );
        return $row ?: null;
    }

    /** Todas as conversas (de qualquer interessado) sobre um item — usada pelo dono para ver quem já entrou em contato. */
    public function contarConversasPorReferencia(string $tipo, int $referenciaId): int
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS n FROM conversas WHERE tipo = ? AND referencia_id = ?',
            [$tipo, $referenciaId]
        );
        return (int)($row['n'] ?? 0);
    }

    public function pertenceAoUsuario(array $conversa, int $usuarioId): bool
    {
        return (int)$conversa['usuario_dono_id'] === $usuarioId
            || (int)$conversa['usuario_interessado_id'] === $usuarioId;
    }

    public function adicionarMensagem(
        int $conversaId,
        int $remetenteId,
        string $mensagem,
        string $tipo = 'texto',
        ?string $arquivo = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): int {
        $id = $this->db->insert('conversa_mensagens', [
            'conversa_id'  => $conversaId,
            'remetente_id' => $remetenteId,
            'mensagem'     => $mensagem,
            'tipo'         => $tipo,
            'arquivo'      => $arquivo,
            'latitude'     => $latitude,
            'longitude'    => $longitude,
        ]);
        $this->db->update('conversas', ['ultima_mensagem_em' => date('Y-m-d H:i:s')], 'id = ?', [$conversaId]);
        return (int)$id;
    }

    public function listarMensagens(int $conversaId): array
    {
        return $this->db->fetchAll(
            'SELECT m.*, u.nome AS remetente_nome
             FROM conversa_mensagens m
             JOIN usuarios u ON u.id = m.remetente_id
             WHERE m.conversa_id = ?
             ORDER BY m.id ASC',
            [$conversaId]
        ) ?: [];
    }

    /** Mensagens novas para polling (id > $depoisDe). */
    public function mensagensNovasDesde(int $conversaId, int $depoisDe): array
    {
        return $this->db->fetchAll(
            'SELECT m.*, u.nome AS remetente_nome
             FROM conversa_mensagens m
             JOIN usuarios u ON u.id = m.remetente_id
             WHERE m.conversa_id = ? AND m.id > ?
             ORDER BY m.id ASC',
            [$conversaId, $depoisDe]
        ) ?: [];
    }

    public function marcarComoLidas(int $conversaId, int $usuarioId): void
    {
        $this->db->update(
            'conversa_mensagens',
            ['lida' => 1],
            'conversa_id = ? AND remetente_id != ? AND lida = 0',
            [$conversaId, $usuarioId]
        );
    }

    public function contarNaoLidas(int $usuarioId): int
    {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS n
             FROM conversa_mensagens m
             JOIN conversas c ON c.id = m.conversa_id
             WHERE m.lida = 0 AND m.remetente_id != ?
               AND (c.usuario_dono_id = ? OR c.usuario_interessado_id = ?)",
            [$usuarioId, $usuarioId, $usuarioId]
        );
        return (int)($row['n'] ?? 0);
    }

    /**
     * Lista as conversas do usuário (como dono ou interessado), com dados do
     * item referenciado (anúncio ou pet do Pet Love), do outro participante,
     * última mensagem e contagem de não lidas.
     */
    public function listarParaUsuario(int $usuarioId): array
    {
        return $this->db->fetchAll(
            "SELECT c.*,
                    CASE c.tipo WHEN 'anuncio' THEN a.nome_pet ELSE p.nome END AS item_nome,
                    CASE c.tipo WHEN 'anuncio' THEN a.especie ELSE p.especie END AS item_especie,
                    CASE c.tipo WHEN 'anuncio' THEN a.status ELSE p.status END AS item_status,
                    CASE c.tipo
                        WHEN 'anuncio' THEN (SELECT nome_arquivo FROM fotos_anuncios WHERE anuncio_id = a.id ORDER BY ordem LIMIT 1)
                        ELSE (SELECT caminho FROM petlove_fotos WHERE petlove_id = p.id AND principal = 1 LIMIT 1)
                    END AS item_foto,
                    u_dono.nome AS dono_nome,
                    u_int.nome  AS interessado_nome,
                    (SELECT CASE tipo
                                WHEN 'imagem' THEN '📷 Foto'
                                WHEN 'localizacao' THEN '📍 Localização'
                                ELSE mensagem
                            END
                     FROM conversa_mensagens WHERE conversa_id = c.id ORDER BY id DESC LIMIT 1) AS ultima_mensagem,
                    (SELECT COUNT(*) FROM conversa_mensagens
                       WHERE conversa_id = c.id AND remetente_id != ? AND lida = 0) AS nao_lidas
             FROM conversas c
             LEFT JOIN anuncios     a ON c.tipo = 'anuncio' AND a.id = c.referencia_id
             LEFT JOIN petlove_pets p ON c.tipo = 'petlove' AND p.id = c.referencia_id
             JOIN usuarios u_dono ON u_dono.id = c.usuario_dono_id
             JOIN usuarios u_int  ON u_int.id  = c.usuario_interessado_id
             WHERE c.usuario_dono_id = ? OR c.usuario_interessado_id = ?
             ORDER BY COALESCE(c.ultima_mensagem_em, c.criado_em) DESC",
            [$usuarioId, $usuarioId, $usuarioId]
        ) ?: [];
    }

    public function encerrar(int $conversaId): void
    {
        $this->db->update('conversas', ['status' => 'resolvida'], 'id = ?', [$conversaId]);
    }

    /** Encerra todas as conversas abertas de um item (chamado ao marcar anúncio/pet como resolvido). */
    public function encerrarPorReferencia(string $tipo, int $referenciaId): void
    {
        $this->db->update(
            'conversas',
            ['status' => 'resolvida'],
            "tipo = ? AND referencia_id = ? AND status = 'aberta'",
            [$tipo, $referenciaId]
        );
    }
}
