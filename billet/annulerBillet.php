<?php
require_once __DIR__ . '/../includes/init.php';
verifierConnexion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rediriger('mesBillets.php');
}
if (!isset($_POST['id'], $_POST['csrf_token'])) {
    messageFlash('erreur', 'Requête invalide.');
    rediriger('mesBillets.php');
}
if (function_exists('csrfVerify')) {
    csrfVerify($_POST['csrf_token']);
}
$inscriptionId = (int) $_POST['id'];
$userId = utilisateurId();

// vérif billet appartient utilisateur
$stmt = $pdo->prepare('SELECT * FROM inscriptions WHERE id = ? AND utilisateur_id = ? LIMIT 1');
$stmt->execute([$inscriptionId, $userId]);
$billet = $stmt->fetch();

if (!$billet) {
    messageFlash('erreur', 'Billet introuvable.');
    rediriger('mesBillets.php');
}

//déja annulé
if ($billet['statut'] === 'annulé') {
    messageFlash('erreur', 'Ce billet est déjà annulé.');
    rediriger('mesBillets.php');
}

//annuler billet
$stmt = $pdo->prepare('UPDATE inscriptions SET statut = "annulé" WHERE id = ?');
$stmt->execute([$inscriptionId]);

messageFlash('succes', 'Votre billet a été annulé.');
rediriger('billet.php');
