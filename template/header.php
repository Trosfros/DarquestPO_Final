<link rel="stylesheet" href="styles/header.css">

<header>
    <nav class="top-nav">
        <div class="logo" style="font-family: 'Cinzel', serif; font-size: 1.5rem; color: #d4af37;">AVERSE</div> 
        
        <form action="catalogue.php" method="GET" class="search-bar">
            <input type="text" name="search" placeholder="Q Rechercher..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            <button type="submit" style="display: none;">Rechercher</button>
        </form>
        
        <div class="user-menu">
            <?php
            $user = @ $_SESSION['user'];
            if (isset($user)):
            ?>
            <div class="player-stats">
                <div class="hp-container" title="Points de Vie">
                    <div class="hp-bar" style="width: <?= $_SESSION['user']['PV'] ?>%;"></div>
                    <span class="hp-text"><?= $user['PV'] ?> HP</span>
                </div>

                <div class="rpg-currency-bar">
                    <div class="coin-item" title="Or">
                        <img src="./img/gold.png" alt="Or">
                        <span><?= $user['PieceOr'] ?></span>
                    </div>
                    <div class="coin-item" title="Argent">
                        <img src="./img/silver.png" alt="Argent">
                        <span><?= $user['PieceArgent'] ?></span>
                    </div>
                    <div class="coin-item" title="Bronze">
                        <img src="./img/bronze.png" alt="Bronze">
                        <span><?= $user['PieceBronze'] ?></span>
                    </div>
                </div>
            </div>

            <a href="inventaire.php" style="text-decoration:none; color: white; margin-right: 10px;">🛍️ <span class="hide-mobile">Inventaire</span></a> 

            <div class="dropdown">
                <strong style="cursor:pointer; color: white;" onclick="toggleHeaderMenu(event, 'userDrop')">
                    👤 <?= htmlspecialchars($user['Alias']) ?> ▾
                </strong>
                <div id="userDrop" class="dropdown-content">
                    <a href="profil.php">👤 Mon Profil</a>
    
                    <?php if ($user['EstAdmin']): ?>
                        <a href="admin_enigmes.php" style="color: #d4af37 !important; border-top: 1px solid #333;">🛡️ Admin Panel</a>

                        <a href="admin_capital.php" class="btn-admin" style="color: #d4af37;">⚔️ Gérer les demandes de fonds</a>
                    <?php endif; ?>

                    
    
                    <a href="logout.php">🚪 Déconnexion</a>
                </div>
            </div>
            <?php else: ?>
                <a href="login.php" class="user-link" style="color: white; text-decoration: none; margin-right: 10px;">🔑 Connexion</a>
                <a href="register.php" class="user-link" style="color: #d4af37; text-decoration: none; border: 1px solid #d4af37; padding: 5px 12px; border-radius: 15px;">📜 S'inscrire</a>
            <?php endif; ?>

            <a href="panier.php" id="cart-container">
                🛒 <span class="hide-mobile">Panier</span> (<span id="cart-count"><?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0 ?></span>)
            </a> 
        </div>
    </nav>

    <nav class="sub-nav" style="background: #111; border-top: 1px solid #222;">
        <div class="links">
            <a href="index.php" style="color: white;">🏠 <span class="hide-mobile">Accueil</span></a> 
            
            <div class="dropdown">
                <span style="cursor:pointer; color: white;" onclick="toggleHeaderMenu(event, 'catDrop')">
                    ⚔️ <span class="hide-mobile">Catalogue</span> ▾
                </span>
                <div id="catDrop" class="dropdown-content">
                    <a href="catalogue.php">📋 Tout voir</a>
                    <a href="catalogue.php?search=Armure">🛡️ Armures</a>
                    <a href="catalogue.php?search=Arme">⚔️ Armes</a>
                    <a href="catalogue.php?search=Potion">🧪 Potions</a>
                    <a href="catalogue.php?search=Sort">𑽎 Sorts</a>
                </div>
            </div>

            <a href="enigma.php" style="color: #f3b412;">🧩 <span class="hide-mobile">Enigma</span></a> 
            <a href="profil_capital.php" style="color: #d4af37;">💰 <span class="hide-mobile">Demande de fond</span></a>
        </div>

        <div class="service-client">
            <a href="service_client.php" style="color: #777; font-size: 0.9rem;">Service client</a> 
        </div>
    </nav>
</header>


<script>
    // Fonction de gestion des menus déroulants
    function toggleHeaderMenu(event, menuId) {
        event.stopPropagation();
        const userMenu = document.getElementById('userDrop');
        const catMenu = document.getElementById('catDrop');

        if (menuId === 'userDrop') {
            userMenu?.classList.toggle('show');
            catMenu?.classList.remove('show');
        } else {
            catMenu?.classList.toggle('show');
            userMenu?.classList.remove('show');
        }
    }

    // Fermeture automatique au clic extérieur
    window.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.getElementById('userDrop')?.classList.remove('show');
            document.getElementById('catDrop')?.classList.remove('show');
        }
    });

    /* =========================================
       NOUVEAU JS POUR L'AJOUT AJAX SMOOTH
       ========================================= */
    function addToCartAsync(productId) {
        // Envoi de la requête AJAX (Fetch) au serveur sans recharger
        // Pour le test, on va simuler la réponse du serveur
        
        // --- DEBUT SIMULATION SERVEUR (À remplacer par le vrai fetch) ---
        // fetch('add_to_cart_ajax.php?id=' + productId)
        // .then(response => response.json())
        // .then(data => { if (data.success) { ... mise à jour ... } });

        // Simulation : on récupère la valeur actuelle et on fait +1
        const countElem = document.getElementById('cart-count');
        const currentCount = parseInt(countElem.innerText);
        const newCount = currentCount + 1;
        
        // Appelle la fonction qui gère l'animation et la mise à jour
        updateCartVisual(newCount);
        // --- FIN SIMULATION SERVEUR ---
    }

    function updateCartVisual(newCount) {
        const cartContainer = document.getElementById('cart-container');
        const cartCount = document.getElementById('cart-count');

        // 1. Appliquer la classe de grossissement (déclenche la transition CSS)
        cartContainer.classList.add('cart-grow');

        // 2. Mettre à jour le chiffre (peut être fait pendant le grossissement)
        cartCount.innerText = newCount;

        // 3. Planifier le retour à la normale après la transition
        // On attend la durée de la transition (0.3s = 300ms)
        setTimeout(() => {
            cartContainer.classList.remove('cart-grow');
        }, 300); // Même durée que la transition CSS 'transform'
    }
</script>
