// import "./bootstrap";
// import "../css/app.css";

// resources/js/app.jsx
import { PrimeReactProvider } from "primereact/api";

// 1. Thème de base PrimeReact (Lara, Saga, etc.)
//import "primereact/resources/themes/lara-light-indigo/theme.css";
//import "primereact/resources/themes/saga-blue/theme.css";
//import "primereact/resources/themes/soho-dark/theme.css";
import "primereact/resources/themes/mdc-light-deeppurple/theme.css";

// 2. Coeur de PrimeReact et Icônes
import "primereact/resources/primereact.min.css";
import "primeicons/primeicons.css";

// 3. PrimeFlex (Utilitaires de layout)
import "primeflex/primeflex.css";

// 4. Styles spécifiques à Sakai (que vous avez copiés)
import "../css/sakai/layout/layout.scss";
import "../css/sakai/demo/Demos.scss"; // Optionnel, pour les exemples de composants

import { createRoot } from "react-dom/client";
import { createInertiaApp } from "@inertiajs/react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";

// createInertiaApp({
//     resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
//     setup({ el, App, props }) {
//         createRoot(el).render(<App {...props} />);
//     },
// });

import { addLocale, locale } from "primereact/api";

// Définition de la configuration française
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
        "mar",
        "avr",
        "mai",
        "jun",
        "jul",
        "aoû",
        "sep",
        "oct",
        "nov",
        "déc",
    ],
    today: "Aujourd'hui",
    clear: "Effacer",
});

// Activation de la langue par défaut
locale("fr");

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
