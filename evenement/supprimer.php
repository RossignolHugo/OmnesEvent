<?php
require_once __DIR__ . '/../includes/init.php';
verifierConnexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Si csrfVerify existe, on vérifie
    if (function_exists('csrfVerify')) {
        csrfVerify($_POST['csrf_token']);
    }

    if (!isset($_POST['id'])) {
        die("ID manquant");
    }

    $id = (int) $_POST['id'];

    // Sup inscription
    $pdo->prepare("DELETE FROM inscriptions WHERE evenement_id = ?")->execute([$id]);

    // Sup event
    $pdo->prepare("DELETE FROM evenements WHERE id = ?")->execute([$id]);

    messageFlash('succes', "L'événement a bien été supprimé.");
    rediriger('../organisateur/dashboard.php');
}
