<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Cancelar Assinatura | Cadê Meu Pet?';

$usuarioId = (int)(getUserId() ?? 0);
$assinaturaModel = new ParceiroAssinatura();
$assinatura = $assinaturaModel->findByUserId($usuarioId);

// Verificar se tem assinatura ativa
if (!$assinatura || $assinatura['status'] !== 'ativa') {
    setFlashMessage('Você não possui uma assinatura ativa para cancelar.', MSG_WARNING);
    redirect('/parceiro/painel');
}

// Processar cancelamento via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Falha na validação do formulário. Recarregue e tente novamente.', MSG_ERROR);
        redirect('/parceiro/cancelar');
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
        redirect('/parceiro/cancelar');
    }

    $cancelamentoController = new CancelamentoController();
    $resultado = $cancelamentoController->cancelarAssinaturaParceiro($usuarioId, $motivo, $senha);

    if ($resultado['success']) {
        setFlashMessage($resultado['message'], MSG_SUCCESS);
        redirect('/parceiro/painel');
    } else {
        $erros = implode('<br>', $resultado['errors']);
        setFlashMessage($erros, MSG_ERROR);
        redirect('/parceiro/cancelar');
    }
}

$breadcrumbs = [
    ['label' => 'Início',             'url' => BASE_URL],
    ['label' => 'Parceiros',          'url' => BASE_URL . '/parceiros'],
    ['label' => 'Cancelar Parceria'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <div class="text-danger mb-3">
                            <i class="fas fa-exclamation-triangle" style="font-size: 3rem;"></i>
                        </div>
                        <h1 class="h4 fw-bold mb-2">Cancelar Assinatura</h1>
                        <p class="text-muted">Tem certeza que deseja cancelar sua assinatura?</p>
                    </div>

                    <!-- Alerta informativo -->
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            <strong>Atenção:</strong> Ao cancelar sua assinatura:
                            <ul class="mb-0 mt-2 small">
                                <li>Seu perfil será despublicado em até 24 horas</li>
                                <li>Você perderá o destaque na listagem</li>
                                <li>Não haverá mais cobranças futuras</li>
                                <li>Seus dados serão mantidos por 90 dias</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Status atual -->
                    <div class="bg-light rounded p-3 mb-4">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="small text-muted">Plano atual</div>
                                <div class="fw-bold text-uppercase"><?php echo sanitize($assinatura['plano']); ?></div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Próxima cobrança</div>
                                <div class="fw-bold"><?php echo formatDateBR($assinatura['proxima_cobranca'] ?? 'N/A'); ?></div>
                            </div>
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
                                <option value="muito_caro">Muito caro</option>
                                <option value="nao_uso">Não estou usando mais</option>
                                <option value="mudanca_negocio">Mudança no negócio</option>
                                <option value="resultado_insatisfatorio">Resultados insatisfatórios</option>
                                <option value="problema_tecnico">Problemas técnicos</option>
                                <option value="falta_tempo">Falta de tempo para gerenciar</option>
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

                        <!-- Termos -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirmar_riscos" required>
                                <label class="form-check-label" for="confirmar_riscos">
                                    Li e entendo que meu perfil será despublicado e perderei todos os benefícios da assinatura.
                                </label>
                            </div>
                        </div>

                        <!-- Botões de ação -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg" id="btnCancelar">
                                <i class="fas fa-times-circle me-2"></i>
                                Confirmar Cancelamento
                            </button>
                            <a href="<?php echo BASE_URL; ?>/parceiro/painel" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Voltar ao Painel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Ajuda -->
            <div class="text-center mt-4">
                <p class="text-muted small">
                    <i class="fas fa-question-circle me-1"></i>
                    Precisa de ajuda? <a href="<?php echo BASE_URL; ?>/ajuda">Fale conosco</a>
                </p>
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
        const confirmarRiscos = document.getElementById('confirmar_riscos').checked;

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

        if (!confirmarRiscos) {
            alert('Você precisa confirmar que leu e entendeu os riscos do cancelamento.');
            return;
        }

        // Confirmação final
        const confirmacao = confirm('Tem certeza que deseja cancelar sua assinatura?\n\n' +
                                  '• Seu perfil será despublicado\n' +
                                  '• Você perderá todos os benefícios\n' +
                                  '• Não haverá mais cobranças futuras\n\n' +
                                  'Esta ação não pode ser desfeita.');

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
        
        // Atualizar contador se existir
        const counter = document.getElementById('caracteres_restantes');
        if (counter) {
            counter.textContent = `${maxLength - this.value.length} caracteres restantes`;
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
