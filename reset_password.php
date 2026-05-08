<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Réinitialisation du mot de passe</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style type="text/css">
    @media screen {
      @font-face {
        font-family: 'Source Sans Pro';
        font-style: normal;
        font-weight: 400;
        src: local('Source Sans Pro Regular'), local('SourceSansPro-Regular'), url(https://fonts.gstatic.com/s/sourcesanspro/v10/ODelI1aHBYDBqgeIAH2zlBM0YzuT7MdOe03otPbuUS0.woff) format('woff');
      }
    }
    body { width: 100% !important; height: 100% !important; padding: 0 !important; margin: 0 !important; }
    table { border-collapse: collapse !important; }
    a { color: #1a82e2; text-decoration: none; }
  </style>
</head>
<body style="background-color: #e9ecef; padding: 20px;">
  <table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td align="center">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-top: 3px solid #d4dadf;">
          
          <tr>
            <td align="left" style="padding: 36px 24px 0; font-family: 'Source Sans Pro', Helvetica, Arial, sans-serif;">
              <h1 style="margin: 0; font-size: 32px; font-weight: 700; line-height: 48px;">Réinitialiser votre mot de passe</h1>
            </td>
          </tr>

          <tr>
            <td align="left" style="padding: 24px; font-family: 'Source Sans Pro', Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px;">
              <p style="margin: 0;">Utilisez le bouton ci-dessous pour réinitialiser le mot de passe de votre compte <strong>AVERSE</strong>.</p>
            </td>
          </tr>

          <tr>
            <td align="center" style="padding: 12px 24px;">
              <table border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" bgcolor="#1a82e2" style="border-radius: 6px;">
                    <a href="<?= trim($domain) ?>/Darquest/reset_password_form.php?guid=<?= trim($guid) ?>" 
                       target="_blank" 
                       style="display: inline-block; padding: 16px 36px; font-family: 'Source Sans Pro', Helvetica, Arial, sans-serif; font-size: 16px; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; border: 1px solid #1a82e2;">
                       Confirmer le nouveau mot de passe
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td align="left" style="padding: 20px 24px; font-family: 'Source Sans Pro', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 20px; color: #666666;">
              <p style="margin: 0;">Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>
              <p style="margin: 10px 0; word-break: break-all;">
                <a href="<?= trim($domain) ?>/Darquest/reset_password_form.php?guid=<?= trim($guid) ?>" style="color: #1a82e2;">
                  <?= trim($domain) ?>/Darquest/reset_password_form.php?guid=<?= trim($guid) ?>
                </a>
              </p>
            </td>
          </tr>

          <tr>
            <td align="left" style="padding: 24px; font-family: 'Source Sans Pro', Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px; border-bottom: 3px solid #d4dadf">
              <p style="margin: 0;">Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer ce courriel en toute sécurité.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>