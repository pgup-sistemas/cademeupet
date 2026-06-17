<?php
/**
 * Template de e-mail para resumo de alertas de busca
 * Variáveis esperadas:
 * - $alertData: dados do alerta criado
 * - $anunciosResumo: lista de anúncios retornados pelo alerta
 * - $baseUrl: URL base da aplicação
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Resumo do seu alerta - Cadê Meu Pet?</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #f6f8fb; margin: 0; padding: 0; color: #333; }
        .container { max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 35px rgba(31,45,61,0.12); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 32px 40px; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 32px 40px; }
        .alert-meta { margin-bottom: 24px; padding: 16px; border-left: 4px solid #764ba2; background: #f3f5ff; border-radius: 8px; font-size: 14px; color: #4a4a68; }
        .pet-card { border: 1px solid #edf2f7; border-radius: 10px; padding: 16px; margin-bottom: 16px; display: flex; gap: 16px; }
        .pet-image { width: 96px; height: 96px; border-radius: 10px; object-fit: cover; background: #f1f1f1; display: flex; align-items: center; justify-content: center; font-size: 32px; }
        .pet-info { flex: 1; }
        .pet-info h3 { margin: 0 0 8px; font-size: 18px; color: #2d3748; }
        .pet-info p { margin: 0 0 6px; font-size: 14px; color: #4a5568; }
        .cta { text-align: center; margin-top: 32px; }
        .cta a { display: inline-block; padding: 12px 28px; border-radius: 999px; background: #764ba2; color: #ffffff; text-decoration: none; font-weight: bold; }
        .footer { text-align: center; font-size: 12px; color: #718096; padding: 24px 16px 40px; }
        .pill { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; margin-right: 6px; }
        .pill-danger { background: #fed7d7; color: #c53030; }
        .pill-success { background: #c6f6d5; color: #2f855a; }
        @media (max-width: 600px) {
            .content, .header { padding: 24px; }
            .pet-card { flex-direction: column; align-items: center; text-align: center; }
            .pet-image { width: 140px; height: 140px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Novidades sobre seu alerta <i class="fa-solid fa-bullseye"></i></h1>
            <p style="margin: 8px 0 0; font-size: 15px; opacity: 0.85;">
                Encontramos anúncios que combinam com as preferências do seu alerta.
            </p>
        </div>
        <div class="content">
            <div class="alert-meta">
                <strong>Filtro:</strong>
                <?php
                    $tipoLabel = [
                        'perdido' => 'Pets perdidos',
                        'encontrado' => 'Pets encontrados',
                        'ambos' => 'Qualquer status'
                    ][strtolower($alertData['tipo'] ?? 'ambos')] ?? 'Qualquer status';

                    $especieLabel = $alertData['especie'] ? ucfirst(sanitize($alertData['especie'])) : 'Qualquer espécie';
                ?>
                <br>
                <?php echo sanitize($tipoLabel); ?> · <?php echo sanitize($especieLabel); ?>
                <br>
                <?php echo sanitize($alertData['cidade']); ?> - <?php echo sanitize($alertData['estado']); ?> · Raio <?php echo (int) $alertData['raio_km']; ?> km
            </div>

            <?php foreach ($anunciosResumo as $anuncio): ?>
                <div class="pet-card">
                    <div class="pet-image">
                        <?php if (!empty($anuncio['foto'])): ?>
                            <img src="<?php echo $baseUrl . '/uploads/anuncios/' . sanitize($anuncio['foto']); ?>" alt="Foto do pet" style="width: 100%; height: 100%; border-radius: 10px; object-fit: cover;">
                        <?php else: ?>
                            <span><i class="fa-solid fa-paw"></i></span>
                        <?php endif; ?>
                    </div>
                    <div class="pet-info">
                        <div style="margin-bottom: 6px;">
                            <?php if (($anuncio['tipo'] ?? '') === 'perdido'): ?>
                                <span class="pill pill-danger">Perdido</span>
                            <?php else: ?>
                                <span class="pill pill-success">Encontrado</span>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo sanitize($anuncio['nome_pet'] ?: 'Pet ' . ucfirst($anuncio['especie'])); ?></h3>
                        <p>
                            <strong>Localização:</strong>
                            <?php echo sanitize($anuncio['bairro']); ?>, <?php echo sanitize($anuncio['cidade']); ?>
                        </p>
                        <?php if (!empty($anuncio['descricao'])): ?>
                            <p><?php echo sanitize(truncate($anuncio['descricao'], 140)); ?></p>
                        <?php endif; ?>
                        <p style="font-size: 12px; color: #718096; margin-top: 12px;">
                            Publicado em <?php echo formatDateTimeBR($anuncio['data_publicacao']); ?>
                        </p>
                        <p style="margin-top: 14px;">
                            <a href="<?php echo $baseUrl . '/anuncio/' . (int) $anuncio['id'] . '/'; ?>" style="display: inline-block; padding: 10px 18px; background: #667eea; color: #fff; border-radius: 999px; text-decoration: none; font-size: 13px;">Ver anúncio completo</a>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="cta">
                <a href="<?php echo $baseUrl . '/alertas.php'; ?>">Gerenciar meus alertas</a>
            </div>
        </div>
        <div class="footer">
            Você está recebendo este e-mail porque solicitou alertas de busca no Cadê Meu Pet?.<br>
            Para alterar suas preferências, <a href="<?php echo $baseUrl . '/perfil.php'; ?>" style="color: #667eea; text-decoration: none;">acesse seu perfil</a>.
            <br><br>
            <strong>Cadê Meu Pet?</strong> · reunindo pets às suas famílias desde 2025 <i class="fa-solid fa-paw"></i>
        </div>
    </div>
</body>
</html>
