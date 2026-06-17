<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Cancelar Doação Recorrente | Cadê Meu Pet?';

$usuarioId = (int)(getUserId() ?? 0);
$doacaoId = (int)($_GET['id'] ?? 0);

$doacaoModel = new Doacao();
$doacao = $doacaoModel->findById($doacaoId);

// Verificar se a doação existe e pertence ao usuário
if (!$doacao || $doacao['usuario_id'] != $usuarioId || $doacao['tipo'] !== 'mensal') {
    setFlashMessage('Doação recorrente não encontrada ou você não tem permissão para cancelá-la.', MSG_ERROR);
    redirect('/doar');
}

// Processar cancelamento via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Falha na validação do formulário. Recarregue e tente novamente.', MSG_ERROR);
        redirect('/doacao/cancelar?id=' . $doacaoId);
    }

    $motivo = (string)($_POST['motivo'] ?? '');
    $senha = (string)($_POST['senha_confirmacao'] ?? '');
    $outroMotivo = (string)($_POST['outro_motivo'] ?? '');

    // Se selecionou "Outro", usar o motivo digitado
    if ($motivo === 'outro' && !empty($outroMotivo)) {
        $motivo = $outroMotivo;
    }

    // Validar motivo
    if (empty($motivo) || strlen($motivo) < 5) {
        setFlashMessage('Por favor, informe um motivo válido (mínimo 5 caracteres).', MSG_ERROR);
        redirect('/doacao/cancelar?id=' . $doacaoId);
    }

    $cancelamentoController = new CancelamentoController();
    $resultado = $cancelamentoController->cancelarDoacaoRecorrente($doacaoId, $usuarioId, $motivo, $senha);

    if ($resultado['success']) {
        setFlashMessage($resultado['message'], MSG_SUCCESS);
        redirect('/doar');
    } else {
        $erros = implode('<br>', $resultado['errors']);
        setFlashMessage($erros, MSG_ERROR);
        redirect('/doacao/cancelar?id=' . $doacaoId);
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <div class="text-info mb-3">
                            <i class="fas fa-heart-broken" style="font-size: 3rem;"></i>
                        </div>
                        <h1 class="h4 fw-bold mb-2">Cancelar Doação Recorrente</h1>
                        <p class="text-muted">Sentiremos sua falta, mas respeitamos sua decisão.</p>
                    </div>

                    <!-- Informações da doação -->
                    <div class="bg-light rounded p-3 mb-4">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="small text-muted">Valor mensal</div>
                                <div class="fw-bold text-success">R$ <?php echo number_format($doacao['valor'], 2, ',', '.'); ?></div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Iniciada em</div>
                                <div class="fw-bold"><?php echo formatDateBR($doacao['data_doacao']); ?></div>
                            </div>
                        </div>
                        <?php if (!empty($doacao['mensagem'])): ?>
                            <div class="mt-3 text-center">
                                <div class="small text-muted">Sua mensagem original</div>
                                <div class="fst-italic">"<?php echo sanitize($doacao['mensagem']); ?>"</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Alerta informativo -->
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            <strong>Importante:</strong> Ao cancelar sua doação recorrente:
                            <ul class="mb-0 mt-2 small">
                                <li>Não haverá mais cobranças futuras</li>
                                <li>Você ainda pode fazer doações pontuais quando desejar</li>
                                <li>Seu apoio até hoje foi fundamental para nossa causa</li>
                            </ul>
                        </div>
                    </div>

                    <form method="POST" id="formCancelamento">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                        <!-- Motivo do cancelamento -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-comment-dots me-1"></i>
                                Motivo do cancelamento <span class="text-danger">*</span>
                            </label>
                            <select name="motivo" id="motivo" class="form-select" required>
                                <option value="">Selecione um motivo...</option>
                                <option value="dificuldade_financeira">Dificuldade financeira temporária</option>
                                <option value="mudanca_prioridades">Mudança de prioridades</option>
                                <option value="insatisfacao">Insatisfação com o projeto</option>
                                <option value="problema_cobranca">Problema com cobrança</option>
                                <option value="doacao_unicaNova">Prefiro fazer doações únicas</option>
                                <option value="outro">Outro motivo</option>
                            </select>
                        </div>

                        <!-- Campo para "Outro motivo" (inicialmente oculto) -->
                        <div class="mb-4" id="campo_outro_motivo" style="display: none;">
                            <label class="form-label fw-semibold">
                                Descreva o motivo <span class="text-danger">*</span>
                            </label>
                            <textarea name="outro_motivo" class="form-control" rows="3" 
                                    placeholder="Por favor, descreva o motivo do cancelamento..." 
                                    minlength="5" maxlength="500"></textarea>
                            <div class="form-text">Mínimo 5 caracteres, máximo 500.</div>
                        </div>

                        <!-- Confirmação de senha -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-lock me-1"></i>
                                Confirme sua senha <span class="text-danger">*</span>
                            </label>
                            <input type="password" name="senha_confirmacao" class="form-control" 
                                   required minlength="8" placeholder="Digite sua senha para confirmar">
                            <div class="form-text">Esta confirmação é necessária para sua segurança.</div>
                        </div>

                        <!-- Mensagem de agradecimento -->
                        <div class="mb-4">
                            <div class="alert alert-success">
                                <i class="fas fa-heart me-2"></i>
                                <strong>Muito obrigado!</strong> Sua contribuição até hoje ajudou a resgatar e cuidar de muitos pets. 
                                As portas do Cadê Meu Pet? estarão sempre abertas para você!
                            </div>
                        </div>

                        <!-- Botões de ação -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-info btn-lg" id="btnCancelar">
                                <i class="fas fa-heart-broken me-2"></i>
                                Confirmar Cancelamento
                            </button>
                            <a href="<?php echo BASE_URL; ?>/doar" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Voltar para Doações
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Alternativas -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body p-4">
                    <h5 class="card-title">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        Alternativas ao Cancelamento
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-semibold">Reduzir Valor</h6>
                                <p class="small text-muted mb-2">Você pode reduzir o valor mensal em vez de cancelar.</p>
                                <a href="<?php echo BASE_URL; ?>/doar" class="btn btn-sm btn-outline-primary">Alterar Valor</a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-semibold">Pausar Temporariamente</h6>
                                <p class="small text-muted mb-2">Entre em contato para pausar por alguns meses.</p>
                                <a href="<?php echo BASE_URL; ?>/contato" class="btn btn-sm btn-outline-primary">Falar Conosco</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const motivoSelect = document.getElementById('motivo');
    const campoOutro = document.getElementById('campo_outro_motivo');
    const outroMotivoTextarea = document.querySelector('textarea[name="outro_motivo"]');
    const form = document.getElementById('formCancelamento');
    const btnCancelar = document.getElementById('btnCancelar');

    // Mostrar/ocultar campo "Outro motivo"
    motivoSelect.addEventListener('change', function() {
        if (this.value === 'outro') {
            campoOutro.style.display = 'block';
            outroMotivoTextarea.required = true;
        } else {
            campoOutro.style.display = 'none';
            outroMotivoTextarea.required = false;
            outroMotivoTextarea.value = '';
        }
    });

    // Confirmação adicional antes de enviar
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const motivo = motivoSelect.value;
        const outroMotivo = outroMotivoTextarea.value.trim();
        const senha = document.querySelector('input[name="senha_confirmacao"]').value;

        // Validações
        if (!motivo) {
            alert('Por favor, selecione um motivo.');
            return;
        }

        if (motivo === 'outro' && (!outroMotivo || outroMotivo.length < 5)) {
            alert('Por favor, descreva o motivo (mínimo 5 caracteres).');
            return;
        }

        if (!senha || senha.length < 8) {
            alert('Por favor, digite sua senha corretamente.');
            return;
        }

        // Confirmação final
        const confirmacao = confirm('Tem certeza que deseja cancelar sua doação recorrente?\n\n' +
                                  '• Não haverá mais cobranças futuras\n' +
                                  '• Você ainda pode fazer doações pontuais\n\n' +
                                  'Sentiremos sua falta, mas respeitamos sua decisão.');

        if (confirmacao) {
            // Desabilitar botão para evitar duplo clique
            btnCancelar.disabled = true;
            btnCancelar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';
            
            // Enviar formulário
            form.submit();
        }
    });

    // Limitar caracteres no textarea
    outroMotivoTextarea.addEventListener('input', function() {
        const maxLength = 500;
        const currentLength = this.value.length;
        
        if (currentLength > maxLength) {
            this.value = this.value.substring(0, maxLength);
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
