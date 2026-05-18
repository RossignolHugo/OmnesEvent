<?php
require_once __DIR__ . '/../includes/init.php';
verifierConnexion();

$userId = utilisateurId();
$flash = messageFlash();
$erreur = '';

$stmt = $pdo->prepare('SELECT id, nom, prenom, email, role, formation, actif, valide, crée_le FROM utilisateurs WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    rediriger('../connexion/connexion.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'modifier_profil') {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $formation = trim($_POST['formation'] ?? '');

        if ($nom === '' || $prenom === '' || $email === '') {
            $erreur = 'Nom, prénom et email sont obligatoires.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = 'Email invalide.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? AND id <> ? LIMIT 1');
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $erreur = 'Cet email est déjà utilisé par un autre compte.';
            } else {
                $stmt = $pdo->prepare('UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, formation = ? WHERE id = ?');
                $stmt->execute([$nom, $prenom, $email, $formation, $userId]);

                $_SESSION['nom'] = $nom;
                $_SESSION['prenom'] = $prenom;
                $_SESSION['email'] = $email;
                messageFlash('succes', 'Profil mis à jour.');
                rediriger('profil.php');
            }
        }
    }

    if ($action === 'modifier_mot_de_passe') {
        $ancien = $_POST['ancien_password'] ?? '';
        $nouveau = $_POST['nouveau_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id = ?');
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($ancien, $hash)) {
            $erreur = 'Ancien mot de passe incorrect.';
        } elseif (strlen($nouveau) < 8) {
            $erreur = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        } elseif ($nouveau !== $confirm) {
            $erreur = 'Les nouveaux mots de passe ne correspondent pas.';
        } else {
            $newHash = password_hash($nouveau, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?');
            $stmt->execute([$newHash, $userId]);
            messageFlash('succes', 'Mot de passe modifié.');
            rediriger('profil.php');
        }
    }

    if ($action === 'desactiver_compte') {
        $stmt = $pdo->prepare('UPDATE utilisateurs SET actif = 0 WHERE id = ?');
        $stmt->execute([$userId]);
        session_destroy();
        rediriger('../index/index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil - OmnesEvent</title>
    <link rel="stylesheet" href="../header/header.css">
</head>
<body>
<?php require_once __DIR__ . '/../header/header.php'; ?>
<main>
    <h1>Mon profil</h1>

    <?php if ($flash): ?>
        <p class="<?= h($flash['type']) ?>"><?= h($flash['message']) ?></p>
    <?php endif; ?>

    <?php if ($erreur): ?>
        <p class="erreur">⚠️ <?= h($erreur) ?></p>
    <?php endif; ?>

    <section>
        <h2>Informations personnelles</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="action" value="modifier_profil">

            <label>Prénom</label>
            <input type="text" name="prenom" value="<?= h($user['prenom']) ?>" required>

            <label>Nom</label>
            <input type="text" name="nom" value="<?= h($user['nom']) ?>" required>

            <label>Email</label>
            <input type="email" name="email" value="<?= h($user['email']) ?>" required>

            <label>Formation</label>
            <input type="text" name="formation" value="<?= h($user['formation']) ?>">

            <button type="submit">Enregistrer</button>
        </form>
    </section>

    <section>
        <h2>Mot de passe</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="action" value="modifier_mot_de_passe">

            <label>Ancien mot de passe</label>
            <input type="password" name="ancien_password" required>

            <label>Nouveau mot de passe</label>
            <input type="password" name="nouveau_password" required>

            <label>Confirmer</label>
            <input type="password" name="confirm_password" required>

            <button type="submit">Modifier le mot de passe</button>
        </form>
    </section>

    <section>
        <h2>Compte</h2>
        <p>Rôle : <?= h($user['role']) ?></p>
        <form method="post" onsubmit="return confirm('Désactiver votre compte ?');">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="action" value="desactiver_compte">
            <button type="submit">Désactiver mon compte</button>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
