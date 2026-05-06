<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alias = $_POST['alias'];
    $mdp = $_POST['mdp'];

    $stmt = $connexion->prepare("SELECT MDP, Guid FROM Joueurs WHERE Alias = ?");
    $stmt->bind_param("s", $alias);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row && password_verify($mdp, $row['MDP']) && $row['Guid'] == null) {
        LogUser($alias);
        header("Location: index.php");
        exit();
    } else {
        header("Location: login.php?error=1");
        exit();
    }
}
?>
