<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$usuarioId      = (int)(getUserId() ?? 0);
$usuarioModel   = new Usuario();
$inscricaoModel = new ParceiroInscricao();
$contratoModel  = new ParceiroContrato();

$usuario   = $usuarioModel->findById($usuarioId);
$inscricao = $inscricaoModel->findByUserId($usuarioId);

if (!$inscricao || $inscricao['status'] !== 'aprovada') {
    http_response_code(403);
    die('Acesso não autorizado.');
}

$plano         = (string)($_GET['plano'] ?? 'basico');
$periodicidade = (string)($_GET['periodicidade'] ?? 'mensal');

$validPlanos         = ['basico', 'destaque'];
$validPeriodicidades = ['mensal', 'anual'];

if (!in_array($plano, $validPlanos, true))         $plano         = 'basico';
if (!in_array($periodicidade, $validPeriodicidades, true)) $periodicidade = 'mensal';

$valorMensal = $plano === 'destaque'
    ? (float)getConfig('parceiro_plano_destaque_mensal', '129.90')
    : (float)getConfig('parceiro_plano_basico_mensal',   '79.90');

// Busca aceite registrado, se existir
$aceite = $contratoModel->findAceiteAtivo($usuarioId, $plano, $periodicidade);

// Prepara variáveis do template
$contrato_usuario       = $usuario;
$contrato_inscricao     = $inscricao;
$contrato_plano         = $plano;
$contrato_periodicidade = $periodicidade;
$contrato_valor_mensal  = $valorMensal;
$contrato_aceite        = $aceite ?: null;
$contrato_versao        = ParceiroContrato::VERSAO_ATUAL;

// Captura HTML do template
ob_start();
include __DIR__ . '/../includes/parceiro-contrato-template.php';
$contratoHtml = ob_get_clean();

// ── Monta HTML completo para o DomPDF ────────────────────────────────────────
$nomeArquivo = 'contrato-parceiro-' . date('Ymd') . '.pdf';

$htmlPdf = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11pt; color: #212529; line-height: 1.65; margin: 0; padding: 0; }
  .contrato-body { padding: 0; }
  .contrato-cabecalho { text-align: center; margin-bottom: 1.5rem; }
  .contrato-secao { font-size: 10pt; font-weight: bold; text-transform: uppercase; letter-spacing: .03em; margin-top: 1.4rem; margin-bottom: .4rem; }
  .contrato-tabela { width: 100%; border-collapse: collapse; font-size: 10pt; margin-bottom: .5rem; }
  .contrato-tabela td { border: 1px solid #dee2e6; padding: .35rem .6rem; vertical-align: top; }
  .fw-bold, .fw-semibold { font-weight: bold; }
  .ms-3 { margin-left: 1.5rem; }
  .fw-semibold { font-weight: 600; }
  .text-muted { color: #6c757d; }
  .small { font-size: 9pt; }
  .fst-italic { font-style: italic; }
  hr { border: none; border-top: 1px solid #dee2e6; margin: 1rem 0; }
  ol { margin: .5rem 0; padding-left: 1.5rem; }
  li { margin-bottom: .3rem; }
  p { margin: .5rem 0; }
  .row { width: 100%; }
  .col-6 { display: inline-block; width: 45%; text-align: center; vertical-align: top; }
  .contrato-assinatura .row { display: block; }
  .contrato-assinatura .col-6 { width: 48%; }
  .mt-4 { margin-top: 1.5rem; }
  .mt-3 { margin-top: 1rem; }
  .text-center { text-align: center; }
  .fs-5 { font-size: 13pt; }
  table.table { width: 100%; border-collapse: collapse; }
  table.table td { border: 1px solid #dee2e6; padding: .3rem .5rem; }
</style>
</head>
<body>' . $contratoHtml . '</body>
</html>';

// ── Gera PDF com DomPDF ───────────────────────────────────────────────────────
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('chroot', BASE_PATH);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($htmlPdf, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
$dompdf->stream($nomeArquivo, ['Attachment' => true]);
exit;
