<?php
session_start();

// Vider toutes varibale session
$_SESSION = [];

// Détruire session
session_destroy();

// direction connexion
header('Location: connexion.php?deconnecte=1');
exit;
