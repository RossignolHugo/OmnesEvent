<?php
require_once __DIR__ . '/../BDD.php';
require_once __DIR__ . '/../includes/functions.php';
verifierAdmin();
verifierCsrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    messageFlash('erreur', 'Compte invalide.');
    rediriger('admin.php');
}

$stmt = $pdo->prepare("UPDATE utilisateurs SET valide = 1, actif = 1 WHERE id = ? AND role = 'organisateur'");
$stmt->execute([$id]);

messageFlash('succes', $stmt->rowCount() > 0 ? 'Compte organisateur validé.' : 'Aucun compte organisateur à valider.');
rediriger('admin.php');
