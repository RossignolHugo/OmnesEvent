/* page_accueil.js — OmnesEvent */

document.addEventListener('DOMContentLoaded', function () {

    // ── 1. Filtres par catégorie (côté client) ────────────────────────────────
    const tabs  = document.querySelectorAll('.filter-tab');
    const cards = document.querySelectorAll('.event-card');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            // Activer l'onglet cliqué
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');

            var filter = tab.dataset.filter;

            cards.forEach(function (card) {
                if (filter === 'all' || card.dataset.cat === filter) {
                    card.style.display = '';
                    // Petite transition d'apparition
                    setTimeout(function () { card.style.opacity = '1'; }, 10);
                } else {
                    card.style.opacity = '0';
                    setTimeout(function () { card.style.display = 'none'; }, 200);
                }
            });
        });
    });

    // ── 2. Scroll reveal ──────────────────────────────────────────────────────
    var revealEls = document.querySelectorAll('.event-card, .asso-pill');

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08 });

        revealEls.forEach(function (el) { observer.observe(el); });
    } else {
        // Fallback pour anciens navigateurs
        revealEls.forEach(function (el) { el.classList.add('visible'); });
    }

    // ── 3. Smooth scroll vers #events depuis le bouton hero ──────────────────
    var btnScroll = document.querySelector('a[href="#events"]');
    if (btnScroll) {
        btnScroll.addEventListener('click', function (e) {
            var target = document.getElementById('events');
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    // ── 4. Jauge animée (démarre à 0, monte à la valeur réelle) ──────────────
    var fills = document.querySelectorAll('.gauge-fill');
    fills.forEach(function (fill) {
        var target = fill.style.width;
        fill.style.width = '0%';
        setTimeout(function () { fill.style.width = target; }, 200);
    });

});