<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>AVERSE - Inscription</title>
    <link rel="stylesheet" href="CSS/style.css">
        <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/register.css">
</head>
<body>
    <?php include_once 'template/header.php' ?>

    <div class="register-box">
        <h2 style="font-family: 'Cinzel', serif; color: #d4af37;">Nouveau Guerrier</h2>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'alias_taken'): ?>
            <div class="error-msg">
                ⚠️ Cet alias est déjà possédé par un autre guerrier !
            </div>
        <?php endif; ?>

        <form action="process_register.php" method="POST">
            <input type="text" name="prenom" placeholder="Prénom" required>
            <input type="text" name="nom" placeholder="Nom" required>
            <input type="text" name="alias" placeholder="Alias (Pseudo)" required>
            <input type="text" name="mail" placeholder="Mail" required>
            <input type="password" name="mdp" placeholder="Mot de passe" required>
            <button type="submit" class="btn-reg">Rejoindre AVERSE</button>
        </form>
        
        <p><a href="login.php" style="color: #999; font-size: 0.8rem;">Déjà inscrit ? Connectez-vous</a></p>
    </div>

    <?php include_once 'template/footer.php' ?>

</body>
</html>
