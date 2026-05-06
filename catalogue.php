<?php
require_once 'config.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$tri = isset($_GET['tri']) ? $_GET['tri'] : '';

if (!in_array($tri, ['A', 'D'])) $tri = '';

$stmt = $connexion->prepare("CALL GetMarketItems(12, ?, '$tri')");
$stmt->bind_param('s', $search);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>AVERSE - Catalogue Royal</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gold: #b8860b;
            --magic: #6c5ce7;
            --text-dark: #1a1a1a;
            --text-light: #666;
            --border: #edf2f7;
        }

        body {
            background-color: #ffffff; /* Fond blanc imposé */
            color: var(--text-dark);
            font-family: 'Poppins', sans-serif;
            margin: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 50px 20px;
        }

        .page-title {
            font-family: 'Cinzel', serif;
            text-align: center;
            font-size: 2.8rem;
            margin-bottom: 50px;
            color: var(--text-dark);
            position: relative;
        }

        .page-title::after {
            content: '';
            display: block;
            width: 60px;
            height: 3px;
            background: var(--gold);
            margin: 15px auto;
        }

        /* Filtres */
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 15px 0;
            border-bottom: 2px solid var(--border);
        }

        .sort-form select {
            background: #fff;
            color: var(--text-dark);
            border: 1px solid #ddd;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 500;
            cursor: pointer;
            transition: border 0.3s;
        }

        .sort-form select:hover {
            border-color: var(--gold);
        }

        /* Grille */
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 35px;
        }

        /* Cartes Minimalistes */
        .item-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            text-decoration: none;
            color: inherit;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        /* L'ombre n'apparaît qu'au survol pour un look très propre */
        .item-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            border-color: transparent;
        }

        /* Zone Image */
        .item-img {
            height: 240px;
            background: #fcfcfd; /* Très léger gris pour détacher l'item */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
            border-radius: 20px 20px 0 0;
        }

        .item-img img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            transition: transform 0.5s ease;
        }

        .item-card:hover .item-img img {
            transform: scale(1.08);
        }

        /* Contenu */
        .item-content {
            padding: 25px;
            text-align: center;
        }

        .category-badge {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #a0aec0;
            letter-spacing: 1px;
            margin-bottom: 10px;
            display: block;
        }

        .item-name {
            font-family: 'Cinzel', serif;
            font-size: 1.3rem;
            margin: 5px 0 12px 0;
            color: var(--text-dark);
        }

        .item-desc {
            font-size: 0.85rem;
            color: var(--text-light);
            line-height: 1.6;
            height: 3.2em;
            overflow: hidden;
            margin-bottom: 25px;
        }

        /* Prix et bouton */
        .item-footer {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }

        .price {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--gold);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .price img {
            width: 22px;
        }

        /* Indicateur spécial pour les SORTS */
        .is-spell {
            border-top: 4px solid var(--magic);
        }
        
        .is-spell .category-badge {
            color: var(--magic);
        }

        .view-btn {
            background: var(--text-dark);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            transition: background 0.3s;
        }

        .item-card:hover .view-btn {
            background: var(--gold);
        }
    </style>
</head>
<body>

<?php include 'template/header.php'; ?>

<div class="container">
    <h1 class="page-title">Boutique de l'Aventurier</h1>

    <div class="filter-bar">
        <span style="color: #a0aec0; font-weight: 500;"><?= count($items) ?> trésors en stock</span>
        <form method="GET" class="sort-form">
            <select name="tri" onchange="this.form.submit()">
                <option value="" <?= $tri == '' ? 'selected' : '' ?>>Trier par Nom</option>
                <option value="A" <?= $tri == 'A' ? 'selected' : '' ?>>Prix : Moins cher</option>
                <option value="D" <?= $tri == 'D' ? 'selected' : '' ?>>Prix : Plus cher</option>
            </select>
        </form>
    </div>

    <div class="items-grid">
        <?php foreach ($items as $item): 
            $isSpell = (isset($item['NomType']) && $item['NomType'] === 'Sort');
        ?>
            <a href="produit.php?id=<?= $item['IdItem'] ?>" class="item-card <?= $isSpell ? 'is-spell' : '' ?>">
                <div class="item-img">
                    <?php if (!empty($item['image'])): ?>
                        <img src="img/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['Nom']) ?>">
                    <?php else: ?>
                        <i class="fa-regular fa-image" style="font-size: 3rem; color: #eee;"></i>
                    <?php endif; ?>
                </div>

                <div class="item-content">
                    <span class="category-badge"><?= htmlspecialchars($item['NomType']) ?></span>
                    <h3 class="item-name"><?= htmlspecialchars($item['Nom']) ?></h3>
                    <p class="item-desc"><?= htmlspecialchars(substr($item['Description'], 0, 80)) ?>...</p>
                    
                    <div class="item-footer">
                        <div class="price">
                            <?= number_format($item['Prix'], 0, '.', ' ') ?> 
                            <img src="img/gold.png" alt="Or">
                        </div>
                        <div class="view-btn">Détails</div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>