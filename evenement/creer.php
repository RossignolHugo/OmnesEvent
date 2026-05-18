<?php

session_start();
require_once "../BDD.php";
// on veyt savoir si l'utilisateur est connecter 
$req = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
$req->execute([$_SESSION["email"]]);
$user = $req->fetch(PDO::FETCH_ASSOC);

// SUPPRESSION DIRECTE
if (isset($_POST["supprimer"])) {

    // supprimer utilisateur
    $req = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
    $req->execute([$user["id"]]);

    session_destroy();

    header("Location: ../index/index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil</title>
</head>
<body>

<h1>Mon profil</h1>

<form method="POST">
    <button type="submit" name="supprimer">
        Supprimer mon compte
    </button>
</form>

</body>
</html>
