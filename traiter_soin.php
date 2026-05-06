<?php
require_once 'config.php';
$user = $_SESSION['user'];
$prixSoin = 50;
$pvMax = 100; 

if ($user['PieceOr'] >= $prixSoin) {
    $id = $user['IdJoueur'];
    $connexion->query("UPDATE Joueurs SET PieceOr = PieceOr - $prixSoin, PV = $pvMax WHERE IdJoueur = $id");
    
    UpdateUserSessionInfo();
    
    header('Location: enigma.php?message=revived');
} else {
    header('Location: game_over.php?error=no_money');
}
exit();