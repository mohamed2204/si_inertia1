#!/bin/bash

RED='\033[0;31m'
NC='\033[0m'

echo -e "${RED}>>> Arrêt des Applications...${NC}"
cd /home/mohamed/docker/app1_si && docker compose stop

# cd /DATA/AppData/app2 && docker compose stop

echo -e "${RED}>>> Arrêt de l'Infrastructure...${NC}"
cd /home/mohamed/docker/infra && docker compose stop

echo -e "${RED}>>> Tous les services sont à l'arrêt.${NC}"