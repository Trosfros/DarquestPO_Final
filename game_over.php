<?php
require_once 'config.php';
$user = $_SESSION['user'];

if ($user['PV'] > 0) {
    header('Location: enigma.php'); 
    exit();
}

$prixSoin = 50; 
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>GAME OVER - AVERSE</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #000; color: white; text-align: center; font-family: 'Cinzel', serif; }
        .death-container { margin-top: 100px; }
        .skull { font-size: 5rem; color: #cc0000; margin-bottom: 20px; }
        .buy-hp-card { 
            background: rgba(255,255,255,0.05); 
            border: 1px solid #333; 
            padding: 30px; 
            display: inline-block; 
            border-radius: 15px;
            margin-top: 30px;
        }
        .btn-revive {
            background: #d4af37; color: black; padding: 15px 30px;
            text-decoration: none; font-weight: bold; border-radius: 5px;
            display: inline-block; margin-top: 20px; transition: 0.3s;
        }
        .btn-revive:hover { background: #fff; transform: scale(1.05); }
    </style>
</head>
<body>
    <div class="death-container">
        <i class="fas fa-skull-crossbones skull"></i>
        <h1>Vous avez succombé...</h1>
        <p>Votre énergie vitale est épuisée.</p>

        <div class="buy-hp-card">
            <h3>Offrande aux Dieux</h3>
            <p>Donnez <strong><?= $prixSoin ?> PO</strong> pour retrouver votre pleine santé.</p>
            <p><small>(Votre solde : <?= $user['PieceOr'] ?> PO)</small></p>
            
            <?php if($user['PieceOr'] >= $prixSoin): ?>
                <a href="traiter_soin.php" class="btn-revive">RENAÎTRE</a>
            <?php else: ?>
                <p style="color: #cc0000;">Vous n'avez pas assez d'or pour revenir...</p>
                <a href="logout.php" style="color: #666;">Quitter l'aventure</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>