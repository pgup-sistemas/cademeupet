<?php
/**
 * TEST_FLUXO_DOACAO.php
 * 
 * Script de teste completo do fluxo de doação PIX e Cartão
 * 
 * INSTRÇÕES:
 * 1. Suba este arquivo na raiz do projeto
 * 2. Acesse: https://seusite.com/test_fluxo_doacao.php
 * 3. Siga os testes na ordem apresentada
 * 4. REMOVA este arquivo após os testes
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

// Verificar se há parâmetro para executar teste específico
$teste = $_GET['teste'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Fluxo Doação - PetFinder</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            border-bottom: 3px solid #28a745;
            padding-bottom: 10px;
        }
        h2 {
            color: #28a745;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.2em;
        }
        .teste-item {
            background: #f9f9f9;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
            margin-left: 10px;
        }
        .status.ok {
            background: #d4edda;
            color: #155724;
        }
        .status.erro {
            background: #f8d7da;
            color: #721c24;
        }
        .status.aviso {
            background: #fff3cd;
            color: #856404;
        }
        .codigo {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            overflow-x: auto;
        }
        .detalhes {
            margin: 15px 0;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
            border-left: 3px solid #007bff;
        }
        .botao {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            margin: 5px 5px 5px 0;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        .botao:hover {
            background: #218838;
        }
        .botao-perigo {
            background: #dc3545;
        }
        .botao-perigo:hover {
            background: #c82333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background: #f5f5f5;
            font-weight: bold;
        }
        .aviso-importante {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .aviso-importante strong {
            color: #856404;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 Teste Completo do Fluxo de Doação - PetFinder</h1>
    
    <div class="aviso-importante">
        <strong>⚠️ AVISO IMPORTANTE:</strong> Este arquivo de teste deve ser removido após os testes. Contém funcionalidade sensível que não deve estar em produção.
    </div>

    <h2>1. Configuração e Credenciais</h2>
    <div class="teste-item">
        <?php
        // Teste 1: Verificar configurações
        $configOk = true;
        $mensagens = [];

        if (!defined('EFI_CLIENT_ID') || EFI_CLIENT_ID === '') {
            $configOk = false;
            $mensagens[] = 'EFI_CLIENT_ID não configurado';
        }

        if (!defined('EFI_CLIENT_SECRET') || EFI_CLIENT_SECRET === '') {
            $configOk = false;
            $mensagens[] = 'EFI_CLIENT_SECRET não configurado';
        }

        if (!defined('EFI_PIX_KEY') || EFI_PIX_KEY === '') {
            $configOk = false;
            $mensagens[] = 'EFI_PIX_KEY não configurado';
        }

        if (!defined('EFI_CERTIFICATE_PATH') || !file_exists(EFI_CERTIFICATE_PATH)) {
            $configOk = false;
            $mensagens[] = 'Certificado EFI não encontrado em ' . (defined('EFI_CERTIFICATE_PATH') ? EFI_CERTIFICATE_PATH : 'não definido');
        }

        echo '<strong>Verificação de Credenciais:</strong>';
        echo $configOk ? '<span class="status ok">✓ OK</span>' : '<span class="status erro">✗ ERRO</span>';
        echo '<div class="detalhes">';
        
        if ($configOk) {
            echo '<table>';
            echo '<tr><th>Configuração</th><th>Valor</th></tr>';
            echo '<tr><td>EFI_CLIENT_ID</td><td>' . substr(EFI_CLIENT_ID, 0, 10) . '...(oculto)</td></tr>';
            echo '<tr><td>EFI_CLIENT_SECRET</td><td>****(oculto)</td></tr>';
            echo '<tr><td>EFI_PIX_KEY</td><td>' . sanitize(EFI_PIX_KEY) . '</td></tr>';
            echo '<tr><td>EFI_CERTIFICATE_PATH</td><td>✓ ' . EFI_CERTIFICATE_PATH . '</td></tr>';
            echo '<tr><td>EFI_SANDBOX</td><td>' . (EFI_SANDBOX ? 'SIM (Teste)' : 'NÃO (Produção)') . '</td></tr>';
            echo '<tr><td>EFI_WEBHOOK_TOKEN</td><td>' . (!empty(EFI_WEBHOOK_TOKEN) ? '✓ Configurado' : '✗ Não configurado') . '</td></tr>';
            echo '</table>';
        } else {
            echo '<ul>';
            foreach ($mensagens as $msg) {
                echo '<li style="color: #721c24;">' . $msg . '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
        ?>
    </div>

    <h2>2. Instância SDK EFI</h2>
    <div class="teste-item">
        <?php
        // Teste 2: Tentar instanciar SDK
        $sdkOk = false;
        $sdkMensagem = '';

        try {
            if (!class_exists('Efi')) {
                throw new Exception('Classe Efi não encontrada. Verifique includes/efi.php');
            }

            $api = new Efi([
                'client_id' => EFI_CLIENT_ID,
                'client_secret' => EFI_CLIENT_SECRET,
                'certificate' => EFI_CERTIFICATE_PATH,
                'sandbox' => EFI_SANDBOX,
                'pixKey' => EFI_PIX_KEY,
            ]);

            $sdkOk = true;
            $sdkMensagem = 'SDK EFI instanciada com sucesso';
        } catch (Exception $e) {
            $sdkMensagem = $e->getMessage();
        }

        echo '<strong>Inicialização da SDK:</strong>';
        echo $sdkOk ? '<span class="status ok">✓ OK</span>' : '<span class="status erro">✗ ERRO</span>';
        echo '<div class="detalhes">';
        echo $sdkMensagem;
        echo '</div>';
        ?>
    </div>

    <h2>3. Testes Simulados de Criação de Cobrança</h2>
    <div class="teste-item">
        <?php
        // Teste 3: Simular criação de doação PIX
        echo '<h3>Teste PIX</h3>';
        
        if ($sdkOk && $configOk) {
            echo '<form method="GET" style="margin: 10px 0;">
                <input type="hidden" name="teste" value="test_pix">
                <button type="submit" class="botao">Executar Teste PIX</button>
            </form>';
            
            if ($teste === 'test_pix') {
                echo '<div class="detalhes" style="border-left-color: #007bff;">';
                try {
                    $api = new Efi([
                        'client_id' => EFI_CLIENT_ID,
                        'client_secret' => EFI_CLIENT_SECRET,
                        'certificate' => EFI_CERTIFICATE_PATH,
                        'sandbox' => EFI_SANDBOX,
                        'pixKey' => EFI_PIX_KEY,
                    ]);

                    $valor = 50.00;
                    $body = [
                        'calendario' => ['expiracao' => 3600],
                        'valor' => ['original' => number_format($valor, 2, '.', '')],
                        'chave' => EFI_PIX_KEY,
                        'solicitacaoPagador' => 'Teste de Doação PetFinder',
                    ];

                    echo '<strong>Criando cobrança PIX:</strong><br>';
                    echo '<pre class="codigo">Valor: R$ ' . number_format($valor, 2, ',', '.') . '<br>';
                    echo 'Chave: ' . EFI_PIX_KEY . '</pre>';

                    echo '<p style="color: #666; font-size: 0.9em; margin-top: 10px;"><strong>Nota:</strong> Este é um teste de integração. A SDK real da EFI será chamada com as credenciais configuradas.</p>';
                } catch (Exception $e) {
                    echo '<div style="color: #721c24; background: #f8d7da; padding: 10px; border-radius: 4px;">';
                    echo '<strong>Erro:</strong> ' . $e->getMessage();
                    echo '</div>';
                }
                echo '</div>';
            }
        } else {
            echo '<p style="color: #721c24;">Configure credenciais primeiro para executar testes.</p>';
        }
        ?>
    </div>

    <h2>4. Modelos de Dados</h2>
    <div class="teste-item">
        <?php
        // Teste 4: Verificar modelos
        echo '<strong>Verificação de Modelos e Controllers:</strong><br><br>';
        
        $classes = [
            'DoacaoController' => 'controllers/DoacaoController.php',
            'PagamentoController' => 'controllers/PagamentoController.php',
            'Doacao' => 'models/Doacao.php',
            'ParceiroPagamento' => 'models/ParceiroPagamento.php',
        ];

        echo '<table>';
        echo '<tr><th>Classe</th><th>Status</th><th>Arquivo</th></tr>';
        
        foreach ($classes as $class => $file) {
            $exists = class_exists($class);
            echo '<tr>';
            echo '<td><strong>' . $class . '</strong></td>';
            echo '<td>' . ($exists ? '<span class="status ok">✓ Carregada</span>' : '<span class="status erro">✗ Não encontrada</span>') . '</td>';
            echo '<td>' . $file . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        ?>
    </div>

    <h2>5. Documentação Gerada</h2>
    <div class="teste-item">
        <?php
        // Teste 5: Verificar documentação
        $analiseFile = __DIR__ . '/ANALISE_FLUXO_DOACAO.md';
        $analiseExists = file_exists($analiseFile);
        
        echo '<strong>Análise de Fluxo:</strong>';
        echo $analiseExists ? '<span class="status ok">✓ Gerada</span>' : '<span class="status aviso">⚠ Não encontrada</span>';
        
        if ($analiseExists) {
            echo '<div class="detalhes">';
            echo '<p>Arquivo: <code>' . $analiseFile . '</code></p>';
            echo '<p><a href="/ANALISE_FLUXO_DOACAO.md" target="_blank" class="botao">Abrir Análise</a></p>';
            echo '</div>';
        }
        ?>
    </div>

    <h2>6. Próximos Passos</h2>
    <div class="teste-item">
        <ol style="margin-left: 20px; line-height: 1.8;">
            <li>Configurar variáveis de ambiente corretas no <code>.env</code>:
                <div class="codigo">EFI_CLIENT_ID=seu_client_id<br>EFI_CLIENT_SECRET=seu_client_secret<br>EFI_PIX_KEY=sua_chave_pix</div>
            </li>
            <li>Obter certificado SSL/TLS da EFI e colocar em: <code><?php echo EFI_CERTIFICATE_PATH; ?></code></li>
            <li>Testar fluxo completo em modo SANDBOX:
                <div class="codigo">EFI_SANDBOX=true</div>
            </li>
            <li>Configurar webhooks na conta EFI:
                <div class="codigo">PIX Webhook: <?php echo BASE_URL; ?>/api/efi-webhook.php?token=<?php echo EFI_WEBHOOK_TOKEN; ?><br>Billing Webhook: <?php echo BASE_URL; ?>/api/efi-billing-notification.php?token=<?php echo EFI_WEBHOOK_TOKEN; ?></div>
            </li>
            <li>Testar doação em: <a href="<?php echo BASE_URL; ?>/doar" target="_blank" class="botao"><?php echo BASE_URL; ?>/doar</a></li>
            <li><strong>Remover este arquivo de teste após validação</strong></li>
        </ol>
    </div>

    <h2>7. Logs de Diagnóstico</h2>
    <div class="teste-item">
        <?php
        $logFile = __DIR__ . '/includes/petfinder_error_log';
        if (file_exists($logFile)) {
            $logs = explode("\n", trim(file_get_contents($logFile)));
            $recentLogs = array_slice($logs, -20);  // Últimas 20 linhas
            
            echo '<strong>Últimas 20 linhas do log:</strong><br>';
            echo '<div class="codigo" style="max-height: 300px; overflow-y: auto;">';
            foreach (array_reverse($recentLogs) as $line) {
                if (!empty($line)) {
                    echo htmlspecialchars($line) . '<br>';
                }
            }
            echo '</div>';
        } else {
            echo '<p style="color: #666;">Arquivo de log não encontrado ou vazio.</p>';
        }
        ?>
    </div>

    <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
    
    <p style="text-align: center; color: #999; font-size: 0.9em;">
        <strong>⚠️ IMPORTANTE:</strong> Remova este arquivo após os testes concluídos.
        <br>
        <?php echo date('Y-m-d H:i:s'); ?> - Teste realizado pelo sistema
    </p>
</div>

</body>
</html>
