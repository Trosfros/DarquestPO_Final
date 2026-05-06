<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['user']['IdJoueur'])) {
    header('Location: login.php');
    exit();
}

$idJoueur = $_SESSION['user']['IdJoueur'];


$sql_level = "SELECT COUNT(*) as niveau FROM HistoriqueCapital WHERE IdJoueur = ?";
$stmt_level = $connexion->prepare($sql_level);
$stmt_level->bind_param("i", $idJoueur);
$stmt_level->execute();
$result_level = $stmt_level->get_result()->fetch_assoc();
$niveauAtteint = $result_level['niveau'];
$stmt_level->close();


$sql_pending = "SELECT COUNT(*) as pending FROM RequetesCapital WHERE IdJoueur = ? AND Statut = 'En attente'";
$stmt_pending = $connexion->prepare($sql_pending);
$stmt_pending->bind_param("i", $idJoueur);
$stmt_pending->execute();
$result_pending = $stmt_pending->get_result()->fetch_assoc();
$hasPendingRequest = ($result_pending['pending'] > 0);
$stmt_pending->close();


$message = "";
$error = "";

if (isset($_POST['demander_bonus']) && !$hasPendingRequest && $niveauAtteint < 3) {
    $nextLevel = $niveauAtteint + 1;
    
    $sql_req = "INSERT INTO RequetesCapital (IdJoueur, NiveauAtteint, Statut) VALUES (?, ?, 'En attente')";
    $stmt_req = $connexion->prepare($sql_req);
    $stmt_req->bind_param("ii", $idJoueur, $nextLevel);
    
    if ($stmt_req->execute()) {
        $message = "Votre demande de bonus (Niveau $nextLevel) a bien été envoyée à l'administrateur.";
        $hasPendingRequest = true;
    } else {
        $error = "Une erreur est survenue, veuillez réessayer.";
    }
    $stmt_req->close();
}


$sql_hist = "SELECT * FROM HistoriqueCapital WHERE IdJoueur = ? ORDER BY IdHistorique DESC";
$stmt_hist = $connexion->prepare($sql_hist);
$stmt_hist->bind_param("i", $idJoueur);
$stmt_hist->execute();
$historique = $stmt_hist->get_result();
$stmt_hist->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AVERSE - Gestion du Capital</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
        .capital-container { max-width: 800px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1, h2 { font-family: 'Cinzel', serif; color: #333; margin-bottom: 20px; }
        .niveau-box { background: #fdfaf5; border: 1px solid #f3e9d8; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
        .niveau-box h3 { color: #b08d57; margin-top: 0; }
        .btn-demande { background: #b08d57; color: #fff; border: none; padding: 12px 25px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-demande:hover:not(:disabled) { background: #8e6f3e; }
        .btn-demande:disabled { background-color: #ccc; cursor: not-allowed; }
        .alert { padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert-success { background-color: #e8f5e9; border: 1px solid #c8e6c9; color: #2e7d32; }
        .alert-danger { background-color: #ffebee; border: 1px solid #ffcdd2; color: #c62828; }
        .history-card { background-color: #fafafa; border: 1px solid #eee; padding: 15px; border-radius: 8px; margin-top: 10px; display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>

<?php include 'template/header.php'; ?>

<main class="capital-container">
    <h1>Gestion de votre Capital</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="niveau-box">
        <h3>Votre progression : Niveau <?= $niveauAtteint ?> / 3</h3>
        
        <?php if ($niveauAtteint == 0): ?>
            <p>Vous n'avez encore réclamé aucun bonus. Le prochain bonus vous donnera : <strong>+10 Or</strong>.</p>
        <?php elseif ($niveauAtteint == 1): ?>
            <p>Vous avez reçu le premier bonus. Le prochain vous donnera : <strong>+10 Argent</strong>.</p>
        <?php elseif ($niveauAtteint == 2): ?>
            <p>Vous avez reçu le second bonus. Le dernier vous donnera : <strong>+10 Bronze</strong>.</p>
        <?php elseif ($niveauAtteint == 3): ?>
            <p style="color: #27ae60; font-weight: 600;">Vous avez atteint le capital maximum. Le processus est terminé pour vous.</p>
        <?php endif; ?>

        <?php if ($niveauAtteint < 3): ?>
            <form action="profil_capital.php" method="POST">
                <?php if ($hasPendingRequest): ?>
                    <p style="color: #e67e22; font-weight: 600;"><i class="fa-solid fa-hourglass-half"></i> Une demande est déjà en attente de validation par l'administrateur.</p>
                    <button type="submit" class="btn-demande" disabled>Demander le bonus (En attente)</button>
                <?php else: ?>
                    <button type="submit" name="demander_bonus" class="btn-demande">Demander le bonus suivant</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>

    <h2>Historique des transactions</h2>
    <div class="history-list">
        <?php if ($historique->num_rows > 0): ?>
            <?php while ($com = $historique->fetch_assoc()): ?>
                <div class="history-card">
                    <div>
                        <i class="fa-solid fa-circle-check" style="color: #2e7d32;"></i>
                        <?= htmlspecialchars($com['Description']) ?>
                    </div>
                    <span style="font-size: 0.8rem; color: #888;">
                        <?= date('d/m/Y', strtotime($com['DateCreation'] ?? 'now')) ?>
                    </span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color: #666; font-style: italic;">Aucun historique de capital pour le moment.</p>
        <?php endif; ?>
    </div>
</main>

</body>
</html>