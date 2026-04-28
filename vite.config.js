import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react'
// import tailwindcss from '@tailwindcss/vite';
import path from 'path'; // <--- AJOUTEZ CETTE LIGNE
import { fileURLToPath } from 'url'; // Importez ces deux utilitaires

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.jsx', 'resources/css/app.css'],
            refresh: true,
        }),
        // tailwindcss(),
        react(),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                quietDeps: true, // Ignore les alertes venant des dépendances
                silenceDeprecations: ['import'], // Silence spécifiquement les alertes @import
            },
        },
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'), // <--- Configurez l'alias ici
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
