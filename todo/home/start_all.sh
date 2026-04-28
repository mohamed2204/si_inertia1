#!/bin/bash

# Couleurs pour la lisibilité
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}>>> Préparation du réseau Docker...${NC}"
# Crée le réseau s'il n'existe pas déjà
docker network inspect proxy-nw >/dev/null 2>&1 || \
    docker network create proxy-nw

echo -e "${BLUE}>>> Démarrage de l'Infrastructure (NPM, Adminer)...${NC}"
cd /home/mohamed/docker/infra && docker compose up -d

# On attend quelques secondes que le Proxy soit prêt
sleep 2

echo -e "${BLUE}>>> Démarrage des Applications...${NC}"

# App 1
echo -e "${GREEN}Démarrage de APP 1 SI...${NC}"
cd /home/mohamed/docker/app1_si && docker compose up -d

# App 2 (ajoutez vos autres apps ici)
# echo -e "${GREEN}Démarrage de App 2...${NC}"
# cd /DATA/AppData/app2 && docker-compose up -d

echo -e "${BLUE}>>> Tous les services ont été lancés avec succès !${NC}"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"