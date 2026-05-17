<?php require_once __DIR__ . '/../header/header.php'; ?>
<?php
require_once __DIR__ . '/../BDD.php';
require_once __DIR__ . '/../functions.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT e.*, u.prenom, u.nom,
COUNT(i.id) AS nb_inscrits
FROM evenements e
LEFT JOIN utilisateurs u ON u.id = e.organisateur_id
LEFT JOIN inscriptions i ON i.evenement_id = e.id AND i.statut <> 'annulé'
WHERE e.id = ?
GROUP BY e.id");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(404);
    exit('Événement introuvable.');
}

$mapsUrl    = 'https://www.google.com/maps/search/?q=' . urlencode($event['lieu']);
$embedUrl   = 'https://maps.google.com/maps?q=' . urlencode($event['lieu']) . '&output=embed';
$nbInscrits = (int) $event['nb_inscrits'];
$capacite   = (int) $event['capacite_max'];
$pct        = $capacite > 0 ? min(100, round($nbInscrits / $capacite * 100)) : 0;
$complet    = ($nbInscrits >= $capacite);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($event['titre']) ?> - OmnesEvent</title>
    <link rel="stylesheet" href="detail.css">

</head>
<body>

<main>

    <!-- ── En-tête de l'événement ── -->
    <div class="detail-header">
        <div class="detail-banner cat-<?= h(strtolower(str_replace(['é','è','ê','ë'], 'e', $event['categorie']))) ?>">
            <span><?php
                $emojis = ['Soirée'=>'🎉','Sport'=>'⚽','Culture'=>'🎬','Conférence'=>'🤖'];
                echo $emojis[$event['categorie']] ?? '📅';
            ?></span>
            <span class="detail-banner-tag"><?= h($event['categorie']) ?></span>
        </div>

        <div class="detail-body">
            <p class="detail-asso"><?= h($event['association']) ?></p>
            <h1 class="detail-title"><?= h($event['titre']) ?></h1>

            <!-- Méta-informations -->
            <div class="detail-meta-grid">
                <div class="meta-item">
                    <span class="meta-icon">📅</span>
                    <div>
                        <span class="meta-label">Date & heure</span>
                        <span class="meta-value">
                            <?= h(date('d/m/Y', strtotime($event['date_evenement']))) ?>
                            à <?= h(substr($event['heure_evenement'], 0, 5)) ?>
                        </span>
                    </div>
                </div>

                <!-- Lieu cliquable → Google Maps -->
                <div class="meta-item">
                    <span class="meta-icon">📍</span>
                    <a href="<?= h($mapsUrl) ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="meta-maps-link"
                       title="Voir sur Google Maps">
                        <span class="meta-label">Lieu</span>
                        <span class="meta-value"><?= h($event['lieu']) ?></span>
                        <span class="maps-badge">
                            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
                                <polyline points="15 3 21 3 21 9"/>
                                <line x1="10" y1="14" x2="21" y2="3"/>
                            </svg>
                            Ouvrir dans Google Maps
                        </span>
                    </a>
                </div>

                <div class="meta-item">
                    <span class="meta-icon">🎟️</span>
                    <div>
                        <span class="meta-label">Prix</span>
                        <span class="meta-value" style="color: <?= (float)$event['prix'] <= 0 ? '#28a745' : '#222' ?>">
                            <?= (float)$event['prix'] <= 0 ? 'Gratuit' : number_format((float)$event['prix'], 2, ',', ' ') . ' €' ?>
                        </span>
                    </div>
                </div>

                <div class="meta-item">
                    <span class="meta-icon">👤</span>
                    <div>
                        <span class="meta-label">Organisateur</span>
                        <span class="meta-value">
                            <?= h(trim(($event['prenom'] ?? '') . ' ' . ($event['nom'] ?? ''))) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Jauge de capacité -->
            <div class="detail-gauge">
                <div class="detail-gauge-label">
                    <span>Places disponibles</span>
                    <strong><?= $nbInscrits ?> / <?= $capacite ?></strong>
                </div>
                <div class="gauge-bar">
                    <div class="gauge-fill <?= $complet ? 'gauge-full' : '' ?>"
                         style="width: <?= $pct ?>%"></div>
                </div>
                <?php if ($complet): ?>
                    <span class="gauge-complet">⚠️ Événement complet — inscription en liste d'attente possible</span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <?php if (!empty($event['description'])): ?>
                <p class="detail-desc"><?= nl2br(h($event['description'])) ?></p>
            <?php endif; ?>

            <!-- Affiche -->
            <?php if (!empty($event['affiche'])): ?>
                <div class="detail-affiche">
                    <img src="../<?= h($event['affiche']) ?>" alt="Affiche — <?= h($event['titre']) ?>">
                </div>
            <?php endif; ?>

            <!-- Boutons d'action -->
            <div class="detail-actions">
                <?php if ($complet): ?>
                    <a href="../reservation/inscription.php?id=<?= (int)$event['id'] ?>"
                       class="btn-reserve-lg btn-wait">📋 File d'attente</a>
                <?php else: ?>
                    <a href="../reservation/inscription.php?id=<?= (int)$event['id'] ?>"
                       class="btn-reserve-lg">🎟️ Réserver ma place</a>
                <?php endif; ?>

                <!-- Bouton Google Maps -->
                <a href="<?= h($mapsUrl) ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn-maps-lg">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    Voir sur Google Maps
                </a>

                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'organisateur' || $_SESSION['role'] === 'admin')): ?>
                    <a href="modifier.php?id=<?= (int)$event['id'] ?>" class="btn-edit">✏️ Modifier</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Carte Google Maps intégrée ── -->
    <div class="maps-section">
        <div class="maps-section-header">
            <h3>
                📍 Localisation
                <span style="font-weight:400; color:#666; font-size:0.9rem;">
                    — <?= h($event['lieu']) ?>
                </span>
            </h3>
            <a href="<?= h($mapsUrl) ?>"
               target="_blank"
               rel="noopener noreferrer"
               class="maps-open-link">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
                    <polyline points="15 3 21 3 21 9"/>
                    <line x1="10" y1="14" x2="21" y2="3"/>
                </svg>
                Ouvrir dans Google Maps
            </a>
        </div>
        <div class="maps-iframe-wrap">
            <iframe
                src="<?= h($embedUrl) ?>"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Carte — <?= h($event['lieu']) ?>">
            </iframe>
        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../footer/footer.php'; ?>

<script>
    // Animation jauge
    document.addEventListener('DOMContentLoaded', function () {
        var fill = document.querySelector('.gauge-fill');
        if (fill) {
            var w = fill.style.width;
            fill.style.width = '0%';
            setTimeout(function () { fill.style.width = w; }, 150);
        }
    });
</script>
</body>
</html>