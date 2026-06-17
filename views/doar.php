<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Doar - Cadê Meu Pet?';

$doacaoController = new DoacaoController();
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Falha na validação do formulário. Recarregue a página.';
    } else {
        $resultado = $doacaoController->criar($_POST);

        if (!empty($resultado['success'])) {
            if (!empty($resultado['redirect'])) {
                redirect((string)$resultado['redirect']);
            }
            $successMessage = 'Sua doação foi registrada! Em instantes você receberá instruções de pagamento.';
        } elseif (!empty($resultado['errors'])) {
            $errors = $resultado['errors'];
        }
    }
}

$resumo = $doacaoController->resumoDashboard();
$metaAtual = $doacaoController->metaAtual();
$mural = $doacaoController->mural(6);

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="display-4">💚</div>
                        <div>
                            <h1 class="h3 fw-bold mb-1">Ajude a Manter o Cadê Meu Pet? Gratuito</h1>
                            <p class="text-muted mb-0">Sua contribuição mantém o site online, gratuito e sem anúncios.</p>
                        </div>
                    </div>

                    <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i><?php echo sanitize($successMessage); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h6 class="fw-bold">Corrija os seguintes pontos:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $erro): ?>
                                    <li><?php echo sanitize($erro); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="mt-4">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                        <div class="mb-4">
                            <label class="form-label fw-bold">Escolha um valor</label>
                            <div class="row g-2">
                                <?php foreach ([5, 10, 20, 50] as $valor): ?>
                                    <div class="col-6 col-md-3">
                                        <input type="radio" class="btn-check" name="valor" id="valor_<?php echo $valor; ?>" value="<?php echo $valor; ?>">
                                        <label class="btn btn-outline-success w-100" for="valor_<?php echo $valor; ?>">
                                            R$ <?php echo $valor; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="row g-2 mt-2">
                                <div class="col-12">
                                    <label class="form-label">Ou digite outro valor</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" class="form-control" id="valorCustom" name="valor_custom" min="<?php echo (float)envValue('MIN_DONATION_AMOUNT', MIN_DONATION_AMOUNT); ?>" step="1" placeholder="Outro valor (mínimo R$ <?php echo number_format((float)envValue('MIN_DONATION_AMOUNT', MIN_DONATION_AMOUNT), 2, ',', '.'); ?>)">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Método de pagamento</label>
                            <div class="row g-2">
                                <?php
                                    // Verificar se a integração EFI está disponível (SDK + credenciais + certificado)
                                    $efiAvailable = false;
                                    try {
                                        $efiAvailable = (class_exists('Efi\\EfiPay') || class_exists('EfiPay'))
                                            && !empty(EFI_CLIENT_ID) && !empty(EFI_CLIENT_SECRET)
                                            && file_exists((string)EFI_CERTIFICATE_PATH);
                                    } catch (Throwable $ex) {
                                        $efiAvailable = false;
                                    }

                                    $metodos = ['PIX' => 'pix'];
                                    if ($efiAvailable) {
                                        $metodos['Cartão (à vista)'] = 'cartao_avista';
                                        $metodos['Cartão (mensal)'] = 'cartao_recorrente';
                                    }
                                ?>

                                <?php foreach ($metodos as $label => $valor): ?>
                                    <div class="col-6 col-md-3">
                                        <input type="radio" class="btn-check" name="metodo_pagamento" id="metodo_<?php echo $valor; ?>" value="<?php echo $valor; ?>">
                                        <label class="btn btn-outline-primary w-100" for="metodo_<?php echo $valor; ?>"><?php echo $label; ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (!$efiAvailable): ?>
                                <div class="alert alert-warning mt-3 mb-0">
                                    <strong>Atenção:</strong> Pagamentos por cartão estão temporariamente indisponíveis no momento. Por favor, escolha PIX ou tente novamente mais tarde.
                                </div>
                            <?php else: ?>
                                <div class="form-text">Pix e cartão à vista são doações únicas. Para doação mensal, use Cartão (mensal).</div>
                            <?php endif; ?>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="recorrente" name="recorrente">
                            <label class="form-check-label" for="recorrente">Quero doar mensalmente</label>
                        </div>

                        <h5 class="fw-bold mb-3">Seus Dados</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome</label>
                                <input type="text" class="form-control" name="nome_doador" placeholder="Seu nome completo">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email_doador" placeholder="seu@email.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CPF</label>
                                <input type="text" class="form-control" name="cpf_doador" placeholder="000.000.000-00">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Mensagem (opcional)</label>
                                <textarea class="form-control" name="mensagem" rows="3" placeholder="Deixe um recado para nossa equipe"></textarea>
                            </div>
                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" value="1" id="exibirMural" name="exibir_mural" checked>
                            <label class="form-check-label" for="exibirMural">
                                Quero aparecer no mural de doadores ❤️
                            </label>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 mt-4">
                            <i class="bi bi-heart-fill me-2"></i>Doar agora
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Meta Mensal</h5>
                    <?php if ($metaAtual): ?>
                        <?php
                            $valorMeta = (float)($metaAtual['valor_meta'] ?? 0);
                            $valorArrecadado = (float)($metaAtual['valor_arrecadado'] ?? 0);
                            $percentual = $valorMeta > 0 ? min(100, round(($valorArrecadado / $valorMeta) * 100)) : 0;
                        ?>
                        <p class="text-muted mb-2"><?php echo sanitize($metaAtual['descricao'] ?? ''); ?></p>
                        <div class="progress mb-2" style="height: 12px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentual; ?>%" aria-valuenow="<?php echo $percentual; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span class="fw-semibold">Arrecadado: <?php echo formatMoney($valorArrecadado); ?></span>
                            <span class="text-muted">Meta: <?php echo formatMoney($valorMeta); ?></span>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Ainda não há meta ativa. Sua doação será essencial para manter o projeto.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Mural de Doadores</h5>
                    <?php if (empty($mural)): ?>
                        <p class="text-muted">Seja o primeiro a aparecer por aqui! 💚</p>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php $first = true; foreach ($mural as $doacao):
                                $nameRaw = trim((string)($doacao['nome_doador'] ?? ''));
                                $displayName = $nameRaw !== '' ? sanitize($nameRaw) : 'Apoiador anônimo';
                                // Gerar iniciais (até duas letras)
                                $initials = '';
                                if ($nameRaw !== '') {
                                    $parts = preg_split('/\s+/', $nameRaw);
                                    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                                } else {
                                    $initials = '💚';
                                }
                            ?>
                                <div class="col-12 <?php echo $first ? 'col-md-12' : 'col-md-6'; ?>">
                                    <div class="donor-card d-flex align-items-start gap-3 p-3 <?php echo $first ? 'donor-highlight' : ''; ?>">
                                        <div class="avatar-circle d-flex align-items-center justify-content-center">
                                            <?php echo sanitize($initials); ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="fw-bold mb-1"><?php echo $displayName; ?></h6>
                                            <?php if (!empty($doacao['mensagem'])): ?>
                                                <p class="mb-1 text-muted small"><?php echo sanitize($doacao['mensagem']); ?></p>
                                            <?php endif; ?>
                                            <div>
                                                <span class="badge bg-light text-dark">
                                                    <?php echo formatMoney($doacao['valor']); ?> · <?php echo date('d/m/Y', strtotime($doacao['data_doacao'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php $first = false; endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.btn-outline-success,
.btn-outline-primary {
    border-radius: 12px;
}

.btn-outline-success:hover,
.btn-outline-success:checked,
.btn-check:checked + .btn-outline-success {
    color: #fff;
    background-color: #00a86b;
    border-color: #00a86b;
}

.btn-outline-primary:hover,
.btn-check:checked + .btn-outline-primary {
    color: #fff;
    background-color: #2196F3;
    border-color: #2196F3;
}

.card {
    border-radius: 16px;
}

/* Mural de Doadores - estilos customizados */
.donor-card {
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 0 0 1px rgba(0,0,0,0.03);
}
.donor-highlight {
    background: linear-gradient(90deg, #e9f7f1 0%, #ffffff 100%);
    border: 1px solid rgba(0,160,107,0.12);
}
.avatar-circle {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #f1f5f4;
    color: #006a45;
    font-weight: 700;
    display: inline-flex;
    font-size: 18px;
}
</style>

<script>
(() => {
    const form = document.querySelector('form');
    const recorrente = document.getElementById('recorrente');
    const metodoInputs = document.querySelectorAll('input[name="metodo_pagamento"]');
    const valorRadios = document.querySelectorAll('input[name="valor"][type="radio"]');
    const valorCustom = document.getElementById('valorCustom');

    // Sincronizar valor customizado com radio buttons
    if (valorCustom) {
        valorCustom.addEventListener('input', () => {
            if (valorCustom.value) {
                // Se o usuário digita um valor customizado, desmarca os radios pré-definidos
                valorRadios.forEach(radio => radio.checked = false);
            }
        });
    }

    valorRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            // Quando um botão de valor é selecionado, preenche o campo de valor custom e marca o radio
            if (valorCustom) {
                // normalizar para número (sem vírgula)
                var v = parseFloat(radio.value);
                if (!isNaN(v)) {
                    valorCustom.value = v;
                } else {
                    valorCustom.value = '';
                }
            }
        });
    });

    // Suporte adicional: ao clicar no label (botão visual), também preenche o input e dispara o change no radio
    var valorLabels = document.querySelectorAll('label[for^="valor_"]');
    valorLabels.forEach(function(lbl) {
        lbl.addEventListener('click', function(e) {
            var forId = lbl.getAttribute('for');
            if (!forId) return;
            var r = document.getElementById(forId);
            if (!r) return;
            // Marcar o radio explicitamente e disparar evento change
            r.checked = true;
            var ev = new Event('change', { bubbles: true });
            r.dispatchEvent(ev);

            // Preencher campo custom conforme valor
            if (valorCustom) {
                var v = parseFloat(r.value);
                if (!isNaN(v)) valorCustom.value = v;
            }
        });
    });

    function getMetodoSelecionado() {
        const el = document.querySelector('input[name="metodo_pagamento"]:checked');
        return el ? el.value : '';
    }

    function getValorSelecionado() {
        const radioSelecionado = document.querySelector('input[name="valor"]:checked');
        if (radioSelecionado) {
            return parseFloat(radioSelecionado.value);
        }
        if (valorCustom && valorCustom.value) {
            return parseFloat(valorCustom.value);
        }
        return 0;
    }

    function selectMetodo(value) {
        const el = document.getElementById('metodo_' + value);
        if (el) {
            el.checked = true;
        }
    }

    function syncRecorrencia() {
        const metodo = getMetodoSelecionado();
        if (metodo === 'cartao_recorrente') {
            recorrente.checked = true;
            recorrente.disabled = true;
            return;
        }

        recorrente.disabled = false;
        if (recorrente.checked) {
            selectMetodo('cartao_recorrente');
            recorrente.disabled = true;
        }
    }

    // Validar formulário antes de submeter
    form.addEventListener('submit', (e) => {
        const valor = getValorSelecionado();
        const metodo = getMetodoSelecionado();
        const minValue = <?php echo (float)envValue('MIN_DONATION_AMOUNT', MIN_DONATION_AMOUNT); ?>;

        if (valor < minValue) {
            e.preventDefault();
            alert('Por favor, selecione um valor mínimo de R$ ' + minValue.toFixed(2).replace('.', ','));
            return false;
        }

        if (!metodo) {
            e.preventDefault();
            alert('Por favor, selecione um método de pagamento');
            return false;
        }
    });

    metodoInputs.forEach((el) => {
        el.addEventListener('change', syncRecorrencia);
    });
    recorrente.addEventListener('change', syncRecorrencia);

    syncRecorrencia();
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
