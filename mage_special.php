<?php
require_once 'config.php';

//noice
$user = $_SESSION['user'] ?? null;

if (!$user) {
    header('Location: login.php');
    exit();
}

// On définit les deux rôles séparément
$estMage = (isset($user['EstMage']) && $user['EstMage'] == 1);
$estAdmin = (isset($user['EstAdmin']) && $user['EstAdmin'] == 1);

// Si le joueur n'est PAS mage ET n'est PAS admin, on bloque l'accès
if (!$estMage && !$estAdmin) {
    header('Location: enigma.php?error=access_denied');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sanctuaire des Arcanes - AVERSE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="CSS/style.css">
    <style>
        :root {
            --mage-purple: #8a2be2;
            --mage-gold: #ffd700;
            --deep-space: #0a0518;
        }

        body {
            background: var(--deep-space);
            color: #e0e0e0;
            font-family: 'Cinzel', serif;
            overflow-x: hidden;
        }

        .sanctuary-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 80vh;
            text-align: center;
            padding: 20px;
        }

        /* Effet de lueur magique en arrière-plan */
        .magic-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(138, 43, 226, 0.2) 0%, transparent 70%);
            filter: blur(50px);
            z-index: -1;
            animation: pulse 4s infinite alternate;
        }

        .mage-icon {
            font-size: 5rem;
            color: var(--mage-gold);
            text-shadow: 0 0 20px var(--mage-purple);
            margin-bottom: 30px;
            animation: float 3s ease-in-out infinite;
        }

        h1 {
            font-size: 3rem;
            color: white;
            letter-spacing: 5px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .construction-tag {
            background: var(--mage-purple);
            color: white;
            padding: 5px 20px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 30px;
            display: inline-block;
            box-shadow: 0 0 15px var(--mage-purple);
        }

        .message-box {
            max-width: 600px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(138, 43, 226, 0.3);
            padding: 40px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        p {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #bbb;
        }

        .btn-back {
            margin-top: 30px;
            display: inline-block;
            color: var(--mage-gold);
            text-decoration: none;
            border: 1px solid var(--mage-gold);
            padding: 10px 25px;
            border-radius: 5px;
            transition: 0.3s;
        }

        .btn-back:hover {
            background: var(--mage-gold);
            color: black;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.4);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        @keyframes pulse {
            from { opacity: 0.5; transform: translate(-50%, -50%) scale(1); }
            to { opacity: 0.8; transform: translate(-50%, -50%) scale(1.2); }
        }
    </style>
</head>
<body>

<?php include_once 'template/header.php'; ?>

<div class="magic-glow"></div>

<div class="sanctuary-container">
    <div class="mage-icon">
        <i class="fas fa-hat-wizard"></i>
    </div>

    <h1>Sanctuaire des Arcanes</h1>
    <div class="construction-tag">Section en cours d'enchantement</div>

    <div class="message-box">
        <p>
            Salutations, <strong><?= htmlspecialchars($user['Alias']) ?></strong>. <br><br>
            Vous avez prouvé votre valeur en résolvant les énigmes les plus complexes du royaume. Les secrets du Grimoire Interdit sont presque à votre portée.
        </p>
        <p style="font-size: 0.85rem; margin-top: 15px; font-style: italic;">
            Nos scribes travaillent jour et nuit pour transcrire les sorts légendaires. Revenez bientôt pour découvrir vos nouveaux pouvoirs.
        </p>
        
        <a href="enigma.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Retourner à la Salle des Quêtes
        </a>
    </div>
</div>

</body>
</html>