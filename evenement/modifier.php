<?php
require_once __DIR__ . '/../BDD.php';
require_once __DIR__ . '/../functions.php';
verifierOrganisateurOuAdmin();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0 || !peutGererEvenement($pdo, $id)) {
    http_response_code(403);
    exit('Événement introuvable ou accès refusé.');
}

$stmt = $pdo->prepare('SELECT * FROM evenements WHERE id = ?');
$stmt->execute([$id]);
$evenement = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$evenement) {
    http_response_code(404);
    exit('Événement introuvable.');
}

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    if (($_POST['action'] ?? '') === 'annuler') {
        $pdo->prepare("UPDATE evenements SET statut = 'annulé' WHERE id = ?")->execute([$id]);
        messageFlash('succes', 'Événement annulé.');
        rediriger('../organisateur/dashboard.php');
    }

    $valeurs = [
        'titre' => trim($_POST['titre'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'date_evenement' => trim($_POST['date_evenement'] ?? ''),
        'heure_evenement' => trim($_POST['heure_evenement'] ?? ''),
        'lieu' => trim($_POST['lieu'] ?? ''),
        'categorie' => trim($_POST['categorie'] ?? ''),
        'association' => trim($_POST['association'] ?? ''),
        'capacite_max' => (int) ($_POST['capacite_max'] ?? 0),
        'prix' => (float) ($_POST['prix'] ?? 0),
        'statut' => trim($_POST['statut'] ?? 'publié'),
    ];

    $categoriesAutorisees = ['Soirée', 'Sport', 'Culture', 'Conférence'];
    $statutsAutorises = ['publié', 'annulé', 'archivé'];

    if ($valeurs['titre'] === '') $erreurs[] = 'Le titre est obligatoire.';
    if ($valeurs['date_evenement'] === '') $erreurs[] = 'La date est obligatoire.';
    if ($valeurs['heure_evenement'] === '') $erreurs[] = "L'heure est obligatoire.";
    if ($valeurs['lieu'] === '') $erreurs[] = 'Le lieu est obligatoire.';
    if ($valeurs['association'] === '') $erreurs[] = "L'association est obligatoire.";
    if (!in_array($valeurs['categorie'], $categoriesAutorisees, true)) $erreurs[] = 'Catégorie invalide.';
    if (!in_array($valeurs['statut'], $statutsAutorises, true)) $erreurs[] = 'Statut invalide.';
    if ($valeurs['capacite_max'] <= 0) $erreurs[] = 'La capacité maximale doit être supérieure à 0.';
    if ($valeurs['prix'] < 0) $erreurs[] = 'Le prix ne peut pas être négatif.';

    $affiche = $evenement['affiche'];
    if (empty($erreurs) && !empty($_FILES['affiche']['name'])) {
        try {
            $nouvelleAffiche = enregistrerAffiche($_FILES['affiche']);
            if ($nouvelleAffiche !== null) {
                $affiche = $nouvelleAffiche;
            }
        } catch (Throwable $e) {
            $erreurs[] = $e->getMessage();
        }
    }

    if (empty($erreurs)) {
        $stmt = $pdo->prepare("UPDATE evenements
            SET titre = ?, description = ?, date_evenement = ?, heure_evenement = ?, lieu = ?, categorie = ?, association = ?, capacite_max = ?, prix = ?, affiche = ?, statut = ?
            WHERE id = ?");
        $stmt->execute([
            $valeurs['titre'],
            $valeurs['description'],
            $valeurs['date_evenement'],
            $valeurs['heure_evenement'],
            $valeurs['lieu'],
            $valeurs['categorie'],
            $valeurs['association'],
            $valeurs['capacite_max'],
            $valeurs['prix'],
            $affiche,
            $valeurs['statut'],
            $id,
        ]);

        messageFlash('succes', 'Événement modifié avec succès.');
        rediriger('../organisateur/dashboard.php');
    }
} else {
    $valeurs = $evenement;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un événement - OmnesEvent</title>
</head>
<body>
<?php require_once __DIR__ . '/../header/header.php'; ?>

<main>
    <h1>Modifier un événement</h1>

    <?php if (!empty($erreurs)): ?>
        <ul>
            <?php foreach ($erreurs as $erreur): ?>
                <li><?= h($erreur) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

        <label for="titre">Titre</label>
        <input type="text" id="titre" name="titre" value="<?= h($valeurs['titre']) ?>" required>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="5"><?= h($valeurs['description']) ?></textarea>

        <label for="date_evenement">Date</label>
        <input type="date" id="date_evenement" name="date_evenement" value="<?= h($valeurs['date_evenement']) ?>" required>

        <label for="heure_evenement">Heure</label>
        <input type="time" id="heure_evenement" name="heure_evenement" value="<?= h(substr($valeurs['heure_evenement'], 0, 5)) ?>" required>

        <label for="lieu">Lieu</label>
        <input type="text" id="lieu" name="lieu" value="<?= h($valeurs['lieu']) ?>" required>

        <label for="categorie">Catégorie</label>
        <select id="categorie" name="categorie" required>
            <?php foreach (['Soirée', 'Sport', 'Culture', 'Conférence'] as $categorie): ?>
                <option value="<?= h($categorie) ?>" <?= $valeurs['categorie'] === $categorie ? 'selected' : '' ?>><?= h($categorie) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="association">Association</label>
        <input type="text" id="association" name="association" value="<?= h($valeurs['association']) ?>" required>

        <label for="capacite_max">Capacité maximale</label>
        <input type="number" id="capacite_max" name="capacite_max" min="1" value="<?= h((string) $valeurs['capacite_max']) ?>" required>

        <label for="prix">Prix</label>
        <input type="number" id="prix" name="prix" min="0" step="0.01" value="<?= h((string) $valeurs['prix']) ?>">

        <label for="statut">Statut</label>
        <select id="statut" name="statut">
            <?php foreach (['publié', 'annulé', 'archivé'] as $statut): ?>
                <option value="<?= h($statut) ?>" <?= $valeurs['statut'] === $statut ? 'selected' : '' ?>><?= h($statut) ?></option>
            <?php endforeach; ?>
        </select>

        <?php if (!empty($valeurs['affiche'])): ?>
            <p>Affiche actuelle : <?= h($valeurs['affiche']) ?></p>
        <?php endif; ?>

        <label for="affiche">Nouvelle affiche</label>
        <input type="file" id="affiche" name="affiche" accept="image/*">

        <button type="submit">Enregistrer les modifications</button>
    </form>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" value="annuler">
        <button type="submit">Annuler cet événement</button>
    </form>
</main>

<?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
