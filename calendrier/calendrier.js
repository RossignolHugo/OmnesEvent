let moisActuel = new Date().getMonth();
let anneeActuelle = new Date().getFullYear();

function afficherCalendrier(mois, annee) {
    const date = new Date(annee, mois, 1);
    const joursDansMois = new Date(annee, mois + 1, 0).getDate();
    const premierJour = (date.getDay() + 6) % 7;

    document.getElementById("moisAnnee").textContent =
        date.toLocaleString("fr-FR", { month: "long", year: "numeric" });

    const conteneur = document.getElementById("joursCalendrier");
    conteneur.innerHTML = "";

    // si premier jour pas lundi
    for (let i = 0; i < premierJour; i++) {
        const div = document.createElement("div");
        div.classList.add("jour", "vide");
        conteneur.appendChild(div);
    }

    // jour du mois
    for (let jour = 1; jour <= joursDansMois; jour++) {
        const div = document.createElement("div");
        div.classList.add("jour");

        const dateStr = `${annee}-${String(mois + 1).padStart(2, '0')}-${String(jour).padStart(2, '0')}`;

        // Num jour
        div.innerHTML = `<span class="numero">${jour}</span>`;

        //evenement jour
        const evenementsDuJour = EVENEMENTS.filter(ev => ev.date_evenement === dateStr);

        evenementsDuJour.forEach(ev => {
            const evDiv = document.createElement("div");
            evDiv.classList.add("evenmentCalendrier");
            evDiv.textContent = ev.titre;
            evDiv.addEventListener("click", () => afficherDetails(ev));
            div.appendChild(evDiv);
        });

        conteneur.appendChild(div);
    }
}

function afficherDetails(ev) {
    const zone = document.getElementById("detailsEvenement");

    zone.innerHTML = `
        <h3>${ev.titre}</h3>
        <p><strong>Date :</strong> ${ev.date_evenement}</p>
        <p><strong>Heure :</strong> ${ev.heure_evenement}</p>
        <p><strong>Lieu :</strong> ${ev.lieu}</p>
        <p><strong>Catégorie :</strong> ${ev.categorie}</p>
        <p><strong>Association :</strong> ${ev.association}</p>
        <p><strong>Capacité :</strong> ${ev.capacite_max}</p>
        ${ev.prix ? `<p><strong>Prix :</strong> ${ev.prix} €</p>` : ""}
        <p><strong>Description :</strong> ${ev.description}</p>
    `;

    zone.style.display = "block";
}

// Navigation
document.getElementById("moisPrecedent").onclick = () => {
    moisActuel--;
    if (moisActuel < 0) {
        moisActuel = 11;
        anneeActuelle--;
    }
    afficherCalendrier(moisActuel, anneeActuelle);
};

document.getElementById("moisSuivant").onclick = () => {
    moisActuel++;
    if (moisActuel > 11) {
        moisActuel = 0;
        anneeActuelle++;
    }
    afficherCalendrier(moisActuel, anneeActuelle);
};

//init
afficherCalendrier(moisActuel, anneeActuelle);
