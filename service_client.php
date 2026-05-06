<?php
require_once 'config.php';

// Calcul du total des items dans le panier
$totalItems = 0;
if (isset($_SESSION['cart'])) {
    $totalItems = array_sum($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Averse - Centre de Support des Héros</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/serviceclients.css">
    
</head>
<body>

<?php include_once 'template/header.php' ?>

<main class="container-support">
    <div class="support-header">
        <h1>Centre d'Assistance</h1>
        <p>Problème technique ou perte de stuff ? Nos mages sont là.</p>
    </div>

    <div class="server-status">
        <span class="dot"></span> Serveurs Averse : Opérationnels
    </div>

    <div class="category-grid">
        <div class="cat-card">
            <i class="fas fa-ghost"></i>
            <h3>Bugs & Glitches</h3>
            <p>Signaler un problème de collision ou un sort défectueux.</p>
        </div>
        <div class="cat-card">
            <i class="fas fa-user-shield"></i>
            <h3>Compte</h3>
            <p>Récupération de mot de passe ou changement d'Alias.</p>
        </div>
        <div class="cat-card">
            <i class="fas fa-gem"></i>
            <h3>Boutique & Or</h3>
            <p>Problème lors d'un achat d'item ou de conversion de pièces.</p>
        </div>
    </div>

    <section class="faq-box">
        <h2 style="font-family: 'Cinzel', serif; color: #fff; margin-bottom: 30px;">FAQ des Aventuriers</h2>
        
        <div class="faq-item">
            <div class="faq-question">
                J'ai perdu mon stuff après un crash, que faire ?
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Pas de panique ! Notre système de sauvegarde automatique enregistre votre inventaire toutes les 5 minutes. Contactez-nous avec l'heure exacte du crash.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Comment convertir mes pièces de Bronze en Or ?
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Comme indiqué dans le Grimoire : 100 Bronze = 10 Argent, et 10 Argent = 1 Or. La conversion se fait automatiquement au Coffre.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Est-ce que le multicompte est autorisé ?
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Non aventurier ! Un seul héros par utilisateur est autorisé pour garantir l'équité lors des quêtes Enigma.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                Le boss de l'Enigma est trop dur, c'est un bug ?
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Non, c'est un défi ! Assurez-vous d'avoir bien lu les parchemins d'indices disséminés dans le village avant de l'affronter.
            </div>
        </div>
    </section>

    <a href="mailto:support@averse.game" class="btn-report">Ouvrir un ticket de support</a>
</main>

<script>
    // Accordéon FAQ
    document.querySelectorAll('.faq-question').forEach(q => {
        q.addEventListener('click', () => {
            q.parentNode.classList.toggle('active');
        });
    });

    // Scripts de ton header
    function toggleDropdown(event) {
        event.stopPropagation();
        document.getElementById("userDropdown").classList.toggle("show");
    }
</script>

</body>
</html>
