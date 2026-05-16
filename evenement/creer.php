<?php
require_once __DIR__ . '/../BDD.php';
require_once __DIR__ . '/../includes/functions.php';
verifierOrganisateurOuAdmin();

$erreurs = [];
$valeurs = [
    'titre' => '',
    'description' => '',
    'date_evenement' => '',
    'heure_evenement' => '',
    'lieu' => '',
    'categorie' => 'Soirée',
    'association' => '',
    'capacite_max' => 100,
    'prix' => '0.00',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    foreach ($valeurs as $champ => $defaut) {
        $valeurs[$champ] = trim($_POST[$champ] ?? (string) $defaut);
    }

    $categoriesAutorisees = ['Soirée', 'Sport', 'Culture', 'Conférence'];

    if ($valeurs['titre'] === '') $erreurs[] = 'Le titre est obligatoire.';
    if ($valeurs['date_evenement'] === '') $erreurs[] = 'La date est obligatoire.';
    if ($valeurs['heure_evenement'] === '') $erreurs[] = "L'heure est obligatoire.";
    if ($valeurs['lieu'] === '') $erreurs[] = 'Le lieu est obligatoire.';
    if ($valeurs['association'] === '') $erreurs[] = "L'association est obligatoire.";
    if (!in_array($valeurs['categorie'], $categoriesAutorisees, true)) $erreurs[] = 'Catégorie invalide.';
    if ((int) $valeurs['capacite_max'] <= 0) $erreurs[] = 'La capacité maximale doit être supérieure à 0.';
    if ((float) $valeurs['prix'] < 0) $erreurs[] = 'Le prix ne peut pas être négatif.';

    $affiche = null;
    if (empty($erreurs)) {
        try {
            $affiche = enregistrerAffiche($_FILES['affiche'] ?? []);
        } catch (Throwable $e) {
            $erreurs[] = $e->getMessage();
        }
    }

    if (empty($erreurs)) {
        $stmt = $pdo->prepare("INSERT INTO evenements (titre, description, date_evenement, heure_evenement, lieu, categorie, association, capacite_max, prix, affiche, organisateur_id, statut)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'publié')");
        $stmt->execute([
            $valeurs['titre'],
            $valeurs['description'],
            $valeurs['date_evenement'],
            $valeurs['heure_evenement'],
            $valeurs['lieu'],
            $valeurs['categorie'],
            $valeurs['association'],
            (int) $valeurs['capacite_max'],
            (float) $valeurs['prix'],
            $affiche,
            utilisateurId(),
        ]);

        messageFlash('succes', 'Événement créé avec succès.');
        rediriger('../organisateur/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un événement - OmnesEvent</title>
</head>
<body>
<?php require_once __DIR__ . '/../header/header.php'; ?>

<main>
    <h1>Créer un événement</h1>

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
        <input type="time" id="heure_evenement" name="heure_evenement" value="<?= h($valeurs['heure_evenement']) ?>" required>

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

        <label for="affiche">Affiche</label>
        <input type="file" id="affiche" name="affiche" accept="image/*">

        <button type="submit">Publier l'événement</button>
    </form>
</main>

<?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>