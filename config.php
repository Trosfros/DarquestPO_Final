<?php
require_once 'include/user.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$serveur = "localhost";
$utilisateur = "usager22";
$motdepasse = "BhgXWedvSMAV";
$nomBaseDonnees = "usager22";

$connexion = new mysqli($serveur, $utilisateur, $motdepasse, $nomBaseDonnees);

if ($connexion->connect_error) {
    die("Erreur de connexion: " . $connexion->connect_error);
}

$connexion->set_charset("utf8");

if (isset($_SESSION['user']))
    UpdateUserSessionInfo();
?>