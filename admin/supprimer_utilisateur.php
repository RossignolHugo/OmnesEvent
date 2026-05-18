<?php
require_once __DIR__ . '/../includes/init.php';
verifierAdmin();
verifierCsrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0 || $id === utilisateurId()) {
    messageFlash('erreur', 'Impossible de désactiver ce compte.');
    rediriger('admin.php');
}

$stmt = $pdo->prepare('UPDATE utilisateurs SET actif = 0 WHERE id = ?');
$stmt->execute([$id]);

messageFlash('succes', 'Compte désactivé.');
rediriger('admin.php');
