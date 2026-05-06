<?php
if (!isset($_SESSION['user']) || $_SESSION['user']['EstAdmin'] != 1) {
    header("Location: index.php?error=forbidden");
    exit();
}
?>
