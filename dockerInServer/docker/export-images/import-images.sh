#!/bin/bash

# Configuration
INPUT_FILE="stack_images.tar.gz"

if [ ! -f "$INPUT_FILE" ]; then
    echo "Erreur: Le fichier $INPUT_FILE est introuvable."
    exit 1
fi

echo "=== 1. Chargement des images Docker ==="
docker load -i "$INPUT_FILE"

echo "=== 2. Lancement du projet avec docker-compose ==="
# --no-build empêche docker-compose de chercher à se connecter pour rebuilder/puller
docker-compose up -d --no-build

echo "=== Succès ! ==="
echo "Votre stack (Laravel, Nginx, MariaDB) est désormais démarrée."