function afficher(id, bouton) {
    document.querySelectorAll('.tab-content').forEach(section => {
        section.style.display = 'none';
    });

    const section = document.getElementById(id);
    if (section) {
        section.style.display = 'block';
    }

    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });

    bouton.classList.add('active');
}
