import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
// import tailwindcss from '@tailwindcss/vite';
import path from 'path';
import { fileURLToPath } from 'url';
import basicSsl from '@vitejs/plugin-basic-ssl';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export default defineConfig(({ command }) => {
    // Détection de l'environnement Docker via une variable d'environnement
    const isDocker = process.env.IS_DOCKER === 'true';

    // 1. Configuration de base commune (Windows local & Docker)
    const config = {
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
                '@': path.resolve(__dirname, 'resources/js'),
            },
        },
    };

    // 2. On injecte le plugin SSL uniquement si on est en mode DEV et sous DOCKER
    if (command === 'serve' && isDocker) {
        config.plugins.unshift(basicSsl()); // Ajoute basicSsl au début du tableau des plugins
        
        config.server = {
            watch: {
                // On indique à Vite d'ignorer complètement ces dossiers lourds
                ignored: [
                    '**/node_modules/**',
                    '**/vendor/**',
                    '**/storage/framework/views/**'
                ],
            },
            host: '0.0.0.0', // Permet l'accès depuis l'extérieur du conteneur
            port: 5173,
            strictPort: true,
            cors: true, // Autorise explicitement les requêtes cross-origin de Nginx
            origin: 'https://app1.work.local', // Dit à Vite que son origine publique est en HTTPS
            hmr: {
                protocol: 'wss',
                host: 'app1.work.local',
                clientPort: 443,
            },
        };
    }

    return config;
});