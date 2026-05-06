<?php
require_once 'config.php';

$result = $connexion->query("CALL GetMarketItems(12, '', 'N')");
$items = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AVERSE - Accueil</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/style.css">

</head>
<body>

<?php include_once 'template/header.php' ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'forbidden'): ?>
    <div style="background: rgba(231, 76, 60, 0.2); border: 1px solid #e74c3c; color: #e74c3c; padding: 15px; text-align: center; margin: 20px auto; max-width: 800px; border-radius: 8px; font-weight: bold; font-family: sans-serif;">
        🚫 ACCÈS REFUSÉ : Vous n'avez pas les privilèges requis pour accéder à cette zone du grimoire.
    </div>
<?php endif; ?>

<main>
    <div class="nouveaute-header">
        <h1 class="title-new">
            <span class="animated-text">Nouveautés</span>
            <span class="badge-new">NEW</span>
        </h1>
    </div>

    <section class="product-grid">
        <?php if (!empty($items)): ?>
            <?php foreach ($items as $index => $item): ?>
                <?php $isOutOfStock = ($item['Quantite'] <= 0); ?>

                <div class="product-card" style="animation-delay: <?= $index * 0.05 ?>s">
                    <a href="produit.php?id=<?= $item['IdItem'] ?>" style="text-decoration: none; color: inherit;">
                        <span class="product-type"><?= htmlspecialchars($item['NomType']) ?></span>

                        <div class="img-box" style="<?= $isOutOfStock ? 'filter: grayscale(1); opacity: 0.5;' : '' ?>">
                            <?php if (!empty($item['image'])): ?>
                                <img src="img/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['Nom']) ?>">
                            <?php else: ?>
                                <i class="fa-regular fa-image" style="font-size: 3rem; color: #eee;"></i>
                            <?php endif; ?>
                        </div>

                        <h3><?= htmlspecialchars($item['Nom']) ?></h3>
<div class="price">
    <?= number_format($item['Prix'], 0, '.', ' ') ?> 
    <img src="img/gold.png" alt="Or" class="coin-icon-small">
                                                         </div>
                    </a>

                    <?php if ($isOutOfStock): ?>
                        <div class="stock-status">RUPTURE DE STOCK</div>
                        <button class="add-btn out-of-stock" disabled title="Cet article n'est plus en stock">
                            <i class="fa-solid fa-ban"></i> Indisponible
                        </button>
                    <?php else: ?>
                        <button class="add-btn" onclick="addToCart(<?= $item['IdItem'] ?>)">
                            <i class="fa-solid fa-plus"></i> Ajouter au panier
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; grid-column: 1/-1; color: #999;">Aucun trésor n'est disponible pour le moment.</p>
        <?php endif; ?>
    </section> </main>

<footer>
    <p>Powered by StackForge • AVERSE © 2026</p>
</footer>

<script>
    function addToCart(id) {
        let formData = new FormData();
        formData.append('id', id);
        formData.append('qty', 1);

        fetch('add_to_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let count = document.getElementById('cart-count');
                if(count) {
                    count.innerText = data.total;
                    count.parentElement.style.transform = "scale(1.2)";
                    setTimeout(() => count.parentElement.style.transform = "scale(1)", 200);
                }
            } else {
                alert("Erreur : " + data.error);
            }
        })
        .catch(error => console.error('Erreur:', error));
    }
</script>
</body>
</html>
