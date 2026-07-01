<?php
/**
 * Template de e-mail: confirmação de cadastro
 * Variáveis esperadas:
 *   $nome  string — nome do usuário
 *   $link  string — URL de confirmação (já formada)
 */
$nomeSafe = htmlspecialchars($nome ?? '', ENT_QUOTES, 'UTF-8');
$linkSafe = htmlspecialchars($link ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirme seu e-mail — Cadê Meu Pet?</title>
</head>
<body style="margin:0;padding:0;background:#f6f8fb;font-family:'Helvetica Neue',Arial,sans-serif;color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f6f8fb;padding:32px 16px;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">

        <!-- Cabeçalho -->
        <tr>
          <td style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:36px 40px;text-align:center;">
            <div style="font-size:36px;margin-bottom:8px;">🐾</div>
            <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">Cadê Meu Pet?</h1>
            <p style="margin:8px 0 0;color:rgba(255,255,255,0.85);font-size:14px;">Bem-vindo(a) à nossa comunidade!</p>
          </td>
        </tr>

        <!-- Corpo -->
        <tr>
          <td style="padding:36px 40px;">
            <h2 style="margin:0 0 12px;font-size:20px;color:#1a202c;">Olá, <?php echo $nomeSafe; ?>!</h2>
            <p style="margin:0 0 16px;color:#4a5568;line-height:1.6;">
              Obrigado por se cadastrar no <strong>Cadê Meu Pet?</strong>. Para ativar sua conta e começar a ajudar pets e tutores, confirme seu e-mail clicando no botão abaixo.
            </p>

            <div style="background:#f7f8fc;border-left:4px solid #764ba2;border-radius:8px;padding:14px 18px;margin:24px 0;font-size:13px;color:#555;">
              ⏱ Este link é válido por <strong>48 horas</strong>.
            </div>

            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td align="center" style="padding:24px 0;">
                  <a href="<?php echo $linkSafe; ?>"
                     style="display:inline-block;background:#764ba2;color:#ffffff;text-decoration:none;font-weight:700;font-size:16px;padding:14px 36px;border-radius:999px;">
                    ✅ Confirmar meu e-mail
                  </a>
                </td>
              </tr>
            </table>

            <p style="margin:0 0 8px;color:#718096;font-size:13px;">Ou copie e cole este link no navegador:</p>
            <p style="margin:0;word-break:break-all;font-size:12px;color:#a0aec0;"><?php echo $linkSafe; ?></p>
          </td>
        </tr>

        <!-- Rodapé -->
        <tr>
          <td style="background:#f7f8fc;padding:24px 40px;text-align:center;font-size:12px;color:#a0aec0;border-top:1px solid #edf2f7;">
            Se você não se cadastrou no Cadê Meu Pet?, ignore este e-mail — sua conta não será criada.<br><br>
            <strong style="color:#718096;">Cadê Meu Pet?</strong> · reunindo pets às suas famílias 🐶🐱
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
