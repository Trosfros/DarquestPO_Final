<?php
require_once 'config.php';

$id_produit = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_joueur = $_SESSION['user']['IdJoueur'] ?? 0;

$sql = "CALL GetItemById(?)";
$stmt = $connexion->prepare($sql);
$stmt->bind_param("i", $id_produit);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$produit = $result->fetch_assoc();
$stmt->close();


$type_noms = [
    'A' => 'Arme', 
    'R' => 'Armure',  
    'P' => 'Potion', 
    'S' => 'Sort'     
];

$type_icones = [
    'A' => 'fa-sword', 
    'R' => 'fa-shield', 
    'P' => 'fa-flask', 
    'S' => 'fa-wand-magic-sparkles'
];

$label_type = $type_noms[strtoupper($produit['Type'])] ?? 'Objet';
$icone_type = $type_icones[strtoupper($produit['Type'])] ?? 'fa-circle';
// ----------------------------------------------------------

$stats_sql = "SELECT 
                COUNT(*) as total, 
                AVG(Etoiles) as moyenne,
                SUM(CASE WHEN Etoiles = 5 THEN 1 ELSE 0 END) as n5,
                SUM(CASE WHEN Etoiles = 4 THEN 1 ELSE 0 END) as n4,
                SUM(CASE WHEN Etoiles = 3 THEN 1 ELSE 0 END) as n3,
                SUM(CASE WHEN Etoiles = 2 THEN 1 ELSE 0 END) as n2,
                SUM(CASE WHEN Etoiles = 1 THEN 1 ELSE 0 END) as n1
              FROM Evaluations WHERE IdItem = ?";
$stmt_stats = $connexion->prepare($stats_sql);
$stmt_stats->bind_param("i", $id_produit);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$total_evals_count = $stats['total'];
$divisor = ($total_evals_count > 0) ? $total_evals_count : 1; 


$com_sql = "SELECT e.*, j.Alias as Pseudo FROM Evaluations e 
            JOIN Joueurs j ON e.IdJoueur = j.IdJoueur 
            WHERE e.IdItem = ? ORDER BY e.IdJoueur DESC"; 
$stmt_com = $connexion->prepare($com_sql);
$stmt_com->bind_param("i", $id_produit);
$stmt_com->execute();
$commentaires = $stmt_com->get_result();

$isOutOfStock = ($produit['Quantite'] <= 0);
$stockMax = intval($produit['Quantite']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AVERSE - <?= htmlspecialchars($produit['Nom']) ?></title>
    <link rel="stylesheet" href="CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/produit.css">
    <style>
        .evaluations-container { margin-top: 50px; padding: 30px; background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .eval-header { display: flex; align-items: center; gap: 40px; padding: 25px; background: #fcfcfc; border-radius: 12px; margin-bottom: 30px; border: 1px solid #eee; }
        .avg-num { font-size: 3.5rem; font-family: 'Cinzel'; color: #b08d57; line-height: 1; }
        .stars-row { display: flex; align-items: center; gap: 15px; margin-bottom: 10px; width: 100%; }
        .bar-container { flex-grow: 1; height: 12px; background: #eee; border-radius: 6px; overflow: hidden; }
        .bar-fill { height: 100%; background: #ffc107; transition: width 0.5s ease; }
        .eval-card { background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #f0f0f0; margin-bottom: 20px; transition: transform 0.2s; }
        .eval-card:hover { transform: translateY(-2px); border-color: #b08d57; }
        .star-active { color: #ffc107; }
        .star-inactive { color: #ddd; }
        .form-eval { background: #fdfaf5; border: 1px solid #f3e9d8; padding: 25px; border-radius: 12px; margin-bottom: 40px; }
        textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Poppins'; resize: vertical; }

        /* Styles pour les nouveaux badges */
        .badges-container { display: flex; gap: 10px; margin: 15px 0; }
        .category-badge { background: #f0f0f0; color: #444; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; border: 1px solid #ddd; }
        .stock-badge { background: #e8f5e9; color: #2e7d32; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; border: 1px solid #c8e6c9; }
        .stock-badge.empty { background: #ffebee; color: #c62828; border-color: #ffcdd2; }
    </style>
</head>
<body>

<?php include 'template/header.php'; ?>

<main class="product-detail-container">
    <div class="product-image-box <?= $isOutOfStock ? 'out-of-stock' : '' ?>">
        <?php if (!empty($produit['image'])): ?><img src="img/<?= htmlspecialchars($produit['image']) ?>"><?php endif; ?>
    </div>

    <div class="product-info">
        <a href="index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Retour</a>
        
        <!-- SECTION MODIFIÉE : Nouveaux Badges Type et Quantité -->
        <div class="badges-container">
            <span class="category-badge">🏷️ <?= htmlspecialchars($produit['Type']) ?></span>
            <span class="stock-badge <?= $isOutOfStock ? 'empty' : '' ?>">
                <?= $isOutOfStock ? '❌ Rupture' : '📦 ' . $produit['Quantite'] . ' en stock' ?>
            </span>
        </div>

        <h1><?= htmlspecialchars($produit['Nom']) ?></h1>
        <p class="description"><?= nl2br(htmlspecialchars($produit['Description'])) ?></p>
        <div class="price-tag"><?= number_format($produit['Prix'], 0) ?> <img src="img/gold.png" style="width:20px;"> <span>OR</span></div>
        
        <div class="purchase-zone">
            <div class="qty-input <?= $isOutOfStock ? 'disabled' : '' ?>">
                <button onclick="changeQty(-1)"><i class="fa-solid fa-minus"></i></button>
                <input type="text" id="quantity" value="<?= $isOutOfStock ? 0 : 1 ?>" readonly>
                <button onclick="changeQty(1)"><i class="fa-solid fa-plus"></i></button>
            </div>
            <button class="add-to-cart-btn" onclick="addToCart(<?= $id_produit ?>)" <?= $isOutOfStock ? 'disabled' : '' ?>>Ajouter au panier</button>
        </div>
    </div>
</main>

<section class="evaluations-container">
    <h2 style="font-family:'Cinzel'; color:#333;">Les évaluations</h2>

    <div class="eval-header">
        <div style="text-align:center; min-width:150px;">
            <div class="avg-num"><?= number_format($stats['moyenne'] ?? 0, 1) ?></div>
            <div style="margin:10px 0;">
                <?php for($i=1;$i<=5;$i++): ?><i class="fa-solid fa-star <?= $i <= round($stats['moyenne']) ? 'star-active' : 'star-inactive' ?>"></i><?php endfor; ?>
            </div>
            <small><?= $total_evals_count ?> avis</small>
        </div>
        <div style="flex-grow:1;">
            <?php foreach([5=>'n5', 4=>'n4', 3=>'n3', 2=>'n2', 1=>'n1'] as $n => $k): $p = ($stats[$k]/$divisor)*100; ?>
            <div class="stars-row">
                <span style="width:30px;"><?= $n ?> <i class="fa-star fa-solid" style="font-size:0.7rem; color:#ffc107;"></i></span>
                <div class="bar-container"><div class="bar-fill" style="width:<?= $p ?>%"></div></div>
                <span style="width:30px; text-align:right; font-size:0.8rem; color:#888;"><?= $stats[$k] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>


    <?php 
    $dejaEvalue = $connexion->query("SELECT 1 FROM Evaluations WHERE IdJoueur = $id_joueur AND IdItem = $id_produit")->num_rows > 0;
    $possedeItem = $connexion->query("SELECT 1 FROM Inventaires WHERE IdJoueur = $id_joueur AND IdItem = $id_produit")->num_rows > 0;

    if ($id_joueur > 0 && $possedeItem && !$dejaEvalue): ?>
        <div class="form-eval">
            <h3 style="font-family:'Cinzel'; margin-bottom:15px;">Laisser votre empreinte</h3>
            <form action="soumettre_evaluation.php" method="POST">
                <input type="hidden" name="id_item" value="<?= $id_produit ?>">
                <div style="margin-bottom:15px;">
                    <label>Votre note : </label>
                    <select name="etoiles" style="padding:5px; border-radius:5px; border:1px solid #ccc;">
                        <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                        <option value="4">⭐⭐⭐⭐ Très bien</option>
                        <option value="3">⭐⭐⭐ Moyen</option>
                        <option value="2">⭐⭐ Décevant</option>
                        <option value="1">⭐ Inutile</option>
                    </select>
                </div>
                <textarea name="commentaire" rows="3" placeholder="Qu'avez-vous pensé de cet item ?" required></textarea>
                <button type="submit" style="margin-top:10px; background:#b08d57; color:white; border:none; padding:10px 25px; border-radius:5px; cursor:pointer;">Publier</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="comments-list">
        <?php while($com = $commentaires->fetch_assoc()): ?>
            <div class="eval-card">
                <div style="display:flex; justify-content:space-between;">
                    <div>
                        <strong style="color:#b08d57;"><?= htmlspecialchars($com['Pseudo']) ?></strong>
                        <div style="margin:5px 0;">
                            <?php for($i=1;$i<=5;$i++): ?><i class="fa-solid fa-star <?= $i <= $com['Etoiles'] ? 'star-active' : 'star-inactive' ?>"></i><?php endfor; ?>
                        </div>
                    </div>
                    <?php if ($id_joueur == $com['IdJoueur'] || (isset($_SESSION['user']['EstAdmin']) && $_SESSION['user']['EstAdmin'] == 1)): ?>
                        <a href="supprimer_commentaire.php?id_item=<?= $id_produit ?>&id_joueur=<?= $com['IdJoueur'] ?>" 
                           onclick="return confirm('Retirer ce commentaire ?')" style="color:#e74c3c; text-decoration:none; font-size:0.8rem;">
                           <i class="fa-solid fa-trash"></i> Retirer
                        </a>
                    <?php endif; ?>
                </div>
                <p style="color:#555; margin-top:10px;"><?= nl2br(htmlspecialchars($com['Commentaire'])) ?></p>
            </div>
        <?php endwhile; ?>
    </div>
</section>

<script>
const stockMax = <?= $stockMax ?>;
function changeQty(val) {
    const input = document.getElementById('quantity');
    let current = parseInt(input.value);
    const newVal = current + val;
    if (newVal >= 1 && newVal <= stockMax) input.value = newVal;
}
function addToCart(id) {
    const qty = document.getElementById('quantity').value;
    let formData = new FormData();
    formData.append('id', id); formData.append('qty', qty);
    fetch('add_to_cart.php', { method: 'POST', body: formData })
    .then(r => r.json()).then(data => { if(data.success) location.reload(); });
}
</script>
</body>
</html>