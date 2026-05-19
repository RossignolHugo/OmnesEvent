<?php
require_once __DIR__ . '/../header/header.php';
require_once __DIR__ . '/../includes/init.php';
verifierConnexion();

$userId = utilisateurId();
$flash = messageFlash();

$stmt = $pdo->prepare('
    SELECT i.id AS inscription_id, i.code_billet, i.statut AS statut_inscription, i.paiement_statut, i.montant_paye, i.crée_le,
    e.id AS evenement_id, e.titre, e.description, e.date_evenement, e.heure_evenement, e.lieu, e.categorie, e.association, e.prix, e.affiche, e.statut AS statut_evenement
    FROM inscriptions i
    INNER JOIN evenements e ON e.id = i.evenement_id
    WHERE i.utilisateur_id = ?
    ORDER BY e.date_evenement ASC, e.heure_evenement ASC
');
$stmt->execute([$userId]);
$billets = $stmt->fetchAll();

$stmt = $pdo->prepare('
    SELECT f.position, f.crée_le, e.titre, e.date_evenement, e.heure_evenement, e.lieu
    FROM file_attente f
    INNER JOIN evenements e ON e.id = f.evenement_id
    WHERE f.utilisateur_id = ?
    ORDER BY e.date_evenement ASC, f.position ASC
');
$stmt->execute([$userId]);
$fileAttente = $stmt->fetchAll();

$avenir = [];
$passes = [];
$annules = [];
$today = date('Y-m-d');

foreach ($billets as $billet) {
    if ($billet['statut_inscription'] === 'annulé' || $billet['statut_evenement'] === 'annulé') {
        $annules[] = $billet;
    } elseif ($billet['date_evenement'] >= $today) {
        $avenir[] = $billet;
    } else {
        $passes[] = $billet;
    }
}

function afficherBillets(array $billets): void
{
    if (empty($billets)) {
        echo '<p>Aucun billet dans cette catégorie.</p>';
        return;
    }
    foreach ($billets as $billet):
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' . urlencode($billet['code_billet']);
        ?>
        <article class="billet-card">
            <h3><?= h($billet['titre']) ?></h3>
            <p>
                <?= h($billet['date_evenement']) ?>
                à <?= h(substr($billet['heure_evenement'], 0, 5)) ?>
                — <?= h($billet['lieu']) ?>
            </p>
            <p>Catégorie : <?= h($billet['categorie']) ?> — Association : <?= h($billet['association']) ?></p>
            <p>
                Statut : <?= h($billet['statut_inscription']) ?>
                — Paiement : <?= h($billet['paiement_statut']) ?>
            </p>
            <p>Code billet : <strong><?= h($billet['code_billet']) ?></strong></p>
            <img src="<?= h($qrUrl) ?>" alt="QR code du billet">
            <?php
            if (in_array($billet['statut_inscription'], ['confirmé', 'présent'])):
            ?>
                <form method="post" action="annulerBillet.php" onsubmit="return confirm('Annuler ce billet ?');">
                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $billet['inscription_id'] ?>">
                    <button type="submit" class="annuler-btn">Annuler le billet</button>
                </form>
            <?php endif; ?>

        </article>
        <?php
    endforeach;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OmnesEvent-Mes billets</title>
    <link rel="stylesheet" href="billet.css">
</head>
<body>
<main>
    <h1>Mes billets</h1>

    <?php if ($flash): ?>
        <p class="<?= h($flash['type']) ?>"><?= h($flash['message']) ?></p>
    <?php endif; ?>

    <section id="EvénementVenir">
        <h2>Evénements à venir</h2>
        <?php afficherBillets($avenir); ?>
    </section>

    <section id="EvenementPassé">
        <h2>Evénements passés</h2>
        <?php afficherBillets($passes); ?>
    </section>

    <section id="BilletsAnnulés">
        <h2>Billets annulés</h2>
        <?php afficherBillets($annules); ?>
    </section>

    <section id="FileAttente">
        <h2>File d’attente</h2>
        <?php if (empty($fileAttente)): ?>
            <p>Vous n’êtes dans aucune file d’attente.</p>
        <?php else: ?>
            <?php foreach ($fileAttente as $attente): ?>
                <article>
                    <h3><?= h($attente['titre']) ?></h3>
                    <p><?= h($attente['date_evenement']) ?> à <?= h(substr($attente['heure_evenement'], 0, 5)) ?> — <?= h($attente['lieu']) ?></p>
                    <p>Position : <?= (int) $attente['position'] ?></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>
<?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
