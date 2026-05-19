<?php
require_once __DIR__ . '../BDD.php';

if (!estConnecte()) { header('Location: ../connexion/connexion.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id  = (int)$_SESSION['id'];
    $event_id = (int)($_POST['event_id'] ?? 0);

    // Récup position actuelle
    $req = $pdo->prepare('SELECT position FROM file_attente WHERE utilisateur_id = ? AND evenement_id = ?');
    $req->execute([$user_id, $event_id]);
    $row = $req->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Sup file attente
        $pdo->prepare('DELETE FROM file_attente WHERE utilisateur_id = ? AND evenement_id = ?')
            ->execute([$user_id, $event_id]);

        // réarange file
        $pdo->prepare('UPDATE file_attente SET position = position - 1 WHERE evenement_id = ? AND position > ?')
            ->execute([$event_id, $row['position']]);
    }
}

header('Location: billet.php');
exit;
