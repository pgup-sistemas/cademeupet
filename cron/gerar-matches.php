<?php
// Cron: motor de match automático (perdido ↔ achado).
//
// Configurar no cPanel/cron host (a cada 30-60 min):
//   */30 * * * *  php /home/user/public_html/cademeupet/cron/gerar-matches.php >> /dev/null 2>&1
//
// 3 passos: (1) gera assinatura visual (pHash) de fotos pendentes,
// (2) roda matching incremental sobre anúncios ainda não processados,
// (3) notifica por e-mail os matches novos acima do limiar mínimo.

// Só pode rodar via CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Proibido.');
}

define('CRON_START', microtime(true));

require_once __DIR__ . '/../config.php';

$db = getDB();
$limitePorExecucao = 200;

// ── Passo 1: gerar pHash das fotos pendentes ────────────────────────────
$fotoEmbeddingModel = new FotoEmbedding($db);
$imagemMatchService = new ImagemMatchService();

$pendentes = $fotoEmbeddingModel->buscarPendentes($limitePorExecucao);
$fotosOk = 0;
$fotosFalha = 0;

foreach ($pendentes as $foto) {
    try {
        $fotoEmbeddingModel->registrarPendente((int)$foto['foto_id'], (int)$foto['anuncio_id']);
        $caminho = BASE_PATH . '/uploads/anuncios/' . $foto['nome_arquivo'];
        $assinatura = $imagemMatchService->gerarAssinaturaVisual($caminho);

        if ($assinatura === null) {
            $fotoEmbeddingModel->marcarFalha((int)$foto['foto_id'], 'Não foi possível processar a imagem.');
            $fotosFalha++;
            continue;
        }

        $fotoEmbeddingModel->marcarProcessado((int)$foto['foto_id'], $assinatura['hash_perceptual']);
        $fotosOk++;
    } catch (Throwable $e) {
        $fotosFalha++;
        error_log('[cron/gerar-matches] Foto #' . $foto['foto_id'] . ': ' . $e->getMessage());
    }
}

// ── Passo 2: matching incremental sobre anúncios ainda não processados ─
$matchController = new MatchController($db);

$anunciosPendentes = $db->fetchAll(
    "SELECT id FROM anuncios
     WHERE tipo IN ('perdido','encontrado')
       AND status = 'ativo'
       AND matching_processado_em IS NULL
     ORDER BY data_publicacao ASC
     LIMIT ?",
    [$limitePorExecucao]
) ?: [];

$matchesCriados = 0;
$anunciosProcessados = 0;
$anunciosFalha = 0;

foreach ($anunciosPendentes as $row) {
    try {
        $matchesCriados += $matchController->processarAnuncio((int)$row['id']);
        $anunciosProcessados++;
    } catch (Throwable $e) {
        $anunciosFalha++;
        error_log('[cron/gerar-matches] Anúncio #' . $row['id'] . ': ' . $e->getMessage());
    }
}

// ── Passo 3: notificar matches novos acima do limiar ────────────────────
$matchModel = new AnuncioMatch($db);
$pendentesNotificacao = $db->fetchAll(
    "SELECT * FROM anuncio_matches
     WHERE status = 'pendente' AND score_total >= ?
     ORDER BY score_total DESC
     LIMIT ?",
    [defined('MATCHING_SCORE_MINIMO') ? (float)MATCHING_SCORE_MINIMO : 40.0, $limitePorExecucao]
) ?: [];

$notificados = 0;
foreach ($pendentesNotificacao as $match) {
    try {
        if ($matchController->notificar($match)) {
            $notificados++;
        }
    } catch (Throwable $e) {
        error_log('[cron/gerar-matches] Notificação do match #' . $match['id'] . ': ' . $e->getMessage());
    }
}

$elapsed = round(microtime(true) - CRON_START, 2);
echo '[' . date('Y-m-d H:i:s') . "] Fotos: {$fotosOk} ok / {$fotosFalha} falhas. "
    . "Anúncios processados: {$anunciosProcessados} ({$anunciosFalha} falhas), {$matchesCriados} matches criados. "
    . "Notificações enviadas: {$notificados}. ({$elapsed}s)" . PHP_EOL;
exit(0);
