<?php
function estConnecte(): bool
{
    return isset($_SESSION['utilisateur_id']);
}

function utilisateurId(): ?int
{
    return estConnecte() ? (int) $_SESSION['utilisateur_id'] : null;
}

function roleUtilisateur(): ?string
{
    return $_SESSION['role'] ?? null;
}

function estAdmin(): bool
{
    return estConnecte() && roleUtilisateur() === 'admin';
}

function estOrganisateur(): bool
{
    return estConnecte() && roleUtilisateur() === 'organisateur';
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

function connecterUtilisateur(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['utilisateur_id'] = (int) $user['id'];
    $_SESSION['nom'] = $user['nom'];
    $_SESSION['prenom'] = $user['prenom'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
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
    $event = $stmt->fetch();

    return $event && (int) $event['organisateur_id'] === utilisateurId();
}
