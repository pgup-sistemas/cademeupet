<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Pagamento via Pix - Cadê Meu Pet?';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$doacaoModel = new Doacao();
$doacao = $id > 0 ? $doacaoModel->findById($id) : null;

if (empty($doacao)) {
    http_response_code(404);
    die('Doação não encontrada.');
}

$usuarioId = getUserId();
$sessionAllowed = !empty($_SESSION['pix_doacoes'][$id]);
if (!empty($doacao['usuario_id'])) {
    if ((int)$doacao['usuario_id'] !== (int)$usuarioId) {
        http_response_code(403);
        die('Acesso negado.');
    }
} else {
    if (!$sessionAllowed) {
        // Se não está na sessão mas tem um transaction_id, permite acesso
        // (o PIX foi criado em uma sessão anterior)
        if (empty($doacao['transaction_id'])) {
            http_response_code(403);
            die('Acesso negado.');
        }
    }
}

$pix = null;
if (!empty($_SESSION['pix_doacoes'][$id]) && is_array($_SESSION['pix_doacoes'][$id])) {
    $pix = $_SESSION['pix_doacoes'][$id];
} elseif (!empty($doacao['pix_qrcode'])) {
    // Se não está na sessão, obter do banco de dados
    try {
        $qrcodeData = json_decode($doacao['pix_qrcode'], true);
        if (is_array($qrcodeData) || is_string($qrcodeData)) {
            $pix = [
                'txid' => (string)$doacao['transaction_id'],
                'qrcode' => $qrcodeData
            ];
            // Armazenar na sessão para próximas vezes
            if (!isset($_SESSION['pix_doacoes'])) {
                $_SESSION['pix_doacoes'] = [];
            }
            $_SESSION['pix_doacoes'][$id] = $pix;
        }
    } catch (Exception $e) {
        error_log('[doacao-pix] Erro ao decodificar QR Code do banco: ' . $e->getMessage());
    }
} elseif (!empty($doacao['transaction_id'])) {
    // Se não está em lugar nenhum, tentar obter os dados do PIX via API
    try {
        $pagamentoController = new PagamentoController();
        $pixData = $pagamentoController->detalharCobrancaPix((string)$doacao['transaction_id']);
        
        if (!empty($pixData)) {
            $pix = [
                'txid' => (string)$doacao['transaction_id'],
                'qrcode' => $pixData
            ];
            // Armazenar no banco para próximas vezes
            $doacaoModel->update((int)$id, ['pix_qrcode' => json_encode($pixData)]);
            
            // Armazenar na sessão para próximas vezes
            if (!isset($_SESSION['pix_doacoes'])) {
                $_SESSION['pix_doacoes'] = [];
            }
            $_SESSION['pix_doacoes'][$id] = $pix;
        }
    } catch (Exception $e) {
        error_log('[doacao-pix] Erro ao obter dados PIX da API: ' . $e->getMessage());
    }
}

if (($doacao['status'] ?? '') === 'pendente' && !empty($doacao['transaction_id'])) {
    try {
        $pagamentoController = new PagamentoController();
        $atualizada = $pagamentoController->sincronizarStatusDoacaoPix((int)$doacao['id'], (string)$doacao['transaction_id']);
        if (!empty($atualizada)) {
            $doacao = $atualizada;
        }
    } catch (Exception $e) {
        error_log('[doacao-pix] Falha ao sincronizar status Pix: ' . $e->getMessage());
    }
}

// Normalizar dados do QR Code para formas consistentes de uso na view
$pix_qr_image = '';
$pix_qr_text = '';
if (!empty($pix)) {
    // Helper para buscar texto copia-e-cola em várias chaves e níveis
    $findQrText = function($arr) {
        // Adicionar chaves comuns e variações encontradas em respostas da API
        $candidates = ['pixCopiaECola', 'pixCopiaEcola', 'pix_copia_e_cola', 'qrcode', 'qrCode', 'pixCopia', 'copiaECola', 'copiaecola', 'copia_e_cola', 'pixCopiaECola', 'payload', 'emv', 'qr_code', 'qr_code_base64', 'qrCodePayload', 'payloadQrCode', 'texto', 'text', 'codigo', 'codigo_pix'];
        foreach ($candidates as $k) {
            if (isset($arr[$k]) && is_string($arr[$k]) && trim($arr[$k]) !== '') {
                return $arr[$k];
            }
        }
        // Buscar em possíveis subarrays
        foreach ($arr as $v) {
            if (is_array($v)) {
                $found = $findQrText($v);
                if (!empty($found)) return $found;
            }
        }
        return '';
    };

    // Função auxiliar: procura recursivamente a primeira string longa que pareça um payload EMV (copia-e-cola)
    $findLongString = function($arr) use (&$findLongString) {
        foreach ($arr as $v) {
            if (is_string($v) && strlen(trim($v)) >= 40) {
                return trim($v);
            }
            if (is_array($v)) {
                $res = $findLongString($v);
                if (!empty($res)) return $res;
            }
        }
        return '';
    };

    // Caso $pix['qrcode'] seja um array vindo de detalharCobrancaPix
    if (is_array($pix['qrcode'])) {
        $p = $pix['qrcode'];
        $pix_qr_image = $p['imagemQrcode'] ?? $p['imagem_qrcode'] ?? $p['imagemQrCode'] ?? $p['imagem'] ?? '';
        $pix_qr_text = $findQrText($p) ?: '';
        // Alguns retornos colocam o copia-e-cola em 'charge' ou 'response'
        if (empty($pix_qr_text) && !empty($pix['qrcode']['charge']) && is_array($pix['qrcode']['charge'])) {
            $pix_qr_text = $findQrText($pix['qrcode']['charge']);
        }
        if (empty($pix_qr_text) && !empty($pix['qrcode']['data']) && is_array($pix['qrcode']['data'])) {
            $pix_qr_text = $findQrText($pix['qrcode']['data']);
        }

        // Fallback: procurar por qualquer string longa que pareça ser o payload (EMV)
        if (empty($pix_qr_text)) {
            $pix_qr_text = $findLongString($pix['qrcode']);
        }
    } else {
        // Caso seja uma string (base64 ou copia-e-cola)
        $maybe = (string)$pix['qrcode'];
        // Detectar se parece com base64 (imagem) ou texto de copia-e-cola
        if (str_starts_with($maybe, 'data:image') || base64_decode($maybe, true) !== false) {
            $pix_qr_image = $maybe;
        } else {
            $pix_qr_text = $maybe;
        }
    }
}

// Debug: registrar payload quando o copia-e-cola estiver vazio (temporário)
if (!empty($pix) && trim((string)$pix_qr_text) === '') {
    $dbgDir = __DIR__ . '/../logs';
    if (!is_dir($dbgDir)) { @mkdir($dbgDir, 0755, true); }
    $dbgFile = $dbgDir . '/doacao_pix_debug.log';
    $entry = [
        'ts' => date('c'),
        'doacao_id' => (int)$id,
        'pix_sample' => $pix,
        'pix_qr_image' => substr((string)$pix_qr_image, 0, 1000),
        'pix_qr_text' => substr((string)$pix_qr_text, 0, 2000),
    ];
    @file_put_contents($dbgFile, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    error_log('[doacao-pix DEBUG missing_copia] id=' . (int)$id . ' wrote ' . $dbgFile);
}

$breadcrumbs = [
    ['label' => 'Início',        'url' => BASE_URL],
    ['label' => 'Fazer Doação',  'url' => BASE_URL . '/doar'],
    ['label' => 'Pagar via PIX'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h4 fw-bold mb-3">Pagamento via Pix</h1>

                    <?php if (($doacao['status'] ?? '') === 'aprovada' || ($doacao['status'] ?? '') === 'aprovado'): ?>
                        <div class="alert alert-success">
                            Pagamento confirmado. Obrigado por apoiar o Cadê Meu Pet?!
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-4">Escaneie o QR Code abaixo no seu app do banco, ou copie o código Pix “copia e cola”.</p>

                        <?php if (!empty($pix_qr_image)): ?>
                            <div class="text-center mb-4">
                                <img src="<?php echo sanitize($pix_qr_image); ?>" alt="QR Code Pix" style="max-width: 260px; width: 100%;" />
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Pix copia e cola</label>
                            <?php if (trim((string)($pix_qr_text ?? '')) !== ''): ?>
                                <textarea class="form-control" rows="4" readonly><?php echo sanitize($pix_qr_text); ?></textarea>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">Código "copia e cola" não está disponível no momento. Tente <strong>Atualizar status</strong> ou aguarde alguns instantes.</div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-outline-primary" id="btnAtualizarStatus" onclick="atualizarStatusPix()">
                                <span id="btnText">Atualizar status</span>
                                <span id="btnLoader" style="display: none;">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    Aguarde...
                                </span>
                            </button>
                            <a class="btn btn-link" href="<?php echo url('/doar'); ?>">Voltar</a>
                        </div>

                        <div id="statusMessage" class="mt-3" style="display: none;"></div>
                    <?php endif; ?>

                    <hr class="my-4" />

                    <div class="small text-muted">
                        <div><strong>ID da doação:</strong> <?php echo (int)$doacao['id']; ?></div>
                        <div><strong>Status:</strong> <?php echo sanitize($doacao['status'] ?? ''); ?></div>
                        <?php if (!empty($doacao['transaction_id'])): ?>
                            <div><strong>TXID:</strong> <?php echo sanitize($doacao['transaction_id']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// ═══════════════════════════════════════════════════════════════════════════
// Polling Automático para Atualizar Status PIX
// ═══════════════════════════════════════════════════════════════════════════

const doacaoId = <?php echo (int)$id; ?>;
const txid = '<?php echo sanitize($doacao['transaction_id'] ?? ''); ?>';
const statusUrl = '<?php echo rtrim((string)BASE_URL, '/'); ?>/api/status-doacao.php';
let pollingInterval = null;
let tentativas = 0;
const MAX_TENTATIVAS = 120; // 10 minutos (30s × 20 tentativas × 2)

// Iniciar polling automático na primeira vez que a página carrega
document.addEventListener('DOMContentLoaded', function() {
    // Iniciar polling apenas se não está aprovado
    if ('<?php echo $doacao['status'] ?? ''; ?>' !== 'aprovada' && '<?php echo $doacao['status'] ?? ''; ?>' !== 'aprovado') {
        console.log('[PIX Polling] Iniciando polling automático...');
        iniciarPolling();
    }
});

/**
 * Inicia polling automático a cada 5 segundos
 */
function iniciarPolling() {
    // Fazer primeira verificação imediatamente
    atualizarStatusPix(true);
    
    // Depois fazer polling a cada 5 segundos
    pollingInterval = setInterval(function() {
        tentativas++;
        console.log(`[PIX Polling] Tentativa ${tentativas}/${MAX_TENTATIVAS}`);
        
        if (tentativas >= MAX_TENTATIVAS) {
            console.log('[PIX Polling] Máximo de tentativas atingido');
            pararPolling();
            return;
        }
        
        atualizarStatusPix(true);
    }, 5000); // 5 segundos
}

/**
 * Para o polling automático
 */
function pararPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
        console.log('[PIX Polling] Polling parado');
    }
}

/**
 * Atualiza o status da doação via AJAX
 * @param {boolean} autoPolling - Se true, é polling automático (silencioso)
 */
function atualizarStatusPix(autoPolling = false) {
    if (!autoPolling) {
        // Mostrar loader no botão
        document.getElementById('btnText').style.display = 'none';
        document.getElementById('btnLoader').style.display = 'inline';
        document.getElementById('btnAtualizarStatus').disabled = true;
    }

    // Fazer requisição AJAX para verificar status
    fetch(statusUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: doacaoId,
            txid: txid
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('[PIX Polling] Resposta:', data);
        
        if (data.ok) {
            if (data.aprovada === true || data.status === 'aprovada') {
                // Pagamento confirmado!
                console.log('[PIX Polling] Pagamento confirmado!');
                pararPolling();

                // Mostrar mensagem de sucesso imediata
                mostrarMensagem(data.message || 'Pagamento confirmado. Obrigado pela sua doação!', 'success');

                // Atualizar indicador de status na página (se presente)
                try {
                    const statusDivs = document.querySelectorAll('.small.text-muted div');
                    statusDivs.forEach(function(div) {
                        if (div.innerHTML.includes('Status:')) {
                            div.innerHTML = '<strong>Status:</strong> <span class="text-success">aprovada</span>';
                        }
                    });
                } catch (err) {
                    console.error(err);
                }

                // Recarregar a página após breve pausa para que o servidor atualize o conteúdo
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else if (data.status === 'processando') {
                // PIX ainda não foi confirmado
                if (!autoPolling) {
                    mostrarMensagem('Pagamento ainda não foi confirmado. Aguarde...', 'warning');
                }
            }
        } else {
            console.error('[PIX Polling] Erro:', data.error);
            if (!autoPolling) {
                mostrarMensagem('Erro ao atualizar status: ' + (data.error || 'Desconhecido'), 'danger');
            }
        }
    })
    .catch(error => {
        console.error('[PIX Polling] Erro na requisição:', error);
        if (!autoPolling) {
            mostrarMensagem('Erro ao conectar com o servidor', 'danger');
        }
    })
    .finally(() => {
        if (!autoPolling) {
            // Restaurar botão
            document.getElementById('btnText').style.display = 'inline';
            document.getElementById('btnLoader').style.display = 'none';
            document.getElementById('btnAtualizarStatus').disabled = false;
        }
    });
}

/**
 * Mostra mensagem na página
 * @param {string} mensagem 
 * @param {string} tipo - 'success', 'danger', 'warning', 'info'
 */
function mostrarMensagem(mensagem, tipo = 'info') {
    const div = document.getElementById('statusMessage');
    div.innerHTML = `<div class="alert alert-${tipo}" role="alert">${sanitizeHtml(mensagem)}</div>`;
    div.style.display = 'block';
    
    // Auto-fechar após 5 segundos (apenas para warning)
    if (tipo === 'warning') {
        setTimeout(() => {
            div.style.display = 'none';
        }, 5000);
    }
}

/**
 * Sanitiza HTML simples
 */
function sanitizeHtml(html) {
    const div = document.createElement('div');
    div.textContent = html;
    return div.innerHTML;
}

// Parar polling quando sair da página
window.addEventListener('beforeunload', function() {
    pararPolling();
});
</script>
