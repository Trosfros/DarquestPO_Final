<?php
session_start();
require_once 'config.php'; 

function accorderRecompense($idJoueur, $niveau) {
    global $connexion;
    
    $recompenses = [
        1 => ["type" => "Or", "valeur" => 10],
        2 => ["type" => "Argent", "valeur" => 10],
        3 => ["type" => "Bronze", "valeur" => 10]
    ];
    
    if (!isset($recompenses[$niveau])) {
        return false;
    }
    
    $data = $recompenses[$niveau];
    
    // 1. Ajouter l'argent au joueur
    $sql = "UPDATE Joueurs SET Capital = Capital + ? WHERE IdJoueur = ?";
    $stmt = $connexion->prepare($sql);
    $stmt->bind_param("ii", $data['valeur'], $idJoueur);
    $stmt->execute();
    
    // 2. Enregistrer dans l'historique
    $historique = "Gain niveau $niveau : +10 " . $data['type'];
    $sql_log = "INSERT INTO HistoriqueCapital (IdJoueur, Description) VALUES (?, ?)";
    $stmt_log = $connexion->prepare($sql_log);
    $stmt_log->bind_param("is", $idJoueur, $historique);
    $stmt_log->execute();
    
    return true;
}
?>