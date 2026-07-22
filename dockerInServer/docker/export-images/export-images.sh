#!/bin/bash

# Configuration
OUTPUT_FILE="stack_images.tar.gz"

echo "=== 1. Génération / Récupération des images avec docker-compose ==="
# S'assure que les images sont construites et téléchargées
docker-compose pull
docker-compose build

echo "=== 2. Récupération de la liste des images de la stack ==="
# Extrait automatiquement le nom des images définies dans le docker-compose.yml
IMAGES=$(docker-compose config | grep 'image:' | awk '{print $2}' | sort -u)

if [ -z "$IMAGES" ]; then
    echo "Erreur: Aucune image trouvée. Assurez-vous d'être dans le dossier contenant docker-compose.yml."
    exit 1
fi

echo "Images détectées à exporter :"
echo "$IMAGES"

echo "=== 3. Exportation et compression des images ==="
# Exporte toutes les images identifiées dans un fichier compressé .tar.gz
docker save $IMAGES | gzip > "$OUTPUT_FILE"

echo "=== Succès ! ==="
echo "L'archive '$OUTPUT_FILE' a été créée."
echo "Copiez '$OUTPUT_FILE' ainsi que votre 'docker-compose.yml' (et le code Laravel) sur votre clé USB."