<?php
require_once __DIR__ . '/../BDD.php';
require_once __DIR__ . '/../includes/functions.php';
verifierAdmin();
verifierCsrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    messageFlash('erreur', 'Événement invalide.');
    rediriger('admin.php');
}

$pdo->beginTransaction();
try {
    $pdo->prepare('DELETE FROM presences WHERE inscription_id IN (SELECT id FROM inscriptions WHERE evenement_id = ?)')->execute([$id]);
    $pdo->prepare('DELETE FROM inscriptions WHERE evenement_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM file_attente WHERE evenement_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM evenements WHERE id = ?')->execute([$id]);
    $pdo->commit();
    messageFlash('succes', 'Événement supprimé.');
} catch (Throwable $e) {
    $pdo->rollBack();
    messageFlash('erreur', 'Erreur lors de la suppression : ' . $e->getMessage());
}

rediriger('admin.php');
