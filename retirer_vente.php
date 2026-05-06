<?php
require_once 'config.php';

$idJoueur = $_SESSION['user']['IdJoueur']; // known safe
$qty = $_POST['qty'] ?? 1;
$item = $_POST['id'];
$stmt = $connexion->prepare("CALL RemoveItemFromMarket($idJoueur, ?, ?)");
$stmt->bind_param("ii", $item, $qty);
$stmt->execute();
$stmt->close();
UpdateUserSessionInfo();
?>
