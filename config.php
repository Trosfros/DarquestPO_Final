<?php
require_once 'include/user.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$domain = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/~darquest14';

if ($_SERVER['SERVER_ADDR'] == "158.69.48.57") {
    $serveur = "158.69.48.109";
    $utilisateur = "equipe14";
    $motdepasse = "u2ea2e47";
    $nomBaseDonnees = "dbdarquest14";
} else {
    $serveur = "localhost";
    $utilisateur = "root";
    $motdepasse = "";
    $nomBaseDonnees = "dbdarquest14";
}

$connexion = new mysqli($serveur, $utilisateur, $motdepasse, $nomBaseDonnees);

if ($connexion->connect_error) {
    die("Erreur de connexion: " . $connexion->connect_error);
}

$connexion->set_charset("utf8");

if (isset($_SESSION['user']))
    UpdateUserSessionInfo();
?>
