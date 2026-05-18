<?php
require_once __DIR__ . '/../includes/init.php';
verifierOrganisateurOuAdmin();

$erreur = '';
$succes = '';
$f = [
    'titre' => '',
    'description' => '',
    'date_evenement' => '',
    'heure_evenement' => '',
    'lieu' => '',
    'categorie' => 'Soirée',
    'association' => '',
    'capacite_max' => '100',
    'prix' => '0.00',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    $f['titre'] = trim($_POST['titre'] ?? '');
    $f['description'] = trim($_POST['description'] ?? '');
    $f['date_evenement'] = trim($_POST['date_evenement'] ?? '');
    $f['heure_evenement'] = trim($_POST['heure_evenement'] ?? '');
    $f['lieu'] = trim($_POST['lieu'] ?? '');
    $f['categorie'] = $_POST['categorie'] ?? 'Soirée';
    $f['association'] = trim($_POST['association'] ?? '');
    $f['capacite_max'] = trim($_POST['capacite_max'] ?? '100');
    $f['prix'] = trim($_POST['prix'] ?? '0');

    $categories = ['Soirée', 'Sport', 'Culture', 'Conférence'];

    if ($f['titre'] === '' || $f['description'] === '' || $f['date_evenement'] === '' || $f['heure_evenement'] === '' || $f['lieu'] === '' || $f['association'] === '') {
        $erreur = 'Tous les champs obligatoires doivent être remplis.';
    } elseif (!in_array($f['categorie'], $categories, true)) {
        $erreur = 'Catégorie invalide.';
    } elseif ((int) $f['capacite_max'] <= 0) {
        $erreur = 'La capacité maximale doit être supérieure à 0.';
    } elseif ((float) $f['prix'] < 0) {
        $erreur = 'Le prix ne peut pas être négatif.';
    } else {
        try {
            $affiche = enregistrerAffiche($_FILES['affiche'] ?? []);

            $req = $pdo->prepare('
                INSERT INTO evenements
                (titre, description, date_evenement, heure_evenement, lieu, categorie, association, capacite_max, prix, affiche, organisateur_id, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "publié")
            ');
            $req->execute([
                $f['titre'],
                $f['description'],
                $f['date_evenement'],
                $f['heure_evenement'],
                $f['lieu'],
                $f['categorie'],
                $f['association'],
                (int) $f['capacite_max'],
                (float) $f['prix'],
                $affiche,
                utilisateurId(),
            ]);

            messageFlash('succes', 'Événement créé avec succès.');
            rediriger('../organisateur/dashboard.php');
        } catch (Throwable $e) {
            $erreur = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un événement - OmnesEvent</title>
    <link rel="stylesheet" href="../header/header.css">
    <link rel="stylesheet" href="evenement.css">
</head>
<body>
<?php require_once __DIR__ . '/../header/header.php'; ?>
<main>
    <h1>Créer un événement</h1>

    <?php if ($erreur): ?>
        <p class="erreur">⚠️ <?= h($erreur) ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

        <label>Titre</label>
        <input type="text" name="titre" value="<?= h($f['titre']) ?>" required>

        <label>Description</label>
        <textarea name="description" required><?= h($f['description']) ?></textarea>

        <label>Date</label>
        <input type="date" name="date_evenement" value="<?= h($f['date_evenement']) ?>" required>

        <label>Heure</label>
        <input type="time" name="heure_evenement" value="<?= h($f['heure_evenement']) ?>" required>

        <label>Lieu</label>
        <input type="text" name="lieu" value="<?= h($f['lieu']) ?>" required>

        <label>Catégorie</label>
        <select name="categorie" required>
            <?php foreach (['Soirée', 'Sport', 'Culture', 'Conférence'] as $categorie): ?>
                <option value="<?= h($categorie) ?>" <?= $f['categorie'] === $categorie ? 'selected' : '' ?>><?= h($categorie) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Association</label>
        <input type="text" name="association" value="<?= h($f['association']) ?>" required>

        <label>Capacité maximale</label>
        <input type="number" name="capacite_max" min="1" value="<?= h($f['capacite_max']) ?>" required>

        <label>Prix en euros</label>
        <input type="number" step="0.01" min="0" name="prix" value="<?= h($f['prix']) ?>" required>

        <label>Affiche</label>
        <input type="file" name="affiche" accept="image/png,image/jpeg,image/gif,image/webp">

        <button type="submit">Publier l'événement</button>
    </form>
</main>
<?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
