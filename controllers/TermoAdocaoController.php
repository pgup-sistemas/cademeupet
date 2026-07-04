<?php
/**
 * Cadê Meu Pet? - Controller do Termo de Responsabilidade de Adoção/Doação.
 * Ver docs/modulo-atendimento-veterinario-laudo.md (Fase 1)
 *
 * Reaproveita o núcleo genérico de documento assinável (Documento +
 * DocumentoAssinatura) criado na Fase 0. Papéis obrigatórios do termo:
 * sempre 'adotante_responsavel'; 'testemunha_parceiro' só é obrigatório
 * se o doador escolheu um parceiro testemunha no momento de iniciar.
 */
class TermoAdocaoController
{
    private $db;
    private $termoModel;
    private $documentoModel;
    private $assinaturaModel;
    private $anuncioModel;
    private $usuarioModel;
    private $parceiroPerfilModel;
    private DocumentoPdfService $pdfService;

    public function __construct(
        $db = null,
        $termoModel = null,
        $documentoModel = null,
        $assinaturaModel = null,
        $anuncioModel = null,
        $usuarioModel = null,
        $parceiroPerfilModel = null,
        ?DocumentoPdfService $pdfService = null
    ) {
        $this->db                  = $db ?: getDB();
        $this->termoModel          = $termoModel ?: new TermoAdocao($this->db);
        $this->documentoModel      = $documentoModel ?: new Documento($this->db);
        $this->assinaturaModel     = $assinaturaModel ?: new DocumentoAssinatura($this->db, $this->documentoModel);
        $this->anuncioModel        = $anuncioModel ?: new Anuncio();
        $this->usuarioModel        = $usuarioModel ?: new Usuario();
        $this->parceiroPerfilModel = $parceiroPerfilModel ?: new ParceiroPerfil();
        $this->pdfService          = $pdfService ?: new DocumentoPdfService();
    }

    public function buscarPorId(int $termoId): ?array
    {
        return $this->termoModel->buscarPorId($termoId);
    }

    public function comoDoador(int $usuarioId): array
    {
        return $this->termoModel->buscarComoDoador($usuarioId);
    }

    public function comoAdotante(int $usuarioId): array
    {
        return $this->termoModel->buscarComoAdotante($usuarioId);
    }

    /**
     * Doador inicia o termo a partir de um anúncio de doação já resolvido.
     * $dadosAdotante: ['adotante_usuario_id' => ?int, 'nome' => ?string,
     * 'telefone' => ?string, 'parceiro_testemunha_id' => ?int]
     */
    public function iniciar(int $anuncioId, int $doadorUsuarioId, array $dadosAdotante): array
    {
        $anuncio = $this->anuncioModel->findByIdAnyStatus($anuncioId);
        if (!$anuncio || (int)$anuncio['usuario_id'] !== $doadorUsuarioId) {
            return ['success' => false, 'error' => 'Anúncio não encontrado.'];
        }
        if ($anuncio['tipo'] !== 'doacao' || $anuncio['status'] !== 'resolvido') {
            return ['success' => false, 'error' => 'Só é possível iniciar o termo para um anúncio de doação já marcado como resolvido.'];
        }
        if ($this->termoModel->buscarPorAnuncio($anuncioId)) {
            return ['success' => false, 'error' => 'Já existe um termo iniciado para este anúncio.'];
        }

        $dadosAdotante = sanitize($dadosAdotante);
        $adotanteUsuarioId = !empty($dadosAdotante['adotante_usuario_id']) ? (int)$dadosAdotante['adotante_usuario_id'] : null;
        $adotanteNome = $dadosAdotante['nome'] ?? null;
        $adotanteTelefone = $dadosAdotante['telefone'] ?? null;

        if (!$adotanteUsuarioId && empty($adotanteNome) && empty($adotanteTelefone)) {
            return ['success' => false, 'error' => 'Informe quem adotou (conta cadastrada, ou nome/telefone).'];
        }

        // Se não veio um usuario_id explícito mas o telefone bate com uma conta
        // existente, já vincula automaticamente — evita depender de busca manual.
        if (!$adotanteUsuarioId && !empty($adotanteTelefone)) {
            $contaExistente = $this->db->fetchOne('SELECT id FROM usuarios WHERE telefone = ?', [$adotanteTelefone]);
            if ($contaExistente) {
                $adotanteUsuarioId = (int)$contaExistente['id'];
            }
        }

        $parceiroTestemunhaId = null;
        if (!empty($dadosAdotante['parceiro_testemunha_id'])) {
            $parceiro = getDB()->fetchOne(
                'SELECT id FROM parceiro_perfis WHERE id = ? AND verificado = 1 AND publicado = 1',
                [(int)$dadosAdotante['parceiro_testemunha_id']]
            );
            if ($parceiro) {
                $parceiroTestemunhaId = (int)$parceiro['id'];
            }
        }

        if ($adotanteUsuarioId) {
            $adotanteConta = $this->usuarioModel->findById($adotanteUsuarioId);
            if (!$adotanteConta) {
                return ['success' => false, 'error' => 'Usuário adotante não encontrado.'];
            }
            $adotanteNome = $adotanteNome ?: $adotanteConta['nome'];
        }

        $doador = $this->usuarioModel->findById($doadorUsuarioId);

        $conteudoHtml = $this->renderizarConteudo([
            'pet_nome'        => $anuncio['nome_pet'] ?: ('Pet ' . ucfirst($anuncio['especie'])),
            'pet_especie'     => $anuncio['especie'],
            'pet_raca'        => $anuncio['raca'] ?? null,
            'doador_nome'     => $doador['nome'] ?? '',
            'adotante_nome'   => $adotanteNome ?: '(a definir na assinatura)',
            'adotante_telefone' => $adotanteTelefone,
            'data_geracao'    => date('d/m/Y'),
        ]);

        $documentoId = $this->documentoModel->criar([
            'tipo'                  => 'termo_adocao',
            'referencia_tipo'       => 'anuncio',
            'referencia_id'         => $anuncioId,
            'conteudo_html'         => $conteudoHtml,
            'criado_por_usuario_id' => $doadorUsuarioId,
        ]);

        $termoId = $this->termoModel->criar([
            'anuncio_id'                  => $anuncioId,
            'documento_id'                => $documentoId,
            'doador_usuario_id'           => $doadorUsuarioId,
            'adotante_usuario_id'         => $adotanteUsuarioId,
            'adotante_nome_informado'     => $adotanteNome,
            'adotante_telefone_informado' => $adotanteTelefone,
            'parceiro_testemunha_id'      => $parceiroTestemunhaId,
        ]);

        auditLog('iniciar_termo_adocao', 'termos_adocao', $termoId);

        if ($adotanteUsuarioId && !empty($adotanteConta['email']) && !empty($adotanteConta['notificacoes_email'])) {
            $url = rtrim((string)BASE_URL, '/') . '/termo-adocao?id=' . $termoId;
            $assunto = 'Você recebeu um Termo de Responsabilidade de Adoção — Cadê Meu Pet?';
            $corpo = '<p>Olá, ' . sanitize($adotanteConta['nome']) . '!</p>'
                . '<p>' . sanitize($doador['nome'] ?? '') . ' registrou que você adotou ' . sanitize($anuncio['nome_pet'] ?: 'um pet') . '. '
                . 'Para formalizar sua responsabilidade pelo cuidado do animal, acesse o termo e assine.</p>'
                . '<p><a href="' . $url . '">Ver e assinar o termo</a></p>';
            @sendEmail($adotanteConta['email'], $assunto, $corpo);
        }

        return ['success' => true, 'id' => $termoId];
    }

    /**
     * O adotante assina o termo. Se o termo ainda não tem adotante_usuario_id
     * vinculado (convite por nome/telefone), tenta reivindicar comparando o
     * telefone da conta logada com o telefone informado pelo doador.
     */
    public function assinarComoAdotante(int $termoId, int $usuarioId, ?string $ip, ?string $userAgent): array
    {
        $termo = $this->termoModel->buscarPorId($termoId);
        if (!$termo) {
            return ['success' => false, 'error' => 'Termo não encontrado.'];
        }
        if ($termo['status'] !== 'aguardando_adotante') {
            return ['success' => false, 'error' => 'Este termo já foi ' . ($termo['status'] === 'assinado' ? 'assinado' : $termo['status']) . '.'];
        }

        if ($termo['adotante_usuario_id'] === null) {
            $usuario = $this->usuarioModel->findById($usuarioId);
            if (!$usuario || empty($termo['adotante_telefone_informado'])
                || $usuario['telefone'] !== $termo['adotante_telefone_informado']) {
                return ['success' => false, 'error' => 'Este termo não está associado à sua conta. Confira o telefone cadastrado.'];
            }
            $this->termoModel->vincularAdotanteUsuario($termoId, $usuarioId);
        } elseif ((int)$termo['adotante_usuario_id'] !== $usuarioId) {
            return ['success' => false, 'error' => 'Este termo pertence a outra pessoa.'];
        }

        $resultado = $this->assinaturaModel->assinar(
            (int)$termo['documento_id'],
            $usuarioId,
            'adotante_responsavel',
            null,
            $ip,
            $userAgent
        );
        if (empty($resultado['success'])) {
            return $resultado;
        }

        auditLog('assinar_termo_adocao', 'termos_adocao', $termoId);
        $this->finalizarSeCompleto($termoId);

        return ['success' => true];
    }

    /** Parceiro (petshop/clínica) confirma que testemunhou a entrega do pet. */
    public function testemunharComoParceiro(int $termoId, int $usuarioIdParceiro, ?string $ip, ?string $userAgent): array
    {
        $termo = $this->termoModel->buscarPorId($termoId);
        if (!$termo || empty($termo['parceiro_testemunha_id'])) {
            return ['success' => false, 'error' => 'Este termo não solicita testemunha.'];
        }

        $perfil = $this->parceiroPerfilModel->findByUserId($usuarioIdParceiro);
        if (!$perfil || (int)$perfil['id'] !== (int)$termo['parceiro_testemunha_id']) {
            return ['success' => false, 'error' => 'Você não é a testemunha designada para este termo.'];
        }

        $resultado = $this->assinaturaModel->assinar(
            (int)$termo['documento_id'],
            $usuarioIdParceiro,
            'testemunha_parceiro',
            sanitize($perfil['nome_fantasia'] ?? ''),
            $ip,
            $userAgent
        );
        if (empty($resultado['success'])) {
            return $resultado;
        }

        auditLog('testemunhar_termo_adocao', 'termos_adocao', $termoId);
        $this->finalizarSeCompleto($termoId);

        return ['success' => true];
    }

    public function recusar(int $termoId, int $usuarioId): array
    {
        $termo = $this->termoModel->buscarPorId($termoId);
        if (!$termo || $termo['status'] !== 'aguardando_adotante') {
            return ['success' => false, 'error' => 'Termo não encontrado ou já resolvido.'];
        }
        if ((int)$termo['adotante_usuario_id'] !== $usuarioId) {
            return ['success' => false, 'error' => 'Apenas o adotante designado pode recusar.'];
        }

        $this->termoModel->atualizarStatus($termoId, 'recusado');
        $this->documentoModel->revogar((int)$termo['documento_id'], 'Adotante recusou assinar o termo.');
        auditLog('recusar_termo_adocao', 'termos_adocao', $termoId);

        return ['success' => true];
    }

    private function finalizarSeCompleto(int $termoId): void
    {
        $termo = $this->termoModel->buscarPorId($termoId);
        $papeisObrigatorios = ['adotante_responsavel'];
        if (!empty($termo['parceiro_testemunha_id'])) {
            $papeisObrigatorios[] = 'testemunha_parceiro';
        }

        if (!$this->assinaturaModel->todosPapeisObrigatoriosAssinados((int)$termo['documento_id'], $papeisObrigatorios)) {
            return;
        }

        $documento = $this->documentoModel->buscarPorId((int)$termo['documento_id']);
        $assinaturas = $this->assinaturaModel->listarPorDocumento((int)$termo['documento_id']);
        $pdfPath = $this->pdfService->gerarESalvar($documento, $assinaturas, 'termo_adocao');

        $this->documentoModel->marcarAssinado((int)$termo['documento_id'], $pdfPath);
        $this->termoModel->atualizarStatus($termoId, 'assinado');
    }

    private function renderizarConteudo(array $dados): string
    {
        return '
<h2 style="text-align:center;">Termo de Responsabilidade de Adoção/Doação de Animal</h2>
<p><strong>Pet:</strong> ' . sanitize($dados['pet_nome']) . ' (' . sanitize(ucfirst($dados['pet_especie'])) . ($dados['pet_raca'] ? ', ' . sanitize($dados['pet_raca']) : '') . ')</p>
<p><strong>Doador(a):</strong> ' . sanitize($dados['doador_nome']) . '</p>
<p><strong>Adotante:</strong> ' . sanitize($dados['adotante_nome']) . ($dados['adotante_telefone'] ? ' — ' . sanitize($dados['adotante_telefone']) : '') . '</p>
<p>Pelo presente termo, o(a) adotante acima identificado(a) declara que:</p>
<ol>
  <li>Recebeu o animal descrito acima de forma voluntária e consciente, comprometendo-se a garantir alimentação, água, abrigo e cuidados veterinários adequados;</li>
  <li>Está ciente de que o abandono de animais é conduta tipificada como crime pela Lei Federal nº 9.605/1998 (Lei de Crimes Ambientais);</li>
  <li>Assume total responsabilidade pelo bem-estar do animal a partir desta data, isentando o(a) doador(a) de responsabilidades futuras sobre os cuidados diários do pet;</li>
  <li>Está ciente de que este termo é um registro eletrônico auditável (hash + data/hora + IP), sem validade de assinatura digital certificada (ICP-Brasil).</li>
</ol>
<p>Documento gerado em ' . sanitize($dados['data_geracao']) . ' pela plataforma Cadê Meu Pet?.</p>
';
    }
}
