<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Triagem Veterinária Emergencial | Cadê Meu Pet?';
$controller = new TriagemController();
$usuarioId = isLoggedIn() ? (int)getUserId() : null;
$errors = [];
$resultado = null;
$solicitacaoId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Falha na validação do formulário. Atualize a página e tente novamente.';
    } else {
        $resposta = $controller->iniciarTriagem($_POST, $usuarioId);
        if (!empty($resposta['success'])) {
            setFlashMessage('Triagem concluída. Veja abaixo a orientação.', MSG_SUCCESS);
            redirect('/triagem?resultado=' . (int)$resposta['id']);
        } else {
            $errors = $resposta['errors'] ?? ['Não foi possível concluir a triagem.'];
        }
    }
}

if (!empty($_GET['resultado'])) {
    $solicitacaoId = (int)$_GET['resultado'];
    $resultado = $controller->buscarResultado($solicitacaoId);
    // Só mostra o resultado para quem criou (logado) ou dentro da mesma sessão anônima
    // não temos como validar posse de solicitação anônima além do id opaco — aceitável no MVP.
    if ($resultado && $usuarioId !== null && $resultado['usuario_id'] !== null && (int)$resultado['usuario_id'] !== $usuarioId) {
        $resultado = null;
    }
}

$sintomas = $controller->listaSintomas();

$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL],
    ['label' => 'Triagem Veterinária'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">

    <div class="alert alert-warning border-0 shadow-sm mb-4">
        <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> Atenção:</strong>
        esta triagem é apenas uma orientação de para onde procurar ajuda e <strong>não substitui uma
        avaliação veterinária presencial</strong>. Em caso de emergência (sangramento intenso, dificuldade
        para respirar, convulsão, trauma grave ou suspeita de envenenamento), procure atendimento
        veterinário imediatamente, independentemente do resultado desta triagem.
    </div>

    <?php if ($resultado): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <h1 class="h4 fw-bold mb-3">Resultado da triagem</h1>

                <?php
                $urgencia = $resultado['nivel_urgencia'];
                $badgeClasse = [
                    'critica'  => 'bg-danger',
                    'alta'     => 'bg-warning text-dark',
                    'moderada' => 'bg-info text-dark',
                    'baixa'    => 'bg-secondary',
                ][$urgencia] ?? 'bg-secondary';
                $urgenciaLabel = [
                    'critica'  => 'Crítica',
                    'alta'     => 'Alta',
                    'moderada' => 'Moderada',
                    'baixa'    => 'Baixa',
                ][$urgencia] ?? ucfirst($urgencia);
                ?>
                <span class="badge <?php echo $badgeClasse; ?> mb-3">Urgência: <?php echo $urgenciaLabel; ?></span>

                <?php if ($resultado['direcionamento_sugerido'] === 'emergencia_imediata'): ?>
                    <div class="alert alert-danger">
                        <strong>Procure atendimento veterinário imediato.</strong> Os sintomas informados indicam
                        risco de vida. Vá à clínica ou hospital veterinário mais próximo agora — pública ou
                        particular, o que for mais rápido de acessar.
                    </div>
                <?php endif; ?>

                <?php if (in_array($resultado['direcionamento_sugerido'], ['publico', 'ambos', 'emergencia_imediata'], true)): ?>
                    <?php
                    $local = $resultado['triagem_locais_publicos_id']
                        ? (new TriagemLocalPublico())->buscarPorId((int)$resultado['triagem_locais_publicos_id'])
                        : null;
                    ?>
                    <?php if ($local): ?>
                        <div class="border rounded p-3 mb-3">
                            <h2 class="h6 fw-bold mb-2"><i class="bi bi-bank me-1"></i> Atendimento público</h2>
                            <p class="mb-1 fw-semibold"><?php echo sanitize($local['nome']); ?></p>
                            <p class="mb-1 small text-muted"><?php echo sanitize($local['endereco'] ?? ''); ?></p>
                            <p class="mb-1 small"><strong>Horário:</strong> <?php echo sanitize($local['horario_funcionamento'] ?? ''); ?></p>
                            <p class="mb-1 small"><strong>Como funciona a fila:</strong> <?php echo sanitize($local['como_funciona_fila'] ?? ''); ?></p>
                            <?php if (!empty($local['requisitos'])): ?>
                                <p class="mb-0 small"><strong>Requisitos:</strong> <?php echo sanitize($local['requisitos']); ?></p>
                            <?php endif; ?>
                            <p class="mb-0 small text-muted mt-2">
                                Não há reserva de vaga por este site — o atendimento é presencial, por ordem de chegada.
                            </p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (in_array($resultado['direcionamento_sugerido'], ['parceiro_privado', 'ambos'], true) && !empty($resultado['parceiro_perfil_id'])): ?>
                    <?php $parceiro = getDB()->fetchOne('SELECT * FROM parceiro_perfis WHERE id = ?', [$resultado['parceiro_perfil_id']]); ?>
                    <?php if ($parceiro): ?>
                        <div class="border rounded p-3 mb-3">
                            <h2 class="h6 fw-bold mb-2"><i class="bi bi-hospital me-1"></i> Clínica parceira</h2>
                            <p class="mb-1 fw-semibold"><?php echo sanitize($parceiro['nome_fantasia']); ?></p>
                            <p class="mb-1 small text-muted"><?php echo sanitize($parceiro['endereco'] ?? ''); ?></p>
                            <?php if (!empty($parceiro['whatsapp'])): ?>
                                <p class="mb-1 small"><strong>WhatsApp:</strong> <?php echo sanitize($parceiro['whatsapp']); ?></p>
                            <?php elseif (!empty($parceiro['telefone'])): ?>
                                <p class="mb-1 small"><strong>Telefone:</strong> <?php echo sanitize($parceiro['telefone']); ?></p>
                            <?php endif; ?>

                            <?php if ($usuarioId): ?>
                                <form method="POST" action="<?php echo BASE_URL; ?>/triagem/conversa" class="mt-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="solicitacao_id" value="<?php echo (int)$resultado['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="bi bi-chat-dots me-1"></i> Falar com a clínica pelo chat interno
                                    </button>
                                </form>
                            <?php else: ?>
                                <p class="mb-0 small text-muted">
                                    <a href="<?php echo BASE_URL; ?>/login">Entre na sua conta</a> para abrir uma conversa
                                    pelo chat interno, ou entre em contato diretamente pelo telefone/WhatsApp acima.
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <a href="<?php echo BASE_URL; ?>/triagem" class="btn btn-outline-secondary btn-sm mt-2">Fazer nova triagem</a>
            </div>
        </div>
    <?php else: ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h1 class="h4 fw-bold mb-1">Triagem veterinária emergencial</h1>
                <p class="text-muted mb-4">Responda algumas perguntas para saber onde buscar atendimento para o seu pet.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $erro): ?>
                                <li><?php echo sanitize($erro); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Espécie</label>
                        <select class="form-select" name="especie" required>
                            <option value="">Selecione</option>
                            <option value="cachorro">Cachorro</option>
                            <option value="gato">Gato</option>
                            <option value="ave">Ave</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">O que está acontecendo? (marque todos que se aplicam)</label>
                        <div class="row">
                            <?php foreach ($sintomas as $chave => $rotulo): ?>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sintomas[]" value="<?php echo $chave; ?>" id="s_<?php echo $chave; ?>">
                                        <label class="form-check-label" for="s_<?php echo $chave; ?>"><?php echo sanitize($rotulo); ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (!$usuarioId): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Seu nome</label>
                                <input type="text" class="form-control" name="nome_contato">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Telefone de contato</label>
                                <input type="text" class="form-control" name="telefone_contato">
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-semibold">Cidade</label>
                            <input type="text" class="form-control" name="cidade" placeholder="Ex: Porto Velho">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Estado (UF)</label>
                            <input type="text" class="form-control" name="estado" maxlength="2">
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input class="form-check-input" type="checkbox" name="renda_baixa_declarada" value="1" id="renda_baixa">
                        <label class="form-check-label" for="renda_baixa">
                            Sou de baixa renda / possuo CadÚnico / sou protetor(a) de animais
                        </label>
                        <small class="d-block text-muted">Usado apenas para sugerir o atendimento público gratuito, quando disponível.</small>
                    </div>

                    <div class="mb-3 form-check">
                        <input class="form-check-input" type="checkbox" name="disclaimer_aceito" value="1" id="disclaimer" required>
                        <label class="form-check-label fw-semibold" for="disclaimer">
                            Entendo que esta triagem não substitui uma avaliação veterinária e que, em caso de
                            emergência, devo procurar atendimento imediato.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Ver orientação</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
