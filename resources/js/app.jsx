// =========================================================================
// 1. IMPORTS DES MODULES & COMPOSANTS
// =========================================================================
// import "./bootstrap"; // Décommenté si nécessaire pour Axios / Echo
// import "../css/app.css"; // Votre CSS global principal (Tailwind, etc.)

import React from "react";
import { createRoot } from "react-dom/client";
import { createInertiaApp, router } from "@inertiajs/react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import Swal from "sweetalert2";

// PrimeReact & Locales
import { PrimeReactProvider, addLocale, locale } from "primereact/api";

// =========================================================================
// 2. IMPORTS DES STYLES (Ordre de spécificité croissant)
// =========================================================================
// A. Thème sélectionné (Soho Dark)
import "primereact/resources/themes/soho-dark/theme.css";
// Autres options disponibles en commentaire si besoin :
// import "primereact/resources/themes/lara-light-indigo/theme.css";
// import "primereact/resources/themes/saga-blue/theme.css";

// B. Bibliothèque de composants & Icônes
import "primereact/resources/primereact.min.css";
import "primeicons/primeicons.css";

// C. Frameworks utilitaires
import "primeflex/primeflex.css";

// D. Thème personnalisé Sakai (Écrase les styles précédents si nécessaire)
import "../css/sakai/layout/layout.scss";
import "../css/sakai/demo/Demos.scss";

// =========================================================================
// 3. CONFIGURATIONS GLOBALES (Locales & Écouteurs)
// =========================================================================

// Configuration et activation de la langue française pour PrimeReact
addLocale("fr", {
    firstDayOfWeek: 1,
    dayNames: [
        "dimanche",
        "lundi",
        "mardi",
        "mercredi",
        "jeudi",
        "vendredi",
        "samedi",
    ],
    dayNamesShort: ["dim", "lun", "mar", "mer", "jeu", "ven", "sam"],
    dayNamesMin: ["D", "L", "M", "M", "J", "V", "S"],
    monthNames: [
        "janvier",
        "février",
        "mars",
        "avril",
        "mai",
        "juin",
        "juillet",
        "août",
        "septembre",
        "octobre",
        "novembre",
        "décembre",
    ],
    monthNamesShort: [
        "jan",
        "fév",
        "mars",
        "avr",
        "mai",
        "juin",
        "juil",
        "août",
        "sep",
        "oct",
        "nov",
        "déc",
    ],
    today: "Aujourd'hui",
    clear: "Effacer",
});
locale("fr");

// Gestion centralisée des erreurs de validation ou d'exceptions via Inertia
router.on("error", (event) => {
    const errors = event.detail.errors;
    const message = Object.values(errors)[0];

    Swal.fire({
        icon: "error",
        title: "Oups !",
        text: message,
        toast: true,
        position: "top-end",
        timer: 4000,
        showConfirmButton: false,
    });
});

// =========================================================================
// 4. INITIALISATION DE L'APPLICATION INERTIA
// =========================================================================
createInertiaApp({
    title: (title) => `${title} - My App`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob("./Pages/**/*.jsx"),
        ),
    setup({ el, App, props }) {
        createRoot(el).render(
            <PrimeReactProvider>
                <App {...props} />
            </PrimeReactProvider>,
        );
    },
});
