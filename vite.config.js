import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
// import tailwindcss from '@tailwindcss/vite';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs'; // Ajout pour une détection Docker à 100% fiable

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export default defineConfig(({ command }) => {
    // Détection automatique de Docker (via fichier ou variable d'environnement)
    const isDocker = fs.existsSync('/.dockerenv') || process.env.IS_DOCKER === 'true';

    // 1. Configuration de base commune
    const config = {
        plugins: [
            laravel({
                input: ['resources/js/app.jsx', 'resources/css/app.css'],
                refresh: true,
            }),
            // tailwindcss(),
            react(),
            // basicSsl a été complètement retiré d'ici
        ],
        css: {
            preprocessorOptions: {
                scss: {
                    quietDeps: true,
                    silenceDeprecations: ['import'],
                },
            },
        },
        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'resources/js'),
            },
        },
    };

    // 2. Configuration pour le serveur de développement dans Docker
    if (command === 'serve' && isDocker) {
        // Suppression complète de basicSsl() ici aussi

        config.server = {
            watch: {
                ignored: [
                    '**/node_modules/**',
                    '**/vendor/**',
                    '**/storage/framework/views/**'
                ],
            },
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,
            cors: true, // Très important pour autoriser Laravel (HTTPS) à lire Vite (HTTP)

            // Correction ici : l'origine passe en HTTP classique
            // origin: 'http://si-app1.lan:5173', 
            origin: 'https://si-app1.lan', // On passe par Nginx
            hmr: {
                protocol: 'ws',        // 'ws' car Nginx est sur le port 80 (HTTP)
                host: 'si-app1.lan',   // Le navigateur envoie le signal à Nginx
                clientPort: 80,        // Nginx intercepte sur le port 80 et redirige vers le 5173 de Windows
            },
        };
    }

    return config;
});