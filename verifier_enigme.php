<?php
require_once 'config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choix'], $_POST['id_enigme'])) {
    $user = @ $_SESSION['user'];
    $id_joueur = $user['IdJoueur'];
    $id_enigme = (int)$_POST['id_enigme']; 
    $choix_joueur = (int)$_POST['choix'];

    if (!$id_joueur) {
        header('Location: login.php');
        exit();
    }

    $stmt = $connexion->query(
        "SELECT * FROM Enigme e INNER JOIN CategorieEnigme c ON e.IdCategorie = c.IdCategorie WHERE IdEnigme = $id_enigme");
    $enigme = $stmt->fetch_assoc();

    if ($enigme) {
        $estUneEnigmeMagique = $enigme['EstMagie'];

       
        if ($enigme['Difficulte'] == 1) {
            $colonnePiece = "PieceBronze";
            $labelPiece = "pièces de Bronze";
        } elseif ($enigme['Difficulte'] == 2) {
            $colonnePiece = "PieceArgent";
            $labelPiece = "pièces d'Argent";
        } else {
            $colonnePiece = "PieceOr";
            $labelPiece = "pièces d'Or";
        }

        $reussi = $choix_joueur == $enigme['BonneReponse'] ? 1 : 0; 
        $connexion->query("INSERT INTO EssaieEnigmes(IdJoueur, IdEnigme, Reussi) VALUES ($id_joueur, $id_enigme, $reussi)");
        
        if ($reussi) {
            $status = "success";
            $message = "Bravo ! Bonne réponse. Vous gagnez 10 $labelPiece.";

            $connexion->query("UPDATE Joueurs SET $colonnePiece = $colonnePiece + 10 WHERE IdJoueur = $id_joueur");
            
           
            if ($estUneEnigmeMagique && $enigme['Difficulte'] == 3) {
               
                $connexion->query("UPDATE Joueurs SET StreakMagie = StreakMagie + 1 WHERE IdJoueur = $id_joueur");
                $connexion->query("UPDATE Joueurs SET MagieReussies = MagieReussies + 1 WHERE IdJoueur = $id_joueur");

                $userData = $connexion->query("SELECT StreakMagie, MagieReussies FROM Joueurs WHERE IdJoueur = $id_joueur")->fetch_assoc();
                
               
                if ($userData['MagieReussies'] >= 5) {
                    $connexion->query("UPDATE Joueurs SET EstMage = 1 WHERE IdJoueur = $id_joueur");
                }

               
                if ($userData['StreakMagie'] > 0 && $userData['StreakMagie'] % 3 == 0) {
                    $connexion->query("UPDATE Joueurs SET PieceOr = PieceOr + 100 WHERE IdJoueur = $id_joueur");
                    $message .= "<br>💰 **SÉRIE DE 3 !** Vous recevez 100 pièces d'Or bonus !";
                }
            }
        } else {
            $status = "error";
            $perte = ($enigme['Difficulte'] == 1) ? 3 : (($enigme['Difficulte'] == 2) ? 6 : 10);
            $message = "Dommage... Mauvaise réponse. Vous perdez $perte PV.";

            $connexion->query("UPDATE Joueurs SET PV = GREATEST(0, PV - $perte) WHERE IdJoueur = $id_joueur");
            
           
            if ($estUneEnigmeMagique && $enigme['Difficulte'] == 3) {
                $connexion->query("UPDATE Joueurs SET StreakMagie = 0 WHERE IdJoueur = $id_joueur");
                $message .= "<br>❌ Série brisée ! Le bonus de série retombe à zéro, mais votre progression de Mage est conservée.";
            }
        }

        $_SESSION['feedback'] = ['message' => $message, 'status' => $status];
        
        
        UpdateUserSessionInfo();

        $checkPV = $connexion->query("SELECT PV FROM Joueurs WHERE IdJoueur = $id_joueur")->fetch_assoc();
        if ($checkPV['PV'] <= 0) {
            header("Location: game_over.php");
        } else {
            header("Location: enigma.php");
        }
        exit();
    }
}