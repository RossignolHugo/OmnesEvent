<?php
session_start();
require_once __DIR__ . '/../BDD.php';

if (!estConnecte()) {
    header('Location: ../connexion/connexion.php');
    exit;
}

$user_id  = (int)$_SESSION['id'];
$event_id = (int)($_GET['id'] ?? 0);

if ($event_id <= 0) {
    header('Location: ../index/index.php?msg=' . urlencode("Événement invalide."));
    exit;
}

//récup événement
$event = $pdo->prepare("SELECT * FROM evenements WHERE id = ?");
$event->execute([$event_id]);
$event = $event->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: ../index/index.php?msg=' . urlencode("Événement introuvable."));
    exit;
}

//deja inscrit ?
$deja = $pdo->prepare("
    SELECT 1 FROM inscriptions 
    WHERE utilisateur_id = ? 
      AND evenement_id = ? 
      AND statut <> 'annulé'
");
$deja->execute([$user_id, $event_id]);

if ($deja->fetch()) {
    header('Location: ../billet/billet.php?msg=' . urlencode("Vous avez déjà un billet pour cet événement."));
    exit;
}

//verif capacité
$nbInscrits = $pdo->prepare("
    SELECT COUNT(*) 
    FROM inscriptions 
    WHERE evenement_id = ? AND statut = 'confirmé'
");
$nbInscrits->execute([$event_id]);
$nbInscrits = (int)$nbInscrits->fetchColumn();

$capacite = (int)$event['capacite_max'];

if ($nbInscrits >= $capacite) {
    $statut   = "liste_attente";
    $paiement = "gratuit";
    $montant  = 0;
    $msg      = "L'événement est complet. Vous êtes placé en liste d'attente.";
} else {
    $statut   = "confirmé";
    $paiement = ($event['prix'] > 0) ? "payé" : "gratuit";
    $montant  = (float)$event['prix'];
    $msg      = "Inscription confirmée ! Votre billet est disponible dans Mes billets.";
}

//génération code billet
$code = "TKT-" . date("Ymd", strtotime($event['date_evenement'])) . "-$user_id" . rand(100, 999);

//ajouter inscription
$insert = $pdo->prepare("
    INSERT INTO inscriptions 
    (utilisateur_id, evenement_id, code_billet, statut, paiement_statut, montant_paye, crée_le)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");

$insert->execute([$user_id, $event_id, $code, $statut, $paiement, $montant]);

header('Location: ../billet/billet.php?msg=' . urlencode($msg));
exit;
