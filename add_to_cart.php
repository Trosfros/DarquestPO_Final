<?php
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['qty'])) {
    $itemId = intval($_POST['id']);
    $qty = intval($_POST['qty']);
    
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Logique de mise à jour
    if (isset($_SESSION['cart'][$itemId])) {
        $_SESSION['cart'][$itemId] += $qty;
        
      
        if ($_SESSION['cart'][$itemId] <= 0) {
            unset($_SESSION['cart'][$itemId]);
        }
    } else {
      
        if ($qty > 0) {
            $_SESSION['cart'][$itemId] = $qty;
        }
    }
    

    $totalPanier = !empty($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
    
    echo json_encode(['success' => true, 'total' => $totalPanier]);
} else {
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
}
?>
