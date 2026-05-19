<?php
require_once __DIR__ . '/../includes/init.php';
verifierConnexion();

$userId = utilisateurId();

// Récupération de l'id événement
$eventId = (int) (
    $_GET['id']
    ?? $_POST['id']
    ?? $_GET['event_id']
    ?? $_POST['event_id']
    ?? 0
);

if ($eventId <= 0) {
    messageFlash('erreur', 'Événement invalide.');
    rediriger('../index/index.php');
}

try {
    $pdo->beginTransaction();

    // Vérifier que l'événement existe et est publié
    $stmt = $pdo->prepare('SELECT * FROM evenements WHERE id = ? AND statut = "publié" FOR UPDATE');
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    if (!$event) {
        throw new RuntimeException('Événement introuvable ou indisponible.');
    }

    // Vérifier si une inscription NON annulée existe déjà
    $stmt = $pdo->prepare('
        SELECT id 
        FROM inscriptions 
        WHERE utilisateur_id = ? 
          AND evenement_id = ? 
          AND statut <> "annulé"
        LIMIT 1
    ');
    $stmt->execute([$userId, $eventId]);
    if ($stmt->fetch()) {
        throw new RuntimeException('Vous avez déjà un billet pour cet événement.');
    }

    $stmt = $pdo->prepare('
        SELECT id 
        FROM inscriptions 
        WHERE utilisateur_id = ? 
          AND evenement_id = ? 
          AND statut = "annulé"
        LIMIT 1
    ');
    $stmt->execute([$userId, $eventId]);
    $annule = $stmt->fetch();

    // Vérifier si l'utilisateur est déjà en file d'attente
    $stmt = $pdo->prepare('SELECT id FROM file_attente WHERE utilisateur_id = ? AND evenement_id = ? LIMIT 1');
    $stmt->execute([$userId, $eventId]);
    if ($stmt->fetch()) {
        throw new RuntimeException('Vous êtes déjà en file d’attente pour cet événement.');
    }

    // Vérifier la capacité
    $stmt = $pdo->prepare('
        SELECT COUNT(*) 
        FROM inscriptions 
        WHERE evenement_id = ? 
          AND statut IN ("confirmé", "présent")
    ');
    $stmt->execute([$eventId]);
    $nbInscrits = (int) $stmt->fetchColumn();

    // Si complet, file d'attente
    if ($nbInscrits >= (int) $event['capacite_max']) {

        $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM file_attente WHERE evenement_id = ?');
        $stmt->execute([$eventId]);
        $position = (int) $stmt->fetchColumn();

        $insert = $pdo->prepare('INSERT INTO file_attente (utilisateur_id, evenement_id, position) VALUES (?, ?, ?)');
        $insert->execute([$userId, $eventId, $position]);

        $pdo->commit();
        messageFlash('succes', "L'événement est complet. Vous êtes placé en file d'attente, position {$position}.");
        rediriger('../billet/billet.php');
    }

    // Si événement payant, redirection paiement
    if ((float) $event['prix'] > 0) {
        $pdo->commit();
        rediriger('../billet/paiement.php?event_id=' . $eventId);
    }

    // Génération du code billet
    $code = genererCodeBillet();

    // Si inscription annulée existe, reactivé
    if ($annule) {
        $update = $pdo->prepare('
            UPDATE inscriptions 
            SET statut = "confirmé",
                code_billet = ?,
                paiement_statut = "gratuit",
                montant_paye = 0
            WHERE id = ?
        ');
        $update->execute([$code, $annule['id']]);

    } else {
        $insert = $pdo->prepare('
            INSERT INTO inscriptions 
                (utilisateur_id, evenement_id, code_billet, statut, paiement_statut, montant_paye)
            VALUES (?, ?, ?, "confirmé", "gratuit", 0)
        ');
        $insert->execute([$userId, $eventId, $code]);
    }

    $pdo->commit();
    messageFlash('succes', 'Inscription confirmée. Votre billet est disponible.');
    rediriger('../billet/billet.php');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    messageFlash('erreur', $e->getMessage());
    rediriger('../index/index.php');
}
