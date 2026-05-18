<?php 
session_start();
require_once __DIR__ . '/../BDD.php'; 
?>

<link rel="stylesheet" href="../header/header.css">
<link rel="stylesheet" href="../footer/footer.css"> 

<header>
    <nav class="bar">
        <div class="bar-int">
            <a class="bar-logo">Omnes<em>Event</em></a>

            <div class="bar-lien">
                <a href="../index/index.php">🏠 Accueil</a>
                <a href="../calendrier/calendrier.php">📅 Calendrier</a>

                <?php if (estConnecte()): ?>
                    <a href="../billet/billet.php">🎫 Mes billets</a>
                    <a href="../profil/profil.php">👤 Mon profil</a>

                    <?php if (estOrganisateur() || estAdmin()): ?>
                        <a href="../evenement/creer.php">✚Géré evenement</a>
                    <?php endif; ?>

                    <?php if (estAdmin()): ?>
                        <a href="../admin/admin.php">⚙️ Admin</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="Navigation-autentification">
                <?php if (estConnecte()): ?>
                    <span class="bar-utilisateur">
                        👋 Bonjour <?= h($_SESSION['prenom']) . ' ' . h($_SESSION['nom']) ?>
                        <span class="role-badge">[<?= ucfirst(h($_SESSION['role'])) ?>]</span>
                    </span>

                    <a href="../connexion/deconnexion.php" class="btn-2">Déconnexion</a>

                <?php else: ?>
                    <a href="../connexion/connexion.php" class="btn-1">Se connecter</a>
                    <a href="../connexion/inscription.php" class="btn-2">S'inscrire</a>
                <?php endif; ?>
                
            </div>

        </div>
    </nav>
</header>
