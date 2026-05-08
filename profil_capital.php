<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']['IdJoueur'])) {
    header('Location: login.php');
    exit();
}

$idJoueur = $_SESSION['user']['IdJoueur'];

// --- LOGIQUE DE TRAITEMENT (PROTECTION CONTRE LES BUGS) ---
if (isset($_POST['demander_bonus'])) {
    // 1. On recompte le niveau REEL en base de données
    $sql_check = "SELECT COUNT(*) as niveau FROM HistoriqueCapital WHERE IdJoueur = ?";
    $stmt_check = $connexion->prepare($sql_check);
    $stmt_check->bind_param("i", $idJoueur);
    $stmt_check->execute();
    $currentLevel = $stmt_check->get_result()->fetch_assoc()['niveau'];
    
    // 2. On vérifie s'il y a une demande déjà en attente
    $sql_pending = "SELECT COUNT(*) as pending FROM RequetesCapital WHERE IdJoueur = ? AND Statut = 'En attente'";
    $stmt_pending = $connexion->prepare($sql_pending);
    $stmt_pending->bind_param("i", $idJoueur);
    $stmt_pending->execute();
    $hasPending = $stmt_pending->get_result()->fetch_assoc()['pending'] > 0;

    // 3. Sécurité : On n'insère QUE si niveau < 3 et pas de demande en attente
    if ($currentLevel < 3 && !$hasPending) {
        $nextLevel = $currentLevel + 1;
        $sql_req = "INSERT INTO RequetesCapital (IdJoueur, NiveauAtteint, Statut) VALUES (?, ?, 'En attente')";
        $stmt_req = $connexion->prepare($sql_req);
        $stmt_req->bind_param("ii", $idJoueur, $nextLevel);
        $stmt_req->execute();
    }
    
    // Toujours rediriger après un POST pour vider le formulaire
    header('Location: index.php');
    exit();
}

// --- RÉCUPÉRATION POUR L'AFFICHAGE ---
$sql_level = "SELECT COUNT(*) as niveau FROM HistoriqueCapital WHERE IdJoueur = ?";
$stmt_level = $connexion->prepare($sql_level);
$stmt_level->bind_param("i", $idJoueur);
$stmt_level->execute();
$niveauAtteint = $stmt_level->get_result()->fetch_assoc()['niveau'];
if ($niveauAtteint > 3) $niveauAtteint = 3; 

$sql_pending = "SELECT COUNT(*) as pending FROM RequetesCapital WHERE IdJoueur = ? AND Statut = 'En attente'";
$stmt_p = $connexion->prepare($sql_pending);
$stmt_p->bind_param("i", $idJoueur);
$stmt_p->execute();
$hasPendingRequest = ($stmt_p->get_result()->fetch_assoc()['pending'] > 0);

$sql_hist = "SELECT * FROM HistoriqueCapital WHERE IdJoueur = ? ORDER BY IdHistorique DESC";
$stmt_hist = $connexion->prepare($sql_hist);
$stmt_hist->bind_param("i", $idJoueur);
$stmt_hist->execute();
$historique = $stmt_hist->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>AVERSE - Gestion du Capital</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
        .capital-container { max-width: 800px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1, h2 { font-family: 'Cinzel', serif; color: #333; }
        .niveau-box { background: #fdfaf5; border: 1px solid #f3e9d8; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
        .btn-demande { background: #b08d57; color: #fff; border: none; padding: 12px 25px; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn-demande:disabled { background-color: #ccc; cursor: not-allowed; }
        .history-card { background-color: #fafafa; border: 1px solid #eee; padding: 15px; border-radius: 8px; margin-top: 10px; display: flex; justify-content: space-between; }
    </style>
</head>
<body>

<?php include 'template/header.php'; ?>

<main class="capital-container">
    <h1>Gestion de votre Capital</h1>
    
    <div id="section-bonus" class="niveau-box">
        <h3>Votre progression : Niveau <span id="display-lv"><?= $niveauAtteint ?></span> / 3</h3>
        
        <div id="status-text">
            <?php if ($niveauAtteint == 0): ?>
                <p>Prochain bonus : <strong>+10 Or</strong>.</p>
            <?php elseif ($niveauAtteint == 1): ?>
                <p>Prochain bonus : <strong>+10 Argent</strong>.</p>
            <?php elseif ($niveauAtteint == 2): ?>
                <p>Dernier bonus : <strong>+10 Bronze</strong>.</p>
            <?php else: ?>
                <p style="color: #27ae60; font-weight: 600;">Capital maximum atteint.</p>
            <?php endif; ?>
        </div>

        <?php if ($niveauAtteint < 3): ?>
            <form action="profil_capital.php" method="POST">
                <button type="submit" name="demander_bonus" class="btn-demande" <?= $hasPendingRequest ? 'disabled' : '' ?>>
                    <?= $hasPendingRequest ? 'Demande en attente...' : 'Demander le bonus suivant' ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <h2>Historique des transactions</h2>
    <div id="history-list">
        <?php while ($com = $historique->fetch_assoc()): ?>
            <div class="history-card">
                <div><i class="fa-solid fa-circle-check" style="color: #2e7d32;"></i> <?= htmlspecialchars($com['Description']) ?></div>
                <span style="font-size: 0.8rem; color: #888;"><?= date('d/m/Y', strtotime($com['DateCreation'])) ?></span>
            </div>
        <?php endwhile; ?>
    </div>
</main>

<script>
function refreshData() {
    // On appelle le nouveau fichier API
    fetch('api_check_capital.php')
        .then(response => response.json())
        .then(data => {
            const currentLv = document.getElementById('display-lv').innerText;
            // Si le niveau en base de données a changé, on actualise la page
            if (data.niveau != currentLv) {
                location.reload(); 
            }
        })
        .catch(err => console.log('En attente du fichier api_check_capital.php...'));
}

setInterval(refreshData, 5000);
</script>

</body>
</html>