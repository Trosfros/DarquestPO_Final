<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>AVERSE - Connexion</title>
    <link rel="stylesheet" href="CSS/style.css"> 
    <link rel="stylesheet" href="styles/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<body>
    
    <?php include_once 'template/header.php' ?>
    

    <p></p>
    <?php if (isset($_GET['message']) && $_GET['message'] === 'restricted'): ?>
        <div style="color: #e74c3c; background: rgba(231, 76, 60, 0.1); border: 1px solid #e74c3c; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold;">
            <i class="fas fa-exclamation-circle"></i> 
            Vous devez être connecté pour accéder aux quêtes.
        </div>
<?php endif; ?>
    <div class="login-box">
        <h2 style="font-family: 'Cinzel', serif; color: #d4af37;">Accès au Royaume</h2>
        <form action="auth.php" method="POST">
            <input type="text" name="alias" placeholder="Alias (Pseudo)" required>
            <input type="password" name="mdp" placeholder="Mot de passe" required>
            <button type="submit" class="btn-login">Se connecter</button>
        </form>
        <?php if(isset($_GET['error'])) echo "<p style='color:red;'>Identifiants invalides</p>"; ?>

        <a href="send_reset_password.php" class="reset-password">
            Réinitialiser le mot de passe
        </a>
    </div>
    <?php include_once 'template/footer.php' ?>
</body>
</html>
