<?php
/**
 * Cadê Meu Pet? - Controller de Conversas (chat interno)
 * Fecha o loop de comunicação entre quem publica e quem entra em contato,
 * registrando a interação dentro da plataforma (anúncios e Pet Love).
 */
class ConversaController
{
    private $db;
    private $conversaModel;

    private const TIPOS_VALIDOS = ['anuncio', 'petlove'];
    private const LIMITE_MENSAGEM = 1000;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
        $this->conversaModel = new Conversa($this->db);
    }

    /**
     * Resolve o dono do item referenciado (anúncio ou pet do Pet Love) e
     * confirma que ainda está disponível para contato.
     */
    private function resolverDono(string $tipo, int $referenciaId): ?array
    {
        if ($tipo === 'anuncio') {
            return $this->db->fetchOne(
                "SELECT usuario_id, nome_pet AS nome FROM anuncios WHERE id = ? AND status = 'ativo'",
                [$referenciaId]
            ) ?: null;
        }
        if ($tipo === 'petlove') {
            return $this->db->fetchOne(
                "SELECT usuario_id, nome FROM petlove_pets WHERE id = ? AND status = 'ativo'",
                [$referenciaId]
            ) ?: null;
        }
        return null;
    }

    /**
     * Abre (ou reaproveita) uma conversa e, se houver mensagem inicial,
     * já envia a primeira mensagem.
     */
    public function abrir(string $tipo, int $referenciaId, string $mensagemInicial = ''): array
    {
        requireLogin();
        $usuarioId = getUserId();

        if (!in_array($tipo, self::TIPOS_VALIDOS, true) || $referenciaId <= 0) {
            return ['ok' => false, 'erro' => 'Dados inválidos.'];
        }

        $item = $this->resolverDono($tipo, $referenciaId);
        if (!$item) {
            return ['ok' => false, 'erro' => 'Este anúncio não está mais disponível.'];
        }

        $donoId = (int)$item['usuario_id'];
        if ($donoId === $usuarioId) {
            return ['ok' => false, 'erro' => 'Você não pode iniciar uma conversa com você mesmo.'];
        }

        $conversa = $this->conversaModel->obterOuCriar($tipo, $referenciaId, $donoId, $usuarioId);
        $ehNova = $this->db->fetchOne(
            'SELECT COUNT(*) AS n FROM conversa_mensagens WHERE conversa_id = ?',
            [$conversa['id']]
        );
        $semMensagens = (int)($ehNova['n'] ?? 0) === 0;

        $mensagemInicial = trim($mensagemInicial);
        if ($semMensagens && $mensagemInicial !== '') {
            $this->enviarMensagemInterna((int)$conversa['id'], $usuarioId, $mensagemInicial, $item['nome'] ?? '');
        }

        return ['ok' => true, 'conversa_id' => (int)$conversa['id']];
    }

    public function enviarMensagem(int $conversaId, int $usuarioId, string $mensagem): array
    {
        $mensagem = trim($mensagem);
        if ($mensagem === '') {
            return ['ok' => false, 'erro' => 'Digite uma mensagem.'];
        }
        if (mb_strlen($mensagem) > self::LIMITE_MENSAGEM) {
            return ['ok' => false, 'erro' => 'Mensagem muito longa.'];
        }

        $conversa = $this->conversaModel->buscarPorId($conversaId);
        if (!$conversa || !$this->conversaModel->pertenceAoUsuario($conversa, $usuarioId)) {
            return ['ok' => false, 'erro' => 'Conversa não encontrada.'];
        }
        if ($conversa['status'] === 'arquivada') {
            return ['ok' => false, 'erro' => 'Esta conversa foi arquivada.'];
        }

        $item = $this->resolverDono($conversa['tipo'], (int)$conversa['referencia_id']);
        $this->enviarMensagemInterna($conversaId, $usuarioId, $mensagem, $item['nome'] ?? '');

        return ['ok' => true];
    }

    /** Compartilha a localização atual do usuário (coordenadas do navegador). */
    public function enviarLocalizacao(int $conversaId, int $usuarioId, float $latitude, float $longitude): array
    {
        if (abs($latitude) > 90 || abs($longitude) > 180 || ($latitude === 0.0 && $longitude === 0.0)) {
            return ['ok' => false, 'erro' => 'Localização inválida.'];
        }

        $conversa = $this->conversaModel->buscarPorId($conversaId);
        if (!$conversa || !$this->conversaModel->pertenceAoUsuario($conversa, $usuarioId)) {
            return ['ok' => false, 'erro' => 'Conversa não encontrada.'];
        }
        if ($conversa['status'] === 'arquivada') {
            return ['ok' => false, 'erro' => 'Esta conversa foi arquivada.'];
        }

        $item = $this->resolverDono($conversa['tipo'], (int)$conversa['referencia_id']);
        $this->enviarMensagemInterna($conversaId, $usuarioId, '', $item['nome'] ?? '', 'localizacao', null, $latitude, $longitude);

        return ['ok' => true];
    }

    /** Anexa uma foto à conversa (ex.: comprovar que é o pet encontrado). */
    public function enviarImagem(int $conversaId, int $usuarioId, array $arquivoUpload): array
    {
        $conversa = $this->conversaModel->buscarPorId($conversaId);
        if (!$conversa || !$this->conversaModel->pertenceAoUsuario($conversa, $usuarioId)) {
            return ['ok' => false, 'erro' => 'Conversa não encontrada.'];
        }
        if ($conversa['status'] === 'arquivada') {
            return ['ok' => false, 'erro' => 'Esta conversa foi arquivada.'];
        }

        $upload = uploadImage($arquivoUpload, BASE_PATH . '/uploads/mensagens');
        if (empty($upload['success'])) {
            return ['ok' => false, 'erro' => implode(' ', $upload['errors'] ?? ['Erro ao enviar a foto.'])];
        }

        $item = $this->resolverDono($conversa['tipo'], (int)$conversa['referencia_id']);
        $this->enviarMensagemInterna($conversaId, $usuarioId, '', $item['nome'] ?? '', 'imagem', $upload['filename']);

        return ['ok' => true, 'arquivo' => $upload['filename']];
    }

    private function enviarMensagemInterna(
        int $conversaId,
        int $remetenteId,
        string $mensagem,
        string $itemNome,
        string $tipo = 'texto',
        ?string $arquivo = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): void {
        $conversa = $this->conversaModel->buscarPorId($conversaId);
        $destinatarioId = (int)$conversa['usuario_dono_id'] === $remetenteId
            ? (int)$conversa['usuario_interessado_id']
            : (int)$conversa['usuario_dono_id'];

        // Só notifica por e-mail se o destinatário ainda não tinha mensagem
        // não lida nesta conversa — evita spam numa troca rápida de mensagens.
        $jaTinhaNaoLida = $this->db->fetchOne(
            'SELECT COUNT(*) AS n FROM conversa_mensagens WHERE conversa_id = ? AND remetente_id != ? AND lida = 0',
            [$conversaId, $destinatarioId]
        );
        $deveNotificar = (int)($jaTinhaNaoLida['n'] ?? 0) === 0;

        $this->conversaModel->adicionarMensagem($conversaId, $remetenteId, $mensagem, $tipo, $arquivo, $latitude, $longitude);

        if ($deveNotificar) {
            $this->notificarNovaMensagem($destinatarioId, $remetenteId, $itemNome, $conversa['tipo'], (int)$conversa['referencia_id']);
        }
    }

    public function listarMensagens(int $conversaId, int $usuarioId): array
    {
        $conversa = $this->conversaModel->buscarPorId($conversaId);
        if (!$conversa || !$this->conversaModel->pertenceAoUsuario($conversa, $usuarioId)) {
            return ['ok' => false, 'erro' => 'Conversa não encontrada.'];
        }

        $mensagens = $this->conversaModel->listarMensagens($conversaId);
        $this->conversaModel->marcarComoLidas($conversaId, $usuarioId);

        return ['ok' => true, 'conversa' => $conversa, 'mensagens' => $mensagens];
    }

    public function poll(int $conversaId, int $usuarioId, int $depoisDe): array
    {
        $conversa = $this->conversaModel->buscarPorId($conversaId);
        if (!$conversa || !$this->conversaModel->pertenceAoUsuario($conversa, $usuarioId)) {
            return ['ok' => false, 'erro' => 'Conversa não encontrada.'];
        }

        $novas = $this->conversaModel->mensagensNovasDesde($conversaId, $depoisDe);
        if (!empty($novas)) {
            $this->conversaModel->marcarComoLidas($conversaId, $usuarioId);
        }

        return ['ok' => true, 'mensagens' => $novas];
    }

    public function listarConversas(int $usuarioId): array
    {
        return $this->conversaModel->listarParaUsuario($usuarioId);
    }

    public function contarNaoLidas(int $usuarioId): int
    {
        return $this->conversaModel->contarNaoLidas($usuarioId);
    }

    /** Usado nas páginas de anúncio/Pet Love pra linkar direto a conversa já aberta do usuário logado com este item. */
    public function buscarConversaDoUsuario(string $tipo, int $referenciaId, int $usuarioId): ?array
    {
        return $this->conversaModel->buscarDoUsuarioPorReferencia($tipo, $referenciaId, $usuarioId);
    }

    /** Usado pelo dono do anúncio/pet pra saber se já tem gente conversando sobre o item. */
    public function contarConversasDoItem(string $tipo, int $referenciaId): int
    {
        return $this->conversaModel->contarConversasPorReferencia($tipo, $referenciaId);
    }

    private function notificarNovaMensagem(int $destinatarioId, int $remetenteId, string $itemNome, string $tipo, int $referenciaId): void
    {
        if (!function_exists('sendEmail')) return;

        $destinatario = $this->db->fetchOne('SELECT nome, email FROM usuarios WHERE id = ?', [$destinatarioId]);
        $remetente    = $this->db->fetchOne('SELECT nome FROM usuarios WHERE id = ?', [$remetenteId]);
        if (!$destinatario || empty($destinatario['email'])) return;

        $urlMensagens = rtrim((string)BASE_URL, '/') . '/mensagens';
        $itemLabel = $itemNome !== '' ? $itemNome : ($tipo === 'petlove' ? 'seu pet no Pet Love' : 'seu anúncio');
        $nomeDestino = sanitize($destinatario['nome']);
        $nomeRemetente = sanitize($remetente['nome'] ?? 'Alguém');
        $itemLabelSafe = sanitize($itemLabel);

        $assunto = "Nova mensagem sobre {$itemLabel} — Cadê Meu Pet?";
        $corpo = "
<p>Olá, {$nomeDestino}!</p>
<p><strong>{$nomeRemetente}</strong> te enviou uma mensagem sobre <strong>{$itemLabelSafe}</strong>.</p>
<p><a href=\"{$urlMensagens}\">Ver conversa</a></p>
<p>Equipe Cadê Meu Pet?</p>
";
        @sendEmail($destinatario['email'], $assunto, $corpo);
    }
}
