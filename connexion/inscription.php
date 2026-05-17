<?php 
require_once __DIR__ . '/../header/header.php';
require_once __DIR__ . '/../BDD.php';

if (estConnecte()) { header('Location: ../index.php'); exit; }

$erreur = '';
$succes = '';
$f = ['nom' => '', 'prenom' => '', 'email' => '', 'formation' => '', 'role' => 'participant'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f['nom']       = trim($_POST['nom']       ?? '');
    $f['prenom']    = trim($_POST['prenom']    ?? '');
    $f['email']     = trim($_POST['email']     ?? '');
    $f['formation'] = trim($_POST['formation'] ?? '');
    $f['role']      = $_POST['role']           ?? 'participant';
    $password       = $_POST['password']       ?? '';
    $password2      = $_POST['password2']      ?? '';

    // Rôles autorisé uniquement
    if (!in_array($f['role'], ['participant', 'organisateur'])) $f['role'] = 'participant';

    if (empty($f['nom']) || empty($f['prenom']) || empty($f['email']) || empty($password)) {
        $erreur = 'Remplissez tous les champs obligatoires.';
    } elseif (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Email invalide.';
    } elseif (strlen($password) < 8) {
        $erreur = 'Mot de passe trop court (8 caractères min.).';
    } elseif ($password !== $password2) {
        $erreur = 'Les mots de passe ne correspondent pas.';
    } else {
        // Vérifier existe déja pas dans bdd
        $chk = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ?');
        $chk->execute([$f['email']]);

        if ($chk->fetch()) {
            $erreur = 'Cet email est déjà utilisé.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // 1 si pas besoin de demande administrateur sinon 0
            $valide = ($f['role'] === 'participant') ? 1 : 0;

            // Insert les données bdd
            $ins = $pdo->prepare('
                INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, formation, actif, valide)
                VALUES (?, ?, ?, ?, ?, ?, 1, ?)
            ');
            $ins->execute([
                h($f['nom']),
                h($f['prenom']),
                $f['email'],
                $hash,
                $f['role'],
                h($f['formation']),
                $valide
            ]);

            if ($f['role'] === 'organisateur') {
                $succes = 'Compte créé ! En attente de validation par un administrateur.';
            } else {
                // Connexion auto apres inscription
                $_SESSION['id']     = (int)$pdo->lastInsertId();
                $_SESSION['nom']    = h($f['nom']);
                $_SESSION['prenom'] = h($f['prenom']);
                $_SESSION['email']  = $f['email'];
                $_SESSION['role']   = $f['role'];
                header('Location: ../index.php');
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription — OmnesEvent</title>
    <link rel="stylesheet" href="../header/header.css">
    <link rel="stylesheet" href="connexion.css">
</head>

<body>
<main>
    <div class="form-carte">
        <h1>Créer un compte</h1>

        <?php if ($erreur): ?>
            <p class="erreur">⚠️ <?= h($erreur) ?></p>
        <?php endif; ?>

        <?php if ($succes): ?>
            <p class="succes">✅ <?= h($succes) ?> <a href="connexion.php">Se connecter →</a></p>
        <?php else: ?>

        <form method="POST">

            <div class="form-colonne">
                <div>
                    <label>Prénom</label>
                    <input type="text" name="prenom" value="<?= h($f['prenom']) ?>" placeholder="Event" required>
                </div>
                <div>
                    <label>Nom</label>
                    <input type="text" name="nom" value="<?= h($f['nom']) ?>" placeholder="Omnes" required>
                </div>
            </div>

            <label>Email</label>
            <input type="email" name="email" value="<?= h($f['email']) ?>" placeholder="exemple@gmail.com" required>

            <label>Formation</label>
            <input type="text" name="formation" value="<?= h($f['formation']) ?>" placeholder="ex: ING2">

            <label>Je suis…</label>
            <select name="role">
                <option value="participant"   <?= $f['role']==='participant'   ? 'selected':'' ?>>Participant (étudiant)</option>
                <option value="organisateur"  <?= $f['role']==='organisateur'  ? 'selected':'' ?>>Organisateur (validation admin requise)</option>
            </select>

            <label>Mot de passe<petit>(8 min.)</petit></label>
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
