<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$pageTitle = 'Cadastrar pet no Pet Love | Cadê Meu Pet?';

$erros   = [];
$sucesso = false;
$petId   = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ctrl = new PetLoveController();
    $res  = $ctrl->processar($_POST, $_FILES);
    if ($res['ok']) {
        setFlashMessage('Pet cadastrado com sucesso no Pet Love!', MSG_SUCCESS);
        redirect('/petlove/' . $res['id']);
    }
    $erros = $res['erros'] ?? [];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- Cabeçalho -->
            <div class="text-center mb-4">
                <h1 class="h3 fw-bold"><i class="fa-solid fa-heart me-2 text-danger"></i>Cadastrar no Pet Love</h1>
                <p class="text-muted">Registre seu pet para encontrar o par ideal</p>
            </div>

            <!-- Banner ético -->
            <div class="alert alert-info d-flex gap-2 align-items-start mb-4">
                <i class="fa-solid fa-circle-info mt-1 flex-shrink-0"></i>
                <span>
                    O Cadê Meu Pet? incentiva a <strong>criação responsável</strong>.
                    Consulte sempre um veterinário antes do cruzamento.
                </span>
            </div>

            <?php if ($erros): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    <strong>Corrija os erros:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($erros as $e): ?>
                            <li><?php echo sanitize($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>/petlove/novo"
                  enctype="multipart/form-data" id="formPetLove" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <!-- Seção 1: Identificação -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent fw-bold py-3">
                        <i class="fa-solid fa-paw me-2 text-primary"></i>Identificação do Pet
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nome do pet <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control"
                                       placeholder="Ex: Rex" maxlength="100"
                                       value="<?php echo sanitize($_POST['nome'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Espécie <span class="text-danger">*</span></label>
                                <select name="especie" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <option value="cachorro" <?php echo ($_POST['especie'] ?? '') === 'cachorro' ? 'selected' : ''; ?>>Cachorro</option>
                                    <option value="gato"     <?php echo ($_POST['especie'] ?? '') === 'gato'     ? 'selected' : ''; ?>>Gato</option>
                                    <option value="outro"    <?php echo ($_POST['especie'] ?? '') === 'outro'    ? 'selected' : ''; ?>>Outro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Raça <span class="text-danger">*</span></label>
                                <input type="text" name="raca" class="form-control"
                                       placeholder="Ex: Labrador Retriever" maxlength="100"
                                       value="<?php echo sanitize($_POST['raca'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Porte <span class="text-danger">*</span></label>
                                <select name="porte" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <?php foreach (['mini' => 'Miniatura','pequeno' => 'Pequeno','medio' => 'Médio','grande' => 'Grande','gigante' => 'Gigante'] as $v => $l): ?>
                                        <option value="<?php echo $v; ?>" <?php echo ($_POST['porte'] ?? '') === $v ? 'selected' : ''; ?>><?php echo $l; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Sexo <span class="text-danger">*</span></label>
                                <select name="sexo" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <option value="macho" <?php echo ($_POST['sexo'] ?? '') === 'macho' ? 'selected' : ''; ?>>Macho</option>
                                    <option value="femea" <?php echo ($_POST['sexo'] ?? '') === 'femea' ? 'selected' : ''; ?>>Fêmea</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Idade (meses) <span class="text-danger">*</span></label>
                                <input type="number" name="idade_meses" class="form-control"
                                       min="1" max="240" placeholder="Ex: 24"
                                       value="<?php echo (int)($_POST['idade_meses'] ?? ''); ?>" required>
                                <div class="form-text">24 meses = 2 anos</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Cor</label>
                                <input type="text" name="cor" class="form-control"
                                       placeholder="Ex: Dourado" maxlength="50"
                                       value="<?php echo sanitize($_POST['cor'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Peso (kg)</label>
                                <input type="number" name="peso_kg" class="form-control"
                                       min="0.1" max="150" step="0.1" placeholder="Ex: 28.5"
                                       value="<?php echo sanitize($_POST['peso_kg'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção 2: Saúde e Pedigree -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent fw-bold py-3">
                        <i class="fa-solid fa-syringe me-2 text-success"></i>Saúde e Documentação
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="vacinado" id="vacinado" value="1"
                                           <?php echo !empty($_POST['vacinado']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="vacinado">
                                        <i class="fa-solid fa-syringe me-1 text-success"></i>Vacinado
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="vermifugado" id="vermifugado" value="1"
                                           <?php echo !empty($_POST['vermifugado']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="vermifugado">
                                        <i class="fa-solid fa-pills me-1 text-info"></i>Vermifugado
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="tem_pedigree" id="tem_pedigree" value="1"
                                           <?php echo !empty($_POST['tem_pedigree']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="tem_pedigree">
                                        <i class="fa-solid fa-award me-1 text-warning"></i>Tem pedigree
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6" id="campoPedigreeNum" style="<?php echo empty($_POST['tem_pedigree']) ? 'display:none' : ''; ?>">
                                <label class="form-label fw-semibold">Número do pedigree</label>
                                <input type="text" name="pedigree_num" class="form-control"
                                       placeholder="Ex: CBKC-123456" maxlength="60"
                                       value="<?php echo sanitize($_POST['pedigree_num'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Objetivo</label>
                                <select name="objetivo" class="form-select">
                                    <option value="cruzamento" <?php echo ($_POST['objetivo'] ?? 'cruzamento') === 'cruzamento' ? 'selected' : ''; ?>>Cruzamento</option>
                                    <option value="pedigree"   <?php echo ($_POST['objetivo'] ?? '') === 'pedigree'   ? 'selected' : ''; ?>>Cruzamento com pedigree</option>
                                    <option value="companhia"  <?php echo ($_POST['objetivo'] ?? '') === 'companhia'  ? 'selected' : ''; ?>>Companhia (sem cruzamento)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Descrição</label>
                                <textarea name="descricao" class="form-control" rows="3"
                                          placeholder="Conte mais sobre o temperamento, rotina e qualidades do seu pet..."
                                          maxlength="1000"><?php echo sanitize($_POST['descricao'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção 3: Localização -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent fw-bold py-3">
                        <i class="fa-solid fa-location-dot me-2 text-danger"></i>Localização
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cidade <span class="text-danger">*</span></label>
                                <input type="text" name="cidade" class="form-control"
                                       placeholder="Ex: Porto Velho" maxlength="100"
                                       value="<?php echo sanitize($_POST['cidade'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                                <select name="estado" class="form-select" required>
                                    <option value="">UF</option>
                                    <?php
                                    $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                                    $ufSel = $_POST['estado'] ?? '';
                                    foreach ($ufs as $uf): ?>
                                        <option value="<?php echo $uf; ?>" <?php echo $ufSel === $uf ? 'selected' : ''; ?>><?php echo $uf; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">
                                    <i class="fa-solid fa-location-crosshairs me-1"></i>GPS
                                </label>
                                <button type="button" class="btn btn-outline-secondary w-100" id="btnGps">
                                    Usar localização
                                </button>
                            </div>
                            <input type="hidden" name="latitude"  id="latitude"  value="<?php echo sanitize($_POST['latitude']  ?? ''); ?>">
                            <input type="hidden" name="longitude" id="longitude" value="<?php echo sanitize($_POST['longitude'] ?? ''); ?>">
                            <div class="col-12">
                                <div id="gpsStatus" class="form-text"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção 4: Fotos -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent fw-bold py-3">
                        <i class="fa-solid fa-camera me-2 text-secondary"></i>Fotos do Pet
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label">Selecione até 5 fotos (JPG, PNG, WEBP — máx. 2MB cada)</label>
                            <input type="file" name="fotos[]" class="form-control"
                                   accept=".jpg,.jpeg,.png,.webp" multiple id="inputFotos">
                        </div>
                        <div id="previewFotos" class="d-flex flex-wrap gap-2"></div>
                    </div>
                </div>

                <!-- Termos obrigatórios -->
                <div class="card border-0 shadow-sm border-danger mb-4" style="border-left:4px solid #dc3545 !important;">
                    <div class="card-body p-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="criacao_responsavel"
                                   id="criacaoResponsavel" value="1" required
                                   <?php echo !empty($_POST['criacao_responsavel']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="criacaoResponsavel">
                                <strong>Declaro que pratico a criação responsável</strong> e que meu pet está
                                saudável, vacinado e acompanhado por veterinário. <span class="text-danger">*</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <a href="<?php echo BASE_URL; ?>/petlove" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
                    </a>
                    <button type="submit" class="btn btn-cmp-primary flex-grow-1">
                        <i class="fa-solid fa-heart me-2"></i>Cadastrar no Pet Love
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('tem_pedigree')?.addEventListener('change', function () {
    document.getElementById('campoPedigreeNum').style.display = this.checked ? '' : 'none';
});

document.getElementById('btnGps')?.addEventListener('click', function () {
    const status = document.getElementById('gpsStatus');
    if (!navigator.geolocation) {
        status.textContent = 'Geolocalização não suportada neste navegador.';
        return;
    }
    status.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Obtendo localização...';
    navigator.geolocation.getCurrentPosition(
        pos => {
            document.getElementById('latitude').value  = pos.coords.latitude.toFixed(7);
            document.getElementById('longitude').value = pos.coords.longitude.toFixed(7);
            status.innerHTML = '<i class="fa-solid fa-circle-check text-success me-1"></i>Localização capturada!';
        },
        () => { status.textContent = 'Não foi possível obter a localização. Verifique as permissões.'; }
    );
});

document.getElementById('inputFotos')?.addEventListener('change', function () {
    const preview = document.getElementById('previewFotos');
    preview.innerHTML = '';
    const maxFiles = 5;
    const files    = Array.from(this.files).slice(0, maxFiles);
    files.forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.cssText = 'width:80px;height:80px;object-fit:cover;border-radius:.5rem;border:2px solid #dee2e6;';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
