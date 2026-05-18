<?php
require_once __DIR__ . '/../includes/init.php';

verifierAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    $action = $_POST['action'] ?? '';
    $cible = (int)($_POST['id'] ?? 0);

    if ($cible <= 0) {
        messageFlash('erreur', 'Action impossible : identifiant invalide.');
        rediriger('admin.php');
    }

    switch ($action) {
        case 'valider_compte':
            $stmt = $pdo->prepare("UPDATE utilisateurs SET valide = 1, actif = 1 WHERE id = ? AND role = 'organisateur'");
            $stmt->execute([$cible]);
            messageFlash('succes', 'Compte organisateur validé.');
            break;

        case 'refuser_compte':
            $stmt = $pdo->prepare("UPDATE utilisateurs SET actif = 0, valide = 0 WHERE id = ? AND role = 'organisateur'");
            $stmt->execute([$cible]);
            messageFlash('succes', 'Compte organisateur refusé.');
            break;

        case 'desactiver_utilisateur':
            if ($cible === utilisateurId()) {
                messageFlash('erreur', 'Vous ne pouvez pas désactiver votre propre compte administrateur.');
                break;
            }

            $stmt = $pdo->prepare('UPDATE utilisateurs SET actif = 0 WHERE id = ?');
            $stmt->execute([$cible]);
            messageFlash('succes', 'Compte utilisateur désactivé.');
            break;

        case 'archiver_evenement':
            $stmt = $pdo->prepare("UPDATE evenements SET statut = 'archivé' WHERE id = ?");
            $stmt->execute([$cible]);
            messageFlash('succes', 'Événement archivé.');
            break;

        default:
            messageFlash('erreur', 'Action inconnue.');
            break;
    }

    rediriger('admin.php');
}

$flash = messageFlash();

$stats = [
    'utilisateurs' => (int) $pdo->query('SELECT COUNT(*) FROM utilisateurs')->fetchColumn(),
    'utilisateurs_actifs' => (int) $pdo->query('SELECT COUNT(*) FROM utilisateurs WHERE actif = 1')->fetchColumn(),
    'organisateurs_attente' => (int) $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'organisateur' AND valide = 0 AND actif = 1")->fetchColumn(),
    'evenements' => (int) $pdo->query('SELECT COUNT(*) FROM evenements')->fetchColumn(),
    'inscriptions' => (int) $pdo->query("SELECT COUNT(*) FROM inscriptions WHERE statut <> 'annulé'")->fetchColumn(),
];

$organisateursEnAttente = $pdo->query("SELECT id, nom, prenom, email, formation, crée_le
    FROM utilisateurs
    WHERE role = 'organisateur' AND valide = 0 AND actif = 1
    ORDER BY crée_le DESC")->fetchAll(PDO::FETCH_ASSOC);

$utilisateurs = $pdo->query("SELECT id, nom, prenom, email, role, formation, actif, valide, crée_le
    FROM utilisateurs
    ORDER BY role ASC, nom ASC, prenom ASC")->fetchAll(PDO::FETCH_ASSOC);

$evenements = $pdo->query("SELECT
        e.id,
        e.titre,
        e.date_evenement,
        e.heure_evenement,
        e.lieu,
        e.categorie,
        e.association,
        e.statut,
        e.capacite_max,
        e.prix,
        u.prenom AS organisateur_prenom,
        u.nom AS organisateur_nom,
        COALESCE(ins.nb_inscrits, 0) AS nb_inscrits
    FROM evenements e
    LEFT JOIN utilisateurs u ON u.id = e.organisateur_id
    LEFT JOIN (
        SELECT evenement_id, COUNT(*) AS nb_inscrits
        FROM inscriptions
        WHERE statut <> 'annulé'
        GROUP BY evenement_id
    ) ins ON ins.evenement_id = e.id
    ORDER BY e.date_evenement DESC, e.heure_evenement DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - OmnesEvent</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<?php require_once __DIR__ . '/../header/header.php'; ?>

<main class="admin-page">
    <div class="container">
        <h1>⚙️ Panneau administrateur</h1>
        <p class="intro">Gestion des comptes, validation des organisateurs et modération des événements.</p>

        <?php if ($flash): ?>
            <p class="message <?= h($flash['type']) ?>">
                <?= h($flash['message']) ?>
            </p>
        <?php endif; ?>

        <section class="stats-grid" aria-label="Résumé administrateur">
            <article class="stat-card">
                <span>Utilisateurs</span>
                <strong><?= $stats['utilisateurs'] ?></strong>
            </article>
            <article class="stat-card">
                <span>Utilisateurs actifs</span>
                <strong><?= $stats['utilisateurs_actifs'] ?></strong>
            </article>
            <article class="stat-card">
                <span>Organisateurs en attente</span>
                <strong><?= $stats['organisateurs_attente'] ?></strong>
            </article>
            <article class="stat-card">
                <span>Événements</span>
                <strong><?= $stats['evenements'] ?></strong>
            </article>
            <article class="stat-card">
                <span>Inscriptions actives</span>
                <strong><?= $stats['inscriptions'] ?></strong>
            </article>
        </section>

        <div class="tabs">
            <button type="button" class="tab active" onclick="afficher('attente', this)">
                🟡 En attente
                <?php if (count($organisateursEnAttente) > 0): ?>
                    <span class="badge"><?= count($organisateursEnAttente) ?></span>
                <?php endif; ?>
            </button>
            <button type="button" class="tab" onclick="afficher('users', this)">👥 Utilisateurs</button>
            <button type="button" class="tab" onclick="afficher('events', this)">📋 Événements</button>
        </div>

        <section id="attente" class="tab-content">
            <h2>Comptes organisateurs à valider</h2>

            <?php if (empty($organisateursEnAttente)): ?>
                <p class="vide">Aucun compte organisateur en attente.</p>
            <?php else: ?>
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Formation</th>
                            <th>Demande</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($organisateursEnAttente as $org): ?>
                            <tr>
                                <td><?= h($org['prenom'] . ' ' . $org['nom']) ?></td>
                                <td><?= h($org['email']) ?></td>
                                <td><?= h($org['formation']) ?></td>
                                <td><?= h($org['crée_le']) ?></td>
                                <td class="actions">
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="valider_compte">
                                        <input type="hidden" name="id" value="<?= (int) $org['id'] ?>">
                                        <button type="submit" class="btn-valider">✅ Valider</button>
                                    </form>

                                    <form method="post" onsubmit="return confirm('Refuser ce compte organisateur ?');">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="refuser_compte">
                                        <input type="hidden" name="id" value="<?= (int) $org['id'] ?>">
                                        <button type="submit" class="btn-supprimer">✖ Refuser</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section id="users" class="tab-content" style="display:none">
            <h2>Gestion des utilisateurs</h2>

            <table class="table-admin">
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
                            <td><span class="badge-role badge-<?= h($user['role']) ?>"><?= h($user['role']) ?></span></td>
                            <td><?= h($user['formation']) ?></td>
                            <td><?= ((int) $user['actif'] === 1) ? '✅ Actif' : '⛔ Désactivé' ?></td>
                            <td><?= ((int) $user['valide'] === 1) ? '✅ Validé' : '⏳ Non validé' ?></td>
                            <td>
                                <?php if ((int) $user['id'] !== utilisateurId() && (int) $user['actif'] === 1): ?>
                                    <form method="post" onsubmit="return confirm('Désactiver ce compte ?');">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="desactiver_utilisateur">
                                        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                        <button type="submit" class="btn-supprimer">🗑️ Désactiver</button>
                                    </form>
                                <?php elseif ((int) $user['id'] === utilisateurId()): ?>
                                    <span class="muted">Vous</span>
                                <?php else: ?>
                                    <span class="muted">Déjà désactivé</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section id="events" class="tab-content" style="display:none">
            <h2>Modération des événements</h2>

            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Date</th>
                        <th>Lieu</th>
                        <th>Catégorie</th>
                        <th>Association</th>
                        <th>Prix</th>
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
                            <td><?= ((float) $event['prix'] <= 0) ? 'Gratuit' : h(number_format((float) $event['prix'], 2, ',', ' ') . ' €') ?></td>
                            <td><?= h(trim(($event['organisateur_prenom'] ?? '') . ' ' . ($event['organisateur_nom'] ?? ''))) ?></td>
                            <td><?= (int) $event['nb_inscrits'] ?> / <?= (int) $event['capacite_max'] ?></td>
                            <td><?= h($event['statut']) ?></td>
                            <td class="actions">
                                <a class="btn-modifier" href="../evenement/modifier.php?id=<?= (int) $event['id'] ?>">Modifier</a>

                                <?php if ($event['statut'] !== 'archivé'): ?>
                                    <form method="post" onsubmit="return confirm('Archiver cet événement ?');">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="archiver_evenement">
                                        <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">
                                        <button type="submit" class="btn-supprimer">Archiver</button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted">Archivé</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</main>

<script src="admin.js"></script>
<?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
