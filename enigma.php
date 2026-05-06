<?php
require_once 'config.php';

$user = @ $_SESSION['user'];
if (!isset($user)) {
    header('Location: login.php?message=restricted'); 
    exit();
}

$stmtUser = $connexion->prepare("CALL EnigmaUserStats(?)");
$stmtUser->bind_param("i", $user['IdJoueur']);
$stmtUser->execute();
$userStats = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

$is_playing = false;
$enigme = null;


if (isset($_GET['action']) && $_GET['action'] === 'start' && isset($_GET['diff'])) {
    $is_playing = true;

    $diff = (int)$_GET['diff'];
    $query = $connexion->prepare("
            SELECT e.*, c.Categorie as NomCat 
            FROM Enigme e 
            LEFT JOIN CategorieEnigme c ON e.IdCategorie = c.IdCategorie 
            WHERE ? = 0 or e.Difficulte = ?
            ORDER BY RAND() 
            LIMIT 1
            ");
    $query->execute([$diff, $diff]);
    $enigme = $query->get_result()->fetch_assoc();
}
if ($is_playing && !$enigme) {
    $enigme = ['IdEnigme' => 0, 'Difficulte' => 1, 'Question' => 'Aucune énigme.', 'Reponse1' => 'Vide', 'Reponse2' => 'Vide', 'Reponse3' => 'Vide', 'Reponse4' => 'Vide', 'NomCat' => 'Aucune'];
}

$estMage = ($userStats['EstMage'] == 1);
$classeNom = $estMage ? 'Maître des Arcanes' : 'Apprenti Guerrier';
$classeIcon = $estMage ? 'fa-hat-wizard' : 'fa-hammer';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Averse - Salle des Quêtes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="styles/engima.css">
</head>
<body>

<?php include_once 'template/header.php'; ?>

<div class="dashboard">
    <div class="player-identity <?= $estMage ? 'is-mage-profile' : '' ?>">
        <div class="class-avatar">
            <i class="fas <?= $classeIcon ?>"></i>
        </div>
        <div class="player-info">
            <span class="class-tag"><?= $classeNom ?></span>
            <h2><?= htmlspecialchars($user['Alias']) ?></h2>
        </div>
        <?php if($estMage): ?>
            <div style="margin-left: auto; color: var(--purple); font-size: 0.8rem; font-weight: bold;">
                <i class="fas fa-sparkles"></i> MAGE ACTIF
            </div>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['feedback'])): ?>
        <div class="alert alert-<?= $_SESSION['feedback']['status'] === 'success' ? 'success' : 'error' ?>">
            <i class="fas <?= $_SESSION['feedback']['status'] === 'success' ? 'fa-trophy' : 'fa-skull-crossbones' ?>"></i>
            <?= $_SESSION['feedback']['message'] ?>
        </div>
        <?php unset($_SESSION['feedback']); ?>
    <?php endif; ?>

<h2 class="stats-title">
    <a href="profil.php" class="stats-link">
        <i class="fas fa-chart-line"></i> 
        Voir statistiques détaillées<i class="fas fa-arrow-right"> </i>
        <span class="stats-hint">(Voir détails)</span>
    </a>
</h2>

<div class="stats-bar"> <div class="stat-item">
        <i class="fas fa-check-circle"></i> 
        <?php 
            $reussies = ($userStats['FacileSuccess']) + 
                        ($userStats['MoyenSuccess']) + 
                        ($userStats['DifficileSuccess']);
            echo $reussies;
        ?> résolues
    </div>

    <div class="stat-item">
        <i class="fas fa-times-circle" style="color:var(--red);"></i> 
        <?php 
            $total_essais = ($userStats['FacileTotal']) + 
                            ($userStats['MoyenTotal']) + 
                            ($userStats['DifficileTotal']);
            
            $total_succes = $reussies;
            echo ($total_essais - $total_succes);
        ?> échouées
    </div>

    <div class="stat-item">
    <i class="fas fa-wand-magic-sparkles"></i> 
    <?= min($userStats['MagieReussies'], 5) ?>/5 magie
</div>
</div>

    <?php if (!$is_playing): ?>
        <h1 class="page-title"><i class="fas fa-scroll"></i> Salle des Quêtes</h1>
        <div class="quest-grid">
            <a href="enigma.php?action=start&diff=1" class="quest-card bronze">
                <div class="quest-icon" style="color:#cd7f32;"><i class="fas fa-hammer"></i></div>
                <h3>Forgeron</h3>
                <p>Énigmes sur les <strong>Armes</strong>.</p>
                <div class="reward-tag">Facile</div>
            </a>

            <a href="enigma.php?action=start&diff=2" class="quest-card silver">
                <div class="quest-icon" style="color:#c0c0c0;"><i class="fas fa-shield-alt"></i></div>
                <h3>Armurier</h3>
                <p>Énigmes sur les <strong>Armures</strong>.</p>
                <div class="reward-tag">Moyen</div>
            </a>

            <a href="enigma.php?action=start&diff=3" class="quest-card gold">
                <div class="quest-icon" style="color:var(--gold);"><i class="fas fa-wand-sparkles"></i></div>
                <h3>Grand Mage</h3>
                <p>Énigmes sur la <strong>Magie</strong>.</p>
                <div class="reward-tag">Difficile</div>
            </a>

            <a href="enigma.php?action=start&diff=0" class="quest-card" style="border-bottom-color: var(--purple); background: #fdf0ff;">
                <div class="quest-icon" style="color: var(--purple);"><i class="fas fa-dice"></i></div>
                <h3>Mélange Magouilleux</h3>
                <p>Difficulté <strong>totalement aléatoire</strong>. Oserez-vous ?</p>
                <div class="reward-tag" style="background: var(--purple); color: white;">Gain Variable</div>
            </a>
        </div>

       <div class="mage-section">
         <!---  
         se
    <h2 class="section-title">
        <i class="fas fa-sparkles" style="color: var(--purple);"></i> 
        Sanctuaire des Arcanes
    </h2>


    <?php if ($estMage): ?>
        <div class="quest-card unlocked-mage-zone">
            <div class="quest-icon" style="color: #dfa6ff;"><i class="fas fa-book-spells"></i></div>
            <h3 style="color: white;">Grimoire Interdit</h3>
            <p style="color: #bbb;">Accédez aux secrets les plus profonds d'Averse.</p>
            <a href="mage_special.php" class="choice-btn" style="display:inline-block; text-decoration:none; margin-top:15px; border-color: var(--purple);">
                Entrer dans le Sanctuaire
            </a>
        </div>
    <?php else: ?>
        <div class="locked-container">
            <div class="locked-overlay">
                <i class="fas fa-lock"></i>
                <p>Devenez Mage pour débloquer (<?= min($userStats['MagieReussies'] ?? 0, 5) ?>/5)</p>
            </div>
            <div style="filter: blur(8px); opacity: 0.3;">
                <i class="fas fa-dragon" style="font-size: 3rem;"></i>
                <h3>Quête Légendaire</h3>
            </div>
        </div>
        --->
    <?php endif; ?>
</div>

    <?php else: ?>
        <div class="enigma-card">
            <div style="display:flex; justify-content:space-between; align-items: center;">
                <?php 
                    $diffClass = "diff-" . $enigme['Difficulte'];
                    $diffLabel = ($enigme['Difficulte'] == 3) ? 'Difficile' : (($enigme['Difficulte'] == 2) ? 'Moyen' : 'Facile');
                ?>
                <span class="badge-diff <?= $diffClass ?>"><?= $diffLabel ?></span>
                <div class="cat-badge">
                    <i class="fas fa-scroll"></i>
                    <span><?= htmlspecialchars($enigme['NomCat'] ?? 'Général') ?></span>
                </div>
            </div>

            <h2 class="question-text"><?= htmlspecialchars($enigme['Question']) ?></h2>

            <form action="verifier_enigme.php" method="POST" class="choices-grid">
                <input type="hidden" name="id_enigme" value="<?=$enigme['IdEnigme']?>">
                <button type="submit" name="choix" value="1" class="choice-btn"><?= htmlspecialchars($enigme['Reponse1']) ?></button>
                <button type="submit" name="choix" value="2" class="choice-btn"><?= htmlspecialchars($enigme['Reponse2']) ?></button>
                <button type="submit" name="choix" value="3" class="choice-btn"><?= htmlspecialchars($enigme['Reponse3']) ?></button>
                <button type="submit" name="choix" value="4" class="choice-btn"><?= htmlspecialchars($enigme['Reponse4']) ?></button>
            </form>
            
            <div style="text-align:center;">
                <a href="enigma.php" class="btn-abandon"><i class="fas fa-flag"></i> Abandonner</a>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
