<?php
/**
 * Cadê Meu Pet? - Geração de PDF para documentos assinados (núcleo genérico).
 * Reutilizado por Termo de Adoção (Fase 1) e Laudo veterinário (Fase 3).
 */
class DocumentoPdfService
{
    /**
     * Renderiza o conteúdo do documento + trilha de assinaturas em PDF e
     * salva em uploads/documentos/. Retorna o nome do arquivo salvo.
     */
    public function gerarESalvar(array $documento, array $assinaturas, string $prefixoArquivo): string
    {
        $rodapeAssinaturas = '<hr><p class="small">Assinaturas eletrônicas registradas (auditáveis por hash e IP — sem validade ICP-Brasil):</p><ul class="small">';
        foreach ($assinaturas as $assinatura) {
            $identificacao = !empty($assinatura['identificacao_extra']) ? ' — ' . sanitize($assinatura['identificacao_extra']) : '';
            $rodapeAssinaturas .= '<li>' . sanitize($assinatura['usuario_nome']) . $identificacao . ' (' . sanitize($assinatura['papel']) . ') em '
                . formatDateTimeBR($assinatura['assinado_em']) . ' — IP ' . sanitize((string)$assinatura['ip_address']) . '</li>';
        }
        $rodapeAssinaturas .= '</ul><p class="small">Código de verificação: <strong>' . sanitize($documento['codigo_verificacao']) . '</strong></p>';

        $htmlPdf = '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><style>
            body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11pt; color: #212529; line-height: 1.6; }
            .small { font-size: 9pt; color: #6c757d; }
            hr { border: none; border-top: 1px solid #dee2e6; margin: 1rem 0; }
        </style></head><body>' . $documento['conteudo_html'] . $rodapeAssinaturas . '</body></html>';

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', BASE_PATH);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($htmlPdf, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $nomeArquivo = $prefixoArquivo . '_' . $documento['id'] . '_' . time() . '.pdf';
        $dirDestino = BASE_PATH . '/uploads/documentos';
        if (!is_dir($dirDestino)) {
            @mkdir($dirDestino, 0755, true);
        }
        file_put_contents($dirDestino . '/' . $nomeArquivo, $dompdf->output());

        return $nomeArquivo;
    }
}
