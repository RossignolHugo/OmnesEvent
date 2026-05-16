<?php
require_once __DIR__ . '/../BDD.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT e.*, u.prenom, u.nom,
COUNT(i.id) AS nb_inscrits
FROM evenements e
LEFT JOIN utilisateurs u ON u.id = e.organisateur_id
LEFT JOIN inscriptions i ON i.evenement_id = e.id AND i.statut <> 'annulé'
WHERE e.id = ?
GROUP BY e.id");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(404);
    exit('Événement introuvable.');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($event['titre']) ?> - OmnesEvent</title>
</head>
<body>
<?php require_once __DIR__ . '/../header/header.php'; ?>

<main>
    <h1><?= h($event['titre']) ?></h1>
    <p><?= h($event['date_evenement']) ?> à <?= h(substr($event['heure_evenement'], 0, 5)) ?></p>
    <p>Lieu : <?= h($event['lieu']) ?></p>
    <p>Catégorie : <?= h($event['categorie']) ?> — Association : <?= h($event['association']) ?></p>
    <p>Organisateur : <?= h(trim(($event['prenom'] ?? '') . ' ' . ($event['nom'] ?? ''))) ?></p>
    <p>Inscrits : <?= (int) $event['nb_inscrits'] ?> / <?= (int) $event['capacite_max'] ?></p>
    <p>Prix : <?= h((string) $event['prix']) ?> €</p>
    <p>Statut : <?= h($event['statut']) ?></p>
    <p><?= nl2br(h($event['description'])) ?></p>

    <?php if (!empty($event['affiche'])): ?>
        <img src="../<?= h($event['affiche']) ?>" alt="Affiche de l'événement <?= h($event['titre']) ?>">
    <?php endif; ?>

    <?php if (estOrganisateur() || estAdmin()): ?>
        <p><a href="modifier.php?id=<?= (int) $event['id'] ?>">Modifier cet événement</a></p>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
