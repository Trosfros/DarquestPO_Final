<?php
require_once 'config.php';

$guid = $_GET['guid'];
$stmt = $connexion->prepare("UPDATE Joueurs SET Guid = NULL WHERE Guid = ?");
$stmt->bind_param("s", $guid);
$stmt->execute();
$stmt->close();

header("Location: login.php");

