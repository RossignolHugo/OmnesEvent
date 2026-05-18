<?php
require_once __DIR__ . '/../includes/init.php';
$_SESSION = [];
session_destroy();
rediriger('../connexion/connexion.php?deconnecte=1');
