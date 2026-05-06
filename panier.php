<?php
require_once 'config.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$totalItems = 0;
$items = [];
$total = 0;
$hasStockError = false;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $itemId => $itemQty) {
        $sql = "CALL GetItemById(?)";
        $stmt = $connexion->prepare($sql);
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stockDisponible = $row['Quantite'];
            $insufficient = ($itemQty > $stockDisponible);
            if ($insufficient) $hasStockError = true;

            $items[] = [
                'id' => $row['IdItem'],
                'name' => $row['Nom'],
                'price' => $row['Prix'],
                'qty' => $itemQty,
                'stock' => $stockDisponible,
                'insufficient' => $insufficient,
                'image' => $row['image']
            ];
            $totalItems += $itemQty;
            $total += ($row['Prix'] * $itemQty);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>AVERSE - Panier</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/panier.css">
    <style>
       
        .coin-icon-small {
            width: 20px;
            height: 20px;
            vertical-align: middle;
            margin-left: 5px;
            filter: drop-shadow(0 2px 2px rgba(0,0,0,0.3));
        }

        .summary-box {
            background: linear-gradient(145deg, #1a1a1a, #2a2a2a);
            border: 2px solid #d4af37;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            color: white;
        }

        .total-container {
            border-top: 1px solid rgba(212, 175, 55, 0.3);
            border-bottom: 1px solid rgba(212, 175, 55, 0.3);
            padding: 20px 0;
            margin: 20px 0;
            text-align: center;
        }

        .total-label {
            font-family: 'Cinzel', serif;
            font-size: 0.9rem;
            letter-spacing: 2px;
            color: #aaa;
            display: block;
            margin-bottom: 5px;
        }

        .total-amount {
            font-size: 2.2rem;
            font-weight: 700;
            color: #d4af37;
            text-shadow: 0 0 15px rgba(212, 175, 55, 0.4);
        }

        .qty-input {
            background: #f0f0f0 !important;
            color: #d4af37 !important;
            border:none !important;
            font-weight: bold;
            width: 45px !important;
            text-align: center;
        }

        .item-price-total {
            font-family: 'Cinzel', serif;
            font-size: 1.4rem;
            color: #d4af37;
        }
    </style>
</head>
<body>

<?php include_once 'template/header.php' ?>

<main class="container">
    <div class="cart-header">
        <h1 class="cart-title">Votre Panier</h1>
    </div>

    <?php if (empty($items)): ?>
        <div class="empty-cart" style="text-align:center; padding:80px 0;">
            <i class="fa-solid fa-box-open" style="font-size: 5rem; color: #ddd; margin-bottom: 20px;"></i>
            <h2 style="font-family: 'Cinzel', serif;">Votre panier est vide</h2>
            <a href="index.php" class="btn-order" style="text-decoration:none; display:inline-block; width:auto; padding: 15px 40px;">Boutique</a>
        </div>
    <?php else: ?>

        <?php if ($hasStockError): ?>
            <div class="error-banner">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    <strong>Alerte de Stock !</strong> Veuillez ajuster les quantités en rouge.
                </div>
            </div>
        <?php endif; ?>

        <div class="cart-layout" id="main-layout">
            <div class="cart-items-list">
                <?php foreach ($items as $item): ?>
                <div class="cart-item <?= $item['insufficient'] ? 'item-error' : '' ?>">
                    <a href="produit.php?id=<?= $item['id']?>" class="item-img-box">
                        <img src="img/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    </a>

                    <div class="item-details">
                        <h3 style="margin: 0 0 5px 0; font-size: 1.2rem; font-family: 'Cinzel', serif;"><?= htmlspecialchars($item['name']) ?></h3>
                        <span style="color: #888; font-size: 0.9rem;">
                            Prix unitaire: <?= number_format($item['price'], 0, '.', ' ') ?> 
                            <img src="img/gold.png" alt="Or" class="coin-icon-small">
                        </span>

                        <div class="quantity-control" style="margin-top: 15px; display: flex; align-items: center;">
                            <button class="qty-btn" onclick="changeQty(<?= $item['id'] ?>, -1)">
                                <?= ($item['qty'] > 1) ? '<i class="fa-solid fa-minus"></i>' : '<i class="fa-solid fa-trash-can" style="color:#e74c3c"></i>' ?>
                            </button>
                            
                            <input type="number" 
                                   id="input-qty-<?= $item['id'] ?>"
                                   class="qty-input" 
                                   value="<?= $item['qty'] ?>" 
                                   onchange="manualUpdate(<?= $item['id'] ?>, <?= $item['qty'] ?>)">
                            
                            <button class="qty-btn" onclick="changeQty(<?= $item['id'] ?>, 1)">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        
                        <?php if($item['insufficient']): ?>
                            <div class="stock-warning" style="color: #ff4d4d; font-size: 0.8rem; margin-top: 5px;">
                                <i class="fa-solid fa-circle-exclamation"></i> Stock disponible : <?= $item['stock'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="text-align: right; min-width: 120px;">
                        <div class="item-price-total">
                            <?= number_format($item['price'] * $item['qty'], 0, '.', ' ') ?> 
                            <img src="img/gold.png" alt="Or" class="coin-icon-small">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <aside class="cart-summary">
                <div class="summary-box">
                    <h2 style="font-family: 'Cinzel', serif; text-align: center; font-size: 1.2rem; color: #d4af37;">Récapitulatif</h2>
                    
                    <div class="total-container">
                        <span class="total-label">TOTAL À PAYER</span>
                        <div class="total-amount">
                            <?= number_format($total, 0, '.', ' ') ?>
                            <img src="img/gold.png" alt="Or" style="width: 32px; height: 32px; vertical-align: baseline;">
                        </div>
                    </div>

                    <?php if(!isset($_SESSION['user'])): ?>
                        <a href="register.php" class="btn-order" style="text-decoration:none; display:block; text-align:center;">Se connecter</a>
                    <?php else: ?>
                        <button id="btn-valider-commande" 
                                onclick="validerCommande()" 
                                class="btn-order" 
                                style="width: 100%; font-family: 'Cinzel', serif; font-size: 1rem;"
                                <?= $hasStockError ? 'disabled' : '' ?>>
                            <?= $hasStockError ? 'Stocks Insuffisants' : 'Finaliser la Transaction' ?>
                        </button>
                    <?php endif; ?>
                    
                    <div style="margin-top: 20px; text-align: center; font-size: 0.8rem; color: #888;">
                        <i class="fa-solid fa-shield-halved"></i> Transaction sécurisée par la Guilde
                    </div>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</main>

<div id="success-overlay" class="order-success-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:9999; justify-content:center; align-items:center; flex-direction:column;">
    <div style="text-align:center; color:white;">
        <i class="fa-solid fa-circle-check" style="font-size: 5rem; color: #2ecc71; margin-bottom: 20px;"></i>
        <h2 style="font-family: 'Cinzel', serif; font-size: 2.5rem;">Achat Réussi !</h2>
        <p>Vos items ont été transférés dans votre inventaire.</p>
    </div>
</div>

<script>
// Fonction pour modifier la quantité avec les boutons +/-
function changeQty(id, delta) {
    updateServerQty(id, delta);
}

// Fonction pour la saisie manuelle dans l'input
function manualUpdate(id, oldVal) {
    const input = document.getElementById('input-qty-' + id);
    const newVal = parseInt(input.value);
    
    if (isNaN(newVal) || newVal < 1) {
        input.value = 1;
        updateServerQty(id, 1 - oldVal);
    } else {
        updateServerQty(id, newVal - oldVal);
    }
}

// Envoi de la mise à jour au serveur
function updateServerQty(id, delta) {
    let formData = new FormData();
    formData.append('id', id);
    formData.append('qty', delta);
    
    fetch('add_to_cart.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload(); 
        } else {
            alert("Erreur : " + (data.error || "Action impossible"));
        }
    });
}

function validerCommande() {
    const btn = document.getElementById('btn-valider-commande');
    const overlay = document.getElementById('success-overlay');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> TRAITEMENT...';

    fetch('commander.php', { method: 'POST' })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            overlay.style.display = 'flex';
            setTimeout(() => { window.location.href = 'inventaire.php'; }, 2500);
        } else {
            alert("Erreur lors de l'achat : " + data.message);
            location.reload();
        }
    });
}
</script>
</body>
</html>