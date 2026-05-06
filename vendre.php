<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $idJoueur = $_SESSION['user']['IdJoueur']; // Cohérence avec inventaire.php
    $idItem = intval($_POST['id']);
    $quantiteAVendre = 1;

    // 1. Récupérer les infos de l'item
    $stmt = $connexion->prepare("
        SELECT i.Quantite, it.Prix, it.Type, it.Rarete 
        FROM Inventaires i 
        JOIN Items it ON i.IdItem = it.IdItem 
        WHERE i.IdJoueur = ? AND i.IdItem = ?");
    $stmt->bind_param("ii", $idJoueur, $idItem);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();

    if ($item && $item['Quantite'] >= $quantiteAVendre) {
        $prixVente = 0;

        
        if ($item['Type'] === 'S') {
            if ($item['Rarete'] == 1) $prixVente = $item['Prix'];
            elseif ($item['Rarete'] == 2) $prixVente = floor($item['Prix'] * 0.95);
            elseif ($item['Rarete'] == 3) $prixVente = floor($item['Prix'] * 0.90);
        } else {
            // Armes, Armures, Potions
            $prixVente = floor($item['Prix'] * 0.60);
        }

        $connexion->begin_transaction();
        try {
            
            $stmtSell = $connexion->prepare("CALL SellItem(?, ?, ?)");
            $stmtSell->bind_param("iii", $idJoueur, $idItem, $quantiteAVendre);
            $stmtSell->execute();

           
            $stmtMoney = $connexion->prepare("UPDATE Joueurs SET PieceOr = PieceOr + ? WHERE IdJoueur = ?");
            $stmtMoney->bind_param("ii", $prixVente, $idJoueur);
            $stmtMoney->execute();

        
            $stmtConv = $connexion->prepare("CALL ConvertCoinsToGold(?)");
            $stmtConv->bind_param("i", $idJoueur);
            $stmtConv->execute();

            $connexion->commit();
            echo "Succès : Vendu pour $prixVente pièces d'or.";
        } catch (Exception $e) {
            $connexion->rollback();
            http_response_code(500);
            echo "Erreur lors de la transaction.";
        }
    }
}