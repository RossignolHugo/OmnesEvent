<?php
require_once __DIR__ . '/../header/header.php';
require_once __DIR__ . '/../BDD.php';

if (!estAdmin()) { 
    header('Location: ../index/index.php'); 
    exit; 
}

$message = '';

//differente action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $cible   = (int)($_POST['id'] ?? 0);

    switch ($action) {

        case 'valider':
            $pdo->prepare('UPDATE utilisateurs SET valide = 1 WHERE id = ? AND role = "organisateur"')
                ->execute([$cible]);
            $message = 'Compte organisateur validé.';
            break;

        case 'supprimer_compte':
            if ($cible !== (int)$_SESSION['id']) {
                $pdo->prepare('DELETE FROM utilisateurs WHERE id = ?')->execute([$cible]);
                $message = 'Compte supprimé.';
            }
            break;

        case 'supprimer_event':
            $pdo->prepare('DELETE FROM evenements WHERE id = ?')->execute([$cible]);
            $message = 'Événement supprimé.';
            break;
    }

    header('Location: admin.php?msg=' . urlencode($message));
    exit;
}

if (isset($_GET['msg'])) $message = h($_GET['msg']);


//petite données des familles
$enAttente = $pdo->query('SELECT * FROM utilisateurs WHERE role = "organisateur" AND valide = 0')
                 ->fetchAll(PDO::FETCH_ASSOC);

$users = $pdo->query('SELECT * FROM utilisateurs ORDER BY role, nom ASC')
             ->fetchAll(PDO::FETCH_ASSOC);

$events = $pdo->query('SELECT e.*, u.nom AS orga_nom, u.prenom AS orga_prenom
                       FROM evenements e
                       LEFT JOIN utilisateurs u ON e.organisateur_id = u.id
                       ORDER BY e.date_evenement ASC')
              ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin — OmnesEvent</title>
    <link rel="stylesheet" href="../header/header.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<main>
<div class="container">

    <h1>⚙️ Panneau administrateur</h1>

    <?php if ($message): ?>
        <p class="succes">✅ <?= h($message) ?></p>
    <?php endif; ?>

    <div class="tabs">
        <button class="tab active" onclick="afficher('attente', this)">
            🟡 En attente <?= count($enAttente) ? '<span class="badge">'.count($enAttente).'</span>' : '' ?>
        </button>
        <button class="tab" onclick="afficher('users', this)">👥 Utilisateurs</button>
        <button class="tab" onclick="afficher('events', this)">📋 Événements</button>
    </div>

    <div id="attente" class="tab-content">
        <h2>Comptes organisateurs en attente</h2>

        <?php if (empty($enAttente)): ?>
            <p class="vide">Aucun compte en attente.</p>
        <?php else: ?>
            <table class="table-admin">
                <tr><th>Nom</th><th>Email</th><th>Formation</th><th>Action</th></tr>
                <?php foreach ($enAttente as $u): ?>
                <tr>
                    <td><?= h($u['prenom'].' '.$u['nom']) ?></td>
                    <td><?= h($u['email']) ?></td>
                    <td><?= h($u['formation']) ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="action" value="valider">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button class="btn-valider">✅ Valider</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <div id="users" class="tab-content" style="display:none">
        <h2>Tous les utilisateurs</h2>

        <table class="table-admin">
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Validé</th>
                <th>Action</th>
            </tr>

            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= h($u['prenom'].' '.$u['nom']) ?></td>
                <td><?= h($u['email']) ?></td>
                <td><?= h($u['role']) ?></td>
                <td><?= $u['valide'] ? '✅' : '⏳' ?></td>
                <td>
                    <?php if ($u['id'] !== (int)$_SESSION['id']): ?>
                    <form method="POST" onsubmit="return confirm('Supprimer ce compte ?')">
                        <input type="hidden" name="action" value="supprimer_compte">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <button class="btn-supprimer">🗑️</button>
                    </form>
                    <?php else: ?>
                        <span style="color:#aaa">Vous</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>

        </table>
    </div>


        <div id="events" class="tab-content" style="display:none">
            <h2>Tous les événements</h2>

            <table class="table-admin">
                <tr>
                    <th>Titre</th>
                    <th>Date</th>
                    <th>Heure</th>
                    <th>Lieu</th>
                    <th>Catégorie</th>
                    <th>Association</th>
                    <th>Capacité</th>
                    <th>Prix</th>
                    <th>Organisateur</th>
                    <th>Action</th>
                </tr>

                <?php foreach ($events as $ev): ?>
                    <tr>
                        <td><?= h($ev['titre']) ?></td>
                        <td><?= h($ev['date_evenement']) ?></td>
                        <td><?= h(substr($ev['heure_evenement'], 0, 5)) ?></td>
                        <td><?= h($ev['lieu']) ?></td>
                        <td><?= h($ev['categorie']) ?></td>
                        <td><?= h($ev['association']) ?></td>
                        <td><?= (int)$ev['capacite_max'] ?></td>
                        <td><?= $ev['prix'] == 0 ? 'Gratuit' : number_format($ev['prix'], 2).' €' ?></td>
                        <td><?= h($ev['orga_prenom'].' '.$ev['orga_nom']) ?></td>

                        <td>
                            <form method="POST" onsubmit="return confirm('Supprimer cet événement ?')">
                                <input type="hidden" name="action" value="supprimer_event">
                                <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
                                <button class="btn-supprimer">🗑️</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>


</div>
</main>

<script src="admin.js"></script>
<?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
