<?php
/**
 * PetFinder - Teste da Solução de Status PIX
 * 
 * Script para testar:
 * 1. Se o endpoint /api/status-doacao.php está funcionando
 * 2. Se o JavaScript está sendo renderizado corretamente
 * 3. Se a sincronização de status funciona
 * 4. Se o webhook é processado corretamente
 * 
 * Usar: http://petfinder.local/test-pix-polling.php
 */

require_once __DIR__ . '/config.php';

// Redirecionar para HTTPS em produção
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    // Está em HTTPS
} else if (empty($_SERVER['HTTPS'])) {
    // header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    // exit;
}

$testResults = [];

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Teste - Polling PIX</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .test-section { margin: 20px 0; }
        .test-item { padding: 10px; margin: 5px 0; border-radius: 4px; }
        .test-pass { background: #d4edda; color: #155724; }
        .test-fail { background: #f8d7da; color: #721c24; }
        .test-warn { background: #fff3cd; color: #856404; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; max-height: 300px; overflow: auto; }
    </style>
</head>
<body>
<div class='container py-5'>
    <h1>🧪 Teste de Polling PIX - PetFinder</h1>
    <p class='text-muted'>Data: " . date('Y-m-d H:i:s') . "</p>
";

// ═══════════════════════════════════════════════════════════════════════════
// TESTE 1: Verificar Endpoint /api/status-doacao.php
// ═══════════════════════════════════════════════════════════════════════════

echo "<div class='test-section'>";
echo "<h3>1️⃣ Verificar Arquivo /api/status-doacao.php</h3>";

$apiFile = __DIR__ . '/api/status-doacao.php';
if (file_exists($apiFile)) {
    echo "<div class='test-item test-pass'>✅ Arquivo existe: /api/status-doacao.php</div>";
    $fileSize = filesize($apiFile);
    echo "<div class='test-item'>Tamanho: " . number_format($fileSize) . " bytes</div>";
} else {
    echo "<div class='test-item test-fail'>❌ Arquivo NÃO existe: /api/status-doacao.php</div>";
}

echo "</div>";

// ═══════════════════════════════════════════════════════════════════════════
// TESTE 2: Testar Endpoint via cURL
// ═══════════════════════════════════════════════════════════════════════════

echo "<div class='test-section'>";
echo "<h3>2️⃣ Testar Requisição POST para /api/status-doacao.php</h3>";

if (function_exists('curl_init')) {
    // Procurar por uma doação pendente para teste
    $doacaoModel = new Doacao();
    $doacoes = $doacaoModel->findByStatus('pendente');
    
    if (!empty($doacoes)) {
        $testeDoacao = is_array($doacoes[0]) ? $doacoes[0] : $doacoes;
        $testId = (int)$testeDoacao['id'];
        $testTxid = (string)($testeDoacao['transaction_id'] ?? '');
        
        if ($testTxid) {
            $url = 'http://' . $_SERVER['HTTP_HOST'] . '/api/status-doacao.php';
            $payload = json_encode(['id' => $testId, 'txid' => $testTxid]);
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            echo "<div class='test-item'>";
            echo "URL: <code>$url</code><br>";
            echo "Payload: <code>ID=$testId, TXID=$testTxid</code><br>";
            echo "HTTP Code: <strong>$httpCode</strong><br>";
            
            if ($httpCode === 200) {
                echo "<div class='test-pass'>✅ Requisição OK (HTTP 200)</div>";
                
                $data = json_decode($response, true);
                echo "<strong>Resposta JSON:</strong>";
                echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                
                if ($data['ok'] === true) {
                    echo "<div class='test-pass'>✅ Resposta OK = true</div>";
                    echo "<div class='test-item'>Status retornado: <strong>" . htmlspecialchars($data['status'] ?? '') . "</strong></div>";
                } else {
                    echo "<div class='test-fail'>❌ Resposta OK = false</div>";
                    echo "<div class='test-item'>Erro: " . htmlspecialchars($data['error'] ?? 'desconhecido') . "</div>";
                }
            } else {
                echo "<div class='test-fail'>❌ HTTP $httpCode</div>";
                if ($error) {
                    echo "<div class='test-item'>cURL Error: $error</div>";
                }
                echo "<strong>Resposta:</strong>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
            echo "</div>";
        } else {
            echo "<div class='test-warn'>⚠️ Doação pendente sem TXID para teste</div>";
        }
    } else {
        echo "<div class='test-warn'>⚠️ Nenhuma doação pendente encontrada para teste</div>";
    }
} else {
    echo "<div class='test-warn'>⚠️ cURL não está disponível</div>";
}

echo "</div>";

// ═══════════════════════════════════════════════════════════════════════════
// TESTE 3: Verificar se o JavaScript está na página de doação
// ═══════════════════════════════════════════════════════════════════════════

echo "<div class='test-section'>";
echo "<h3>3️⃣ Verificar JavaScript na Página de Doação PIX</h3>";

$paginaFile = __DIR__ . '/views/doacao-pix.php';
$paginaContent = file_get_contents($paginaFile);

$checks = [
    'iniciarPolling' => 'Função iniciarPolling()',
    'atualizarStatusPix' => 'Função atualizarStatusPix()',
    'pararPolling' => 'Função pararPolling()',
    'btnAtualizarStatus' => 'Elemento com ID btnAtualizarStatus',
    '/api/status-doacao.php' => 'Chamada para /api/status-doacao.php',
];

foreach ($checks as $pattern => $description) {
    if (strpos($paginaContent, $pattern) !== false) {
        echo "<div class='test-item test-pass'>✅ $description encontrado</div>";
    } else {
        echo "<div class='test-item test-fail'>❌ $description NÃO encontrado</div>";
    }
}

echo "</div>";

// ═══════════════════════════════════════════════════════════════════════════
// TESTE 4: Verificar Models e Controllers
// ═══════════════════════════════════════════════════════════════════════════

echo "<div class='test-section'>";
echo "<h3>4️⃣ Verificar Modelos e Controladores</h3>";

$checks = [
    ['file' => __DIR__ . '/models/Doacao.php', 'name' => 'Model Doacao'],
    ['file' => __DIR__ . '/controllers/PagamentoController.php', 'name' => 'Controller Pagamento'],
];

foreach ($checks as $check) {
    if (file_exists($check['file'])) {
        echo "<div class='test-item test-pass'>✅ " . $check['name'] . " existe</div>";
    } else {
        echo "<div class='test-item test-fail'>❌ " . $check['name'] . " NÃO existe</div>";
    }
}

// Verificar se PagamentoController tem o método sincronizarStatusDoacaoPix
$controllerContent = file_get_contents(__DIR__ . '/controllers/PagamentoController.php');
if (strpos($controllerContent, 'sincronizarStatusDoacaoPix') !== false) {
    echo "<div class='test-item test-pass'>✅ Método sincronizarStatusDoacaoPix() existe</div>";
} else {
    echo "<div class='test-item test-fail'>❌ Método sincronizarStatusDoacaoPix() NÃO existe</div>";
}

echo "</div>";

// ═══════════════════════════════════════════════════════════════════════════
// TESTE 5: Verificar Configuração EFI
// ═══════════════════════════════════════════════════════════════════════════

echo "<div class='test-section'>";
echo "<h3>5️⃣ Verificar Configuração EFI</h3>";

$webhookFile = __DIR__ . '/api/efi-webhook.php';
if (file_exists($webhookFile)) {
    echo "<div class='test-item test-pass'>✅ Webhook existe: /api/efi-webhook.php</div>";
    
    $webhookContent = file_get_contents($webhookFile);
    $checks = [
        'sincronizarStatusDoacaoPix' => 'Chamada a sincronizarStatusDoacaoPix',
        'X-EFI-Webhook-Token' => 'Validação de Token',
    ];
    
    foreach ($checks as $pattern => $description) {
        if (strpos($webhookContent, $pattern) !== false) {
            echo "<div class='test-item test-pass'>✅ $description encontrada</div>";
        } else {
            echo "<div class='test-item test-warn'>⚠️ $description NÃO encontrada</div>";
        }
    }
} else {
    echo "<div class='test-item test-fail'>❌ Webhook NÃO existe: /api/efi-webhook.php</div>";
}

echo "</div>";

// ═══════════════════════════════════════════════════════════════════════════
// TESTE 6: Listar Doações Pendentes
// ═══════════════════════════════════════════════════════════════════════════

echo "<div class='test-section'>";
echo "<h3>6️⃣ Doações Pendentes (Últimas 5)</h3>";

try {
    $doacaoModel = new Doacao();
    $doacoes = $doacaoModel->findByStatus('pendente');
    
    if (!empty($doacoes)) {
        // Se encontrou, transformar em array de arrays se necessário
        if (!is_array($doacoes[0])) {
            $doacoes = [$doacoes];
        }
        
        echo "<table class='table table-sm table-striped'>";
        echo "<thead><tr><th>ID</th><th>Status</th><th>TXID</th><th>Criado em</th></tr></thead>";
        echo "<tbody>";
        
        $contador = 0;
        foreach ($doacoes as $doacao) {
            if ($contador >= 5) break;
            $contador++;
            
            echo "<tr>";
            echo "<td>" . (int)$doacao['id'] . "</td>";
            echo "<td><span class='badge bg-warning'>" . htmlspecialchars($doacao['status'] ?? '') . "</span></td>";
            echo "<td><code>" . htmlspecialchars(substr($doacao['transaction_id'] ?? '', 0, 20)) . "...</code></td>";
            echo "<td>" . htmlspecialchars($doacao['created_at'] ?? '') . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<div class='test-warn'>⚠️ Nenhuma doação pendente encontrada</div>";
    }
} catch (Exception $e) {
    echo "<div class='test-fail'>❌ Erro ao buscar doações: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// ═══════════════════════════════════════════════════════════════════════════
// Footer
// ═══════════════════════════════════════════════════════════════════════════

echo "
    <div class='test-section alert alert-info'>
        <h4>📝 Como Testar Manualmente:</h4>
        <ol>
            <li>Acesse uma doação pendente em: <code>/doacao-pix?id=NUM</code></li>
            <li>Abra o DevTools do navegador (F12)</li>
            <li>Vá para a aba 'Console'</li>
            <li>Você verá logs como: <code>[PIX Polling] Iniciando polling automático...</code></li>
            <li>A cada 5 segundos, a página chamará <code>/api/status-doacao.php</code></li>
            <li>Quando o pagamento for confirmado, verá: <code>[PIX Polling] Pagamento confirmado!</code></li>
            <li>A página será recarregada automaticamente</li>
        </ol>
    </div>

    <div class='test-section alert alert-info'>
        <h4>🔗 Links Úteis:</h4>
        <ul>
            <li><a href='/doacao-pix?id=1' class='btn btn-sm btn-primary'>Teste com ID=1</a></li>
            <li><a href='/api/status-doacao.php' class='btn btn-sm btn-secondary'>Testar Endpoint (GET)</a></li>
            <li><a href='/admin.php' class='btn btn-sm btn-warning'>Admin</a></li>
        </ul>
    </div>

    <hr>
    <p class='text-muted text-center'>
        Gerado em: " . date('Y-m-d H:i:s') . "<br>
        PHP " . PHP_VERSION . " | MySQL " . (defined('MYSQL_VERSION') ? MYSQL_VERSION : 'conectar para verificar') . "
    </p>
</div>
</body>
</html>
";
?>
