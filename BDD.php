<?php
//Connexion a ma base de données

$host     = 'localhost';
$dbname   = 'omnesevent';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}


function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function estConnecte() {
    return isset($_SESSION['id']); 
}


function estAdmin() {
    return estConnecte() && $_SESSION['role'] === 'admin';  
}

function estOrganisateur() {
    return estConnecte() && $_SESSION['role'] === 'organisateur'; 
}

function requireConnexion() {
    if (!estConnecte()) {
        header('Location: ../connexion/connexion.php');
        exit;
    }
}
