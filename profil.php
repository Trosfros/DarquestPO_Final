<?php
require_once 'config.php';

$user = @ $_SESSION['user'];
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// --- LOGIQUE DES DONNÉES ---
$alias = $user['Alias'];
$pv = $user['PV'];
$or = $user['PieceOr'];
$argent = $user['PieceArgent'];
$bronze = $user['PieceBronze'];
$id = $user['IdJoueur'];

$stmtUser = $connexion->prepare("CALL EnigmaUserStats(?)");
$stmtUser->bind_param("i", $id);
$stmtUser->execute();
$userStats = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

// --- CALCUL DES STATISTIQUES ---
$f_succes = $userStats['FacileSuccess'];
$m_succes = $userStats['MoyenSuccess'];
$d_succes = $userStats['DifficileSuccess'];

$f_total = $userStats['FacileTotal'];
$m_total = $userStats['MoyenTotal'];
$d_total = $userStats['DifficileTotal'];

$totalSucces = $f_succes + $m_succes + $d_succes;
$totalTentatives = $f_total + $m_total + $d_total;
$tauxReussite = ($totalTentatives > 0) ? round(($totalSucces / $totalTentatives) * 100) : 0;

UpdateUserSessionInfo();

$pvPercent = min(100, max(0, ($pv / 100) * 100));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AVERSE - Profil de <?= htmlspecialchars($alias) ?></title>
    
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/profil.css">
    
</head>
<body>

<?php include_once 'template/header.php'; ?>

<main class="container-profil">
    <div class="profile-card">
        
        <div class="profile-header">
            <div class="avatar-frame">
                <img src="https://api.dicebear.com/7.x/pixel-art/svg?seed=<?= urlencode($alias) ?>" alt="Avatar">
            </div>
            <div class="user-info">
                <div class="badge-rank">🛡️ Aventurier #<?= $id ?></div>
                <h1><?= htmlspecialchars($alias) ?></h1>
            </div>
        </div>

        <div class="vitalite-hud">
            <div class="info">
                <span>VITALITÉ</span>
                <span style="color: #ff416c;"><?= $pv ?> / 100 HP</span>
            </div>
            <div class="barre-fond">
                <div class="barre-vie" id="js-hp-bar"></div>
            </div>
        </div>

        <div class="monnaie-grid">
            <div class="coin-box">
                <img src="img/gold.png" alt="Or">
                <h3><?= number_format($or) ?></h3>
                <span>Pièces d'Or</span>
            </div>
            <div class="coin-box">
                <img src="img/silver.png" alt="Argent">
                <h3><?= number_format($argent) ?></h3>
                <span>Pièces d'Argent</span>
            </div>
            <div class="coin-box">
                <img src="img/bronze.png" alt="Bronze">
                <h3><?= number_format($bronze) ?></h3>
                <span>Pièces de Bronze</span>
            </div>
        </div>

        <div class="stats-section">
            <h2 class="stats-title"><i class="fas fa-medal"></i>Statistiques Des Quêtes</h2>

            <div class="global-progress">
                <div>
                    <span style="display:block; font-size: 0.7rem; color: #888; text-transform: uppercase;">Efficacité globale</span>
                    <span style="font-size: 1.5rem; font-weight: bold; color: #d4af37;"><?= $tauxReussite ?>%</span>
                </div>
                <div style="text-align: right;">
                    <span style="display:block; font-size: 0.7rem; color: #888; text-transform: uppercase;">Quêtes Complétées</span>
                    <span style="font-size: 1.5rem; font-weight: bold; color: white;"><?= $totalSucces ?> / <?= $totalTentatives ?></span>
                </div>
            </div>

            <div class="stats-grid-detail">
                <div class="stat-detail-box box-f">
                    <i class="fas fa-hammer"></i>
                    <span class="val"><?= $f_succes ?> / <?= $f_total ?></span>
                    <span class="lab">Facile</span>
                </div>
                <div class="stat-detail-box box-m">
                    <i class="fas fa-shield-alt"></i>
                    <span class="val"><?= $m_succes ?> / <?= $m_total ?></span>
                    <span class="lab">Moyen</span>
                </div>
                <div class="stat-detail-box box-d">
                    <i class="fas fa-wand-sparkles"></i>
                    <span class="val"><?= $d_succes ?> / <?= $d_total ?></span>
                    <span class="lab">Difficile</span>
                </div>
            </div>
        </div>

        <div class="actions-bar">
            <a href="modifier_profil.php" class="btn btn-gold">
                <i class="fas fa-user-pen"></i> MODIFIER
            </a>
            <a href="inventaire.php" class="btn btn-dark">
                <i class="fas fa-scroll"></i> INVENTAIRE
            </a>
            <a href="logout.php" class="btn btn-logout">
                <i class="fas fa-power-off"></i> DÉCONNEXION
            </a>
        </div>

    </div>
</main>

<script>
    window.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            const bar = document.getElementById('js-hp-bar');
            if(bar) bar.style.width = "<?= $pvPercent ?>%";
        }, 400);
    });
</script>

</body>
</html>
