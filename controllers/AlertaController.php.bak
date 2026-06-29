<?php
/**
 * PetFinder - Controller de Alertas de Busca
 */
class AlertaController
{
    private $alertaModel;
    private $anuncioModel;
    private $usuarioModel;

    public function __construct($alertaModel = null, $anuncioModel = null, $usuarioModel = null)
    {
        $this->alertaModel = $alertaModel ?: new Alerta();
        $this->anuncioModel = $anuncioModel ?: new Anuncio();
        $this->usuarioModel = $usuarioModel ?: new Usuario();
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        return $this->alertaModel->listByUser($usuarioId);
    }

    public function criar(int $usuarioId, array $dados, bool $disparoImediato = false): array
    {
        $dados = sanitize($dados);
        $erros = $this->validar($usuarioId, $dados);

        if (!empty($erros)) {
            return ['success' => false, 'errors' => $erros];
        }

        $alertaId = $this->alertaModel->create([
            'usuario_id' => $usuarioId,
            'tipo' => $dados['tipo'] ?? 'ambos',
            'especie' => $dados['especie'] ?? null,
            'cidade' => $dados['cidade'],
            'estado' => strtoupper($dados['estado']),
            'raio_km' => (int)($dados['raio_km'] ?? 10),
            'ativo' => 1,
            'ultimo_envio' => null
        ]);

        if ($disparoImediato) {
            $alerta = $this->alertaModel->findById($alertaId, $usuarioId);
            $this->dispararResumo($alerta);
        }

        return ['success' => true, 'id' => $alertaId];
    }

    public function atualizar(int $usuarioId, int $alertaId, array $dados): array
    {
        $dados = sanitize($dados);
        $payload = [];

        if (isset($dados['tipo']) && in_array($dados['tipo'], ['perdido', 'encontrado', 'ambos'], true)) {
            $payload['tipo'] = $dados['tipo'];
        }

        if (isset($dados['especie'])) {
            $payload['especie'] = $dados['especie'] ?: null;
        }

        if (!empty($dados['cidade'])) {
            $payload['cidade'] = $dados['cidade'];
        }

        if (!empty($dados['estado']) && strlen($dados['estado']) === 2) {
            $payload['estado'] = strtoupper($dados['estado']);
        }

        if (isset($dados['raio_km'])) {
            $raio = (int)$dados['raio_km'];
            if (!in_array($raio, [5, 10, 20, 50], true)) {
                return ['success' => false, 'errors' => ['Selecione um raio válido.']];
            }
            $payload['raio_km'] = $raio;
        }

        if (isset($dados['ativo'])) {
            $payload['ativo'] = $dados['ativo'] ? 1 : 0;
        }

        if (empty($payload)) {
            return ['success' => false, 'errors' => ['Nenhuma alteração informada.']];
        }

        $this->alertaModel->update($alertaId, $usuarioId, $payload);

        return ['success' => true];
    }

    public function remover(int $usuarioId, int $alertaId): bool
    {
        return $this->alertaModel->delete($alertaId, $usuarioId);
    }

    public function alternarStatus(int $usuarioId, int $alertaId): bool
    {
        $alerta = $this->alertaModel->findById($alertaId, $usuarioId);
        if (!$alerta) {
            return false;
        }

        $novoStatus = $alerta['ativo'] ? 0 : 1;
        return $this->alertaModel->update($alertaId, $usuarioId, ['ativo' => $novoStatus]);
    }

    public function dispararResumo(array $alerta): bool
    {
        if (empty($alerta)) {
            return false;
        }

        $usuario = $this->usuarioModel->findById((int)$alerta['usuario_id']);
        if (!$usuario || empty($usuario['email']) || empty($usuario['notificacoes_email'])) {
            return false;
        }

        $limit = defined('ALERT_EMAIL_MAX_RESULTS') ? ALERT_EMAIL_MAX_RESULTS : 5;
        $anuncios = $this->anuncioModel->findByAlert($alerta, $limit);

        if (empty($anuncios)) {
            return false;
        }

        $subject = 'Novos anúncios combinando com seu alerta - PetFinder';

        ob_start();
        $baseUrl = BASE_URL;
        $alertData = $alerta;
        $anunciosResumo = $anuncios;
        include BASE_PATH . '/views/emails/alerta_resumo.php';
        $html = ob_get_clean();

        if (sendEmail($usuario['email'], $subject, $html)) {
            $this->alertaModel->updateLastSent((int)$alerta['id']);
            return true;
        }

        return false;
    }

    private function validar(int $usuarioId, array $dados): array
    {
        $erros = [];

        $totalAtivos = $this->alertaModel->countByUser($usuarioId);
        if ($totalAtivos >= MAX_ALERTS_PER_USER) {
            $erros[] = 'Você atingiu o limite de ' . MAX_ALERTS_PER_USER . ' alertas ativos.';
        }

        $tipo = $dados['tipo'] ?? 'ambos';
        if (!in_array($tipo, ['perdido', 'encontrado', 'ambos'], true)) {
            $erros[] = 'Tipo de alerta inválido.';
        }

        if (empty($dados['cidade'])) {
            $erros[] = 'Informe a cidade para o alerta.';
        }

        if (empty($dados['estado']) || strlen($dados['estado']) !== 2) {
            $erros[] = 'Informe a sigla do estado.';
        }

        if (!empty($dados['raio_km'])) {
            $raio = (int)$dados['raio_km'];
            if (!in_array($raio, [5, 10, 20, 50], true)) {
                $erros[] = 'Selecione um raio válido.';
            }
        }

        return $erros;
    }
}
