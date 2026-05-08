<?php
session_start();
require_once 'config.php';

$response = ['niveau' => 0];

if (isset($_SESSION['user']['IdJoueur'])) {
    $idJoueur = $_SESSION['user']['IdJoueur'];
    $sql = "SELECT COUNT(*) as niveau FROM HistoriqueCapital WHERE IdJoueur = ?";
    $stmt = $connexion->prepare($sql);
    $stmt->bind_param("i", $idJoueur);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $response['niveau'] = ($result['niveau'] > 3) ? 3 : $result['niveau'];
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);