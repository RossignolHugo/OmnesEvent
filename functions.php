<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function rediriger(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function roleUtilisateur(): ?string
{
    return $_SESSION['role'] ?? null;
}



function utilisateurId(): ?int
{
    return isset($_SESSION['utilisateur_id']) ? (int) $_SESSION['utilisateur_id'] : null;
}

function nomAfficheUtilisateur(): string
{
    if (!empty($_SESSION['pseudo'])) {
        return $_SESSION['pseudo'];
    }

    $prenom = $_SESSION['prenom'] ?? '';
    $nom = $_SESSION['nom'] ?? '';
    $nomComplet = trim($prenom . ' ' . $nom);

    return $nomComplet !== '' ? $nomComplet : 'Utilisateur';
}

function verifierConnexion(): void
{
    if (!estConnecte()) {
        rediriger('../connexion/connexion.php');
    }
}

function verifierAdmin(): void
{
    verifierConnexion();
    if (!estAdmin()) {
        http_response_code(403);
        exit('Accès refusé : cette page est réservée aux administrateurs.');
    }
}

function verifierOrganisateurOuAdmin(): void
{
    verifierConnexion();
    if (!estOrganisateur() && !estAdmin()) {
        http_response_code(403);
        exit('Accès refusé : cette page est réservée aux organisateurs et aux administrateurs.');
    }
}

function messageFlash(?string $type = null, ?string $message = null): ?array
{
    if ($type !== null && $message !== null) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        return null;
    }

    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    return null;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifierCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        exit('Requête invalide : jeton CSRF incorrect.');
    }
}

function peutGererEvenement(PDO $pdo, int $evenementId): bool
{
    if (estAdmin()) {
        return true;
    }

    if (!estOrganisateur()) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT organisateur_id FROM evenements WHERE id = ?');
    $stmt->execute([$evenementId]);
    $evenement = $stmt->fetch(PDO::FETCH_ASSOC);

    return $evenement && (int) $evenement['organisateur_id'] === utilisateurId();
}

function enregistrerAffiche(array $fichier): ?string
{
    if (empty($fichier['name']) || $fichier['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Erreur lors de l'upload de l'affiche.");
    }

    $tailleMax = 2 * 1024 * 1024;
    if ($fichier['size'] > $tailleMax) {
        throw new RuntimeException("L'affiche ne doit pas dépasser 2 Mo.");
    }

    $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
    $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($extension, $extensionsAutorisees, true)) {
        throw new RuntimeException("Format d'affiche non autorisé. Formats acceptés : jpg, jpeg, png, gif, webp.");
    }

    $nomFichier = 'affiche_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $dossier = __DIR__ . '/../uploads/';

    if (!is_dir($dossier)) {
        mkdir($dossier, 0775, true);
    }

    $destination = $dossier . $nomFichier;
    if (!move_uploaded_file($fichier['tmp_name'], $destination)) {
        throw new RuntimeException("Impossible d'enregistrer l'affiche.");
    }

    return 'uploads/' . $nomFichier;
}
