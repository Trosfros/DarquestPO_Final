<?php
require_once 'config.php';
require_once 'Email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alias = $_POST['alias'];
    $stmt = $connexion->prepare("SELECT Mail FROM Joueurs WHERE Alias = ?");
    $stmt->bind_param("s", $alias);
    $stmt->execute();
    $mail = $stmt->get_result()->fetch_assoc()['Mail'];

    $guid = bin2hex(openssl_random_pseudo_bytes(16));
    $stmt = $connexion->prepare("UPDATE Joueurs SET GuidReset = ? WHERE Alias = ?");
    $stmt->bind_param("ss", $guid, $alias);
    $stmt->execute();

    require 'reset_password.php'; // $body
    Email::readConfig('gmail.ini');
    Email::send($mail, 'Réinitialisation du mot de passe', $body);
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
</head>

<body>
    <?php include_once 'template/header.php' ?>
    <div class="login-box">
        <h2 style="font-family: 'Cinzel', serif; color: #d4af37;">Réinitialisation du Mot de Passe</h2>
        <form method="POST">
            <input type="text" name="alias" placeholder="Alias (Pseudo)" required>
            <button type="submit" class="btn-login">Réinitilaliser le Mot de Passe</button>
        </form>
    </div>
    <?php include_once 'template/footer.php' ?>
</body>
<<<<<<< HEAD
</html>
=======
</html>
>>>>>>> 02141e3ba8185369bfabe6500f52d59d263fec7a
