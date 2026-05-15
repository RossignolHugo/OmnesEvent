<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Calendrier</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="calendrier.css">
    <link rel="stylesheet" href="../header/header.css">
</head>
<?php require_once __DIR__ . '/../header/header.php'; ?>
<body>
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

<script src="calendrier.js"></script>
</body>
</html>
