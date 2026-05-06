<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || empty($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Session invalide ou panier vide']);
    exit;
}
$idJoueur = $_SESSION['user']['IdJoueur'];

try {
    $connexion->begin_transaction();

    foreach ($_SESSION['cart'] as $itemId => $qty) {
        $sql = "CALL BuyItem('$idJoueur',?,?)";
        $stmt = $connexion->prepare($sql);
        $stmt->bind_param("ii", $itemId, $qty);
        $stmt->execute();
        $stmt->close();
        }


    $_SESSION['cart'] = [];

    $connexion->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {

    $connexion->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
 UpdateUserSessionInfo();
 