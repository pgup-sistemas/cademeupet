<?php
/**
 * Template do contrato de parceria.
 * Incluído tanto na página de aceite quanto na geração do PDF.
 *
 * Variáveis esperadas (todas obrigatórias):
 *   $contrato_usuario      array  — dados do usuário
 *   $contrato_inscricao    array  — dados da inscrição aprovada
 *   $contrato_plano        string — 'basico' | 'destaque'
 *   $contrato_periodicidade string — 'mensal' | 'anual'
 *   $contrato_valor_mensal float
 *   $contrato_aceite       array|null — dados do aceite (quando visualizando contrato já assinado)
 *   $contrato_versao       string — ex: '1.0'
 */

$_planoLabel         = $contrato_plano === 'destaque' ? 'Destaque' : 'Básico';
$_periodicidadeLabel = $contrato_periodicidade === 'anual' ? 'Anual' : 'Mensal';
$_valorTotal         = $contrato_periodicidade === 'anual' ? $contrato_valor_mensal * 12 : $contrato_valor_mensal;
$_nomeParceiro       = (string)($contrato_inscricao['nome_fantasia'] ?? $contrato_usuario['nome'] ?? '');
$_responsavel        = (string)($contrato_usuario['nome'] ?? '');
$_email              = (string)($contrato_usuario['email'] ?? '');
$_telRaw             = preg_replace('/\D/', '', (string)($contrato_inscricao['telefone'] ?? $contrato_usuario['telefone'] ?? ''));
$_telefone           = (preg_match('/^0+$/', $_telRaw) || strlen($_telRaw) < 10) ? '' : $_telRaw;
$_cidade             = (string)($contrato_inscricao['cidade'] ?? $contrato_usuario['cidade'] ?? '');
$_estado             = (string)($contrato_inscricao['estado'] ?? $contrato_usuario['estado'] ?? '');
$_categoria          = (string)($contrato_inscricao['categoria'] ?? '');
$_tipoDocumento      = (string)($contrato_inscricao['tipo_documento'] ?? '');
$_numeroDocumento    = (string)($contrato_inscricao['numero_documento'] ?? '');
$_documentoFormatado = $_numeroDocumento !== '' ? formatCpfCnpj($_tipoDocumento, $_numeroDocumento) : '';
$_dataHoje           = date('d/m/Y');
$_hojeFormatado      = date('d \d\e F \d\e Y');
$_versao             = $contrato_versao ?? '1.0';

$_vigenteAte = $contrato_periodicidade === 'anual'
    ? date('d/m/Y', strtotime('+1 year'))
    : date('d/m/Y', strtotime('+1 month'));
?>
<div class="contrato-body">

<div class="contrato-cabecalho text-center mb-4">
    <div class="fw-bold fs-5">CONTRATO DE PARCERIA</div>
    <div class="text-muted small">Cadê Meu Pet? — Plataforma de Adoção e Serviços para Pets</div>
    <div class="text-muted small">Versão <?php echo htmlspecialchars($_versao); ?> &nbsp;|&nbsp; <?php echo $_dataHoje; ?></div>
</div>

<hr>

<h2 class="contrato-secao">1. PARTES CONTRATANTES</h2>

<p><strong>CONTRATANTE (Plataforma):</strong></p>
<p class="ms-3">
    <strong>Cadê Meu Pet?</strong>, plataforma online de serviços para pets operada por
    <strong>Pageup Sistemas</strong>, disponível em
    <strong>https://cademeupet.pageup.net.br</strong>,
    doravante denominada simplesmente <em>Plataforma</em>.
</p>

<p><strong>CONTRATADO (Parceiro):</strong></p>
<p class="ms-3">
    <strong>Nome / Razão Social:</strong> <?php echo htmlspecialchars($_nomeParceiro); ?><br>
    <?php if ($_documentoFormatado !== ''): ?>
    <strong><?php echo strtoupper($_tipoDocumento); ?>:</strong> <?php echo htmlspecialchars($_documentoFormatado); ?><br>
    <?php endif; ?>
    <strong>Responsável:</strong> <?php echo htmlspecialchars($_responsavel); ?><br>
    <strong>E-mail:</strong> <?php echo htmlspecialchars($_email); ?><br>
    <?php if ($_telefone !== ''): ?>
    <strong>Telefone:</strong> <?php echo htmlspecialchars($_telefone); ?><br>
    <?php endif; ?>
    <strong>Localização:</strong> <?php echo htmlspecialchars($_cidade . ' - ' . $_estado); ?><br>
    <?php if ($_categoria !== ''): ?>
    <strong>Categoria:</strong> <?php echo htmlspecialchars(ucfirst($_categoria)); ?><br>
    <?php endif; ?>
    Doravante denominado simplesmente <em>Parceiro</em>.
</p>

<hr>

<h2 class="contrato-secao">2. OBJETO DO CONTRATO</h2>

<p>
    O presente contrato tem por objeto a prestação de serviços de exposição do Parceiro
    na Plataforma Cadê Meu Pet?, incluindo a criação e manutenção de perfil profissional,
    visibilidade para usuários da plataforma e acesso às ferramentas de contato disponíveis,
    conforme o plano escolhido.
</p>

<hr>

<h2 class="contrato-secao">3. PLANO E VALORES</h2>

<table class="table table-bordered table-sm contrato-tabela">
    <tbody>
        <tr>
            <td class="fw-semibold" style="width:45%">Plano contratado</td>
            <td>Parceiro <?php echo htmlspecialchars($_planoLabel); ?></td>
        </tr>
        <tr>
            <td class="fw-semibold">Modalidade</td>
            <td><?php echo htmlspecialchars($_periodicidadeLabel); ?></td>
        </tr>
        <tr>
            <td class="fw-semibold">Valor mensal</td>
            <td>R$ <?php echo number_format($contrato_valor_mensal, 2, ',', '.'); ?></td>
        </tr>
        <?php if ($contrato_periodicidade === 'anual'): ?>
        <tr>
            <td class="fw-semibold">Valor total (anual)</td>
            <td>R$ <?php echo number_format($_valorTotal, 2, ',', '.'); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td class="fw-semibold">Vigência inicial</td>
            <td><?php echo $_dataHoje; ?> a <?php echo $_vigenteAte; ?></td>
        </tr>
    </tbody>
</table>

<p>
    <?php if ($contrato_periodicidade === 'mensal'): ?>
    O valor de <strong>R$ <?php echo number_format($contrato_valor_mensal, 2, ',', '.'); ?></strong>
    será cobrado mensalmente na data de vencimento acordada.
    A assinatura se renova automaticamente a cada mês até o cancelamento por qualquer das partes.
    <?php else: ?>
    O valor de <strong>R$ <?php echo number_format($_valorTotal, 2, ',', '.'); ?></strong>
    será cobrado de forma única, correspondente a 12 (doze) meses de acesso à plataforma
    (equivalente a R$ <?php echo number_format($contrato_valor_mensal, 2, ',', '.'); ?>/mês).
    <?php endif; ?>
</p>

<hr>

<h2 class="contrato-secao">4. VIGÊNCIA E RENOVAÇÃO</h2>

<p>
    Este contrato entra em vigor na data de confirmação do pagamento e tem duração
    <?php echo $contrato_periodicidade === 'anual' ? 'de 12 (doze) meses' : 'mensal'; ?>,
    renovando-se automaticamente nas mesmas condições salvo manifestação em contrário.
</p>
<p>
    A renovação automática pode ser cancelada a qualquer tempo pelo Parceiro através do
    painel de controle ou pelo administrador da Plataforma, sem multa ou ônus, desde que
    o cancelamento ocorra com pelo menos 5 (cinco) dias de antecedência ao próximo vencimento.
</p>

<hr>

<h2 class="contrato-secao">5. OBRIGAÇÕES DO PARCEIRO</h2>

<ol>
    <li>Manter as informações do perfil atualizadas e verídicas;</li>
    <li>Não divulgar conteúdo falso, enganoso, discriminatório ou ilegal;</li>
    <li>Responder às solicitações de usuários com presteza e profissionalismo;</li>
    <li>Manter o pagamento em dia para garantir a visibilidade do perfil;</li>
    <li>Comunicar à Plataforma qualquer alteração cadastral relevante;</li>
    <li>Utilizar a plataforma apenas para fins lícitos e compatíveis com seu objeto social;</li>
    <li>Não reproduzir, vender ou ceder a terceiros o acesso à plataforma.</li>
</ol>

<hr>

<h2 class="contrato-secao">6. OBRIGAÇÕES DA PLATAFORMA</h2>

<ol>
    <li>Manter o perfil do Parceiro visível e acessível durante o período pago;</li>
    <li>Garantir a segurança dos dados cadastrados conforme a LGPD;</li>
    <li>Notificar o Parceiro com antecedência em caso de alterações nos planos ou preços;</li>
    <li>Prover suporte técnico por e-mail em prazo razoável;</li>
    <li>Não compartilhar dados do Parceiro com terceiros sem consentimento, salvo obrigação legal.</li>
</ol>

<hr>

<h2 class="contrato-secao">7. CANCELAMENTO</h2>

<p>
    <strong>Pelo Parceiro:</strong> O Parceiro pode cancelar a qualquer momento pelo painel
    ou por e-mail. O perfil permanecerá publicado até o fim do período já pago. Não há
    reembolso proporcional para períodos já vigentes.
</p>
<p>
    <strong>Pela Plataforma:</strong> A Plataforma pode suspender ou cancelar o acesso em
    caso de violação dos termos, inadimplência superior a 15 dias ou determinação judicial,
    notificando o Parceiro por e-mail.
</p>

<hr>

<h2 class="contrato-secao">8. PRIVACIDADE E LGPD</h2>

<p>
    O tratamento de dados pessoais neste contrato obedece à Lei nº 13.709/2018 (LGPD).
    Os dados do Parceiro são coletados para execução deste contrato e divulgação do perfil
    na plataforma. O Parceiro pode solicitar acesso, correção ou exclusão dos seus dados
    a qualquer tempo pelo e-mail de contato da Plataforma.
</p>
<p>
    A base legal para o tratamento é a <strong>execução contratual</strong> (art. 7º, V, LGPD).
    Dados de terceiros eventualmente inseridos no perfil são de responsabilidade do Parceiro.
</p>

<hr>

<h2 class="contrato-secao">9. DISPOSIÇÕES GERAIS</h2>

<p>
    Este instrumento não cria vínculo empregatício, societário ou de representação comercial
    entre as partes. O Parceiro atua de forma autônoma e independente.
</p>
<p>
    As partes elegem o foro da comarca de <strong><?php echo htmlspecialchars($_cidade . ' - ' . $_estado); ?></strong>
    para dirimir eventuais litígios, com renúncia expressa a qualquer outro, por mais privilegiado que seja.
</p>
<p>
    Todas as comunicações serão realizadas pelos e-mails cadastrados na plataforma,
    com validade jurídica equivalente à comunicação escrita.
</p>

<hr>

<?php if (!empty($contrato_aceite)): ?>
<div class="contrato-assinatura mt-4">
    <h2 class="contrato-secao">10. ASSINATURA DIGITAL</h2>

    <p>
        O Parceiro identificado neste contrato expressou seu aceite de forma eletrônica
        em cumprimento ao art. 10 da Medida Provisória nº 2.200-2/2001 e ao Marco Civil da Internet
        (Lei nº 12.965/2014).
    </p>

    <table class="table table-bordered table-sm contrato-tabela mt-3">
        <tbody>
            <tr>
                <td class="fw-semibold" style="width:45%">Data e hora do aceite</td>
                <td><?php echo date('d/m/Y \à\s H:i:s', strtotime((string)$contrato_aceite['aceito_em'])); ?> (UTC-3)</td>
            </tr>
            <tr>
                <td class="fw-semibold">Endereço IP</td>
                <td><?php echo htmlspecialchars((string)($contrato_aceite['ip_aceite'] ?? '—')); ?></td>
            </tr>
            <tr>
                <td class="fw-semibold">Versão do contrato</td>
                <td><?php echo htmlspecialchars((string)($contrato_aceite['versao_contrato'] ?? '1.0')); ?></td>
            </tr>
            <tr>
                <td class="fw-semibold">Hash SHA-256 do contrato</td>
                <td style="word-break:break-all;font-size:.75rem;font-family:monospace;"><?php echo htmlspecialchars((string)($contrato_aceite['hash_contrato'] ?? '—')); ?></td>
            </tr>
            <tr>
                <td class="fw-semibold">ID do aceite</td>
                <td>#<?php echo (int)$contrato_aceite['id']; ?></td>
            </tr>
        </tbody>
    </table>

    <div class="row mt-4 text-center">
        <div class="col-6">
            <div style="border-top:2px solid #000;padding-top:.5rem;">
                <div class="fw-semibold"><?php echo htmlspecialchars($_nomeParceiro); ?></div>
                <div class="small text-muted">Parceiro / Contratado</div>
                <div class="small text-muted fst-italic">Assinatura eletrônica — IP <?php echo htmlspecialchars((string)($contrato_aceite['ip_aceite'] ?? '')); ?></div>
            </div>
        </div>
        <div class="col-6">
            <div style="border-top:2px solid #000;padding-top:.5rem;">
                <div class="fw-semibold">Cadê Meu Pet? / Pageup Sistemas</div>
                <div class="small text-muted">Plataforma / Contratante</div>
                <div class="small text-muted fst-italic">Representada pelo sistema automatizado</div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="mt-4 text-muted small fst-italic">
    <strong>Versão do contrato:</strong> <?php echo htmlspecialchars($_versao); ?>
    &nbsp;|&nbsp; Gerado em: <?php echo $_dataHoje; ?>
</div>
<?php endif; ?>

</div>
