#!/bin/bash

# ==========================================
# CONFIGURATION
# ==========================================
USER_NAME="mohamed"
DB_CONTAINER="NOM_DU_CONTENEUR_POSTGRES" # Le conteneur doit être lancé avant
DB_USER="casaos"
DB_PASS="casaos"

echo "--- DÉBUT DE LA RESTAURATION AUTOMATIQUE ---"

# 1. Restauration des fichiers système et volumes
echo "[1/4] Restauration des dossiers /DATA et /home..."
if [ -f "all_volumes_data.tar.gz" ]; then
    sudo tar -xpzf all_volumes_data.tar.gz -C /
    echo "Fichiers volumes restaurés."
fi

if [ -f "casaos_system.tar.gz" ]; then
    sudo tar -xpzf casaos_system.tar.gz -C /
    echo "Configs CasaOS restaurées."
fi

# 2. Chargement des images Docker (si présentes)
echo "[2/4] Chargement des images Docker (cela peut prendre du temps)..."
if [ -f "all_images.tar" ]; then
    docker load -i all_images.tar
fi

# 3. Redémarrage des conteneurs
echo "[3/4] Redémarrage des services..."
# Si tu utilises CasaOS, il va détecter les dossiers dans /var/lib/casaos/apps
sudo systemctl restart docker
echo "Patientez 10 secondes que les conteneurs démarrent..."
sleep 10

# 4. Restauration de la base de données
echo "[4/4] Injection de la base de données PostgreSQL..."
if [ -f "full_database.sql.gz" ]; then
    gunzip < full_database.sql.gz | docker exec -i "$DB_CONTAINER" psql -U "$DB_USER"
    echo "Base de données injectée."
fi

echo "--- RESTAURATION TERMINÉE ---"
echo "Vérifiez vos services avec : docker ps"
