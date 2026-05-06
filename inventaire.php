<?php
require_once 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$idJoueur = $_SESSION['user']['IdJoueur'];

$result = $connexion->query(
    "SELECT i.IdItem, i.Quantite, it.Nom, it.image, it.Prix, it.Rarete, it.Type, COALESCE(p.Soins, s.Soins, 0) AS soins
     FROM Inventaires i 
     JOIN Items it ON i.IdItem = it.IdItem
     left JOIN Potions p ON p.IdItem = i.IdItem
     left JOIN Sorts s ON s.IdItem = i.IdItem
     WHERE i.IdJoueur = $idJoueur"
);
$items_possedes = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>AVERSE - Inventaire</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/inventaire.css">
</head>
<body>

<?php include 'template/header.php'; ?>

<main class="inventory-container">
    <h1 class="inventory-title">Mon Inventaire</h1>

    <?php if (empty($items_possedes)): ?>
        <p style="text-align:center; color: #aaa;">Votre inventaire est vide.</p>
    <?php else: ?>
        <div class="inventory-grid">
            <?php foreach ($items_possedes as $item): 
                // Calcul dynamique du prix de vente pour l'affichage
                $prixVente = 0;
                if ($item['Type'] === 'S') {
                    if ($item['Rarete'] == 1) $prixVente = $item['Prix'];
                    elseif ($item['Rarete'] == 2) $prixVente = floor($item['Prix'] * 0.95);
                    elseif ($item['Rarete'] == 3) $prixVente = floor($item['Prix'] * 0.90);
                } else {
                    $prixVente = floor($item['Prix'] * 0.60);
                }
            ?>
                <div class="inventory-item">
                    <div class="item-img" style="height: 100px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                        <img src="img/<?= htmlspecialchars($item['image'] ?: 'default.png') ?>" alt="" style="max-height: 100%;">
                    </div>
                    <h3 style="margin: 5px 0;"><?= htmlspecialchars($item['Nom']) ?></h3>
                    <p style="color: #d4af37; font-weight: bold;">Quantité : <?= $item['Quantite'] ?></p>
                    <p style="font-size: 0.9em; color: #2ecc71;">Prix de vente : <?= $prixVente ?> <i class="fas fa-coins"></i></p>
                    
                    <div style="display: flex; gap: 5px; margin-top: 10px; alighn-items: center; justify-content: center;">
                        <button class="btn btn-sell" onclick="openModal(this, '<?= $item['Nom'] ?>', <?= $item['IdItem']?>, 'vendre.php')">Vendre</button>
                        <?php if ($item['soins'] > 0): ?>
                            <button class="btn btn-use" onclick="openModal(this, '<?= $item['Nom'] ?>', <?= $item['IdItem']?>, 'useItem.php')" <?= ($_SESSION['user']['PV'] == 100) ? 'disabled style="background: #626262;" title="Vous avez déjà tous vos points de vies."' : '' ?> >Utiliser</button>
                        <?php endif; ?>
                         
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<div id="confirmModal" class="modal-overlay">
    <div class="modal-content">
        <h2>Confirmation</h2>
        <p>Voulez-vous vraiment <span id="actionNameModal"></span><br>
            <strong id="itemNameModal">l'objet</strong> ?</p>
        <div class="modal-buttons">
            <button class="btn" style="background: #ccc;" onclick="closeModal()">Annuler</button>
            <button id="btn-confirm" class="btn"></button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>   
<script>
    function openModal(btn, nom, id, fichier) {
        const confirmBtn = document.getElementById('btn-confirm');
        confirmBtn.textContent = btn.innerText;
        confirmBtn.className = btn.className; 
        confirmBtn.onclick = () => onConfirm(id, fichier);

        document.getElementById('itemNameModal').innerText = nom;
        document.getElementById('actionNameModal').innerText = btn.innerText.toLowerCase();
        document.getElementById('confirmModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('confirmModal').style.display = 'none';
    }

   function onConfirm(id, fichier) {
    $.post(fichier, {id: id})
        .done(function(data) {
         
            window.location.reload(true); 
        })
        .fail(function() {
            alert("Une erreur est survenue lors de la vente.");
        });
    closeModal();
}

    window.onclick = function(event) {
        let modal = document.getElementById('confirmModal');
        if (event.target == modal) closeModal();
    }
</script>
</body>
</html>