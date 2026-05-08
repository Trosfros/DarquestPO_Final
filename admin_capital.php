<?php
session_start();
require_once 'config.php';

// 1. Vérification Admin
if (!isset($_SESSION['user']['EstAdmin']) || $_SESSION['user']['EstAdmin'] != 1) {
    header('Location: login.php');
    exit();
}

// Fonction pour accorder la récompense avec sécurité anti-doublon
function accorderRecompense($idJoueur, $niveau) {
    global $connexion;
    
    $check_sql = "SELECT COUNT(*) as existe FROM HistoriqueCapital WHERE IdJoueur = ? AND Description LIKE ?";
    $desc_search = "Gain niveau $niveau%";
    $stmt_c = $connexion->prepare($check_sql);
    $stmt_c->bind_param("is", $idJoueur, $desc_search);
    $stmt_c->execute();
    $dejaFait = $stmt_c->get_result()->fetch_assoc()['existe'] > 0;
    $stmt_c->close();
    
    if ($dejaFait) return false;

    $recompenses = [
        1 => ["colonne" => "PieceOr", "valeur" => 10, "type" => "Or"],
        2 => ["colonne" => "PieceArgent", "valeur" => 10, "type" => "Argent"],
        3 => ["colonne" => "PieceBronze", "valeur" => 10, "type" => "Bronze"]
    ];
    
    if (!isset($recompenses[$niveau])) return false;
    
    $data = $recompenses[$niveau];
    $colonne = $data['colonne'];
    $valeur = $data['valeur'];
    
    $sql = "UPDATE Joueurs SET $colonne = $colonne + ? WHERE IdJoueur = ?";
    $stmt = $connexion->prepare($sql);
    $stmt->bind_param("ii", $valeur, $idJoueur);
    $stmt->execute();
    $stmt->close();
    
    $historique = "Gain niveau $niveau : +10 " . $data['type'];
    $sql_log = "INSERT INTO HistoriqueCapital (IdJoueur, Description) VALUES (?, ?)";
    $stmt_log = $connexion->prepare($sql_log);
    $stmt_log->bind_param("is", $idJoueur, $historique);
    $stmt_log->execute();
    $stmt_log->close();
    
    return true;
}

$message = "";
$error = "";

// TRAITEMENT POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idRequete = isset($_POST['id_requete']) ? intval($_POST['id_requete']) : 0;
    $idJoueur = isset($_POST['id_joueur']) ? intval($_POST['id_joueur']) : 0;
    $niveau = isset($_POST['niveau_atteint']) ? intval($_POST['niveau_atteint']) : 0;
    
    if (isset($_POST['valider'])) {
        if (accorderRecompense($idJoueur, $niveau)) {
            $sql_upd = "UPDATE RequetesCapital SET Statut = 'Validé' WHERE IdRequete = ?";
            $stmt_upd = $connexion->prepare($sql_upd);
            $stmt_upd->bind_param("i", $idRequete);
            $stmt_upd->execute();
            $stmt_upd->close();
            // Redirection immédiate pour éviter le renvoi du formulaire au F5
            header("Location: admin_capital.php?msg=success");
            exit();
        } else {
            $sql_rej = "UPDATE RequetesCapital SET Statut = 'Rejeté' WHERE IdRequete = ?";
            $stmt_rej = $connexion->prepare($sql_rej);
            $stmt_rej->bind_param("i", $idRequete);
            $stmt_rej->execute();
            header("Location: admin_capital.php?msg=doublon");
            exit();
        }
    } elseif (isset($_POST['refuser'])) {
        $sql_upd = "UPDATE RequetesCapital SET Statut = 'Rejeté' WHERE IdRequete = ?";
        $stmt_upd = $connexion->prepare($sql_upd);
        $stmt_upd->bind_param("i", $idRequete);
        $stmt_upd->execute();
        header("Location: admin_capital.php?msg=refused");
        exit();
    }
}

// Gestion des messages après redirection
if(isset($_GET['msg'])){
    if($_GET['msg'] == 'success') $message = "Requête validée avec succès.";
    if($_GET['msg'] == 'doublon') $error = "Doublon détecté : Le joueur possède déjà ce niveau.";
    if($_GET['msg'] == 'refused') $message = "La demande a été rejetée.";
}

$sql_req = "SELECT r.*, j.Alias FROM RequetesCapital r JOIN Joueurs j ON r.IdJoueur = j.IdJoueur WHERE r.Statut = 'En attente' ORDER BY r.DateDemande ASC";
$requetes = $connexion->query($sql_req);
$total_initial = $requetes->num_rows;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>AVERSE - Validation des fonds</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
        .admin-container { max-width: 800px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { font-family: 'Cinzel', serif; color: #333; margin-bottom: 20px; }
        .alert { padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert-success { background-color: #e8f5e9; border: 1px solid #c8e6c9; color: #2e7d32; }
        .alert-danger { background-color: #ffebee; border: 1px solid #ffcdd2; color: #c62828; }
        .requete-card { background: #fdfdfd; border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .btn-action { padding: 8px 16px; border-radius: 5px; font-weight: 600; cursor: pointer; border: none; margin-left: 10px; }
        .btn-valider { background: #27ae60; color: #fff; }
        .btn-refuser { background: #c0392b; color: #fff; }
    </style>
</head>
<body>

<?php include 'template/header.php'; ?>

<main class="admin-container">
    <h1><i class="fa-solid fa-shield-halved"></i> Validation des demandes (<span id="req-count"><?= $total_initial ?></span>)</h1>
    
    <div id="status-messages">
        <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    </div>

    <div id="liste-demandes">
        <?php if ($requetes && $requetes->num_rows > 0): ?>
            <?php while ($req = $requetes->fetch_assoc()): ?>
                <div class="requete-card">
                    <div>
                        <strong>Joueur :</strong> <?= htmlspecialchars($req['Alias']) ?><br>
                        <strong>Niveau :</strong> <?= $req['NiveauAtteint'] ?><br>
                        <small style="color: #666;"><i class="fa-regular fa-clock"></i> <?= date('d/m/Y H:i', strtotime($req['DateDemande'])) ?></small>
                    </div>
                    <div>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id_requete" value="<?= $req['IdRequete'] ?>">
                            <input type="hidden" name="id_joueur" value="<?= $req['IdJoueur'] ?>">
                            <input type="hidden" name="niveau_atteint" value="<?= $req['NiveauAtteint'] ?>">
                            <button type="submit" name="valider" class="btn-action btn-valider">Accepter</button>
                            <button type="submit" name="refuser" class="btn-action btn-refuser">Rejeter</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="font-style: italic; color: #555;">Aucune demande en attente.</p>
        <?php endif; ?>
    </div>

    <p style="margin-top:30px;"><a href="admin_enigmes.php"><i class="fa-solid fa-chevron-left"></i> Retour</a></p>
</main>

<script>
let currentCount = <?= $total_initial ?>;

function checkUpdates() {
    fetch('api_admin_check_requests.php')
        .then(res => res.json())
        .then(data => {
            if (data.total_requetes !== undefined && data.total_requetes != currentCount) {
                // On redirige vers l'URL sans paramètres pour forcer un refresh propre en GET
                window.location.href = 'admin_capital.php'; 
            }
        })
        .catch(err => console.error("Erreur d'auto-refresh"));
}

setInterval(checkUpdates, 5000);
</script>

</body>
</html>