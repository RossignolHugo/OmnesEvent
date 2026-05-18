<?php
require_once __DIR__ . '/../header/header.php';
require_once __DIR__ . '/../BDD.php';

if (!estConnecte()) {
    header('Location: ../connexion/connexion.php');
    exit;
}

$user_id  = (int)$_SESSION['id'];
$event_id = (int)($_GET['event_id'] ?? 0);
$erreur   = '';

//petite fonction utilitaire des familles
function fetchOne($pdo, $sql, $params = []) {
    $req = $pdo->prepare($sql);
    $req->execute($params);
    return $req->fetch(PDO::FETCH_ASSOC);
}

//evenement payant ou pas?
$event = fetchOne($pdo, 'SELECT * FROM evenements WHERE id = ? AND prix > 0', [$event_id]);

if (!$event) {
    header('Location: billet.php');
    exit;
}

//Déja inscrit?
$deja = fetchOne($pdo,
    'SELECT id FROM inscriptions WHERE utilisateur_id = ? AND evenement_id = ? AND statut = "confirmé"',
    [$user_id, $event_id]
);

if ($deja) {
    header('Location: billet.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $numero = preg_replace('/\s/', '', trim($_POST['numero'] ?? ''));
    $expiry = trim($_POST['expiry'] ?? '');
    $cvc    = trim($_POST['cvc'] ?? '');

    // Validation
    if (!$numero || !$expiry || !$cvc) {
        $erreur = 'Remplissez tous les champs de paiement.';
    } elseif (!preg_match('/^\d{16}$/', $numero)) {
        $erreur = 'Numéro de carte invalide (16 chiffres requis).';
    } elseif (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
        $erreur = 'Date d\'expiration invalide (MM/AA).';
    } elseif (!preg_match('/^\d{3}$/', $cvc)) {
        $erreur = 'CVC invalide (3 chiffres requis).';
    } else {

        // paiement valide -> creation billet
        $code = 'TKT-' . date('Ymd') . "-$event_id-" . rand(1000, 9999);

        $pdo->prepare('
            INSERT INTO inscriptions (utilisateur_id, evenement_id, code_billet, statut, paiement_statut, montant_paye)
            VALUES (?, ?, ?, "confirmé", "payé", ?)
        ')->execute([$user_id, $event_id, $code, $event['prix']]);

        // enlever les place
        $pdo->prepare('UPDATE evenements SET capacite_max = capacite_max - 1 WHERE id = ?')
            ->execute([$event_id]);

        header('Location: billet.php?paye=1&code=' . urlencode($code));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paiement — OmnesEvent</title>

    <link rel="stylesheet" href="../header/header.css">
    <link rel="stylesheet" href="../connexion/connexion.css">
    <link rel="stylesheet" href="billet.css">
</head>

<body>
<main>
    <div class="form-card">
        <h1>💳 Paiement</h1>

        <div class="recap">
            <p><strong><?= h($event['titre']) ?></strong></p>
            <p>📅 <?= h($event['date_evenement']) ?> — 🕐 <?= h(substr($event['heure_evenement'], 0, 5)) ?></p>
            <p>📍 <?= h($event['lieu']) ?></p>
            <p class="total">Total : <strong><?= number_format($event['prix'], 2) ?> €</strong></p>
        </div>

        <?php if ($erreur): ?>
            <p class="erreur">⚠️ <?= h($erreur) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label>Numéro de carte</label>
            <input type="text" name="numero" placeholder="1234 5678 9012 3456" maxlength="19" required>

            <div class="form-row">
                <div>
                    <label>Expiration</label>
                    <input type="text" name="expiry" placeholder="MM/AA" maxlength="5" required>
                </div>
                <div>
                    <label>CVC</label>
                    <input type="text" name="cvc" placeholder="123" maxlength="3" required>
                </div>
            </div>

            <button type="submit">Payer <?= number_format($event['prix'], 2) ?> €</button>
        </form>

        <p class="lien"><a href="billet.php">← Annuler</a></p>
    </div>
</main>

<?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
