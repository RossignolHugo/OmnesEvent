<?php
require_once __DIR__ . '/../BDD.php';
require_once __DIR__ . '/../includes/functions.php';
verifierAdmin();

$flash = messageFlash();

$stats = [
    'utilisateurs' => (int) $pdo->query('SELECT COUNT(*) FROM utilisateurs')->fetchColumn(),
    'organisateurs_attente' => (int) $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'organisateur' AND valide = 0 AND actif = 1")->fetchColumn(),
    'evenements' => (int) $pdo->query('SELECT COUNT(*) FROM evenements')->fetchColumn(),
    'inscriptions' => (int) $pdo->query("SELECT COUNT(*) FROM inscriptions WHERE statut <> 'annulé'")->fetchColumn(),
];

$organisateursEnAttente = $pdo->query("SELECT id, nom, prenom, email, formation, crée_le FROM utilisateurs WHERE role = 'organisateur' AND valide = 0 AND actif = 1 ORDER BY crée_le DESC")->fetchAll(PDO::FETCH_ASSOC);

$utilisateurs = $pdo->query("SELECT id, nom, prenom, email, role, formation, actif, valide, crée_le FROM utilisateurs ORDER BY role ASC, nom ASC, prenom ASC")->fetchAll(PDO::FETCH_ASSOC);

$evenements = $pdo->query("SELECT e.id, e.titre, e.date_evenement, e.heure_evenement, e.lieu, e.categorie, e.association, e.statut, e.capacite_max, u.prenom, u.nom, COUNT(i.id) AS nb_inscrits
    FROM evenements e
    LEFT JOIN utilisateurs u ON u.id = e.organisateur_id
    LEFT JOIN inscriptions i ON i.evenement_id = e.id AND i.statut <> 'annulé'
    GROUP BY e.id
    ORDER BY e.date_evenement DESC, e.heure_evenement DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - OmnesEvent</title>
</head>
<body>
<?php require_once __DIR__ . '/../header/header.php'; ?>

<main>
    <h1>Tableau de bord administrateur</h1>
    <p>Gestion des comptes, validation des organisateurs et modération des événements.</p>

    <?php if ($flash): ?>
        <p><?= h($flash['message']) ?></p>
    <?php endif; ?>

    <section>
        <h2>Résumé</h2>
        <ul>
            <li>Utilisateurs : <?= $stats['utilisateurs'] ?></li>
            <li>Organisateurs en attente : <?= $stats['organisateurs_attente'] ?></li>
            <li>Événements : <?= $stats['evenements'] ?></li>
            <li>Inscriptions actives : <?= $stats['inscriptions'] ?></li>
        </ul>
    </section>

    <section>
        <h2>Comptes organisateurs à valider</h2>
        <?php if (empty($organisateursEnAttente)): ?>
            <p>Aucun compte organisateur en attente.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Formation</th>
                        <th>Demande</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($organisateursEnAttente as $org): ?>
                        <tr>
                            <td><?= h($org['prenom'] . ' ' . $org['nom']) ?></td>
                            <td><?= h($org['email']) ?></td>
                            <td><?= h($org['formation']) ?></td>
                            <td><?= h($org['crée_le']) ?></td>
                            <td>
                                <form method="post" action="valider_compte.php">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $org['id'] ?>">
                                    <button type="submit">Valider</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section>
        <h2>Gestion des utilisateurs</h2>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Formation</th>
                    <th>Statut</th>
                    <th>Validation</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utilisateurs as $user): ?>
                    <tr>
                        <td><?= h($user['prenom'] . ' ' . $user['nom']) ?></td>
                        <td><?= h($user['email']) ?></td>
                        <td><?= h($user['role']) ?></td>
                        <td><?= h($user['formation']) ?></td>
                        <td><?= ((int) $user['actif'] === 1) ? 'Actif' : 'Désactivé' ?></td>
                        <td><?= ((int) $user['valide'] === 1) ? 'Validé' : 'Non validé' ?></td>
                        <td>
                            <?php if ((int) $user['id'] !== utilisateurId()): ?>
                                <form method="post" action="supprimer_utilisateur.php" onsubmit="return confirm('Supprimer ce compte ?');">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                    <button type="submit">Supprimer</button>
                                </form>
                            <?php else: ?>
                                Compte actuel
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Modération des événements</h2>
        <table>
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Date</th>
                    <th>Lieu</th>
                    <th>Catégorie</th>
                    <th>Association</th>
                    <th>Organisateur</th>
                    <th>Inscrits</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($evenements as $event): ?>
                    <tr>
                        <td><?= h($event['titre']) ?></td>
                        <td><?= h($event['date_evenement'] . ' ' . substr($event['heure_evenement'], 0, 5)) ?></td>
                        <td><?= h($event['lieu']) ?></td>
                        <td><?= h($event['categorie']) ?></td>
                        <td><?= h($event['association']) ?></td>
                        <td><?= h(trim(($event['prenom'] ?? '') . ' ' . ($event['nom'] ?? ''))) ?></td>
                        <td><?= (int) $event['nb_inscrits'] ?> / <?= (int) $event['capacite_max'] ?></td>
                        <td><?= h($event['statut']) ?></td>
                        <td>
                            <a href="../evenement/modifier.php?id=<?= (int) $event['id'] ?>">Modifier</a>
                            <form method="post" action="supprimer_evenement.php" onsubmit="return confirm('Supprimer définitivement cet événement ?');">
                                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">
                                <button type="submit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>

<?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
