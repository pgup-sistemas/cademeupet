<?php
/**
 * Cadê Meu Pet? - Controller de Triagem Veterinária Emergencial
 *
 * Direciona o tutor (com ou sem login) para a clínica pública municipal
 * ou uma clínica parceira privada, com base numa árvore de decisão
 * determinística (TriagemMotor). NÃO substitui avaliação veterinária —
 * o disclaimer é obrigatório em toda tela de resultado.
 *
 * Não há fluxo de dinheiro nesta fase (MVP): apenas orientação/direcionamento.
 */
class TriagemController
{
    private $solicitacaoModel;
    private $localPublicoModel;
    private $parceiroPerfilModel;
    private $conversaModel;

    public function __construct(
        $solicitacaoModel = null,
        $localPublicoModel = null,
        $parceiroPerfilModel = null,
        $conversaModel = null
    ) {
        $this->solicitacaoModel    = $solicitacaoModel ?: new TriagemSolicitacao();
        $this->localPublicoModel   = $localPublicoModel ?: new TriagemLocalPublico();
        $this->parceiroPerfilModel = $parceiroPerfilModel ?: new ParceiroPerfil();
        $this->conversaModel       = $conversaModel ?: new Conversa();
    }

    public function listaSintomas(): array
    {
        return TriagemMotor::listaSintomas();
    }

    /**
     * Processa o formulário de triagem, calcula o direcionamento e persiste
     * a solicitação. Retorna ['success' => bool, 'errors' => [...]] ou
     * ['success' => true, 'id' => int, 'direcionamento' => array].
     */
    public function iniciarTriagem(array $dados, ?int $usuarioId): array
    {
        $dados = sanitize($dados);
        $erros = $this->validar($dados);
        if (!empty($erros)) {
            return ['success' => false, 'errors' => $erros];
        }

        $sintomas = array_values(array_intersect(
            array_keys(TriagemMotor::listaSintomas()),
            (array)($dados['sintomas'] ?? [])
        ));

        $nivelUrgencia = TriagemMotor::classificarUrgencia($sintomas);

        $cidade = trim($dados['cidade'] ?? '');
        $estado = strtoupper(trim($dados['estado'] ?? ''));

        $locaisPublicos = ($cidade !== '' && $estado !== '')
            ? $this->localPublicoModel->buscarAtivosPorCidade($cidade, $estado)
            : [];

        $clinicasParceiras = ($cidade !== '')
            ? $this->parceiroPerfilModel->listPublic($cidade, 'clinica', 5, 0)
            : [];
        // Só considera parceiro verificado como opção de direcionamento (segurança/confiança).
        $clinicasParceiras = array_values(array_filter($clinicasParceiras, function ($c) {
            return !empty($c['verificado']);
        }));

        $direcionamento = TriagemMotor::decidirDirecionamento(
            $nivelUrgencia,
            isset($dados['renda_baixa_declarada']) ? (bool)$dados['renda_baixa_declarada'] : null,
            !empty($clinicasParceiras),
            !empty($locaisPublicos)
        );

        $localPublicoId = $locaisPublicos[0]['id'] ?? null;
        $parceiroId     = $clinicasParceiras[0]['id'] ?? null;

        $id = $this->solicitacaoModel->criar([
            'usuario_id'                 => $usuarioId,
            'nome_contato'               => $dados['nome_contato'] ?? null,
            'telefone_contato'           => $dados['telefone_contato'] ?? null,
            'especie'                    => $dados['especie'],
            'sintomas'                   => $sintomas,
            'nivel_urgencia'              => $nivelUrgencia,
            'renda_baixa_declarada'       => $dados['renda_baixa_declarada'] ?? null,
            'cidade'                     => $cidade ?: null,
            'estado'                     => $estado ?: null,
            'direcionamento_sugerido'     => $direcionamento,
            'triagem_locais_publicos_id' => $localPublicoId,
            'parceiro_perfil_id'         => $parceiroId,
            'disclaimer_aceito'          => $dados['disclaimer_aceito'] ?? false,
        ]);

        return [
            'success' => true,
            'id' => $id,
            'direcionamento' => [
                'tipo'               => $direcionamento,
                'nivel_urgencia'     => $nivelUrgencia,
                'locais_publicos'    => $locaisPublicos,
                'clinicas_parceiras' => $clinicasParceiras,
            ],
        ];
    }

    public function buscarResultado(int $id): ?array
    {
        return $this->solicitacaoModel->buscarPorId($id);
    }

    public function historicoDoUsuario(int $usuarioId): array
    {
        return $this->solicitacaoModel->buscarPorUsuario($usuarioId);
    }

    /**
     * Abre conversa entre o tutor logado e o dono do perfil de parceiro
     * indicado na solicitação. Exige login — tutor anônimo recebe o
     * contato direto do parceiro (telefone/whatsapp) em vez de chat interno.
     */
    public function abrirConversaComParceiro(int $solicitacaoId, int $usuarioId): array
    {
        $solicitacao = $this->solicitacaoModel->buscarPorId($solicitacaoId);
        if (!$solicitacao || empty($solicitacao['parceiro_perfil_id'])) {
            return ['success' => false, 'error' => 'Solicitação sem clínica parceira associada.'];
        }

        $parceiro = $this->db_buscarParceiroPorId((int)$solicitacao['parceiro_perfil_id']);
        if (!$parceiro) {
            return ['success' => false, 'error' => 'Clínica parceira não encontrada.'];
        }

        $conversa = $this->conversaModel->obterOuCriar(
            'triagem',
            $solicitacaoId,
            (int)$parceiro['usuario_id'],
            $usuarioId
        );

        $this->solicitacaoModel->vincularConversa($solicitacaoId, (int)$conversa['id']);

        return ['success' => true, 'conversa_id' => (int)$conversa['id']];
    }

    public function painelParceiro(int $parceiroPerfilId): array
    {
        return $this->solicitacaoModel->buscarPorParceiro($parceiroPerfilId);
    }

    private function db_buscarParceiroPorId(int $id): ?array
    {
        $row = getDB()->fetchOne('SELECT * FROM parceiro_perfis WHERE id = ?', [$id]);
        return $row ?: null;
    }

    private function validar(array $dados): array
    {
        $erros = [];

        if (empty($dados['disclaimer_aceito'])) {
            $erros[] = 'É necessário confirmar que entendeu o aviso antes de continuar.';
        }

        if (empty($dados['especie'])) {
            $erros[] = 'Informe a espécie do animal.';
        }

        if (empty($dados['sintomas']) || !is_array($dados['sintomas'])) {
            $erros[] = 'Selecione ao menos um sintoma ou motivo da consulta.';
        }

        if (empty($dados['nome_contato']) && empty($dados['telefone_contato'])) {
            $erros[] = 'Informe ao menos um nome ou telefone de contato.';
        }

        return $erros;
    }
}
