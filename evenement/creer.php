<?php

session_start();
require_once "../BDD.php";



if (isset($_POST["ajouter"])) {

    $req = $pdo->prepare(
        "INSERT INTO evenements
        (
            titre,
            description,
            date_evenement,
            heure_evenement,
            lieu,
            categorie,
            association,
            capacite_max,
            prix,
            organisateur_id
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $req->execute([
        $_POST["titre"],
        $_POST["description"],
        $_POST["date_evenement"],
        $_POST["heure_evenement"],
        $_POST["lieu"],
        $_POST["categorie"],
        $_POST["association"],
        $_POST["capacite_max"],
        $_POST["prix"],
    ]);
}

if (isset($_GET["supprimer"])) {

    $req = $pdo->prepare(
        "DELETE FROM evenements WHERE id = ?"
    );

    $req->execute([
        $_GET["supprimer"]
    ]);
}

$events = $pdo->query("SELECT * FROM evenements ORDER BY id DESC")
              ->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Événements</title>
</head>

<body>

<h1>Panneau Admin - Événements</h1>

<hr>

<h2>Ajouter un événement</h2>

<form method="POST">

    <input type="text" name="titre" placeholder="Titre" required>
    <br><br>

    <textarea name="description" placeholder="Description" required></textarea>
    <br><br>

    <input type="date" name="date_evenement" required>
    <br><br>

    <input type="time" name="heure_evenement" required>
    <br><br>

    <input type="text" name="lieu" placeholder="Lieu" required>
    <br><br>

    <select name="categorie" required>
        <option value="Soirée">Soirée</option>
        <option value="Sport">Sport</option>
        <option value="Culture">Culture</option>
        <option value="Conférence">Conférence</option>
    </select>

    <br><br>

    <input type="text" name="association" placeholder="Association" required>
    <br><br>

    <input type="number" name="capacite_max" placeholder="Capacité max" required>
    <br><br>

    <input type="number" step="0.01" name="prix" placeholder="Prix" required>
    <br><br>

    <button type="submit" name="ajouter">
        Ajouter l'événement
    </button>

</form>

<hr>

<h2>Liste des événements</h2>

<?php if (count($events) == 0): ?>
    <p>Aucun événement.</p>
<?php endif; ?>

<?php foreach ($events as $event): ?>

<div style="border:1px solid black; padding:10px; margin:10px;">

    <h3><?= htmlspecialchars($event["titre"]) ?></h3>

    <p><?= htmlspecialchars($event["description"]) ?></p>

    <p> <?= $event["date_evenement"] ?> à <?= $event["heure_evenement"] ?></p>

    <p> <?= htmlspecialchars($event["lieu"]) ?></p>

    <p> <?= $event["categorie"] ?></p>

    <p> <?= htmlspecialchars($event["association"]) ?></p>

    <p><?= $event["capacite_max"] ?> places</p>

    <p><?= $event["prix"] ?> €</p>

    <a href="?supprimer=<?= $event["id"] ?>">
        <button>Supprimer</button>
    </a>

</div>

<?php endforeach; ?>

</body>
</html>
