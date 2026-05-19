<?php
require_once __DIR__ . '/../includes/init.php';
verifierConnexion();

$userId = utilisateurId();
$eventId = (int) ($_GET['event_id'] ?? 0);
$erreur = '';

$stmt = $pdo->prepare('SELECT * FROM evenements WHERE id = ? AND prix > 0 AND statut = "publié" LIMIT 1');
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    messageFlash('erreur', 'Événement payant introuvable.');
    rediriger('billet.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    $numero = preg_replace('/\s+/', '', trim($_POST['numero'] ?? ''));
    $expiry = trim($_POST['expiry'] ?? '');
    $cvc = trim($_POST['cvc'] ?? '');

    if (!preg_match('/^\d{16}$/', $numero)) {
        $erreur = 'Numéro de carte invalide : 16 chiffres requis.';
    } elseif (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
        $erreur = 'Date d’expiration invalide : format MM/AA.';
    } elseif (!preg_match('/^\d{3}$/', $cvc)) {
        $erreur = 'CVC invalide : 3 chiffres requis.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT * FROM evenements WHERE id = ? AND statut = "publié" FOR UPDATE');
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();

            $stmt = $pdo->prepare('SELECT id FROM inscriptions WHERE utilisateur_id = ? AND evenement_id = ? AND statut <> "annulé" LIMIT 1');
            $stmt->execute([$userId, $eventId]);
            if ($stmt->fetch()) {
                throw new RuntimeException('Vous êtes déjà inscrit à cet événement.');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM inscriptions WHERE evenement_id = ? AND statut IN ("confirmé", "présent")');
            $stmt->execute([$eventId]);
            $nbInscrits = (int) $stmt->fetchColumn();

            if ($nbInscrits >= (int) $event['capacite_max']) {
                $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM file_attente WHERE evenement_id = ?');
                $stmt->execute([$eventId]);
                $position = (int) $stmt->fetchColumn();

                $insertAttente = $pdo->prepare('INSERT IGNORE INTO file_attente (utilisateur_id, evenement_id, position) VALUES (?, ?, ?)');
                $insertAttente->execute([$userId, $eventId, $position]);

                $pdo->commit();
                messageFlash('erreur', "L'événement est complet. Vous avez été placé en file d'attente.");
                rediriger('billet.php');
            }

            $code = genererCodeBillet();
            $insert = $pdo->prepare('
                INSERT INTO inscriptions (utilisateur_id, evenement_id, code_billet, statut, paiement_statut, montant_paye)
                VALUES (?, ?, ?, "confirmé", "payé", ?)
            ');
            $insert->execute([$userId, $eventId, $code, (float) $event['prix']]);

            $pdo->commit();
            messageFlash('succes', 'Paiement simulé validé. Votre billet est disponible.');
            rediriger('billet.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $erreur = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - OmnesEvent</title>
    <link rel="stylesheet" href="../connexion/connexion.css">
    <link rel="stylesheet" href="billet.css">
</head>
<body>
<?php require_once __DIR__ . '/../header/header.php'; ?>
<main>
    <div class="form-card">
        <h1>Paiement simulé</h1>
        <p><strong><?= h($event['titre']) ?></strong></p>
        <p><?= h($event['date_evenement']) ?> à <?= h(substr($event['heure_evenement'], 0, 5)) ?> — <?= h($event['lieu']) ?></p>
        <p>Total : <strong><?= number_format((float) $event['prix'], 2, ',', ' ') ?> €</strong></p>

        <?php if ($erreur): ?>
            <p class="erreur">⚠️ <?= h($erreur) ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

            <label>Numéro de carte</label>
            <input type="text" name="numero" maxlength="19" placeholder="1234 5678 9012 3456" required>

            <label>Expiration</label>
            <input type="text" name="expiry" maxlength="5" placeholder="MM/AA" required>

            <label>CVC</label>
            <input type="text" name="cvc" maxlength="3" placeholder="123" required>

            <button type="submit">Payer <?= number_format((float) $event['prix'], 2, ',', ' ') ?> €</button>
        </form>

        <p><a href="../index/index.php">Annuler</a></p>
    </div>
</main>
<?php require_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
