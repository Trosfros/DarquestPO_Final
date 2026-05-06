<?php
require_once 'config.php';

// On vérifie que l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header("Location: connexion.php");
    exit();
}

$id_joueur = $_SESSION['user']['IdJoueur'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $id_item = isset($_POST['id_item']) ? intval($_POST['id_item']) : 0;
    $etoiles = isset($_POST['etoiles']) ? intval($_POST['etoiles']) : 0;
    $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';

    
    $check_possess = $connexion->prepare("SELECT 1 FROM Inventaires WHERE IdJoueur = ? AND IdItem = ?");
    $check_possess->bind_param("ii", $id_joueur, $id_item);
    $check_possess->execute();
    $result_possess = $check_possess->get_result();

    if ($result_possess->num_rows > 0) {
      
        $sql = "INSERT INTO Evaluations (IdJoueur, IdItem, Etoiles, Commentaire) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE Etoiles = VALUES(Etoiles), Commentaire = VALUES(Commentaire)";
        
        $stmt = $connexion->prepare($sql);
        $stmt->bind_param("iiis", $id_joueur, $id_item, $etoiles, $commentaire);

        if ($stmt->execute()) {
          
            header("Location: produit.php?id=$id_item&success=1");
        } else {
           
            header("Location: produit.php?id=$id_item&error=sql");
        }
        $stmt->close();
    } else {
       
        header("Location: produit.php?id=$id_item&error=not_owned");
    }
    $check_possess->close();
    exit();
} else {
  
    header("Location: index.php");
    exit();
}