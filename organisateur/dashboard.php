<?php
require_once __DIR__ . '/../BDD.php';
require_once __DIR__ . '/../includes/functions.php';
verifierOrganisateurOuAdmin();

$flash = messageFlash();
$params = [];
$where = '';

if (estOrganisateur()) {
    $where = 'WHERE e.organisateur_id = ?';
    $params[] = utilisateurId();
}

$stmt = $pdo->prepare("SELECT e.id, e.titre, e.description, e.date_evenement, e.heure_evenement, e.lieu, e.categorie, e.association, e.capacite_max, e.prix, e.statut,
        COUNT(i.id) AS nb_inscrits,
        SUM(CASE WHEN i.statut = 'présent' THEN 1 ELSE 0 END) AS nb_presents
    FROM evenements e
    LEFT JOIN inscriptions i ON i.evenement_id = e.id AND i.statut <> 'annulé'
    $where
    GROUP BY e.id
    ORDER BY e.date_evenement ASC, e.heure_evenement ASC");
$stmt->execute($params);
$evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$inscritsParEvenement = [];
if (!empty($evenements)) {
    $ids = array_column($evenements, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtInscrits = $pdo->prepare("SELECT i.id AS inscription_id, i.evenement_id, i.code_billet, i.statut, i.paiement_statut, i.montant_paye, i.crée_le,
    u.nom, u.prenom, u.email, u.formation
    FROM inscriptions i
    INNER JOIN utilisateurs u ON u.id = i.utilisateur_id
    WHERE i.evenement_id IN ($placeholders)
    ORDER BY i.crée_le DESC");
    $stmtInscrits->execute($ids);
    foreach ($stmtInscrits->fetchAll(PDO::FETCH_ASSOC) as $inscrit) {
        $inscritsParEvenement[(int) $inscrit['evenement_id']][] = $inscrit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard organisateur - OmnesEvent</title>
</head>
<body>
<?php require_once __DIR__ . '/../header/header.php'; ?>

<main>
    <h1>Tableau de bord organisateur</h1>
    <p>Créer, modifier, annuler des événements, consulter les inscrits et valider les présences.</p>

    <?php if ($flash): ?>
        <p><?= h($flash['message']) ?></p>
    <?php endif; ?>

    <p>
        <a href="../evenement/creer.php">Créer un nouvel événement</a> |
        <a href="scanner.php">Valider une présence par code billet</a>
    </p>

    <?php if (empty($evenements)): ?>
        <p>Aucun événement à afficher.</p>
    <?php else: ?>
        <?php foreach ($evenements as $event): ?>
            <section>
                <h2><?= h($event['titre']) ?></h2>
                <p>
                    <?= h($event['date_evenement']) ?> à <?= h(substr($event['heure_evenement'], 0, 5)) ?> —
                    <?= h($event['lieu']) ?> — <?= h($event['categorie']) ?> — <?= h($event['association']) ?>
                </p>
                <p>Statut : <?= h($event['statut']) ?></p>
                <p>Inscriptions : <?= (int) $event['nb_inscrits'] ?> / <?= (int) $event['capacite_max'] ?> — Présences validées : <?= (int) $event['nb_presents'] ?></p>
                <p>
                    <a href="../evenement/modifier.php?id=<?= (int) $event['id'] ?>">Modifier</a>
                </p>

                <form method="post" action="../evenement/modifier.php?id=<?= (int) $event['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                    <input type="hidden" name="action" value="annuler">
                    <button type="submit">Annuler cet événement</button>
                </form>

                <h3>Liste des inscrits</h3>
                <?php $inscrits = $inscritsParEvenement[(int) $event['id']] ?? []; ?>
                <?php if (empty($inscrits)): ?>
                    <p>Aucun inscrit pour le moment.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Participant</th>
                                <th>Email</th>
                                <th>Formation</th>
                                <th>Code billet</th>
                                <th>Paiement</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscrits as $inscrit): ?>
                                <tr>
                                    <td><?= h($inscrit['prenom'] . ' ' . $inscrit['nom']) ?></td>
                                    <td><?= h($inscrit['email']) ?></td>
                                    <td><?= h($inscrit['formation']) ?></td>
                                    <td><?= h($inscrit['code_billet']) ?></td>
                                    <td><?= h($inscrit['paiement_statut']) ?> — <?= h((string) $inscrit['montant_paye']) ?> €</td>
                                    <td><?= h($inscrit['statut']) ?></td>
                                    <td>
                                        <?php if ($inscrit['statut'] !== 'présent' && $inscrit['statut'] !== 'annulé'): ?>
                                            <form method="post" action="scanner.php">
                                                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                                <input type="hidden" name="code_billet" value="<?= h($inscrit['code_billet']) ?>">
                                                <button type="submit">Valider présence</button>
                                            </form>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>