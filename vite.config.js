import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react'
// import tailwindcss from '@tailwindcss/vite';
import path from 'path'; // <--- AJOUTEZ CETTE LIGNE
import { fileURLToPath } from 'url'; // Importez ces deux utilitaires
import basicSsl from '@vitejs/plugin-basic-ssl'; // 1. Importez le plugin

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export default defineConfig({
    plugins: [
        basicSsl(), // 2. Ajoutez-le aux plugins
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
            // ⚠️ On indique à Vite d'ignorer complètement ces dossiers lourds
            ignored: [
                '**/node_modules/**',
                '**/vendor/**',
                '**/storage/framework/views/**'
            ],
        },
        host: '0.0.0.0', // Permet l'accès depuis l'extérieur
        port: 5173,
        strictPort: true,
        cors: true,      // Autorise explicitement les requêtes cross-origin de Nginx
        origin: 'https://si-app.domain.lan', // Dit à Vite que son origine publique est Nginx en HTTPS
        hmr: {
            protocol: 'wss',
            host: 'si-app.domain.lan',
            clientPort: 443,
        },
    },
});

