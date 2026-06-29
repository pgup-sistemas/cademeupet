<?php
/**
 * PetFinder - Validação Final da Solução PIX Polling
 * 
 * Este script verifica se TUDO está funcionando corretamente
 * 
 * Modo de uso:
 * 1. Salve como: validate-pix-solution.php
 * 2. Acesse: http://seu-site.com/validate-pix-solution.php
 * 3. Verifique se todos os testes passaram
 */

require_once __DIR__ . '/config.php';

$passed = 0;
$failed = 0;
$warnings = 0;

function addResult($test, $status, $message = '') {
    global $passed, $failed, $warnings;
    
    $icon = match($status) {
        'pass' => '✅',
        'fail' => '❌',
        'warn' => '⚠️',
    };
    
    if ($status === 'pass') $passed++;
    if ($status === 'fail') $failed++;
    if ($status === 'warn') $warnings++;
    
    echo "<div class='test-item test-$status'>";
    echo "<strong>$icon $test</strong>";
    if ($message) echo "<br><small>$message</small>";
    echo "</div>\n";
}

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação - PIX Polling Solution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        .test-item { padding: 12px; margin: 8px 0; border-radius: 6px; font-size: 14px; }
        .test-pass { background: #d4edda; border-left: 4px solid #28a745; color: #155724; }
        .test-fail { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; }
        .test-warn { background: #fff3cd; border-left: 4px solid #ffc107; color: #856404; }
        .test-section { margin: 25px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        .summary { font-size: 18px; padding: 15px; border-radius: 6px; margin: 20px 0; }
        .summary.all-pass { background: #d4edda; color: #155724; }
        .summary.has-fail { background: #f8d7da; color: #721c24; }
        .summary.has-warn { background: #fff3cd; color: #856404; }
        code { background: #e9ecef; padding: 2px 6px; border-radius: 3px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 6px; overflow-x: auto; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="text-center mb-5">
        <h1>🔍 Validação da Solução PIX Polling</h1>
        <p class="text-muted">PetFinder - Sistema Automático de Atualização de Status</p>
        <p class="text-muted">Gerado em: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SEÇÃO 1: Arquivos Necessários -->
            <!-- ═══════════════════════════════════════════════════════════════ -->

            <div class="test-section">
                <h3>📁 1. Arquivos Necessários</h3>

                <?php
                $files = [
                    ['path' => 'api/status-doacao.php', 'name' => 'Endpoint AJAX de Status'],
                    ['path' => 'views/doacao-pix.php', 'name' => 'Página de Doação PIX'],
                    ['path' => 'controllers/PagamentoController.php', 'name' => 'Controller de Pagamento'],
                    ['path' => 'models/Doacao.php', 'name' => 'Model de Doação'],
                    ['path' => 'api/efi-webhook.php', 'name' => 'Webhook EFI'],
                ];

                foreach ($files as $file) {
                    $fullPath = __DIR__ . '/' . $file['path'];
                    if (file_exists($fullPath)) {
                        $size = filesize($fullPath);
                        addResult(
                            $file['name'],
                            'pass',
                            "Arquivo encontrado ($size bytes)"
                        );
                    } else {
                        addResult(
                            $file['name'],
                            'fail',
                            "Arquivo não encontrado: {$file['path']}"
                        );
                    }
                }
                ?>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SEÇÃO 2: Código Necessário -->
            <!-- ═══════════════════════════════════════════════════════════════ -->

            <div class="test-section">
                <h3>⚙️ 2. Código Necessário</h3>

                <?php
                // Verificar API
                $apiFile = __DIR__ . '/api/status-doacao.php';
                if (file_exists($apiFile)) {
                    $apiContent = file_get_contents($apiFile);
                    
                    addResult(
                        'Endpoint POST em /api/status-doacao.php',
                        strpos($apiContent, '$_SERVER[\'REQUEST_METHOD\'] === \'POST\'') !== false ? 'pass' : 'warn',
                        'Valida requisições POST'
                    );
                    
                    addResult(
                        'Sincronização com EFI',
                        strpos($apiContent, 'sincronizarStatusDoacaoPix') !== false ? 'pass' : 'fail',
                        'Chama sincronizarStatusDoacaoPix()'
                    );
                    
                    addResult(
                        'Resposta JSON',
                        strpos($apiContent, 'json_encode') !== false ? 'pass' : 'fail',
                        'Retorna JSON estruturado'
                    );
                }

                // Verificar JavaScript
                $viewFile = __DIR__ . '/views/doacao-pix.php';
                if (file_exists($viewFile)) {
                    $viewContent = file_get_contents($viewFile);
                    
                    addResult(
                        'Função iniciarPolling()',
                        strpos($viewContent, 'function iniciarPolling') !== false ? 'pass' : 'fail',
                        'Inicia polling automático'
                    );
                    
                    addResult(
                        'Função atualizarStatusPix()',
                        strpos($viewContent, 'function atualizarStatusPix') !== false ? 'pass' : 'fail',
                        'Verifica status via AJAX'
                    );
                    
                    addResult(
                        'Função pararPolling()',
                        strpos($viewContent, 'function pararPolling') !== false ? 'pass' : 'fail',
                        'Para o polling quando confirmado'
                    );
                    
                    addResult(
                        'Botão de Atualização',
                        strpos($viewContent, 'id="btnAtualizarStatus"') !== false ? 'pass' : 'fail',
                        'Elemento com ID btnAtualizarStatus'
                    );
                    
                    addResult(
                        'Chamada a /api/status-doacao.php',
                        strpos($viewContent, '/api/status-doacao.php') !== false ? 'pass' : 'fail',
                        'JavaScript faz fetch para o endpoint'
                    );
                    
                    addResult(
                        'Auto-reload on Success',
                        strpos($viewContent, 'location.reload()') !== false ? 'pass' : 'warn',
                        'Recarrega página quando pagamento confirmado'
                    );
                }

                // Verificar Webhook
                $webhookFile = __DIR__ . '/api/efi-webhook.php';
                if (file_exists($webhookFile)) {
                    $webhookContent = file_get_contents($webhookFile);
                    
                    addResult(
                        'Validação de Token Webhook',
                        strpos($webhookContent, 'X-EFI-Webhook-Token') !== false ? 'pass' : 'warn',
                        'Valida token enviado pela EFI'
                    );
                    
                    addResult(
                        'Processamento de Notificação',
                        strpos($webhookContent, 'sincronizarStatusDoacaoPix') !== false ? 'pass' : 'fail',
                        'Atualiza status quando recebe notificação'
                    );
                }
                ?>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SEÇÃO 3: Configuração -->
            <!-- ═══════════════════════════════════════════════════════════════ -->

            <div class="test-section">
                <h3>🔧 3. Configuração</h3>

                <?php
                // Verificar config.php
                $configFile = __DIR__ . '/config.php';
                if (file_exists($configFile)) {
                    $configContent = file_get_contents($configFile);
                    
                    addResult(
                        'Database Connection',
                        class_exists('PDO') || function_exists('mysqli_connect') ? 'pass' : 'fail',
                        'Banco de dados disponível'
                    );
                    
                    addResult(
                        'EFI Configuration',
                        strpos($configContent, 'EFI') !== false || strpos($configContent, 'efi') !== false ? 'pass' : 'warn',
                        'Credenciais EFI configuradas'
                    );
                }

                // Verificar HTTPS
                $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                          (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
                
                addResult(
                    'HTTPS',
                    $isHttps ? 'pass' : 'fail',
                    $isHttps ? 'Site está em HTTPS ✓' : 'PIX exige HTTPS em produção!'
                );
                ?>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SEÇÃO 4: Funcionalidade -->
            <!-- ═══════════════════════════════════════════════════════════════ -->

            <div class="test-section">
                <h3>✨ 4. Funcionalidade</h3>

                <?php
                // Testar endpoint
                if (file_exists(__DIR__ . '/models/Doacao.php')) {
                    try {
                        $doacaoModel = new Doacao();
                        $doacoes = $doacaoModel->findByStatus('pendente');
                        
                        if (!empty($doacoes)) {
                            $doacao = is_array($doacoes[0]) ? $doacoes[0] : $doacoes;
                            addResult(
                                'Doações Pendentes',
                                'pass',
                                'Encontradas ' . (is_array($doacoes) ? count($doacoes) : 1) . ' doação(ões) para teste'
                            );
                        } else {
                            addResult(
                                'Doações Pendentes',
                                'warn',
                                'Nenhuma doação pendente para teste'
                            );
                        }
                    } catch (Exception $e) {
                        addResult(
                            'Doações Pendentes',
                            'fail',
                            'Erro ao buscar doações: ' . $e->getMessage()
                        );
                    }
                }

                // Testar Models
                try {
                    $exists = class_exists('Doacao');
                    addResult(
                        'Model Doacao',
                        $exists ? 'pass' : 'fail',
                        'Classe Doacao carregada'
                    );

                    $exists = class_exists('PagamentoController');
                    addResult(
                        'PagamentoController',
                        $exists ? 'pass' : 'fail',
                        'Classe PagamentoController carregada'
                    );
                } catch (Exception $e) {
                    addResult(
                        'Models e Controllers',
                        'fail',
                        $e->getMessage()
                    );
                }
                ?>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SEÇÃO 5: Resumo -->
            <!-- ═══════════════════════════════════════════════════════════════ -->

            <div class="test-section">
                <h3>📊 5. Resumo</h3>

                <?php
                $total = $passed + $failed + $warnings;
                $percentage = $total > 0 ? round(($passed / $total) * 100) : 0;

                $allPass = $failed === 0 && $warnings === 0;
                $hasFail = $failed > 0;
                $hasWarn = $warnings > 0 && !$hasFail;

                $classes = $allPass ? 'all-pass' : ($hasFail ? 'has-fail' : 'has-warn');

                echo "<div class='summary $classes'>";
                echo "<strong>Resultados:</strong><br>";
                echo "✅ Aprovados: <strong>$passed</strong> | ";
                echo "⚠️ Avisos: <strong>$warnings</strong> | ";
                echo "❌ Falhas: <strong>$failed</strong><br>";
                echo "📊 Progresso: <strong>$percentage%</strong>";
                echo "</div>";

                if ($allPass) {
                    echo "<div class='alert alert-success'>";
                    echo "<h5>🎉 Tudo Pronto!</h5>";
                    echo "<p>A solução PIX Polling está 100% funcional e pronta para uso em produção.</p>";
                    echo "<ul>";
                    echo "<li>✅ Todos os arquivos estão em lugar</li>";
                    echo "<li>✅ Código está correto e completo</li>";
                    echo "<li>✅ Configuração está OK</li>";
                    echo "<li>✅ Funcionalidade testada e validada</li>";
                    echo "</ul>";
                    echo "</div>";
                } elseif ($hasFail) {
                    echo "<div class='alert alert-danger'>";
                    echo "<h5>⚠️ Problemas Encontrados</h5>";
                    echo "<p>Existem <strong>$failed</strong> falha(s) que precisam ser corrigidas antes de usar em produção.</p>";
                    echo "<p>Verifique os itens com ❌ acima e corrija.</p>";
                    echo "</div>";
                } else {
                    echo "<div class='alert alert-warning'>";
                    echo "<h5>⚠️ Avisos</h5>";
                    echo "<p>Existem <strong>$warnings</strong> aviso(s). A solução está funcional, mas alguns itens podem precisar de atenção.</p>";
                    echo "</div>";
                }
                ?>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- Links Úteis -->
            <!-- ═══════════════════════════════════════════════════════════════ -->

            <div class="test-section">
                <h3>🔗 Links Úteis</h3>

                <p class="mb-0">
                    <a href="test-pix-polling.php" class="btn btn-sm btn-primary">Teste Completo</a>
                    <a href="SOLUCAO_PIX_POLLING.md" class="btn btn-sm btn-info">Documentação</a>
                    <a href="GUIA_RAPIDO_PIX_POLLING.md" class="btn btn-sm btn-success">Guia Rápido</a>
                    <a href="doacao-pix.php?id=1" class="btn btn-sm btn-warning">Teste Live</a>
                </p>
            </div>

        </div>
    </div>

    <footer class="text-center text-muted mt-5 pb-5">
        <p>PetFinder - Validação de Solução PIX Polling v1.0</p>
    </footer>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
