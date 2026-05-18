<?php
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
        throw new RuntimeException('Format non autorisé. Formats acceptés : jpg, jpeg, png, gif, webp.');
    }

    $dossier = __DIR__ . '/../uploads/events/';
    if (!is_dir($dossier)) {
        mkdir($dossier, 0775, true);
    }

    $nom = 'event_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    $destination = $dossier . $nom;

    if (!move_uploaded_file($fichier['tmp_name'], $destination)) {
        throw new RuntimeException("Impossible d'enregistrer l'affiche.");
    }

    return 'uploads/events/' . $nom;
}
