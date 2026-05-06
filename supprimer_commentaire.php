<?php
require_once 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: connexion.php");
    exit();
}

$id_joueur_session = $_SESSION['user']['IdJoueur'];
$est_admin = isset($_SESSION['user']['EstAdmin']) && $_SESSION['user']['EstAdmin'] == 1;

$id_item = isset($_GET['id_item']) ? intval($_GET['id_item']) : 0;
$id_auteur_commentaire = isset($_GET['id_joueur']) ? intval($_GET['id_joueur']) : 0;

if ($id_item > 0 && $id_auteur_commentaire > 0) {
    
    if ($id_joueur_session == $id_auteur_commentaire || $est_admin) {
        
        $sql = "DELETE FROM Evaluations WHERE IdJoueur = ? AND IdItem = ?";
        $stmt = $connexion->prepare($sql);
        $stmt->bind_param("ii", $id_auteur_commentaire, $id_item);

        if ($stmt->execute()) {
 
            header("Location: produit.php?id=$id_item&msg=deleted");
        } else {
            header("Location: produit.php?id=$id_item&error=sql_delete");
        }
        $stmt->close();
    } else {
       
        header("Location: produit.php?id=$id_item&error=unauthorized");
    }
} else {
    header("Location: index.php");
}
exit();