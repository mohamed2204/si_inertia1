import './bootstrap';
import '../css/app.css';


// 1. Thème de base PrimeReact (Lara, Saga, etc.)
import "primereact/resources/themes/lara-light-indigo/theme.css"; 

// 2. Coeur de PrimeReact et Icônes
import "primereact/resources/primereact.min.css";
import "primeicons/primeicons.css";

// 3. PrimeFlex (Utilitaires de layout)
import "primeflex/primeflex.css";

// 4. Styles spécifiques à Sakai (que vous avez copiés)
import "../css/sakai/layout/layout.scss"; 
import "../css/sakai/demo/Demos.scss"; // Optionnel, pour les exemples de composants

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

// createInertiaApp({
//     resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
//     setup({ el, App, props }) {
//         createRoot(el).render(<App {...props} />);
//     },
// });

createInertiaApp({
    title: (title) => `${title} - My App`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
});
