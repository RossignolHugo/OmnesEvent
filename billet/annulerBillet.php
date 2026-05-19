<?php
require_once __DIR__ . '/../includes/init.php';
verifierConnexion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rediriger('billet.php');
}

if (!isset($_POST['id'], $_POST['csrf_token'])) {
    messageFlash('erreur', 'Requête invalide.');
    rediriger('billet.php');
}

// Vérification CSRF (remplace csrfVerify)
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    messageFlash('erreur', 'Token CSRF invalide.');
    rediriger('billet.php');
}

$inscriptionId = (int) $_POST['id'];
$userId = utilisateurId();

// Vérifier que le billet appartient à l'utilisateur
$stmt = $pdo->prepare('SELECT * FROM inscriptions WHERE id = ? AND utilisateur_id = ? LIMIT 1');
$stmt->execute([$inscriptionId, $userId]);
$billet = $stmt->fetch();

if (!$billet) {
    messageFlash('erreur', 'Billet introuvable.');
    rediriger('billet.php');
}

// Annuler le billet
$stmt = $pdo->prepare('UPDATE inscriptions SET statut = "annulé" WHERE id = ?');
$stmt->execute([$inscriptionId]);

messageFlash('succes', 'Vous êtes maintenant désinscrit de cet événement.');
rediriger('billet.php');
