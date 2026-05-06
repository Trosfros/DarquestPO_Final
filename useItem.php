<?php
require_once 'config.php';

$joueur = $_SESSION['user']['IdJoueur'];  

$stmt = $connexion->prepare("
    SELECT COALESCE(p.Soins, s.Soins, 0) AS Soins
    FROM Inventaires i                              
    LEFT JOIN Potions p ON p.IdItem = i.IdItem
    LEFT JOIN Sorts s ON s.IdItem = i.IdItem
    WHERE i.IdItem = ? AND i.IdJoueur = $joueur
");
$stmt->bind_param("i", $_POST['id']);
$stmt->execute();
$soins = $stmt->get_result()->fetch_assoc()['Soins'];

$connexion->query("UPDATE Joueurs SET PV = LEAST(PV + $soins, 100) WHERE IdJoueur = $joueur");
$stmt = $connexion->prepare("
    UPDATE Inventaires SET Quantite = Quantite - 1
    WHERE IdItem = ? AND IdJoueur = $joueur
");
$stmt->bind_param("i", $_POST['id']);
$stmt->execute();

$connexion->query("DELETE FROM Inventaires WHERE Quantite = 0");
UpdateUserSessionInfo();
?>
