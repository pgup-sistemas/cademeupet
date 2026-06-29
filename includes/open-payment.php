<?php
/**
 * Partial: abre a URL de pagamento EFI em nova aba.
 * Variáveis esperadas:
 *   string $paymentUrl  — URL de checkout do gateway
 *   string $backUrl     — URL de retorno em caso de erro (opcional)
 *   string $lastError   — mensagem de erro para exibir se $paymentUrl estiver vazio (opcional)
 */
$backUrl   = $backUrl   ?? BASE_URL;
$lastError = $lastError ?? '';

if (empty($paymentUrl)) {
    $msg = htmlspecialchars($lastError ?: 'Não foi possível obter o link de pagamento no momento.');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Pagamento</title>'
       . '<meta name="viewport" content="width=device-width,initial-scale=1"></head><body>'
       . "<p>{$msg}</p>"
       . '<p><a href="' . htmlspecialchars($backUrl) . '">Voltar</a></p>'
       . '</body></html>';
    exit;
}

$escapedUrl = htmlspecialchars($paymentUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?><!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Abrindo pagamento ...</title>
</head>
<body>
    <p>Estamos abrindo a página de pagamento em uma nova aba.
       Se nada acontecer, <a id="linkPay" href="<?= $escapedUrl ?>"
       target="_blank" rel="noopener noreferrer">clique aqui para abrir o pagamento</a>.</p>

    <script>
        (function () {
            try {
                var w = window.open(<?= json_encode($paymentUrl) ?>, '_blank');
                if (!w || w.closed) document.getElementById('linkPay').style.display = 'inline';
            } catch (e) {
                document.getElementById('linkPay').style.display = 'inline';
            }
        })();
    </script>
</body>
</html>
