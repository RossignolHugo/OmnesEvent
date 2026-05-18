<?php
require_once __DIR__ . '/../header/header.php';
require_once __DIR__ . '/../BDD.php';

if (!estConnecte()) { 
    header('Location: ../connexion/connexion.php'); 
    exit; 
}

$user_id = (int)$_SESSION['id'];
$estOrga = estOrganisateur() || estAdmin();

//fonction utilitaire
function fetchAll($pdo, $sql, $params = []) {
    $req = $pdo->prepare($sql);
    $req->execute($params);
    return $req->fetchAll(PDO::FETCH_ASSOC);
}

//billet futur ou passées
$sql_billets = '
    SELECT 
        i.id AS inscription_id,
        i.*, 
        e.*
    FROM inscriptions i
    JOIN evenements e ON e.id = i.evenement_id
    WHERE i.utilisateur_id = ?
      AND i.statut = "confirmé"
      AND e.date_evenement %s CURDATE()
    ORDER BY e.date_evenement %s
';

$billets_futurs = fetchAll($pdo, sprintf($sql_billets, '>=', 'ASC'), [$user_id]);
$billets_passes = fetchAll($pdo, sprintf($sql_billets, '<', 'DESC'), [$user_id]);

//file attente
$attente = fetchAll($pdo, '
    SELECT f.position, e.*, e.id AS event_id
    FROM file_attente f
    JOIN evenements e ON e.id = f.evenement_id
    WHERE f.utilisateur_id = ?
    ORDER BY f.position ASC
', [$user_id]);

//annuler billet
if (!empty($_POST['annuler_billet'])) {

    $event_id = (int)$_POST['event_id'];

    $pdo->prepare('
        DELETE FROM inscriptions
        WHERE utilisateur_id = ?
          AND evenement_id = ?
          AND statut = "confirmé"
        LIMIT 1
    ')->execute([$user_id, $event_id]);

    header('Location: billet.php?msg=' . urlencode("Billet annulé."));
    exit;
}



//sortir de la file d'attente
if (isset($_POST['quitter_file'])) {
    $event_id = (int)$_POST['event_id'];

    $row = fetchAll($pdo, '
        SELECT position FROM file_attente 
        WHERE utilisateur_id = ? AND evenement_id = ?
    ', [$user_id, $event_id])[0] ?? null;

    if ($row) {
        $pdo->prepare('DELETE FROM file_attente WHERE utilisateur_id = ? AND evenement_id = ?')
            ->execute([$user_id, $event_id]);

        $pdo->prepare('UPDATE file_attente 
                       SET position = position - 1 
                       WHERE evenement_id = ? AND position > ?')
            ->execute([$event_id, $row['position']]);
    }

    header('Location: billet.php?msg=' . urlencode("Vous avez quitté la file d'attente."));
    exit;
}

//tableaux de bord organisateur/admin
$dashboard = [];
$inscrits_par_event = [];

if ($estOrga) {
    $dashboard = fetchAll($pdo, "
        SELECT 
            e.id,
            e.titre,
            e.date_evenement,
            e.heure_evenement,
            e.capacite_max,
            COUNT(i.id) AS inscrits,
            SUM(i.paiement_statut = 'payé') AS payants,
            SUM(i.paiement_statut = 'gratuit') AS gratuits,
            (SELECT COUNT(*) FROM file_attente f WHERE f.evenement_id = e.id) AS attente
        FROM evenements e
        LEFT JOIN inscriptions i ON i.evenement_id = e.id
        WHERE e.organisateur_id = ?
        GROUP BY e.id
        ORDER BY e.date_evenement ASC
    ", [$user_id]);

    foreach ($dashboard as $d) {
        $inscrits_par_event[$d['id']] = fetchAll($pdo, "
            SELECT i.*, u.nom, u.prenom, u.email
            FROM inscriptions i
            JOIN utilisateurs u ON u.id = i.utilisateur_id
            WHERE i.evenement_id = ?
            ORDER BY i.statut DESC, i.crée_le ASC
        ", [$d['id']]);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>OmnesEvent-MesBillets</title>
    <link rel="stylesheet" href="billet.css">
</head>

<body>
<main>
<div class="conteneur">
<?php
function afficherSection($titre, $liste, $type) {
    echo "<h2>$titre</h2>";

    if (empty($liste)) {
        echo '<p class="msg-vide">Aucun élément.</p>';
        return;
    }

    echo '<div class="grille-evenements">';

    foreach ($liste as $item) {

        echo '<div class="carte-evenement">';

        echo '<h3>' . h($item['titre']) . '</h3>';
        echo '<p>📅 ' . h($item['date_evenement']) . ' — 🕐 ' . h(substr($item['heure_evenement'], 0, 5)) . '</p>';
        echo '<p>📍 ' . h($item['lieu']) . '</p>';

        //event future
        if ($type === 'futur') {

            echo '<p><strong>Code billet :</strong> <code>' . h($item['code_billet']) . '</code></p>';

            echo '<img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' 
                 . urlencode($item['code_billet']) . '">';

            //annuler billet
            echo '<form method="POST" action="billet.php" style="margin-top:10px;">
                    <input type="hidden" name="annuler_billet" value="1">
                    <input type="hidden" name="event_id" value="' . (int)$item['evenement_id'] . '">
                    <button type="submit" class="btn-quitter">❌ Annuler</button>
                  </form>';
        }
        //event passé
        if ($type === 'passe') {
            echo '<p class="badge-passe">✔ Participé</p>';
        }
        //file attente
        if ($type === 'attente') {
            echo '<p class="badge-attente">Position : #' . (int)$item['position'] . '</p>';
            echo '<form method="POST" action="billet.php" style="margin-top:10px;">
                    <input type="hidden" name="quitter_file" value="1">
                    <input type="hidden" name="event_id" value="' . (int)$item['event_id'] . '">
                    <button type="submit" class="btn-quitter">❌ Quitter</button>
                  </form>';
        }

        echo '</div>';
    }

    echo '</div>';
}
?>

<section id="Billets">
    <h1>🎫 Mes billets</h1>
    <?php if (!empty($_GET['msg'])): ?>
        <p class="succes"><?= h($_GET['msg']) ?></p>
    <?php endif; ?>

    <?php
    afficherSection("📅 Événements à venir", $billets_futurs, "futur");
    afficherSection("📜 Événements passés", $billets_passes, "passe");
    afficherSection("⏳ File d'attente", $attente, "attente");
    ?>

    </div>
    </main>
</section>
<?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
