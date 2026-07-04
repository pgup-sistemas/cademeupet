<?php
require_once __DIR__ . '/../config.php';

$pageTitle = 'Verificar Documento | Cadê Meu Pet?';
$controller = new VerificacaoController();

$codigo = trim($_GET['codigo'] ?? '');
$resultado = $codigo !== '' ? $controller->buscarPorCodigo($codigo) : null;

$tipoLabel = [
    'laudo' => 'Laudo Veterinário',
    'atestado' => 'Atestado Veterinário',
    'receituario' => 'Receituário Veterinário',
    'termo_adocao' => 'Termo de Responsabilidade de Adoção',
    'termo_responsabilidade' => 'Termo de Responsabilidade',
];
$statusLabel = [
    'rascunho' => 'Incompleto (ainda não assinado)',
    'aguardando_assinaturas' => 'Incompleto (aguardando assinaturas)',
    'assinado' => 'Válido',
    'revogado' => 'Revogado',
];
$statusCor = [
    'rascunho' => 'secondary',
    'aguardando_assinaturas' => 'warning text-dark',
    'assinado' => 'success',
    'revogado' => 'danger',
];

$breadcrumbs = [
    ['label' => 'Início', 'url' => BASE_URL],
    ['label' => 'Verificar Documento'],
];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <h1 class="h4 fw-bold mb-1">Verificação de autenticidade</h1>
            <p class="text-muted mb-4">Confira se um laudo, atestado, receituário ou termo emitido pela plataforma é genuíno.</p>

            <form method="GET" class="mb-4">
                <div class="input-group">
                    <input type="text" class="form-control" name="codigo" placeholder="Código de verificação (ex: A1B2C3D4E5)" value="<?php echo sanitize($codigo); ?>" required>
                    <button type="submit" class="btn btn-primary">Verificar</button>
                </div>
            </form>

            <?php if ($codigo !== ''): ?>
                <?php if (!$resultado): ?>
                    <div class="alert alert-danger">
                        <strong>Código não encontrado.</strong> Confira se digitou corretamente. Se o problema persistir, o documento pode não ter sido emitido por esta plataforma.
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h2 class="h5 fw-bold mb-0"><?php echo $tipoLabel[$resultado['tipo']] ?? ucfirst($resultado['tipo']); ?></h2>
                                <span class="badge bg-<?php echo $statusCor[$resultado['status']] ?? 'secondary'; ?>">
                                    <?php echo $statusLabel[$resultado['status']] ?? ucfirst($resultado['status']); ?>
                                </span>
                            </div>

                            <?php if ($resultado['foi_substituido']): ?>
                                <div class="alert alert-warning">
                                    Este documento foi retificado e substituído por uma versão mais recente.
                                    <a href="<?php echo BASE_URL; ?>/verificar?codigo=<?php echo sanitize($resultado['codigo_substituto']); ?>">Ver versão atual</a>.
                                </div>
                            <?php endif; ?>

                            <?php if ($resultado['eh_retificacao']): ?>
                                <div class="alert alert-info">Este documento é uma retificação de uma versão anterior.</div>
                            <?php endif; ?>

                            <?php if (!empty($resultado['referencia']['pet_nome'])): ?>
                                <p class="mb-1"><strong>Pet:</strong> <?php echo sanitize($resultado['referencia']['pet_nome']); ?>
                                    <?php if (!empty($resultado['referencia']['pet_especie'])): ?> (<?php echo sanitize(ucfirst($resultado['referencia']['pet_especie'])); ?>)<?php endif; ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($resultado['referencia']['emissor_nome'])): ?>
                                <p class="mb-1"><strong>Emitido por:</strong> <?php echo sanitize($resultado['referencia']['emissor_nome']); ?>
                                    <?php if (!empty($resultado['referencia']['emissor_local'])): ?> — <?php echo sanitize($resultado['referencia']['emissor_local']); ?><?php endif; ?>
                                </p>
                            <?php endif; ?>
                            <p class="mb-3"><strong>Emitido em:</strong> <?php echo formatDateTimeBR($resultado['criado_em']); ?></p>

                            <?php if (!empty($resultado['assinaturas'])): ?>
                                <h3 class="h6 fw-bold mt-3">Assinaturas registradas</h3>
                                <ul class="small">
                                    <?php foreach ($resultado['assinaturas'] as $a): ?>
                                        <li>
                                            <?php echo sanitize($a['usuario_nome']); ?>
                                            <?php if (!empty($a['identificacao_extra'])): ?> (<?php echo sanitize($a['identificacao_extra']); ?>)<?php endif; ?>
                                            — <?php echo sanitize(str_replace('_', ' ', ucfirst($a['papel']))); ?>
                                            em <?php echo formatDateTimeBR($a['assinado_em']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted small">Nenhuma assinatura registrada ainda — documento incompleto.</p>
                            <?php endif; ?>

                            <p class="small text-muted mt-3 mb-0">
                                Por privacidade do tutor e do animal, o conteúdo clínico completo não é exibido nesta página pública —
                                apenas os metadados necessários para confirmar autenticidade.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
