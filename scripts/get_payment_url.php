<?php
// CLI helper: retorna ou gera o payment_url para uma doação
// Usage: php get_payment_url.php --id=34 [--user=2]

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Doacao.php';
require_once __DIR__ . '/../controllers/PagamentoController.php';

function usage()
{
    echo "Usage: php get_payment_url.php --id=DOACAO_ID [--user=USER_ID]\n";
    exit(1);
}

$opts = [];
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--')) {
        $pair = explode('=', substr($arg, 2), 2);
        $key = $pair[0];
        $val = $pair[1] ?? '1';
        $opts[$key] = $val;
    }
}

if (empty($opts['id'])) {
    usage();
}

$doacaoId = (int)$opts['id'];
$userParam = isset($opts['user']) ? (int)$opts['user'] : null;

$doacaoModel = new Doacao();
$doacao = $doacaoModel->findById($doacaoId);
if (empty($doacao)) {
    fwrite(STDERR, "Doação não encontrada: $doacaoId\n");
    exit(2);
}

$paymentUrl = trim((string)($doacao['payment_url'] ?? ''));
if ($paymentUrl !== '') {
    echo $paymentUrl . PHP_EOL;
    exit(0);
}

try {
    $pag = new PagamentoController();

    if (($doacao['tipo'] ?? '') === 'mensal') {
        $usuarioId = (int)($doacao['usuario_id'] ?? 0);
        if ($usuarioId > 0) {
            if ($userParam === null) {
                fwrite(STDERR, "A doação é recorrente e está associada ao usuário $usuarioId. Execute com --user=$usuarioId ou use um usuário autenticado no sistema.\n");
                exit(3);
            }
            if ($userParam !== $usuarioId) {
                fwrite(STDERR, "O --user informado ($userParam) não corresponde ao dono da doação ($usuarioId).\n");
                exit(4);
            }
            $resp = $pag->criarAssinaturaCartaoDoacao($usuarioId, $doacaoId, (float)$doacao['valor']);
            $paymentUrl = (string)($resp['data']['payment_url'] ?? ($resp['payment_url'] ?? ''));
            echo $paymentUrl . PHP_EOL;
            exit(0);
        } else {
            if ($userParam === null) {
                fwrite(STDERR, "A doação é recorrente mas não tem usuário associado. Execute com --user=USER_ID para gerar a assinatura.\n");
                exit(5);
            }
            $resp = $pag->criarAssinaturaCartaoDoacao($userParam, $doacaoId, (float)$doacao['valor']);
            $paymentUrl = (string)($resp['data']['payment_url'] ?? ($resp['payment_url'] ?? ''));
            echo $paymentUrl . PHP_EOL;
            exit(0);
        }
    } else {
        $resp = $pag->criarLinkPagamentoDoacao($doacaoId, (float)$doacao['valor'], 'cartao_avista');
        $paymentUrl = (string)($resp['data']['payment_url'] ?? ($resp['payment_url'] ?? ''));
        echo $paymentUrl . PHP_EOL;
        exit(0);
    }
} catch (Exception $e) {
    fwrite(STDERR, "Erro ao gerar payment_url: " . $e->getMessage() . "\n");
    exit(6);
}
