<?php
require_once __DIR__ . '/../BDD.php';
require_once __DIR__ . '/../header/header.php';

// ── Récupération des filtres GET ──────────────────────────────────────────────
$q         = trim($_GET['q']        ?? '');
$categorie = trim($_GET['categorie'] ?? '');
$date      = trim($_GET['date']      ?? '');
$asso      = trim($_GET['asso']      ?? '');

// ── Requête principale : événements à venir (statut publié) ───────────────────
$sql    = "SELECT e.*, COUNT(i.id) AS nb_inscrits
           FROM evenements e
           LEFT JOIN inscriptions i ON i.evenement_id = e.id AND i.statut <> 'annulé'
           WHERE e.statut = 'publié'
             AND e.date_evenement >= CURDATE()";
$params = [];

if ($q !== '') {
    $sql .= " AND (e.titre LIKE ? OR e.description LIKE ? OR e.association LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($categorie !== '') {
    $sql .= " AND e.categorie = ?";
    $params[] = $categorie;
}
if ($date !== '') {
    $sql .= " AND e.date_evenement = ?";
    $params[] = $date;
}
if ($asso !== '') {
    $sql .= " AND e.association LIKE ?";
    $params[] = '%' . $asso . '%';
}

$sql .= " GROUP BY e.id ORDER BY e.date_evenement ASC, e.heure_evenement ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Stats globales ─────────────────────────────────────────────────────────────
$nbEvents       = (int) $pdo->query("SELECT COUNT(*) FROM evenements WHERE statut = 'publié'")->fetchColumn();
$nbInscrits     = (int) $pdo->query("SELECT COUNT(DISTINCT utilisateur_id) FROM inscriptions WHERE statut <> 'annulé'")->fetchColumn();
$nbAssos        = (int) $pdo->query("SELECT COUNT(DISTINCT association) FROM evenements WHERE statut = 'publié'")->fetchColumn();

// ── Associations distinctes pour le filtre ─────────────────────────────────────
$assosList = $pdo->query("SELECT DISTINCT association FROM evenements WHERE statut = 'publié' ORDER BY association ASC")
                 ->fetchAll(PDO::FETCH_COLUMN);

// ── Helpers ───────────────────────────────────────────────────────────────────
function p(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function categorieSlug(string $cat): string {
    $map = ['Soirée' => 'soiree', 'Sport' => 'sport', 'Culture' => 'culture', 'Conférence' => 'conference'];
    return $map[$cat] ?? strtolower($cat);
}

function categorieTag(string $cat): string {
    $classes = ['Soirée' => 'tag-soiree', 'Sport' => 'tag-sport', 'Culture' => 'tag-culture', 'Conférence' => 'tag-conf'];
    $class = $classes[$cat] ?? 'tag-conf';
    return '<span class="tag ' . $class . '">' . p($cat) . '</span>';
}

function categorieEmoji(string $cat): string {
    $map = ['Soirée' => '🎉', 'Sport' => '⚽', 'Culture' => '🎬', 'Conférence' => '🤖'];
    return $map[$cat] ?? '📅';
}

function prixAffiche(float $prix): string {
    if ($prix <= 0) return '<span class="event-price free">Gratuit</span>';
    return '<span class="event-price">' . number_format($prix, 2, ',', ' ') . ' €</span>';
}

function lieuAffiche(string $lieu): string {
    $lieu = trim($lieu);

    // Lien Google Maps
    if (str_contains($lieu, 'google.com/maps') || str_contains($lieu, 'maps.app.goo.gl')) {
        return '<a href="' . p($lieu) . '" target="_blank" class="btn-map">📍 Ouvrir Google Maps</a>';
    }

    // Adresse classique
    return '📍 ' . p($lieu);
}


$moisFr = ['01'=>'Jan','02'=>'Fév','03'=>'Mar','04'=>'Avr','05'=>'Mai','06'=>'Juin',
           '07'=>'Juil','08'=>'Août','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Déc'];


?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>OmnesEvent — Plateforme Événementielle</title>
  <link rel="stylesheet" href="index.css" />
</head>
<body>

<!-- ──────────────── HERO ──────────────── -->
<section class="hero">
  <div class="hero-content">
    <span class="hero-badge">✦ Plateforme officielle Omnes</span>
    <h1 class="hero-title">
      Tous les events,<br />
      <span class="hero-accent">en un seul endroit.</span>
    </h1>
    <p class="hero-sub">
      Soirées BDE, tournois sportifs, conférences, culture…<br />
      Découvre, réserve et profite sans te perdre dans tes mails.
    </p>
    <div class="hero-actions">
      <a href="#events" class="btn-primary">Explorer les événements</a>
    </div>
    <div class="hero-stats">
      <div class="stat">
        <strong><?= $nbEvents ?></strong>
        <span>Événements publiés</span>
      </div>
      <div class="stat-sep"></div>
      <div class="stat">
        <strong><?= $nbInscrits ?></strong>
        <span>Étudiants inscrits</span>
      </div>
      <div class="stat-sep"></div>
      <div class="stat">
        <strong><?= $nbAssos ?></strong>
        <span>Associations</span>
      </div>
    </div>
  </div>
  <div class="hero-illustration" aria-hidden="true">
    <div class="hero-card-float">
      <span class="hero-card-emoji">🎉</span>
      <p class="hero-card-label">Prochain event</p>
      <?php if (!empty($evenements)): $next = $evenements[0]; ?>
        <p class="hero-card-title"><?= h($next['titre']) ?></p>
        <p class="hero-card-date">
          📅 <?= p(date('d/m/Y', strtotime($next['date_evenement']))) ?>
          · <?= p(substr($next['heure_evenement'], 0, 5)) ?>
        </p>
      <?php else: ?>
        <p class="hero-card-title">Aucun événement à venir</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ──────────────── BARRE DE RECHERCHE ──────────────── -->
<section class="search-section" id="search">
  <div class="page-container">
    <form class="search-form" method="get" action="">
      <div class="search-field">
        <label for="q">Mot-clé</label>
        <input type="text" id="q" name="q" placeholder="Ex : soirée, futsal, conf…"
               value="<?= h($q) ?>" />
      </div>
      <div class="search-divider"></div>
      <div class="search-field">
        <label for="categorie">Catégorie</label>
        <select id="categorie" name="categorie">
          <option value="">Toutes</option>
          <?php foreach (['Soirée','Sport','Culture','Conférence'] as $cat): ?>
            <option value="<?= p($cat) ?>" <?= $categorie === $cat ? 'selected' : '' ?>>
              <?= p($cat) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="search-divider"></div>
      <div class="search-field">
        <label for="date">Date</label>
        <input type="date" id="date" name="date" value="<?= h($date) ?>" />
      </div>
      <div class="search-divider"></div>
      <div class="search-field">
        <label for="asso">Association</label>
        <select id="asso" name="asso">
          <option value="">Toutes</option>
          <?php foreach ($assosList as $a): ?>
            <option value="<?= p($a) ?>" <?= $asso === $a ? 'selected' : '' ?>>
              <?= p($a) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn-search">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
        </svg>
        Rechercher
      </button>
      <?php if ($q || $categorie || $date || $asso): ?>
        <a href="page_accueil.php" class="btn-reset">✕ Réinitialiser</a>
      <?php endif; ?>
    </form>
  </div>
</section>

<!-- ──────────────── ÉVÉNEMENTS ──────────────── -->
<section class="events-section" id="events">
  <div class="page-container">

    <div class="section-header">
      <div>
        <span class="section-label">✦ Au programme</span>
        <h2 class="section-title">Événements à venir</h2>
      </div>
      <!-- Filtres JS côté client (si pas de filtre serveur actif) -->
      <?php if (!$categorie): ?>
      <div class="filter-tabs" id="filterTabs">
        <button class="filter-tab active" data-filter="all">Tous</button>
        <button class="filter-tab" data-filter="soiree">Soirée</button>
        <button class="filter-tab" data-filter="sport">Sport</button>
        <button class="filter-tab" data-filter="culture">Culture</button>
        <button class="filter-tab" data-filter="conference">Conférence</button>
      </div>
      <?php endif; ?>
    </div>

    <?php if (empty($evenements)): ?>
      <div class="no-events">
        <span class="no-events-icon">🔍</span>
        <p>Aucun événement trouvé pour ces critères.</p>
        <a href="index.php" class="btn-outline">Voir tous les événements</a>
      </div>
    <?php else: ?>
    <div class="events-grid" id="eventsGrid">
      <?php foreach ($evenements as $ev):
        $slug       = categorieSlug($ev['categorie']);
        $nbInscrits = (int) $ev['nb_inscrits'];
        $capacite   = (int) $ev['capacite_max'];
        $pct        = $capacite > 0 ? min(100, round($nbInscrits / $capacite * 100)) : 0;
        $complet    = ($nbInscrits >= $capacite);
        $dateObj    = date_create($ev['date_evenement']);
        $jour       = date_format($dateObj, 'd');
        $moisNum    = date_format($dateObj, 'm');
        $moisLabel  = $moisFr[$moisNum] ?? date_format($dateObj, 'M');
        $emoji      = categorieEmoji($ev['categorie']);
      ?>
      <article class="event-card" data-cat="<?= p($slug) ?>">
        <div class="event-img cat-<?= p($slug) ?>">
          <?= categorieTag($ev['categorie']) ?>
          <span class="event-date-badge">
            <?= p($jour) ?><small><?= p($moisLabel) ?></small>
          </span>
          <span class="event-emoji" aria-hidden="true"><?= $emoji ?></span>
        </div>
        <div class="event-body">
          <p class="event-asso"><?= p($ev['association']) ?></p>
          <h3 class="event-title">
            <a href="detail.php?id=<?= (int)$ev['id'] ?>">
              <?= p($ev['titre']) ?>
            </a>
          </h3>
          <p class="event-meta">
            <?= lieuAffiche($ev['lieu']) ?> · <?= p(substr($ev['heure_evenement'], 0, 5)) ?>
          </p>

          <div class="event-gauge">
            <div class="gauge-bar">
              <div class="gauge-fill <?= $complet ? 'gauge-full' : '' ?>"
                   style="width:<?= $pct ?>%"></div>
            </div>
            <?php if ($complet): ?>
              <span class="complet">Complet · Liste d'attente</span>
            <?php else: ?>
              <span><?= $nbInscrits ?> / <?= $capacite ?> places</span>
            <?php endif; ?>
          </div>
          <div class="event-footer">
            <?php if ($complet): ?>
              <a href="../reservation/inscription.php?id=<?= (int)$ev['id'] ?>"
                 class="btn-reserve btn-wait">File d'attente</a>
            <?php else: ?>
              <a href="../reservation/inscription.php?id=<?= (int)$ev['id'] ?>"
                 class="btn-reserve">Réserver</a>
            <?php endif; ?>
            <?= prixAffiche((float) $ev['prix']) ?>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</section>

<?php require_once __DIR__ . '/../footer/footer.php'; ?>

<script src="index.js"></script>
</body>
</html>