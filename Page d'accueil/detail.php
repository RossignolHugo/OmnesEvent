<?php
require_once __DIR__ . '/../BDD.php';
require_once __DIR__ . '/../includes/functions.php';

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
    <link rel="stylesheet" href="../evenement/evenement.css">
    <style>
        /* ── Styles spécifiques à la page détail ── */
        main { width: 92%; max-width: 900px; margin: 40px auto; }

        .detail-header {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }

        .detail-banner {
            height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
            position: relative;
        }
        .cat-soiree    { background: linear-gradient(135deg, #1a1a3e, #4a2080); }
        .cat-sport     { background: linear-gradient(135deg, #0a3020, #1a7a45); }
        .cat-culture   { background: linear-gradient(135deg, #3a2000, #a05a00); }
        .cat-conference{ background: linear-gradient(135deg, #0a1a4a, #003399); }

        .detail-banner-tag {
            position: absolute;
            top: 16px; left: 16px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(4px);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 5px 12px;
            border-radius: 50px;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .detail-body { padding: 28px 32px; }

        .detail-asso {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #007bff;
            font-weight: 700;
            margin: 0 0 8px;
        }

        .detail-title {
            font-size: 2rem;
            font-weight: 800;
            color: #222;
            margin: 0 0 20px;
            line-height: 1.2;
        }

        .detail-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        .meta-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #f8f8f8;
            border: 1px solid #ebebeb;
            border-radius: 10px;
            padding: 14px 16px;
        }

        .meta-icon { font-size: 1.3rem; flex-shrink: 0; line-height: 1; margin-top: 1px; }

        .meta-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #999;
            font-weight: 700;
            display: block;
            margin-bottom: 3px;
        }

        .meta-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #222;
        }

        /* Lieu cliquable */
        .meta-maps-link {
            color: #222;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            transition: 0.2s ease;
            width: 100%;
        }

        .meta-maps-link:hover .meta-value {
            color: #007bff;
        }

        .maps-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.7rem;
            color: #007bff;
            font-weight: 600;
            margin-top: 4px;
        }

        .maps-badge svg { flex-shrink: 0; }

        /* Jauge */
        .detail-gauge { margin-bottom: 24px; }
        .detail-gauge-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 6px;
        }
        .detail-gauge-label strong { color: #222; }
        .gauge-bar { height: 8px; background: #ececec; border-radius: 50px; overflow: hidden; }
        .gauge-fill { height: 100%; border-radius: 50px; transition: width 0.8s ease;
            background: linear-gradient(90deg, #0069d9, #007bff); }
        .gauge-fill.gauge-full { background: linear-gradient(90deg, #c0392b, #dc3545); }
        .gauge-complet { color: #dc3545; font-weight: 600; font-size: 0.85rem; margin-top: 4px; display: block; }

        /* Description */
        .detail-desc {
            font-size: 1rem;
            color: #444;
            line-height: 1.75;
            margin: 0 0 28px;
        }

        /* Boutons action */
        .detail-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
        }

        .btn-reserve-lg {
            display: inline-block;
            background: #007bff;
            color: #fff;
            padding: 13px 28px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            transition: 0.2s ease;
        }
        .btn-reserve-lg:hover { background: #0069d9; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,123,255,0.35); }

        .btn-reserve-lg.btn-wait {
            background: #f0f0f0;
            color: #666;
            border: 1px solid #ddd;
        }
        .btn-reserve-lg.btn-wait:hover { background: #e5e5e5; color: #333; }

        .btn-maps-lg {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 2px solid #007bff;
            color: #007bff;
            padding: 11px 22px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            transition: 0.2s ease;
        }
        .btn-maps-lg:hover { background: #007bff; color: #fff; }

        .btn-edit {
            display: inline-block;
            border: 1px solid #ccc;
            color: #666;
            padding: 11px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: 0.2s ease;
        }
        .btn-edit:hover { border-color: #888; color: #333; }

        /* Affiche */
        .detail-affiche {
            margin-bottom: 24px;
        }
        .detail-affiche img {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #e5e5e5;
        }

        /* ── Carte Google Maps intégrée ── */
        .maps-section {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }

        .maps-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid #e5e5e5;
        }

        .maps-section-header h3 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .maps-open-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #007bff;
            transition: 0.2s ease;
        }
        .maps-open-link:hover { color: #0069d9; text-decoration: underline; }

        .maps-iframe-wrap { position: relative; width: 100%; height: 320px; }
        .maps-iframe-wrap iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }

        @media (max-width: 640px) {
            .detail-body { padding: 20px 18px; }
            .detail-meta-grid { grid-template-columns: 1fr; }
            .detail-title { font-size: 1.5rem; }
            main { margin: 20px auto; }
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../header/header.php'; ?>

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
                    <a href="../reservation/inscrire.php?id=<?= (int)$event['id'] ?>"
                       class="btn-reserve-lg btn-wait">📋 File d'attente</a>
                <?php else: ?>
                    <a href="../reservation/inscrire.php?id=<?= (int)$event['id'] ?>"
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