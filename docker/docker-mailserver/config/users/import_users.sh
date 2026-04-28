#!/bin/bash

# Configuration
CONTAINER_NAME="mailserver"
USER_FILE="users.csv"

# Vérification de la présence du fichier CSV
if [ ! -f "$USER_FILE" ]; then
    echo "Erreur : Le fichier $USER_FILE est introuvable."
    exit 1
fi

echo "--- Démarrage de l'importation ---"

# Lecture du fichier CSV
while IFS=',' read -r email password quota
do
    # Nettoyage des caractères invisibles (cas du CSV édité sous Windows)
    email=$(echo "$email" | tr -d '\r')
    password=$(echo "$password" | tr -d '\r')
    quota=$(echo "$quota" | tr -d '\r')

    # Sauter les lignes vides ou les commentaires
    [[ -z "$email" || "$email" =~ ^# ]] && continue

    # 1. Vérifier si l'utilisateur existe déjà
    CHECK=$(docker exec "$CONTAINER_NAME" setup email list | grep -w "$email")

    if [ -n "$CHECK" ]; then
        echo "Info : L'utilisateur $email existe déjà. Mise à jour du quota uniquement."
    else
        # 2. Création de l'utilisateur
        echo "Création de : $email..."
        docker exec "$CONTAINER_NAME" setup email add "$email" "$password"
    fi

    # 3. Application du quota (qu'il soit nouveau ou ancien)
    if [ ! -z "$quota" ] && [ "$quota" != "0" ]; then
        echo "Réglage quota : $quota pour $email"
        docker exec "$CONTAINER_NAME" setup quota set "$email" "$quota"
    fi

    echo "------------------------------------------"
done < "$USER_FILE"

echo "--- Importation terminée ---"
