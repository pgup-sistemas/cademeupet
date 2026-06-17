<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Editar Anúncio - Cadê Meu Pet?';

requireLogin();

$errors = [];
$controller = new AnuncioController();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    setFlashMessage('Anúncio não encontrado.', MSG_WARNING);
    redirect('/busca.php');
}

$anuncio = $controller->getDetalhes($id);
if (!$anuncio) {
    setFlashMessage('Anúncio não encontrado ou removido.', MSG_WARNING);
    redirect('/busca.php');
}

$isOwner = isLoggedIn() && getUserId() == $anuncio['usuario_id'];
if (!$isOwner && !isAdmin()) {
    setFlashMessage('Você não tem permissão para editar este anúncio.', MSG_ERROR);
    redirect('/anuncio/' . $id . '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Erro de validação do formulário. Atualize a página e tente novamente.';
    } else {
        $result = $controller->update($id, $_POST);

        if (!empty($result['success'])) {
            setFlashMessage('Anúncio atualizado com sucesso!', MSG_SUCCESS);
            redirect('/anuncio/' . $id . '/');
        }

        if (!empty($result['errors'])) {
            $errors = $result['errors'];
        }
    }

    // Recarrega dados para repopular form
    $anuncio = $controller->getDetalhes($id) ?: $anuncio;
}

$includeMapAssets = true;

$_petLabel = $anuncio['nome_pet'] ?: ('Pet ' . ucfirst($anuncio['especie']));
$breadcrumbs = [
    ['label' => 'Início',                                      'url' => BASE_URL],
    ['label' => 'Meus Anúncios',                               'url' => BASE_URL . '/meus-anuncios'],
    ['label' => sanitize($_petLabel),                          'url' => BASE_URL . '/anuncio/' . $id . '/'],
    ['label' => 'Editar'],
];

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0">
                <div class="card-body p-4 p-md-5">
                    <h2 class="fw-bold mb-3">Editar anúncio</h2>
                    <p class="text-muted">Atualize os dados do seu anúncio abaixo.</p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <strong>Ops! Corrija os seguintes erros:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo sanitize($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipo *</label>
                                <select class="form-select form-select-lg" name="tipo" required>
                                    <option value="perdido" <?php echo ($anuncio['tipo'] ?? '') === 'perdido' ? 'selected' : ''; ?>>Perdido</option>
                                    <option value="encontrado" <?php echo ($anuncio['tipo'] ?? '') === 'encontrado' ? 'selected' : ''; ?>>Encontrado</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Espécie *</label>
                                <select class="form-select form-select-lg" name="especie" required>
                                    <option value="cachorro" <?php echo ($anuncio['especie'] ?? '') === 'cachorro' ? 'selected' : ''; ?>>Cachorro</option>
                                    <option value="gato" <?php echo ($anuncio['especie'] ?? '') === 'gato' ? 'selected' : ''; ?>>Gato</option>
                                    <option value="ave" <?php echo ($anuncio['especie'] ?? '') === 'ave' ? 'selected' : ''; ?>>Ave</option>
                                    <option value="outro" <?php echo ($anuncio['especie'] ?? '') === 'outro' ? 'selected' : ''; ?>>Outro</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nome do pet</label>
                                <input type="text" class="form-control form-control-lg" name="nome_pet" value="<?php echo sanitize($anuncio['nome_pet'] ?? ''); ?>" maxlength="100">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tamanho *</label>
                                <select class="form-select form-select-lg" name="tamanho" required>
                                    <option value="pequeno" <?php echo ($anuncio['tamanho'] ?? '') === 'pequeno' ? 'selected' : ''; ?>>Pequeno</option>
                                    <option value="medio" <?php echo ($anuncio['tamanho'] ?? '') === 'medio' ? 'selected' : ''; ?>>Médio</option>
                                    <option value="grande" <?php echo ($anuncio['tamanho'] ?? '') === 'grande' ? 'selected' : ''; ?>>Grande</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Raça</label>
                                <input type="text" class="form-control form-control-lg" name="raca" value="<?php echo sanitize($anuncio['raca'] ?? ''); ?>" maxlength="100">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Cor</label>
                                <input type="text" class="form-control form-control-lg" name="cor" value="<?php echo sanitize($anuncio['cor'] ?? ''); ?>" maxlength="100">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Data do ocorrido *</label>
                                <input type="date" class="form-control form-control-lg" name="data_ocorrido" value="<?php echo sanitize($anuncio['data_ocorrido'] ?? ''); ?>" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descrição *</label>
                                <textarea class="form-control form-control-lg" name="descricao" rows="5" required><?php echo sanitize($anuncio['descricao'] ?? ''); ?></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Endereço completo *</label>
                                <input type="text" class="form-control form-control-lg" name="endereco_completo" value="<?php echo sanitize($anuncio['endereco_completo'] ?? ''); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Bairro *</label>
                                <input type="text" class="form-control form-control-lg" name="bairro" value="<?php echo sanitize($anuncio['bairro'] ?? ''); ?>" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Cidade *</label>
                                <input type="text" class="form-control form-control-lg" name="cidade" value="<?php echo sanitize($anuncio['cidade'] ?? ''); ?>" required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">UF *</label>
                                <input type="text" class="form-control form-control-lg" name="estado" value="<?php echo sanitize($anuncio['estado'] ?? ''); ?>" maxlength="2" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Marque no mapa</label>
                                <div id="mapPickerEditar" class="cmp-map"></div>
                                <div class="form-text">Clique no mapa para ajustar a posição (ou arraste o marcador).</div>
                                <input type="hidden" name="latitude" id="latitude" value="<?php echo sanitize($anuncio['latitude'] ?? ''); ?>">
                                <input type="hidden" name="longitude" id="longitude" value="<?php echo sanitize($anuncio['longitude'] ?? ''); ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">CEP</label>
                                <input type="text"
                                       class="form-control form-control-lg"
                                       name="cep"
                                       inputmode="numeric"
                                       pattern="\d*"
                                       data-mask="cep"
                                       placeholder="00000-000"
                                       maxlength="9"
                                       value="<?php echo sanitize($anuncio['cep'] ?? ''); ?>">
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Ponto de referência</label>
                                <input type="text" class="form-control form-control-lg" name="ponto_referencia" value="<?php echo sanitize($anuncio['ponto_referencia'] ?? ''); ?>" maxlength="255">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">WhatsApp *</label>
                                <input type="text" class="form-control form-control-lg" name="whatsapp" value="<?php echo sanitize($anuncio['whatsapp'] ?? ''); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control form-control-lg" name="telefone_contato" value="<?php echo sanitize($anuncio['telefone_contato'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">E-mail de contato</label>
                                <input type="email" class="form-control form-control-lg" name="email_contato" value="<?php echo sanitize($anuncio['email_contato'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Recompensa</label>
                                <input type="text" class="form-control form-control-lg" name="recompensa" value="<?php echo sanitize($anuncio['recompensa'] ?? ''); ?>" maxlength="100">
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <a class="btn btn-outline-secondary btn-lg" href="<?php echo BASE_URL; ?>/anuncio/<?php echo $id; ?>/">Cancelar</a>
                            <button type="submit" class="btn btn-primary btn-lg flex-grow-1">Salvar alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cmp-map {
    height: 280px;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.08);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('mapPickerEditar') && window.Cadê Meu Pet?Map) {
        const instance = window.Cadê Meu Pet?Map.init({
            containerId: 'mapPickerEditar',
            latInputId: 'latitude',
            lngInputId: 'longitude'
        });

        if (instance && instance.fitToPoint) {
            instance.fitToPoint();
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
