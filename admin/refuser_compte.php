<?php
require_once __DIR__ . '/../includes/init.php';
verifierAdmin();
verifierCsrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0 || $id === utilisateurId()) {
    messageFlash('erreur', 'Compte invalide.');
    rediriger('admin.php');
}

$stmt = $pdo->prepare("UPDATE utilisateurs SET actif = 0, valide = 0 WHERE id = ? AND role = 'organisateur'");
$stmt->execute([$id]);

messageFlash('succes', $stmt->rowCount() > 0 ? 'Demande organisateur refusée.' : 'Aucun compte à refuser.');
rediriger('admin.php');
