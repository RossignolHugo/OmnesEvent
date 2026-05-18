<?php
require_once __DIR__ . '/../BDD.php';
require_once __DIR__ . '/../header/header.php';

if (estConnecte()) {
    header('Location: ../calendrier/calendrier.php');
    exit;
}

$erreur = '';
$email = '';

//l’utilisateur a cliqué sur “Se connecter”, trim supprime espace et ?? evite erreur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $erreur = 'Remplissez tous les champs.';
    } else {
        //regarde bdd
        $req = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ? AND actif = 1 LIMIT 1');
        $req->execute([$email]);
        $user = $req->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['mot_de_passe'])) {
            $erreur = 'Email ou mot de passe incorrect.';

            //validation admine
        } elseif ($user['role'] === 'organisateur' && $user['valide'] == 0) {
            $erreur = 'Votre compte est en attente de validation par un administrateur.';

        } else {
            $_SESSION['id'] = $user['id'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['prenom'] = $user['prenom'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            header('Location: ../calendrier/calendrier.php');
            exit;
        }
    }
}
try {
    $host = 'fdb1031 .your - hosting . net ';
    $nom_base = '4470413 _test ';
    $utilisateur = '4470413 _test ';
    $mot_de_passe = 'votre mot de passe ... ';
    $bdd = new PDO(
        'mysql : host =' . $host . '; dbname =' . $nom_base . ';
charset = utf8 ',
        $utilisateur,
        $mot_de_passe,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>OmnesEvent-Connexion</title>
    <link rel="stylesheet" href="../header/header.css">
    <link rel="stylesheet" href="connexion.css">
</head>

<body>
    <main>
        <div class="form-carte">
            <h1>Connexion</h1>

            <?php if ($erreur): ?>
                <p class="erreur">Erreur <?= h($erreur) ?></p>
            <?php endif; ?>

            <?php if (isset($_GET['deconnecte'])): ?>
                <p class="succes"> Vous êtes déconnecté.</p>
            <?php endif; ?>

            <form method="POST">
                <label>Email</label>
                <input type="email" name="email" value="<?= h($email) ?>" placeholder="exemple@gmail.com" required>
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="Votre mot de passe" required>
                <button type="submit">Se Connecter</button>

            </form>


        <p class="lien">Pas de compte ? <a href="inscrire.php">S'inscrire</a></p>
    </div>
</main>

    <?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>

</html>