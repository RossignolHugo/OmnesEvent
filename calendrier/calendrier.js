const moisAnnee = document.getElementById("moisAnnee");
const joursCalendrier = document.getElementById("joursCalendrier");

let dateActuelle = new Date();

const mois = [
    "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
    "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"
];

function afficherCalendrier() {
    const annee = dateActuelle.getFullYear();
    const mois = dateActuelle.getMonth();

    moisAnnee.textContent = `${mois[mois]} ${annee}`;

    const premierJour = new Date(annee, mois, 1);
    const dernierJour = new Date(annee, mois + 1, 0);
    const decalage = (premierJour.getDay() + 6) % 7; // Lundi = 0
    const nbJours = dernierJour.getDate();

    joursCalendrier.innerHTML = "";

    for (let i = 0; i < decalage; i++) {
        joursCalendrier.innerHTML += `<div class="jour"></div>`;
    }

    for (let jour = 1; jour <= nbJours; jour++) {
        joursCalendrier.innerHTML += `
            <div class="jour">
                <div class="numero-jour">${jour}</div>
            </div>
        `;
    }
}


document.getElementById("moisPrecedent").onclick = () => {
    dateActuelle.setMonth(dateActuelle.getMonth() - 1);
    afficherCalendrier();
};

document.getElementById("moisSuivant").onclick = () => {
    dateActuelle.setMonth(dateActuelle.getMonth() + 1);
    afficherCalendrier();
};

afficherCalendrier();
