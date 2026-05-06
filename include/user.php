<?php
function UpdateUserSessionInfo() {
    $alias = $_SESSION['user']['Alias'];
    $stmt = $GLOBALS['connexion']->prepare("SELECT IdJoueur, Alias, EstAdmin,
        PieceBronze, PieceArgent, PieceOr, PV FROM Joueurs WHERE Alias = ?");
    $stmt->bind_param("s", $alias);
    $stmt->execute();

    $_SESSION['user'] = $stmt->get_result()->fetch_assoc();
}

function LogUser($alias) {
    $_SESSION['user']['Alias'] = $alias;
    UpdateUserSessionInfo();
}
?>
