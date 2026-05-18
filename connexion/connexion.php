<?php
require_once __DIR__ . '/../includes/init.php';

if (estConnecte()) {
    rediriger('../index/index.php');
}

$erreur = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $erreur = 'Remplissez tous les champs.';
    } else {
        $req = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ? AND actif = 1 LIMIT 1');
        $req->execute([$email]);
        $user = $req->fetch();

        if (!$user || !password_verify($password, $user['mot_de_passe'])) {
            $erreur = 'Email ou mot de passe incorrect.';
        } elseif ($user['role'] === 'organisateur' && (int) $user['valide'] === 0) {
            $erreur = 'Votre compte organisateur est en attente de validation par un administrateur.';
        } else {
            connecterUtilisateur($user);
            rediriger('../index/index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - OmnesEvent</title>
    <link rel="stylesheet" href="../header/header.css">
    <link rel="stylesheet" href="connexion.css">
</head>
<body>
<?php require_once __DIR__ . '/../header/header.php'; ?>
<main>
    <div class="form-carte">
        <h1>Connexion</h1>

        <?php if ($erreur): ?>
            <p class="erreur">⚠️ <?= h($erreur) ?></p>
        <?php endif; ?>

        <?php if (isset($_GET['deconnecte'])): ?>
            <p class="succes">Vous êtes déconnecté.</p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

            <label>Email</label>
            <input type="email" name="email" value="<?= h($email) ?>" required>

            <label>Mot de passe</label>
            <input type="password" name="password" required>

            <button type="submit">Se connecter</button>
        </form>

        <p class="lien">Pas de compte ? <a href="inscrire.php">S'inscrire</a></p>
    </div>
</main>
<?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
