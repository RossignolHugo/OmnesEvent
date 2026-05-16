<?php
require_once __DIR__ . '/../BDD.php';
require_once __DIR__ . '/../includes/functions.php';
verifierOrganisateurOuAdmin();

$flash = messageFlash();
$resultat = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();
    $code = trim($_POST['code_billet'] ?? '');

    if ($code === '') {
        messageFlash('erreur', 'Veuillez saisir un code billet.');
        rediriger('scanner.php');
    }

    $stmt = $pdo->prepare("SELECT i.id AS inscription_id, i.statut AS statut_inscription, i.code_billet,
    e.id AS evenement_id, e.titre, e.date_evenement, e.heure_evenement, e.organisateur_id,
    u.prenom, u.nom, u.email
    FROM inscriptions i
    INNER JOIN evenements e ON e.id = i.evenement_id
    INNER JOIN utilisateurs u ON u.id = i.utilisateur_id
    WHERE i.code_billet = ?");
    $stmt->execute([$code]);
    $resultat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resultat) {
        messageFlash('erreur', 'Billet introuvable.');
        rediriger('scanner.php');
    }

    if (!estAdmin() && (int) $resultat['organisateur_id'] !== utilisateurId()) {
        http_response_code(403);
        exit("Accès refusé : ce billet ne correspond pas à l'un de vos événements.");
    }

    if ($resultat['statut_inscription'] === 'annulé') {
        messageFlash('erreur', 'Ce billet a été annulé.');
        rediriger('scanner.php');
    }

    if ($resultat['statut_inscription'] !== 'présent') {
        $pdo->prepare("UPDATE inscriptions SET statut = 'présent' WHERE id = ?")->execute([(int) $resultat['inscription_id']]);
        $pdo->prepare('INSERT INTO presences (inscription_id, scanne_par) VALUES (?, ?)')->execute([(int) $resultat['inscription_id'], utilisateurId()]);
        messageFlash('succes', 'Présence validée pour ' . $resultat['prenom'] . ' ' . $resultat['nom'] . '.');
    } else {
        messageFlash('succes', 'Cette présence avait déjà été validée.');
    }

    rediriger('scanner.php');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation des présences - OmnesEvent</title>
</head>
<body>
<?php require_once __DIR__ . '/../header/header.php'; ?>

<main>
    <h1>Validation des présences</h1>
    <p>Saisir le code billet du participant pour valider sa présence.</p>

    <?php if ($flash): ?>
        <p><?= h($flash['message']) ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <label for="code_billet">Code billet</label>
        <input type="text" id="code_billet" name="code_billet" required>
        <button type="submit">Valider la présence</button>
    </form>

    <p><a href="dashboard.php">Retour au dashboard organisateur</a></p>
</main>

<?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>