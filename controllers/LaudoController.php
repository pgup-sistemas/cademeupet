<?php
/**
 * Cadê Meu Pet? - Controller de Laudo/Atestado/Receituário veterinário.
 * Ver docs/modulo-atendimento-veterinario-laudo.md (Fase 3)
 *
 * Reaproveita o núcleo genérico de documento assinável (Fase 0). Único
 * papel obrigatório: 'veterinario_autor' — assina com reautenticação por
 * senha (não basta a sessão logada) para reforçar intenção deliberada.
 * Documento assinado é imutável: correções geram um novo documento
 * vinculado ao anterior como retificação.
 */
class LaudoController
{
    private $db;
    private $laudoModel;
    private $documentoModel;
    private $assinaturaModel;
    private $atendimentoModel;
    private $veterinarioModel;
    private $usuarioModel;
    private DocumentoPdfService $pdfService;

    private const TIPOS_VALIDOS = ['laudo', 'atestado', 'receituario'];

    public function __construct(
        $db = null,
        $laudoModel = null,
        $documentoModel = null,
        $assinaturaModel = null,
        $atendimentoModel = null,
        $veterinarioModel = null,
        $usuarioModel = null,
        ?DocumentoPdfService $pdfService = null
    ) {
        $this->db               = $db ?: getDB();
        $this->laudoModel       = $laudoModel ?: new Laudo($this->db);
        $this->documentoModel   = $documentoModel ?: new Documento($this->db);
        $this->assinaturaModel  = $assinaturaModel ?: new DocumentoAssinatura($this->db, $this->documentoModel);
        $this->atendimentoModel = $atendimentoModel ?: new Atendimento($this->db);
        $this->veterinarioModel = $veterinarioModel ?: new ParceiroVeterinario($this->db);
        $this->usuarioModel     = $usuarioModel ?: new Usuario();
        $this->pdfService       = $pdfService ?: new DocumentoPdfService();
    }

    /**
     * Gera o rascunho do laudo/atestado/receituário a partir de um
     * atendimento já finalizado. Ainda não está assinado — precisa passar
     * por assinar() para virar definitivo.
     */
    public function gerar(int $atendimentoId, int $veterinarioUsuarioId, string $tipo, string $conteudoTexto): array
    {
        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            return ['success' => false, 'errors' => ['Tipo de documento inválido.']];
        }

        $veterinario = $this->veterinarioModel->buscarAprovadoPorUsuarioId($veterinarioUsuarioId);
        if (!$veterinario) {
            return ['success' => false, 'errors' => ['Você não está aprovado como veterinário.']];
        }

        $atendimento = $this->atendimentoModel->buscarPorId($atendimentoId);
        if (!$atendimento || (int)$atendimento['veterinario_id'] !== (int)$veterinario['id']) {
            return ['success' => false, 'errors' => ['Atendimento não encontrado ou não pertence a você.']];
        }
        if ($atendimento['status'] !== 'finalizado') {
            return ['success' => false, 'errors' => ['Só é possível gerar laudo a partir de um atendimento finalizado.']];
        }

        $conteudoTexto = trim(sanitize($conteudoTexto));
        if ($conteudoTexto === '') {
            return ['success' => false, 'errors' => ['Preencha o conteúdo do documento.']];
        }

        $conteudoHtml = $this->renderizarConteudo($tipo, $atendimento, $veterinario, $conteudoTexto);

        $documentoId = $this->documentoModel->criar([
            'tipo'                  => $tipo,
            'referencia_tipo'       => 'atendimento',
            'referencia_id'         => $atendimentoId,
            'conteudo_html'         => $conteudoHtml,
            'criado_por_usuario_id' => $veterinarioUsuarioId,
        ]);

        $laudoId = $this->laudoModel->vincular($atendimentoId, $documentoId);
        auditLog('gerar_laudo', 'laudos', $laudoId);

        return ['success' => true, 'id' => $laudoId];
    }

    /**
     * Gera o pedido de exames a partir dos exames já cadastrados na aba
     * "Exames" do atendimento. Diferente de laudo/atestado/receituário,
     * NÃO exige o atendimento finalizado — na prática o pedido costuma
     * ser impresso durante a própria consulta, antes de qualquer
     * diagnóstico fechado.
     */
    public function gerarPedidoExame(int $atendimentoId, int $veterinarioUsuarioId): array
    {
        $veterinario = $this->veterinarioModel->buscarAprovadoPorUsuarioId($veterinarioUsuarioId);
        if (!$veterinario) {
            return ['success' => false, 'errors' => ['Você não está aprovado como veterinário.']];
        }

        $atendimento = $this->atendimentoModel->buscarPorId($atendimentoId);
        if (!$atendimento || (int)$atendimento['veterinario_id'] !== (int)$veterinario['id']) {
            return ['success' => false, 'errors' => ['Atendimento não encontrado ou não pertence a você.']];
        }
        if (!in_array($atendimento['status'], ['em_andamento', 'finalizado'], true)) {
            return ['success' => false, 'errors' => ['Não é possível gerar pedido de exames para este atendimento.']];
        }

        $exames = Atendimento::decodificarExames($atendimento['exames_solicitados'] ?? null);
        if (empty($exames)) {
            return ['success' => false, 'errors' => ['Adicione ao menos um exame na aba "Exames" antes de gerar o pedido.']];
        }

        $conteudoHtml = $this->renderizarPedidoExame($atendimento, $veterinario, $exames);

        $documentoId = $this->documentoModel->criar([
            'tipo'                  => 'pedido_exame',
            'referencia_tipo'       => 'atendimento',
            'referencia_id'         => $atendimentoId,
            'conteudo_html'         => $conteudoHtml,
            'criado_por_usuario_id' => $veterinarioUsuarioId,
        ]);

        $laudoId = $this->laudoModel->vincular($atendimentoId, $documentoId);
        auditLog('gerar_pedido_exame', 'laudos', $laudoId);

        return ['success' => true, 'id' => $laudoId];
    }

    public function buscarPorId(int $laudoId): ?array
    {
        return $this->laudoModel->buscarPorId($laudoId);
    }

    public function buscarPorAtendimento(int $atendimentoId): array
    {
        return $this->laudoModel->buscarPorAtendimento($atendimentoId);
    }

    public function historicoDoPet(int $petId): array
    {
        return $this->laudoModel->buscarPorPet($petId);
    }

    /**
     * Assina o laudo com reautenticação por senha (não basta a sessão
     * logada — reforça que é um ato deliberado de assinatura).
     */
    public function assinar(int $laudoId, int $veterinarioUsuarioId, string $senha, ?string $ip, ?string $userAgent): array
    {
        $laudo = $this->laudoModel->buscarPorId($laudoId);
        if (!$laudo || !$this->laudoModel->pertenceAoVeterinario($laudoId, $veterinarioUsuarioId)) {
            return ['success' => false, 'error' => 'Laudo não encontrado.'];
        }
        if ($laudo['documento_status'] === 'assinado') {
            return ['success' => false, 'error' => 'Este documento já está assinado.'];
        }
        if ($laudo['documento_status'] === 'revogado') {
            return ['success' => false, 'error' => 'Este documento foi revogado.'];
        }

        $usuario = $this->usuarioModel->findById($veterinarioUsuarioId);
        if (!$usuario || !verifyPassword($senha, $usuario['senha'])) {
            return ['success' => false, 'error' => 'Senha incorreta. Digite sua senha para confirmar a assinatura.'];
        }

        $veterinario = $this->veterinarioModel->buscarAprovadoPorUsuarioId($veterinarioUsuarioId);
        if (!$veterinario) {
            return ['success' => false, 'error' => 'Você não está mais aprovado como veterinário.'];
        }

        $resultado = $this->assinaturaModel->assinar(
            (int)$laudo['documento_id'],
            $veterinarioUsuarioId,
            'veterinario_autor',
            'CRMV ' . $veterinario['crmv_numero'] . '-' . $veterinario['crmv_uf'],
            $ip,
            $userAgent
        );
        if (empty($resultado['success'])) {
            return $resultado;
        }

        auditLog('assinar_laudo', 'laudos', $laudoId);

        // Papel obrigatório único: assinar já finaliza o documento.
        $documento = $this->documentoModel->buscarPorId((int)$laudo['documento_id']);
        $assinaturas = $this->assinaturaModel->listarPorDocumento((int)$laudo['documento_id']);
        $pdfPath = $this->pdfService->gerarESalvar($documento, $assinaturas, $laudo['tipo']);
        $this->documentoModel->marcarAssinado((int)$laudo['documento_id'], $pdfPath);

        return ['success' => true];
    }

    /**
     * Cria uma nova versão do laudo referenciando o documento anterior
     * (assinado é imutável — correção sempre gera um novo registro,
     * nunca sobrescreve o original).
     */
    public function retificar(int $laudoAnteriorId, int $veterinarioUsuarioId, string $novoConteudoTexto): array
    {
        $laudoAnterior = $this->laudoModel->buscarPorId($laudoAnteriorId);
        if (!$laudoAnterior || !$this->laudoModel->pertenceAoVeterinario($laudoAnteriorId, $veterinarioUsuarioId)) {
            return ['success' => false, 'errors' => ['Laudo original não encontrado.']];
        }
        if ($laudoAnterior['documento_status'] !== 'assinado') {
            return ['success' => false, 'errors' => ['Só é possível retificar um documento já assinado.']];
        }

        $veterinario = $this->veterinarioModel->buscarAprovadoPorUsuarioId($veterinarioUsuarioId);
        if (!$veterinario) {
            return ['success' => false, 'errors' => ['Você não está aprovado como veterinário.']];
        }

        $atendimento = $this->atendimentoModel->buscarPorId((int)$laudoAnterior['atendimento_id']);

        if ($laudoAnterior['tipo'] === 'pedido_exame') {
            $exames = Atendimento::decodificarExames($atendimento['exames_solicitados'] ?? null);
            if (empty($exames)) {
                return ['success' => false, 'errors' => ['Nenhum exame cadastrado no atendimento para gerar a retificação.']];
            }
            $conteudoHtml = $this->renderizarPedidoExame($atendimento, $veterinario, $exames, true, $laudoAnterior['codigo_verificacao']);
        } else {
            $novoConteudoTexto = trim(sanitize($novoConteudoTexto));
            if ($novoConteudoTexto === '') {
                return ['success' => false, 'errors' => ['Preencha o novo conteúdo do documento.']];
            }
            $conteudoHtml = $this->renderizarConteudo($laudoAnterior['tipo'], $atendimento, $veterinario, $novoConteudoTexto, true, $laudoAnterior['codigo_verificacao']);
        }

        $documentoId = $this->documentoModel->criar([
            'tipo'                  => $laudoAnterior['tipo'],
            'referencia_tipo'       => 'atendimento',
            'referencia_id'         => (int)$laudoAnterior['atendimento_id'],
            'retifica_documento_id' => (int)$laudoAnterior['documento_id'],
            'conteudo_html'         => $conteudoHtml,
            'criado_por_usuario_id' => $veterinarioUsuarioId,
        ]);

        $novoLaudoId = $this->laudoModel->vincular((int)$laudoAnterior['atendimento_id'], $documentoId);
        auditLog('retificar_laudo', 'laudos', $novoLaudoId, ['documento_original' => $laudoAnterior['documento_id']]);

        return ['success' => true, 'id' => $novoLaudoId];
    }

    private function renderizarConteudo(
        string $tipo,
        array $atendimento,
        array $veterinario,
        string $conteudoTexto,
        bool $ehRetificacao = false,
        ?string $codigoOriginal = null
    ): string {
        $tituloTipo = ['laudo' => 'Laudo Veterinário', 'atestado' => 'Atestado Veterinário', 'receituario' => 'Receituário Veterinário'][$tipo] ?? 'Documento Veterinário';

        $avisoRetificacao = $ehRetificacao
            ? '<p style="color:#b02a37;"><strong>Este documento retifica e substitui o documento de código ' . sanitize((string)$codigoOriginal) . '.</strong></p>'
            : '';

        return '
<h2 style="text-align:center;">' . $tituloTipo . '</h2>
' . $avisoRetificacao . '
<p><strong>Pet:</strong> ' . sanitize($atendimento['pet_nome']) . ' (' . sanitize(ucfirst($atendimento['pet_especie'])) . ')</p>
<p><strong>Motivo da consulta:</strong> ' . sanitize($atendimento['motivo_consulta']) . '</p>
<p><strong>Veterinário responsável:</strong> ' . sanitize($veterinario['nome_completo']) . ' — CRMV ' . sanitize($veterinario['crmv_numero'] . '-' . $veterinario['crmv_uf']) . '</p>
<hr>
<div>' . nl2br(sanitize($conteudoTexto)) . '</div>
<hr>
<p class="small">Este documento é um registro eletrônico auditável (hash + data/hora + IP), sem validade de assinatura digital certificada (ICP-Brasil). A responsabilidade técnica pelo conteúdo é do veterinário identificado acima.</p>
';
    }

    private function renderizarPedidoExame(
        array $atendimento,
        array $veterinario,
        array $exames,
        bool $ehRetificacao = false,
        ?string $codigoOriginal = null
    ): string {
        $avisoRetificacao = $ehRetificacao
            ? '<p style="color:#b02a37;"><strong>Este documento retifica e substitui o documento de código ' . sanitize((string)$codigoOriginal) . '.</strong></p>'
            : '';

        $linhas = '';
        foreach ($exames as $indice => $exame) {
            $linhas .= '<tr>'
                . '<td style="border:1px solid #ccc;padding:6px;">' . ($indice + 1) . '</td>'
                . '<td style="border:1px solid #ccc;padding:6px;">' . sanitize($exame['nome']) . '</td>'
                . '<td style="border:1px solid #ccc;padding:6px;">' . sanitize($exame['observacao'] ?? '') . '</td>'
                . '</tr>';
        }

        return '
<h2 style="text-align:center;">Pedido de Exames</h2>
' . $avisoRetificacao . '
<p><strong>Clínica:</strong> ' . sanitize($atendimento['clinica_nome']) . (!empty($atendimento['clinica_cidade']) ? ' — ' . sanitize($atendimento['clinica_cidade']) : '') . '</p>
<p><strong>Tutor:</strong> ' . sanitize($atendimento['tutor_nome']) . (!empty($atendimento['tutor_telefone']) ? ' — ' . sanitize($atendimento['tutor_telefone']) : '') . '</p>
<p><strong>Pet:</strong> ' . sanitize($atendimento['pet_nome']) . ' (' . sanitize(ucfirst($atendimento['pet_especie'])) . (!empty($atendimento['pet_raca']) ? ', ' . sanitize($atendimento['pet_raca']) : '') . ')</p>
<p><strong>Veterinário solicitante:</strong> ' . sanitize($veterinario['nome_completo']) . ' — CRMV ' . sanitize($veterinario['crmv_numero'] . '-' . $veterinario['crmv_uf']) . '</p>
<hr>
<table style="width:100%; border-collapse: collapse;">
  <thead>
    <tr>
      <th style="border:1px solid #ccc;padding:6px;">#</th>
      <th style="border:1px solid #ccc;padding:6px;">Exame</th>
      <th style="border:1px solid #ccc;padding:6px;">Observação</th>
    </tr>
  </thead>
  <tbody>' . $linhas . '</tbody>
</table>
<hr>
<p class="small">Este documento é um registro eletrônico auditável (hash + data/hora + IP), sem validade de assinatura digital certificada (ICP-Brasil). A responsabilidade técnica pelo conteúdo é do veterinário identificado acima.</p>
';
    }
}
