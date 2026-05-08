<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']['EstAdmin']) || $_SESSION['user']['EstAdmin'] != 1) {
    exit(json_encode(['error' => 'Non autorisé']));
}

$sql = "SELECT COUNT(*) as total FROM RequetesCapital WHERE Statut = 'En attente'";
$result = $connexion->query($sql);
$data = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode(['total_requetes' => $data['total']]);