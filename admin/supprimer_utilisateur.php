<?php
require_once __DIR__ . '/../BDD.php';
require_once __DIR__ . '/../includes/functions.php';
verifierAdmin();
verifierCsrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0 || $id === utilisateurId()) {
    messageFlash('erreur', 'Impossible de supprimer ce compte.');
    rediriger('admin.php');
}

$pdo->beginTransaction();
try {
    $stmtEvents = $pdo->prepare('SELECT id FROM evenements WHERE organisateur_id = ?');
    $stmtEvents->execute([$id]);
    $eventIds = $stmtEvents->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($eventIds)) {
        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $stmtInscriptions = $pdo->prepare("DELETE FROM inscriptions WHERE evenement_id IN ($placeholders)");
        $stmtInscriptions->execute($eventIds);

        $stmtAttente = $pdo->prepare("DELETE FROM file_attente WHERE evenement_id IN ($placeholders)");
        $stmtAttente->execute($eventIds);

        $stmtEventsDelete = $pdo->prepare("DELETE FROM evenements WHERE id IN ($placeholders)");
        $stmtEventsDelete->execute($eventIds);
    }

    $pdo->prepare('DELETE FROM inscriptions WHERE utilisateur_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM file_attente WHERE utilisateur_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM utilisateurs WHERE id = ?')->execute([$id]);

    $pdo->commit();
    messageFlash('succes', 'Compte supprimé. Les données liées ont été nettoyées.');
} catch (Throwable $e) {
    $pdo->rollBack();
    messageFlash('erreur', 'Erreur lors de la suppression : ' . $e->getMessage());
}

rediriger('admin.php');
