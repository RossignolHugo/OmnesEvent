-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 13 mai 2026 à 12:43
-- Version du serveur : 8.4.7
-- Version de PHP : 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `omnesevent`
--

-- --------------------------------------------------------

--
-- Structure de la table `evenements`
--

DROP TABLE IF EXISTS `evenements`;
CREATE TABLE IF NOT EXISTS `evenements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `date_evenement` date NOT NULL,
  `heure_evenement` time NOT NULL,
  `lieu` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` enum('Soirée','Sport','Culture','Conférence') COLLATE utf8mb4_unicode_ci NOT NULL,
  `association` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacite_max` int NOT NULL DEFAULT '100',
  `prix` decimal(8,2) DEFAULT '0.00',
  `affiche` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `organisateur_id` int NOT NULL,
  `statut` enum('publié','annulé','archivé') COLLATE utf8mb4_unicode_ci DEFAULT 'publié',
  `crée_le` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `organisateur_id` (`organisateur_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `evenements`
--

INSERT INTO `evenements` (`id`, `titre`, `description`, `date_evenement`, `heure_evenement`, `lieu`, `categorie`, `association`, `capacite_max`, `prix`, `affiche`, `organisateur_id`, `statut`, `crée_le`) VALUES
(1, 'Soirée BDE', 'DJ, open bar', '2025-09-15', '21:00:00', 'Part-dieux Lyon', 'Soirée', 'BDE', 200, 5.00, NULL, 2, 'publié', '2026-05-13 14:38:08');

-- --------------------------------------------------------

--
-- Structure de la table `file_attente`
--

DROP TABLE IF EXISTS `file_attente`;
CREATE TABLE IF NOT EXISTS `file_attente` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int NOT NULL,
  `evenement_id` int NOT NULL,
  `position` int NOT NULL,
  `crée_le` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attente` (`utilisateur_id`,`evenement_id`),
  KEY `evenement_id` (`evenement_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `file_attente`
--

INSERT INTO `file_attente` (`id`, `utilisateur_id`, `evenement_id`, `position`, `crée_le`) VALUES
(1, 4, 5, 1, '2026-05-13 14:38:08');

-- --------------------------------------------------------

--
-- Structure de la table `inscriptions`
--

DROP TABLE IF EXISTS `inscriptions`;
CREATE TABLE IF NOT EXISTS `inscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int NOT NULL,
  `evenement_id` int NOT NULL,
  `code_billet` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('confirmé','annulé','présent') COLLATE utf8mb4_unicode_ci DEFAULT 'confirmé',
  `paiement_statut` enum('gratuit','payé','remboursé') COLLATE utf8mb4_unicode_ci DEFAULT 'gratuit',
  `montant_paye` decimal(8,2) DEFAULT '0.00',
  `crée_le` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_billet` (`code_billet`),
  UNIQUE KEY `unique_inscription` (`utilisateur_id`,`evenement_id`),
  KEY `evenement_id` (`evenement_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `inscriptions`
--

INSERT INTO `inscriptions` (`id`, `utilisateur_id`, `evenement_id`, `code_billet`, `statut`, `paiement_statut`, `montant_paye`, `crée_le`) VALUES
(1, 3, 1, 'TKT-20250915-3001', 'confirmé', 'payé', 5.00, '2026-05-13 14:38:08'),
(2, 3, 3, 'TKT-20250925-3002', 'confirmé', 'gratuit', 0.00, '2026-05-13 14:38:08');

-- --------------------------------------------------------

--
-- Structure de la table `presences`
--

DROP TABLE IF EXISTS `presences`;
CREATE TABLE IF NOT EXISTS `presences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inscription_id` int NOT NULL,
  `scanne_par` int NOT NULL,
  `scanne_le` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inscription_id` (`inscription_id`),
  KEY `scanne_par` (`scanne_par`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','organisateur','participant') COLLATE utf8mb4_unicode_ci DEFAULT 'participant',
  `formation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `actif` tinyint(1) DEFAULT '1',
  `valide` tinyint(1) DEFAULT '0',
  `crée_le` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `formation`, `actif`, `valide`, `crée_le`) VALUES
(1, 'Omnes', 'Admin', 'admin@omnes.fr', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Staff', 1, 1, '2026-05-13 14:38:08'),
(2, 'Leroy', 'Thomas', 'thomas@omnes.fr', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organisateur', 'ING3', 1, 1, '2026-05-13 14:38:08'),
(3, 'Martin', 'Alice', 'alice@omnes.fr', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'participant', 'ING2', 1, 1, '2026-05-13 14:38:08');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
