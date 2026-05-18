function afficher(id, bouton) {
    // Masquer tous les contenus
    document.querySelectorAll('.tab-content').forEach(div => {
        div.style.display = 'none';
    });

    // Afficher l'onglet demandé
    document.getElementById(id).style.display = 'block';

    // Mettre à jour l'état visuel des boutons
    document.querySelectorAll('.tab').forEach(btn => {
        btn.classList.remove('active');
    });

    bouton.classList.add('active');
}
