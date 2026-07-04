<?php
/**
 * Cadê Meu Pet? - Controller do motor de match automático (perdido ↔ achado).
 *
 * Nunca expõe telefone/e-mail do outro tutor antes de uma confirmação
 * humana explícita — o e-mail de notificação só menciona atributos
 * genéricos do possível match.
 */
class MatchController
{
    private $db;
    private $anuncioModel;
    private $matchModel;
    private $matchEngine;
    private $conversaModel;
    private $usuarioModel;

    public function __construct(
        $db = null,
        $anuncioModel = null,
        $matchModel = null,
        $matchEngine = null,
        $conversaModel = null,
        $usuarioModel = null
    ) {
        $this->db            = $db ?: getDB();
        $this->anuncioModel  = $anuncioModel ?: new Anuncio();
        $this->matchModel    = $matchModel ?: new AnuncioMatch($this->db);
        $this->matchEngine   = $matchEngine ?: new MatchEngine($this->db);
        $this->conversaModel = $conversaModel ?: new Conversa($this->db);
        $this->usuarioModel  = $usuarioModel ?: new Usuario();
    }

    /**
     * Roda o matching incremental para um anúncio (perdido ou achado),
     * criando registros em anuncio_matches para os candidatos com score
     * acima do limiar mínimo. Retorna quantos matches novos foram criados.
     */
    public function processarAnuncio(int $anuncioId): int
    {
        $anuncio = $this->anuncioModel->findByIdAnyStatus($anuncioId);
        if (!$anuncio || !in_array($anuncio['tipo'], ['perdido', 'encontrado'], true) || $anuncio['status'] !== 'ativo') {
            return 0;
        }

        $scoreMinimo = defined('MATCHING_SCORE_MINIMO') ? (float)MATCHING_SCORE_MINIMO : 40.0;
        $candidatos = $this->matchEngine->buscarCandidatos($anuncio);
        $criados = 0;

        foreach ($candidatos as $candidato) {
            $perdido = $anuncio['tipo'] === 'perdido' ? $anuncio : $candidato;
            $achado  = $anuncio['tipo'] === 'perdido' ? $candidato : $anuncio;

            if ($this->matchModel->existeParaPar((int)$perdido['id'], (int)$achado['id'])) {
                continue;
            }

            $resultado = $this->matchEngine->calcularScore($perdido, $achado);
            if ($resultado === null || $resultado['score_total'] < $scoreMinimo) {
                continue;
            }

            $this->matchModel->criar([
                'anuncio_perdido_id' => $perdido['id'],
                'anuncio_achado_id'  => $achado['id'],
                'score_total'        => $resultado['score_total'],
                'score_visual'       => $resultado['score_visual'],
                'score_geo'          => $resultado['score_geo'],
                'score_atributos'    => $resultado['score_atributos'],
                'score_tempo'        => $resultado['score_tempo'],
                'distancia_km'       => $resultado['distancia_km'],
                'dias_diferenca'     => $resultado['dias_diferenca'],
            ]);
            $criados++;
        }

        $this->db->update('anuncios', ['matching_processado_em' => date('Y-m-d H:i:s')], 'id = ?', [$anuncioId]);

        return $criados;
    }

    public function listarParaUsuario(int $usuarioId): array
    {
        return $this->matchModel->buscarParaUsuario($usuarioId);
    }

    public function confirmar(int $matchId, int $usuarioId): array
    {
        $match = $this->matchModel->buscarComDonosPorId($matchId);
        if (!$match || !$this->matchModel->pertenceAoUsuario($match, $usuarioId)) {
            return ['success' => false, 'error' => 'Match não encontrado.'];
        }
        if ($match['status'] !== 'pendente' && $match['status'] !== 'notificado') {
            return ['success' => false, 'error' => 'Este match já foi resolvido.'];
        }

        $donoId = (int)$match['perdido_usuario_id'];
        $interessadoId = (int)$match['achado_usuario_id'];
        if ($donoId === $interessadoId) {
            return ['success' => false, 'error' => 'Não é possível confirmar um match com você mesmo.'];
        }

        $conversa = $this->conversaModel->obterOuCriar('match', $matchId, $donoId, $interessadoId);
        $this->matchModel->confirmar($matchId, $usuarioId, (int)$conversa['id']);

        return ['success' => true, 'conversa_id' => (int)$conversa['id']];
    }

    public function rejeitar(int $matchId, int $usuarioId): array
    {
        $match = $this->matchModel->buscarComDonosPorId($matchId);
        if (!$match || !$this->matchModel->pertenceAoUsuario($match, $usuarioId)) {
            return ['success' => false, 'error' => 'Match não encontrado.'];
        }

        $this->matchModel->rejeitar($matchId);
        return ['success' => true];
    }

    /**
     * Notifica por e-mail os dois tutores de um match ainda não notificado,
     * sem expor telefone/endereço — só um link para revisar o match logado.
     */
    public function notificar(array $match): bool
    {
        $perdido = $this->anuncioModel->findByIdAnyStatus((int)$match['anuncio_perdido_id']);
        $achado  = $this->anuncioModel->findByIdAnyStatus((int)$match['anuncio_achado_id']);
        if (!$perdido || !$achado) {
            return false;
        }

        $enviouAlgum = false;
        $urlMatches = rtrim((string)BASE_URL, '/') . '/matches';

        foreach ([$perdido, $achado] as $anuncio) {
            $usuario = $this->usuarioModel->findById((int)$anuncio['usuario_id']);
            if (!$usuario || empty($usuario['email']) || empty($usuario['notificacoes_email'])) {
                continue;
            }
            $assunto = 'Encontramos um possível match para o seu anúncio — Cadê Meu Pet?';
            $corpo = "
<p>Olá, " . sanitize($usuario['nome']) . "!</p>
<p>Nosso sistema encontrou um anúncio que pode ter relação com o seu ("
    . sanitize((string)($anuncio['nome_pet'] ?: ucfirst($anuncio['especie']))) . "), com "
    . (int)$match['score_total'] . "% de compatibilidade (raça, cor, localização e período).</p>
<p><a href=\"{$urlMatches}\">Ver sugestão de match</a></p>
<p>Nenhum dado de contato é compartilhado até que você confirme o match.</p>
<p>Equipe Cadê Meu Pet?</p>
";
            if (sendEmail($usuario['email'], $assunto, $corpo)) {
                $enviouAlgum = true;
            }
        }

        if ($enviouAlgum) {
            $this->matchModel->marcarNotificado((int)$match['id']);
        }

        return $enviouAlgum;
    }
}
