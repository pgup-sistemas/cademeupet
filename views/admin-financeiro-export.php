<?php
require_once __DIR__ . '/../config.php';

requireAdmin();

$tab = isset($_GET['tab']) ? trim((string)$_GET['tab']) : 'doacoes';
$tab = in_array($tab, ['doacoes', 'parceiros'], true) ? $tab : 'doacoes';

$format = isset($_GET['format']) ? strtolower(trim((string)$_GET['format'])) : 'csv';
$format = in_array($format, ['csv', 'pdf'], true) ? $format : 'csv';

$status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$allowedStatus = ['', 'pendente', 'aprovada', 'cancelada', 'estornada'];
if (!in_array($status, $allowedStatus, true)) {
    $status = '';
}

$partnerStatus = isset($_GET['partner_status']) ? trim((string)$_GET['partner_status']) : '';
$allowedPartnerStatus = ['', 'pendente', 'aprovado', 'recusado'];
if (!in_array($partnerStatus, $allowedPartnerStatus, true)) {
    $partnerStatus = '';
}

$partnerMes = isset($_GET['partner_mes']) ? trim((string)$_GET['partner_mes']) : '';
if ($partnerMes !== '' && !preg_match('/^\d{4}-\d{2}$/', $partnerMes)) {
    $partnerMes = '';
}

$now = date('Y-m-d_H-i');

if ($tab === 'doacoes') {
    $doacaoModel = new Doacao();
    $rows = $doacaoModel->findAll(10000, 0, $status !== '' ? $status : null);

    if ($format === 'csv') {
        $filename = 'relatorio_doacoes_' . $now . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Data', 'Valor', 'Doador', 'Método', 'Status', 'Transaction ID']);
        foreach ($rows as $d) {
            fputcsv($out, [
                (string)($d['id'] ?? ''),
                (string)($d['data_doacao'] ?? ''),
                (string)($d['valor'] ?? ''),
                (string)($d['nome_doador'] ?? ''),
                (string)($d['metodo_pagamento'] ?? ''),
                (string)($d['status'] ?? ''),
                (string)($d['transaction_id'] ?? ''),
            ]);
        }
        fclose($out);
        exit;
    }

    if (!class_exists('Dompdf\\Dompdf')) {
        setFlashMessage('Exportação PDF indisponível. Instale dependências via Composer.', MSG_ERROR);
        redirect('/admin/financeiro?tab=doacoes');
    }

    $totalRegistros = is_array($rows) ? count($rows) : 0;
    $totalValor = 0.0;
    foreach ($rows as $d) {
        $totalValor += (float)($d['valor'] ?? 0);
    }

    $title = 'Relatório de Doações';
    $subtitle = $status !== '' ? ('Filtro status: ' . $status) : 'Sem filtro de status';

    $html = '<html><head><meta charset="utf-8"><style>'
        . 'body{font-family:DejaVu Sans, Arial, sans-serif;font-size:12px;}'
        . '.brand{font-size:12px;color:#666;margin:0 0 10px 0;}'
        . 'h1{font-size:18px;margin:0 0 4px 0;}'
        . '.sub{color:#666;margin:0 0 12px 0;}'
        . '.summary{margin:0 0 12px 0;padding:8px;border:1px solid #eee;background:#fafafa;}'
        . '.summary strong{display:inline-block;min-width:120px;}'
        . 'table{width:100%;border-collapse:collapse;}'
        . 'th,td{border:1px solid #ddd;padding:6px;vertical-align:top;}'
        . 'th{background:#f5f5f5;text-align:left;}'
        . '</style></head><body>';

    $html .= '<div class="brand">Cadê Meu Pet? · Financeiro</div>';
    $html .= '<h1>' . sanitize($title) . '</h1>';
    $html .= '<div class="sub">' . sanitize($subtitle) . ' · Gerado em ' . date('d/m/Y H:i') . '</div>';

    $html .= '<div class="summary">'
        . '<div><strong>Total de registros:</strong> ' . sanitize((string)$totalRegistros) . '</div>'
        . '<div><strong>Total do relatório:</strong> ' . sanitize(formatMoney((float)$totalValor)) . '</div>'
        . '</div>';

    $html .= '<table><thead><tr>'
        . '<th>ID</th><th>Data</th><th>Valor</th><th>Doador</th><th>Método</th><th>Status</th>'
        . '</tr></thead><tbody>';

    foreach ($rows as $d) {
        $html .= '<tr>'
            . '<td>' . sanitize((string)($d['id'] ?? '')) . '</td>'
            . '<td>' . sanitize((string)($d['data_doacao'] ?? '')) . '</td>'
            . '<td>' . sanitize(formatMoney((float)($d['valor'] ?? 0))) . '</td>'
            . '<td>' . sanitize((string)($d['nome_doador'] ?? '')) . '</td>'
            . '<td>' . sanitize((string)($d['metodo_pagamento'] ?? '')) . '</td>'
            . '<td>' . sanitize((string)($d['status'] ?? '')) . '</td>'
            . '</tr>';
    }

    $html .= '</tbody></table></body></html>';

    $options = new Dompdf\Options();
    $options->set('isRemoteEnabled', false);

    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $filename = 'relatorio_doacoes_' . $now . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $dompdf->output();
    exit;
}

$parceiroPagamentoModel = new ParceiroPagamento();
$rows = $parceiroPagamentoModel->findAll(10000, 0, $partnerStatus !== '' ? $partnerStatus : null, $partnerMes !== '' ? $partnerMes : null);

if ($format === 'csv') {
    $filename = 'relatorio_parceiros_' . $now . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Data', 'Usuário', 'Email', 'Plano', 'Método', 'Periodicidade', 'Valor', 'Referência', 'Status', 'Efí Charge', 'Efí Subscription', 'Payment URL']);
    foreach ($rows as $p) {
        fputcsv($out, [
            (string)($p['id'] ?? ''),
            (string)($p['data_criacao'] ?? ''),
            (string)($p['usuario_nome'] ?? ''),
            (string)($p['email'] ?? ''),
            (string)($p['plano'] ?? ''),
            (string)($p['gateway_tipo'] ?? $p['metodo'] ?? ''),
            (string)($p['periodicidade'] ?? ''),
            (string)($p['valor'] ?? ''),
            (string)($p['referencia'] ?? $p['efi_charge_id'] ?? ''),
            (string)($p['status'] ?? ''),
            (string)($p['efi_charge_id'] ?? ''),
            (string)($p['efi_subscription_id'] ?? ''),
            (string)($p['payment_url'] ?? ''),
        ]);
    }
    fclose($out);
    exit;
}

if (!class_exists('Dompdf\\Dompdf')) {
    setFlashMessage('Exportação PDF indisponível. Instale dependências via Composer.', MSG_ERROR);
    redirect('/admin/financeiro?tab=parceiros');
}

$totalRegistros = is_array($rows) ? count($rows) : 0;
$totalValor = 0.0;
foreach ($rows as $p) {
    $totalValor += (float)($p['valor'] ?? 0);
}

$title = 'Relatório de Pagamentos de Parceiros';
$subtitleParts = [];
if ($partnerMes !== '') {
    $subtitleParts[] = 'Mês: ' . $partnerMes;
}
if ($partnerStatus !== '') {
    $subtitleParts[] = 'Status: ' . $partnerStatus;
}
$subtitle = !empty($subtitleParts) ? implode(' · ', $subtitleParts) : 'Sem filtros';

$html = '<html><head><meta charset="utf-8"><style>'
    . 'body{font-family:DejaVu Sans, Arial, sans-serif;font-size:12px;}'
    . '.brand{font-size:12px;color:#666;margin:0 0 10px 0;}'
    . 'h1{font-size:18px;margin:0 0 4px 0;}'
    . '.sub{color:#666;margin:0 0 12px 0;}'
    . '.summary{margin:0 0 12px 0;padding:8px;border:1px solid #eee;background:#fafafa;}'
    . '.summary strong{display:inline-block;min-width:120px;}'
    . 'table{width:100%;border-collapse:collapse;}'
    . 'th,td{border:1px solid #ddd;padding:6px;vertical-align:top;}'
    . 'th{background:#f5f5f5;text-align:left;}'
    . '</style></head><body>';

$html .= '<div class="brand">Cadê Meu Pet? · Financeiro</div>';
$html .= '<h1>' . sanitize($title) . '</h1>';
$html .= '<div class="sub">' . sanitize($subtitle) . ' · Gerado em ' . date('d/m/Y H:i') . '</div>';

$html .= '<div class="summary">'
    . '<div><strong>Total de registros:</strong> ' . sanitize((string)$totalRegistros) . '</div>'
    . '<div><strong>Total do relatório:</strong> ' . sanitize(formatMoney((float)$totalValor)) . '</div>'
    . '</div>';

$html .= '<table><thead><tr>'
    . '<th>ID</th><th>Data</th><th>Usuário</th><th>Plano</th><th>Método</th><th>Periodicidade</th><th>Valor</th><th>Referência</th><th>Status</th><th>Efí Charge</th><th>Efí Subscription</th>'
    . '</tr></thead><tbody>';

foreach ($rows as $p) {
    $html .= '<tr>'
        . '<td>' . sanitize((string)($p['id'] ?? '')) . '</td>'
        . '<td>' . sanitize((string)($p['data_criacao'] ?? '')) . '</td>'
        . '<td>' . sanitize((string)($p['usuario_nome'] ?? '')) . '</td>'
        . '<td>' . sanitize((string)($p['plano'] ?? '')) . '</td>'
        . '<td>' . sanitize((string)($p['gateway_tipo'] ?? $p['metodo'] ?? '')) . '</td>'
        . '<td>' . sanitize((string)($p['periodicidade'] ?? '')) . '</td>'
        . '<td>' . sanitize(formatMoney((float)($p['valor'] ?? 0))) . '</td>'
        . '<td>' . sanitize((string)($p['referencia'] ?? $p['efi_charge_id'] ?? '')) . '</td>'
        . '<td>' . sanitize((string)($p['status'] ?? '')) . '</td>'
        . '<td>' . sanitize((string)($p['efi_charge_id'] ?? '')) . '</td>'
        . '<td>' . sanitize((string)($p['efi_subscription_id'] ?? '')) . '</td>'
        . '</tr>';
}

$html .= '</tbody></table></body></html>';

$options = new Dompdf\Options();
$options->set('isRemoteEnabled', false);

$dompdf = new Dompdf\Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = 'relatorio_parceiros_' . $now . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $dompdf->output();
exit;
