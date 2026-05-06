<?php
require_once 'config.php';
require 'check_admin.php'; 

$message = "";

// --- LOGIQUE AJOUT ÉNIGME ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_enigme'])) {
    $idCategorie = intval($_POST['id_categorie']);
    $question = htmlspecialchars($_POST['question']); 
    $r1 = htmlspecialchars($_POST['r1']);
    $r2 = htmlspecialchars($_POST['r2']);
    $r3 = htmlspecialchars($_POST['r3']);
    $r4 = htmlspecialchars($_POST['r4']);
    $bonne_rep = intval($_POST['bonne_rep']);
    $difficulte = $idCategorie;

    $sql = "INSERT INTO Enigme (IdCategorie, Difficulte, Question, Reponse1, Reponse2, Reponse3, Reponse4, BonneReponse)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $connexion->prepare($sql);
    $stmt->bind_param("iisssssi", $idCategorie, $difficulte, $question, $r1, $r2, $r3, $r4, $bonne_rep);

    if ($stmt->execute()) {
        $message = "<p style='color: #27ae60; font-weight: bold;'>📜 Quête scellée avec succès !</p>";
    } else {
        $message = "<p style='color: #e74c3c;'>❌ Erreur SQL : " . $stmt->error . "</p>";
    }
}

// --- LOGIQUE AJOUT ITEM + MISE EN MARCHÉ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $nom = htmlspecialchars($_POST['nom_item']);
    $type = htmlspecialchars($_POST['type_item']); 
    $prix = intval($_POST['prix_item']);
    $description = htmlspecialchars($_POST['desc_item']);
    $image = htmlspecialchars($_POST['img_item']);
    $rarete = intval($_POST['rarete_item']);
    $quantite = intval($_POST['quantite_item']);

    // 1. Insertion dans la table Items
    $sql = "INSERT INTO Items (Nom, Type, Prix, Description, image, Rarete) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $connexion->prepare($sql);
    $stmt->bind_param("ssissi", $nom, $type, $prix, $description, $image, $rarete);

    if ($stmt->execute()) {
        $new_id_item = $connexion->insert_id; // Récupère l'ID généré pour l'item
        $id_vendeur = $_SESSION['user']['IdJoueur']; // L'admin actuel devient le possesseur initial

        // 2. Insertion dans la table Marche pour l'affichage en boutique
        $sql_marche = "INSERT INTO Marche (IdJoueur, IdItem, Quantite) VALUES (?, ?, ?)";
        $stmt_marche = $connexion->prepare($sql_marche);
        $stmt_marche->bind_param("iii", $id_vendeur, $new_id_item, $quantite);
        
        if ($stmt_marche->execute()) {
            $message = "<p style='color: #d4af37; font-weight: bold;'>⚔️ Item forgé et mis en rayon avec succès (Quantité: $quantite) !</p>";
        } else {
            $message = "<p style='color: #e74c3c;'>⚠️ Item créé, mais erreur lors de la mise en rayon : " . $stmt_marche->error . "</p>";
        }
    } else {
        $message = "<p style='color: #e74c3c;'>❌ Erreur de forge : " . $stmt->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Grimoire & Forge</title>
    <link rel="stylesheet" href="CSS/style.css">
    <style>
        .admin-box { max-width: 850px; margin: 40px auto; padding: 30px; background: #1a1a1a; color: white; border-radius: 15px; border: 2px solid #d4af37; box-shadow: 0 0 25px rgba(0,0,0,0.7); }
        .section-divider { border-top: 1px solid #444; margin: 50px 0 30px 0; position: relative; }
        .section-divider span { position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: #1a1a1a; padding: 0 15px; color: #d4af37; font-weight: bold; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
        label { color: #d4af37; font-weight: bold; margin-top: 15px; display: block; text-transform: uppercase; font-size: 0.85rem; }
        input, select, textarea { width: 100%; padding: 12px; background: #262626; border: 1px solid #444; color: white; border-radius: 5px; box-sizing: border-box; }
        input:focus { border-color: #d4af37; outline: none; }
        .btn-submit { background: #d4af37; color: black; border: none; padding: 18px; width: 100%; margin-top: 25px; border-radius: 5px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: white; transform: scale(1.01); }
        h1, h2 { text-align: center; color: #d4af37; }
    </style>
</head>
<body>

<?php include 'template/header.php'; ?>

<main class="admin-box">
    <h1>🏛️ Panel d'Administration</h1>
    <?= $message ?>

    <!-- SECTION : ÉNIGMES -->
    <section>
        <h2>📜 Forgeron de Quêtes</h2>
        <form method="POST">
            <label>📍 Origine :</label>
            <select name="id_categorie">
                <option value="1">🔨 Forgeron (Facile)</option>
                <option value="2">🛡️ Armurier (Moyen)</option>
                <option value="3">✨ Grand Mage (Difficile)</option>
            </select>

            <label>❓ Énigme :</label>
            <input type="text" name="question" maxlength="100" required>

            <div class="form-grid">
                <div><label>R1 :</label><input type="text" name="r1" required></div>
                <div><label>R2 :</label><input type="text" name="r2" required></div>
                <div><label>R3 :</label><input type="text" name="r3" required></div>
                <div><label>R4 :</label><input type="text" name="r4" required></div>
            </div>

            <label>✅ Réponse correcte :</label>
            <select name="bonne_rep">
                <option value="1">Réponse 1</option>
                <option value="2">Réponse 2</option>
                <option value="3">Réponse 3</option>
                <option value="4">Réponse 4</option>
            </select>

            <button type="submit" name="add_enigme" class="btn-submit">INSCRIRE DANS LE GRIMOIRE 📜</button>
        </form>
    </section>

    <div class="section-divider"><span>OU</span></div>

    <!-- SECTION : ITEMS -->
    <section>
        <h2>⚔️ Arsenal du Shop</h2>
        <form method="POST">
            <div class="form-grid">
                <div>
                    <label>Nom de l'objet :</label>
                    <input type="text" name="nom_item" required>
                </div>
                <div>
                    <label>Type :</label>
                    <select name="type_item">
                        <option value="A">Arme</option>
                        <option value="D">Armure</option>
                        <option value="P">Potion</option>
                        <option value="S">Sortilège</option>
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div>
                    <label>Prix (Or) :</label>
                    <input type="number" name="prix_item" min="0" value="100" required>
                </div>
                <div>
                    <label>Rareté (1 à 5) :</label>
                    <input type="number" name="rarete_item" min="1" max="5" value="1" required>
                </div>
            </div>

            <div class="form-grid">
                <div>
                    <label>Image (ex: epee.png) :</label>
                    <input type="text" name="img_item" required>
                </div>
                <div>
                    <label>Quantité en stock :</label>
                    <input type="number" name="quantite_item" min="1" value="10" required>
                </div>
            </div>

            <label>📖 Description :</label>
            <textarea name="desc_item" rows="2" required></textarea>

            <button type="submit" name="add_item" class="btn-submit" style="background: #b08d57;">AJOUTER À LA BOUTIQUE 💰</button>
        </form>
    </section>
</main>

</body>
</html>