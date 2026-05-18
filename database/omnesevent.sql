CREATE DATABASE IF NOT EXISTS omnesevent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE omnesevent;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS presences;
DROP TABLE IF EXISTS file_attente;
DROP TABLE IF EXISTS inscriptions;
DROP TABLE IF EXISTS evenements;
DROP TABLE IF EXISTS utilisateurs;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin','organisateur','participant') NOT NULL DEFAULT 'participant',
    formation VARCHAR(100) DEFAULT '',
    actif TINYINT(1) NOT NULL DEFAULT 1,
    valide TINYINT(1) NOT NULL DEFAULT 0,
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evenements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    date_evenement DATE NOT NULL,
    heure_evenement TIME NOT NULL,
    lieu VARCHAR(200) NOT NULL,
    categorie ENUM('Soirée','Sport','Culture','Conférence') NOT NULL,
    association VARCHAR(100) NOT NULL,
    capacite_max INT NOT NULL DEFAULT 100,
    prix DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    affiche VARCHAR(255) DEFAULT NULL,
    organisateur_id INT NOT NULL,
    statut ENUM('publié','annulé','archivé') NOT NULL DEFAULT 'publié',
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_evenements_date (date_evenement, heure_evenement),
    INDEX idx_evenements_organisateur (organisateur_id),
    CONSTRAINT fk_evenements_organisateur
    FOREIGN KEY (organisateur_id) REFERENCES utilisateurs(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    evenement_id INT NOT NULL,
    code_billet VARCHAR(60) NOT NULL UNIQUE,
    statut ENUM('confirmé','annulé','présent') NOT NULL DEFAULT 'confirmé',
    paiement_statut ENUM('gratuit','payé','remboursé') NOT NULL DEFAULT 'gratuit',
    montant_paye DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_inscription (utilisateur_id, evenement_id),
    INDEX idx_inscriptions_evenement (evenement_id),
    CONSTRAINT fk_inscriptions_utilisateur
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
    ON DELETE CASCADE,
    CONSTRAINT fk_inscriptions_evenement
    FOREIGN KEY (evenement_id) REFERENCES evenements(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE file_attente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    evenement_id INT NOT NULL,
    position INT NOT NULL,
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attente (utilisateur_id, evenement_id),
    INDEX idx_file_attente_evenement_position (evenement_id, position),
    CONSTRAINT fk_file_attente_utilisateur
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
    ON DELETE CASCADE,
    CONSTRAINT fk_file_attente_evenement
    FOREIGN KEY (evenement_id) REFERENCES evenements(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE presences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inscription_id INT NOT NULL,
    scanne_par INT NOT NULL,
    scanne_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_presences_inscription (inscription_id),
    INDEX idx_presences_scanne_par (scanne_par),
    CONSTRAINT fk_presences_inscription
    FOREIGN KEY (inscription_id) REFERENCES inscriptions(id)
    ON DELETE CASCADE,
    CONSTRAINT fk_presences_scanne_par
    FOREIGN KEY (scanne_par) REFERENCES utilisateurs(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO utilisateurs (id, nom, prenom, email, mot_de_passe, role, formation, actif, valide) VALUES
(1, 'Omnes', 'Admin', 'admin@omnes.fr', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Staff', 1, 1),
(2, 'Leroy', 'Thomas', 'thomas@omnes.fr', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organisateur', 'ING3', 1, 1),
(3, 'Martin', 'Alice', 'alice@omnes.fr', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'participant', 'ING2', 1, 1);

INSERT INTO evenements (id, titre, description, date_evenement, heure_evenement, lieu, categorie, association, capacite_max, prix, affiche, organisateur_id, statut) VALUES
(1, 'Soirée BDE', 'DJ, animations et rencontre entre étudiants.', '2026-09-15', '21:00:00', 'Campus Omnes Lyon', 'Soirée', 'BDE', 200, 5.00, NULL, 2, 'publié');
