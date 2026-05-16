<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Calendrier</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="calendrier.css">
</head>



<?php
 require_once __DIR__ . '/../header/header.php';

// Récupération des infos utiles uniquement
$sql = "SELECT 
            id,
            titre,
            description,
            date_evenement,
            heure_evenement,
            lieu,
            categorie,
            association,
            capacite_max,
            prix
        FROM evenements
        ORDER BY date_evenement ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<body>
    <section id="calendrier">
        <h2 class="h2">OmnesEvent-Calendrier</h2>
        <a class="a">Toutes vos dates d’événement réunies</a>

        <div class="calendrier-conteneur">
            <div class="calendrier-entete">
                <button id="moisPrecedent">◀</button>
                <h2 id="moisAnnee"></h2>
                <button id="moisSuivant">▶</button>
            </div>

            <div class="calendrier-grille">
                <div class="nom-jour">Lun</div>
                <div class="nom-jour">Mar</div>
                <div class="nom-jour">Mer</div>
                <div class="nom-jour">Jeu</div>
                <div class="nom-jour">Ven</div>
                <div class="nom-jour">Sam</div>
                <div class="nom-jour">Dim</div>

                <div id="joursCalendrier"></div>
            </div>
        </div>

        <!-- Zone d'affichage des détails -->
        <div id="detailsEvenement" class="details-evenement"></div>

        <!-- envoi evenement a js -->
        <script>
            const EVENEMENTS = <?= json_encode($evenements) ?>;
        </script>

        <script src="calendrier.js"></script>
    </section>

    <?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
