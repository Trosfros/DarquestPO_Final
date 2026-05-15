<?php
require_once 'config.php';
require_once 'Email.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alias = $_POST['alias'];
    
    $stmt = $connexion->prepare("SELECT Mail FROM Joueurs WHERE Alias = ?");
    $stmt->bind_param("s", $alias);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && !empty($user['Mail'])) {
        $mail = $user['Mail'];

        $guid = bin2hex(openssl_random_pseudo_bytes(16));
        $stmt = $connexion->prepare("UPDATE Joueurs SET GuidReset = ? WHERE Alias = ?");
        $stmt->bind_param("ss", $guid, $alias);
        $stmt->execute();

        ob_start();
        require 'reset_password.php'; 
        $body = ob_get_clean(); 
       

        Email::readConfig('gmail.ini');
        
        try {
            Email::send($mail, 'Réinitialisation du mot de passe', $body);
            header("Location: login.php");
            exit();
        } catch (Exception $e) {
            $error = "Erreur lors de l'envoi de l'e-mail : " . $e->getMessage();
        }
    } else {
        $error = "Aucun utilisateur trouvé avec cet alias ou l'adresse e-mail est invalide.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>AVERSE - Réinitialisation</title>
    <link rel="stylesheet" href="CSS/style.css"> 
    <link rel="stylesheet" href="styles/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>
    <?php include_once 'template/header.php' ?>
    <div class="login-box">
        <h2 style="font-family: 'Cinzel', serif; color: #d4af37;">Réinitialisation</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-message" style="color: #ff4b2b; margin-bottom: 15px; text-align: center; font-weight: bold;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="alias" placeholder="Alias (Pseudo)" required>
            <button type="submit" class="btn-login">Réinitialiser le Mot de Passe</button>
        </form>
    </div>
    <?php include_once 'template/footer.php' ?>
</body>
</html>
