<?php
require_once __DIR__ . '/../includes/init.php';

if (estConnecte()) {
    rediriger('../index/index.php');
}

$erreur = '';
$succes = '';
$f = [
    'nom' => '',
    'prenom' => '',
    'email' => '',
    'formation' => '',
    'role' => 'participant',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    $f['nom'] = trim($_POST['nom'] ?? '');
    $f['prenom'] = trim($_POST['prenom'] ?? '');
    $f['email'] = trim($_POST['email'] ?? '');
    $f['formation'] = trim($_POST['formation'] ?? '');
    $f['role'] = $_POST['role'] ?? 'participant';
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!in_array($f['role'], ['participant', 'organisateur'], true)) {
        $f['role'] = 'participant';
    }

    if ($f['nom'] === '' || $f['prenom'] === '' || $f['email'] === '' || $password === '') {
        $erreur = 'Remplissez tous les champs obligatoires.';
    } elseif (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Email invalide.';
    } elseif (strlen($password) < 8) {
        $erreur = 'Mot de passe trop court : 8 caractères minimum.';
    } elseif ($password !== $password2) {
        $erreur = 'Les mots de passe ne correspondent pas.';
    } else {
        $chk = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? LIMIT 1');
        $chk->execute([$f['email']]);

        if ($chk->fetch()) {
            $erreur = 'Cet email est déjà utilisé.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $valide = ($f['role'] === 'participant') ? 1 : 0;

            $ins = $pdo->prepare('
                INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, formation, actif, valide)
                VALUES (?, ?, ?, ?, ?, ?, 1, ?)
            ');
            $ins->execute([
                $f['nom'],
                $f['prenom'],
                $f['email'],
                $hash,
                $f['role'],
                $f['formation'],
                $valide,
            ]);

            if ($f['role'] === 'organisateur') {
                $succes = 'Compte créé. Il doit maintenant être validé par un administrateur.';
            } else {
                connecterUtilisateur([
                    'id' => (int) $pdo->lastInsertId(),
                    'nom' => $f['nom'],
                    'prenom' => $f['prenom'],
                    'email' => $f['email'],
                    'role' => $f['role'],
                ]);
                rediriger('../index/index.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - OmnesEvent</title>
    <link rel="stylesheet" href="../header/header.css">
    <link rel="stylesheet" href="connexion.css">
</head>
<body>
<?php require_once __DIR__ . '/../header/header.php'; ?>
<main>
    <div class="form-carte">
        <h1>Créer un compte</h1>

        <?php if ($erreur): ?>
            <p class="erreur">⚠️ <?= h($erreur) ?></p>
        <?php endif; ?>

        <?php if ($succes): ?>
            <p class="succes">✅ <?= h($succes) ?> <a href="connexion.php">Se connecter</a></p>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

                <label>Prénom</label>
                <input type="text" name="prenom" value="<?= h($f['prenom']) ?>" required>

                <label>Nom</label>
                <input type="text" name="nom" value="<?= h($f['nom']) ?>" required>

                <label>Email</label>
                <input type="email" name="email" value="<?= h($f['email']) ?>" required>

                <label>Formation</label>
                <input type="text" name="formation" value="<?= h($f['formation']) ?>" placeholder="ex : ING2">

                <label>Type de compte</label>
                <select name="role">
                    <option value="participant" <?= $f['role'] === 'participant' ? 'selected' : '' ?>>Participant</option>
                    <option value="organisateur" <?= $f['role'] === 'organisateur' ? 'selected' : '' ?>>Organisateur, validation admin requise</option>
                </select>

                <label>Mot de passe</label>
                <input type="password" name="password" required>

                <label>Confirmer le mot de passe</label>
                <input type="password" name="password2" required>

                <button type="submit">Créer mon compte</button>
            </form>
        <?php endif; ?>

        <p class="lien">Déjà inscrit ? <a href="connexion.php">Se connecter</a></p>
    </div>
</main>
<?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
