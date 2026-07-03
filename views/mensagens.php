<?php
require_once __DIR__ . '/../config.php';

requireLogin();

$pageTitle = 'Minhas Mensagens - Cadê Meu Pet?';
$includeMapAssets = true;

$conversaController = new ConversaController();
$usuarioId = getUserId();
$conversas = $conversaController->listarConversas($usuarioId);
$conversaSelecionadaId = (int)($_GET['conversa'] ?? 0);

$breadcrumbs = [
    ['label' => 'Início',    'url' => BASE_URL],
    ['label' => 'Mensagens'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <h1 class="h3 fw-bold mb-1"><i class="fa-solid fa-comments me-2"></i>Minhas Mensagens</h1>
    <p class="text-muted mb-4">Converse direto com quem entrou em contato sobre um anúncio ou pet do Pet Love.</p>

    <?php if (empty($conversas)): ?>
        <div class="alert alert-info">
            Você ainda não tem conversas. Quando alguém entrar em contato sobre um anúncio seu, ou você
            demonstrar interesse em um anúncio ou pet do Pet Love, a conversa aparece aqui.
        </div>
    <?php else: ?>
        <div class="card chat-card border-0 shadow-sm overflow-hidden">
            <div class="row g-0" style="min-height: 520px;">
                <!-- Lista de conversas -->
                <div class="col-md-4 border-end chat-list-panel chat-scroll" style="max-height: 640px;">
                    <div class="list-group list-group-flush" id="listaConversas">
                        <?php foreach ($conversas as $c): ?>
                            <?php
                                $outroNome = (int)$c['usuario_dono_id'] === $usuarioId ? $c['interessado_nome'] : $c['dono_nome'];
                                $itemNome  = $c['item_nome'] ?: ($c['tipo'] === 'petlove' ? 'Pet Love' : 'Pet');
                                $fotoUrl   = null;
                                if (!empty($c['item_foto'])) {
                                    $fotoUrl = BASE_URL . '/uploads/' . ($c['tipo'] === 'petlove' ? 'petlove/' : 'anuncios/') . $c['item_foto'];
                                }
                            ?>
                            <a href="#" class="list-group-item list-group-item-action chat-list-item conversa-item d-flex gap-2 align-items-start py-3 px-3 <?php echo ((int)$c['id'] === $conversaSelecionadaId) ? 'active' : ''; ?>"
                               data-conversa-id="<?php echo (int)$c['id']; ?>"
                               data-item-nome="<?php echo sanitize($itemNome); ?>"
                               data-outro-nome="<?php echo sanitize($outroNome); ?>">
                                <?php if ($fotoUrl): ?>
                                    <img src="<?php echo sanitize($fotoUrl); ?>" class="rounded-circle flex-shrink-0" style="width:44px;height:44px;object-fit:cover;" alt="">
                                <?php else: ?>
                                    <div class="rounded-circle bg-white d-flex align-items-center justify-content-center text-muted flex-shrink-0 border" style="width:44px;height:44px;">
                                        <i class="fa-solid fa-paw"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <div class="d-flex justify-content-between align-items-center gap-1">
                                        <strong class="chat-item-name"><?php echo sanitize($outroNome); ?></strong>
                                        <?php if ((int)$c['nao_lidas'] > 0): ?>
                                            <span class="badge bg-danger rounded-pill flex-shrink-0"><?php echo (int)$c['nao_lidas']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted chat-item-sub">sobre <?php echo sanitize($itemNome); ?></div>
                                    <div class="small text-muted chat-item-sub"><?php echo sanitize((string)($c['ultima_mensagem'] ?? '')); ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Painel da conversa -->
                <div class="col-md-8 d-flex flex-column">
                    <div id="conversaVazia" class="flex-grow-1 d-flex align-items-center justify-content-center text-muted p-4">
                        <div class="text-center">
                            <i class="fa-solid fa-comments fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0">Selecione uma conversa para começar</p>
                        </div>
                    </div>

                    <div id="conversaAberta" class="d-none d-flex flex-column flex-grow-1">
                        <div class="border-bottom p-3 chat-panel-header">
                            <strong id="conversaTitulo"></strong>
                            <div class="small text-muted" id="conversaSubtitulo"></div>
                        </div>
                        <div id="conversaMensagens" class="flex-grow-1 p-3 chat-bubble-area chat-scroll" style="max-height: 460px;"></div>
                        <form id="formEnviarMensagem" class="border-top p-3 d-flex gap-2 align-items-center">
                            <input type="hidden" id="conversaIdAtual" value="">
                            <input type="file" id="inputFoto" accept="image/jpeg,image/png,image/webp" class="d-none">
                            <button type="button" id="btnAnexarFoto" class="btn btn-outline-secondary" title="Enviar foto">
                                <i class="fa-solid fa-camera"></i>
                            </button>
                            <button type="button" id="btnEnviarLocalizacao" class="btn btn-outline-secondary" title="Enviar minha localização">
                                <i class="fa-solid fa-location-dot"></i>
                            </button>
                            <input type="text" id="inputMensagem" class="form-control" placeholder="Digite sua mensagem..." maxlength="1000" required>
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    const csrfToken = <?php echo json_encode(generateCSRFToken()); ?>;
    const apiUrl = <?php echo json_encode(rtrim((string)BASE_URL, '/') . '/api'); ?>;
    const usuarioId = <?php echo (int)$usuarioId; ?>;

    let conversaAtualId = 0;
    let ultimoMensagemId = 0;
    let pollingTimer = null;

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatarHora(criadoEm) {
        return new Date(criadoEm.replace(' ', 'T')).toLocaleString('pt-BR', {day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'});
    }

    function renderMensagem(m) {
        const minha = parseInt(m.remetente_id, 10) === usuarioId;
        const wrap = document.createElement('div');
        wrap.className = 'd-flex mb-2 ' + (minha ? 'justify-content-end' : 'justify-content-start');

        let corpo;
        if (m.tipo === 'imagem') {
            const src = `${<?php echo json_encode(rtrim((string)BASE_URL, '/') . '/uploads/mensagens/'); ?>}${encodeURIComponent(m.arquivo)}`;
            corpo = `<a href="${src}" target="_blank" rel="noopener"><img src="${src}" alt="Foto enviada" class="rounded-2" style="max-width:220px;max-height:220px;object-fit:cover;display:block;"></a>`;
        } else if (m.tipo === 'localizacao') {
            const mapId = 'loc-map-' + m.id;
            const gmaps = `https://maps.google.com/?q=${m.latitude},${m.longitude}`;
            corpo = `
                <div id="${mapId}" data-lat="${m.latitude}" data-lng="${m.longitude}" class="rounded-2 mb-1" style="width:220px;height:140px;"></div>
                <a href="${gmaps}" target="_blank" rel="noopener" class="small ${minha ? 'text-white' : 'text-primary'}">
                    <i class="fa-solid fa-location-dot me-1"></i>Abrir no Google Maps
                </a>`;
        } else {
            corpo = `<div style="white-space: pre-wrap; word-break: break-word;">${escapeHtml(m.mensagem)}</div>`;
        }

        wrap.innerHTML = `
            <div class="p-2 px-3 rounded-3 ${minha ? 'bg-primary text-white' : 'bg-light'}" style="max-width: 75%;">
                ${corpo}
                <div class="small ${minha ? 'text-white-50' : 'text-muted'} mt-1">${formatarHora(m.criado_em)}</div>
            </div>`;

        if (m.tipo === 'localizacao' && window.L) {
            setTimeout(() => {
                const el = wrap.querySelector('#loc-map-' + m.id);
                if (!el || el.dataset.rendered) return;
                el.dataset.rendered = '1';
                const map = L.map(el, { zoomControl: false, attributionControl: false, dragging: false, scrollWheelZoom: false })
                    .setView([m.latitude, m.longitude], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                L.marker([m.latitude, m.longitude]).addTo(map);
            }, 0);
        }

        return wrap;
    }

    function pararPolling() {
        if (pollingTimer) { clearInterval(pollingTimer); pollingTimer = null; }
    }

    function iniciarPolling() {
        pararPolling();
        pollingTimer = setInterval(async function () {
            if (!conversaAtualId) return;
            try {
                const resp = await fetch(`${apiUrl}/conversa-poll.php?id=${conversaAtualId}&depois_de=${ultimoMensagemId}`);
                const data = await resp.json();
                if (data.ok && data.mensagens && data.mensagens.length) {
                    const box = document.getElementById('conversaMensagens');
                    data.mensagens.forEach(m => {
                        box.appendChild(renderMensagem(m));
                        ultimoMensagemId = Math.max(ultimoMensagemId, parseInt(m.id, 10));
                    });
                    box.scrollTop = box.scrollHeight;
                    atualizarBadgeGlobal();
                }
            } catch (e) { /* silencioso, tenta de novo no próximo ciclo */ }
        }, 4000);
    }

    async function abrirConversa(item) {
        conversaAtualId = parseInt(item.dataset.conversaId, 10);
        document.getElementById('conversaIdAtual').value = conversaAtualId;
        document.getElementById('conversaTitulo').textContent = item.dataset.outroNome;
        document.getElementById('conversaSubtitulo').textContent = 'sobre ' + item.dataset.itemNome;

        document.querySelectorAll('.conversa-item').forEach(el => el.classList.remove('active'));
        item.classList.add('active');
        const badge = item.querySelector('.badge');
        if (badge) badge.remove();

        document.getElementById('conversaVazia').classList.add('d-none');
        document.getElementById('conversaAberta').classList.remove('d-none');
        document.getElementById('conversaAberta').classList.add('d-flex');

        const box = document.getElementById('conversaMensagens');
        box.innerHTML = '<div class="text-center text-muted small py-3">Carregando...</div>';

        try {
            const resp = await fetch(`${apiUrl}/conversas.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ acao: 'ler', conversa_id: conversaAtualId, csrf_token: csrfToken })
            });
            const data = await resp.json();
            box.innerHTML = '';
            if (data.ok) {
                ultimoMensagemId = 0;
                data.mensagens.forEach(m => {
                    box.appendChild(renderMensagem(m));
                    ultimoMensagemId = Math.max(ultimoMensagemId, parseInt(m.id, 10));
                });
                box.scrollTop = box.scrollHeight;
                atualizarBadgeGlobal();
            } else {
                box.innerHTML = '<div class="alert alert-danger">' + escapeHtml(data.erro || 'Erro ao carregar conversa.') + '</div>';
            }
        } catch (e) {
            box.innerHTML = '<div class="alert alert-danger">Erro de conexão.</div>';
        }

        iniciarPolling();
    }

    document.querySelectorAll('.conversa-item').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            abrirConversa(item);
        });
    });

    document.getElementById('formEnviarMensagem')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const input = document.getElementById('inputMensagem');
        const texto = input.value.trim();
        if (!texto || !conversaAtualId) return;

        input.disabled = true;
        try {
            const resp = await fetch(`${apiUrl}/conversas.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ acao: 'enviar', conversa_id: conversaAtualId, mensagem: texto, csrf_token: csrfToken })
            });
            const data = await resp.json();
            if (data.ok) {
                input.value = '';
                await atualizarMensagensNovas();
            } else {
                alert(data.erro || 'Erro ao enviar mensagem.');
            }
        } catch (e) {
            alert('Erro de conexão.');
        }
        input.disabled = false;
        input.focus();
    });

    document.getElementById('btnAnexarFoto')?.addEventListener('click', function () {
        if (!conversaAtualId) { alert('Selecione uma conversa primeiro.'); return; }
        document.getElementById('inputFoto').click();
    });

    document.getElementById('inputFoto')?.addEventListener('change', async function () {
        const arquivo = this.files[0];
        this.value = '';
        if (!arquivo || !conversaAtualId) return;

        const fd = new FormData();
        fd.append('conversa_id', conversaAtualId);
        fd.append('csrf_token', csrfToken);
        fd.append('imagem', arquivo);

        try {
            const resp = await fetch(`${apiUrl}/conversa-imagem.php`, { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.ok) {
                await atualizarMensagensNovas();
            } else {
                alert(data.erro || 'Erro ao enviar a foto.');
            }
        } catch (e) {
            alert('Erro de conexão ao enviar a foto.');
        }
    });

    document.getElementById('btnEnviarLocalizacao')?.addEventListener('click', function () {
        if (!conversaAtualId) { alert('Selecione uma conversa primeiro.'); return; }
        if (!navigator.geolocation) { alert('Seu navegador não suporta geolocalização.'); return; }

        const btn = this;
        btn.disabled = true;
        navigator.geolocation.getCurrentPosition(async function (pos) {
            try {
                const resp = await fetch(`${apiUrl}/conversas.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        acao: 'localizacao',
                        conversa_id: conversaAtualId,
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                        csrf_token: csrfToken
                    })
                });
                const data = await resp.json();
                if (data.ok) {
                    await atualizarMensagensNovas();
                } else {
                    alert(data.erro || 'Erro ao enviar localização.');
                }
            } catch (e) {
                alert('Erro de conexão ao enviar localização.');
            }
            btn.disabled = false;
        }, function () {
            alert('Não foi possível obter sua localização. Verifique a permissão do navegador.');
            btn.disabled = false;
        });
    });

    async function atualizarMensagensNovas() {
        const resp = await fetch(`${apiUrl}/conversa-poll.php?id=${conversaAtualId}&depois_de=${ultimoMensagemId}`);
        const data = await resp.json();
        if (data.ok) {
            const box = document.getElementById('conversaMensagens');
            data.mensagens.forEach(m => {
                box.appendChild(renderMensagem(m));
                ultimoMensagemId = Math.max(ultimoMensagemId, parseInt(m.id, 10));
            });
            box.scrollTop = box.scrollHeight;
        }
    }

    function atualizarBadgeGlobal() {
        fetch(`${apiUrl}/conversa-nao-lidas.php`).then(r => r.json()).then(data => {
            const badge = document.getElementById('navMensagensBadge');
            if (!badge) return;
            if (data.ok && data.total > 0) {
                badge.textContent = data.total;
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        }).catch(() => {});
    }

    <?php if ($conversaSelecionadaId > 0): ?>
    const preSelecionado = document.querySelector('.conversa-item[data-conversa-id="<?php echo $conversaSelecionadaId; ?>"]');
    if (preSelecionado) abrirConversa(preSelecionado);
    <?php endif; ?>
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
