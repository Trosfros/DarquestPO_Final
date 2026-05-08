<?php
require_once 'config.php';
require_once 'Email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guid'])) {
    $stmt = $connexion->prepare("UPDATE Joueurs SET MDP = ? WHERE GuidReset = ?");
    $stmt->bind_param("ss", password_hash($_POST['mdp'], PASSWORD_BCRYPT), $_POST['guid']);
    $stmt->execute();

    header("Location: login.php");
    exit();
}
?>

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

    <div class="login-box">
        <h2 style="font-family: 'Cinzel', serif; color: #d4af37;">Réinitialiser le Mot de Passe</h2>
        
        <form method="POST">
            <input type="hidden" name="guid" value="<?=$_GET['guid']?>">
            <input type="password" name="mdp" placeholder="Mot de passe" required>
            <button type="submit" class="btn-login">Réinitialiser le mot de passe</button>
        </form>
    </div>
    <?php include_once 'template/footer.php' ?>
</body>
</html>