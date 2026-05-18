<?php
require_once __DIR__ . '/../includes/init.php';
verifierAdmin();
verifierCsrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    messageFlash('erreur', 'Événement invalide.');
    rediriger('admin.php');
}

$stmt = $pdo->prepare("UPDATE evenements SET statut = 'archivé' WHERE id = ?");
$stmt->execute([$id]);

messageFlash('succes', 'Événement archivé.');
rediriger('admin.php');
