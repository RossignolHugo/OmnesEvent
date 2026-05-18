# OmnesEvent

Plateforme web de billetterie et de gestion d'événements dédiée aux étudiants et au personnel d'Omnes.

## Rôles

- Administrateur : valide/refuse les organisateurs, modère les événements, désactive les comptes.
- Organisateur : crée, modifie, annule ses événements, consulte les inscrits et valide les présences.
- Participant : consulte le catalogue, réserve des billets, consulte son historique et modifie son profil.

## Installation locale

1. Copier le projet dans le dossier web local, par exemple `htdocs/OmnesEvent`.
2. Créer/importer la base avec `database/omnesevent.sql`.
3. Vérifier les identifiants dans `config/database.php`.
4. Lancer le projet via `index/index.php`.

## Comptes de test

Mot de passe commun des comptes de test : `password`.

- Admin : `admin@omnes.fr`
- Organisateur : `thomas@omnes.fr`
- Participant : `alice@omnes.fr`

## Organisation

- `config/` : configuration et connexion PDO.
- `includes/` : initialisation, sécurité, authentification, helpers, upload.
- `index/` : accueil, recherche et détail public.
- `connexion/` : inscription, connexion, déconnexion.
- `evenement/` : création et modification d'événements.
- `reservation/` : réservation et file d'attente.
- `billet/` : billets participant et paiement simulé.
- `organisateur/` : dashboard organisateur et validation des présences.
- `admin/` : administration et modération.
- `profil/` : modification du profil participant.
- `database/` : script SQL propre en InnoDB.
- `uploads/events/` : affiches des événements.

## Fonctionnalités couvertes

- Authentification et rôles.
- Validation des comptes organisateurs.
- Création d'événement avec affiche et jauge.
- Réservation avec blocage automatique si complet.
- File d'attente simple.
- Paiement simulé.
- Mes billets : à venir, passés, annulés.
- Profil modifiable.
- Dashboard organisateur avec inscrits et présences.
- Admin : validation/refus, désactivation comptes, archivage événements.
