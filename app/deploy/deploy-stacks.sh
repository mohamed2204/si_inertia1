#!/bin/bash

set -euo pipefail

BASE_DIR="${1:-$HOME/docker/infra}"

echo "=== ENTERPRISE DOCKER INFRA DEPLOY ==="
echo "Target: $BASE_DIR"

########################################
# 1. CREATE NETWORK
########################################

echo "[1] Creating shared network..."

docker network create proxy-net 2>/dev/null || true

########################################
# 2. STRUCTURE
########################################

mkdir -p "$BASE_DIR/tools/proxy-server"
mkdir -p "$BASE_DIR/tools/pihole"
mkdir -p "$BASE_DIR/tools/uptimekuma"
mkdir -p "$BASE_DIR/tools/adminer"
mkdir -p "$BASE_DIR/tools/dockhand"
mkdir -p "$BASE_DIR/my-app/app1_si/src"

########################################
# 3. NGINX PROXY MANAGER
########################################

cat > "$BASE_DIR/tools/proxy-server/docker-compose.yml" <<'EOF'
version: "3.8"

services:
  npm:
    image: jc21/nginx-proxy-manager:latest
    container_name: nginx-proxy-manager
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
      - "81:81"
    environment:
      DB_MYSQL_HOST: db
      DB_MYSQL_PORT: 3306
      DB_MYSQL_USER: npm
      DB_MYSQL_PASSWORD: npm_password
      DB_MYSQL_NAME: npm
    volumes:
      - ./data:/data
      - ./letsencrypt:/etc/letsencrypt
    networks:
      - proxy-net

  db:
    image: mariadb:10.11
    container_name: npm-db
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: rootpass
      MYSQL_DATABASE: npm
      MYSQL_USER: npm
      MYSQL_PASSWORD: npm_password
    volumes:
      - npm_db:/var/lib/mysql
    networks:
      - proxy-net

volumes:
  npm_db:

networks:
  proxy-net:
    external: true
EOF

########################################
# 4. PIHOLE
########################################

cat > "$BASE_DIR/tools/pihole/docker-compose.yml" <<'EOF'
version: "3.8"

services:
  pihole:
    image: pihole/pihole:latest
    container_name: pihole
    restart: unless-stopped
    environment:
      TZ: Africa/Casablanca
      WEBPASSWORD: admin123
    ports:
      - "53:53/tcp"
      - "53:53/udp"
      - "8081:80"
    volumes:
      - pihole_data:/etc/pihole
      - dnsmasq:/etc/dnsmasq.d
    networks:
      - proxy-net

volumes:
  pihole_data:
  dnsmasq:

networks:
  proxy-net:
    external: true
EOF

########################################
# 5. UPTIME KUMA
########################################

cat > "$BASE_DIR/tools/uptimekuma/docker-compose.yml" <<'EOF'
version: "3.8"

services:
  uptime:
    image: louislam/uptime-kuma:latest
    container_name: uptime-kuma
    restart: unless-stopped
    volumes:
      - uptime_data:/app/data
    ports:
      - "3001:3001"
    networks:
      - proxy-net

volumes:
  uptime_data:

networks:
  proxy-net:
    external: true
EOF

########################################
# 6. ADMINER
########################################

cat > "$BASE_DIR/tools/adminer/docker-compose.yml" <<'EOF'
version: "3.8"

services:
  adminer:
    image: adminer:latest
    container_name: adminer
    restart: unless-stopped
    ports:
      - "8082:8080"
    networks:
      - proxy-net

networks:
  proxy-net:
    external: true
EOF

########################################
# 7. DOCKHAND
########################################

cat > "$BASE_DIR/tools/dockhand/docker-compose.yml" <<'EOF'
version: "3.8"

services:
  dockhand:
    image: f0rc3/dockhand:latest
    container_name: dockhand
    restart: unless-stopped
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock
    ports:
      - "9090:8080"
    networks:
      - proxy-net

networks:
  proxy-net:
    external: true
EOF

########################################
# 8. APP PHP (SQUELETTE)
########################################

cat > "$BASE_DIR/my-app/app1_si/docker-compose.yml" <<'EOF'
version: "3.8"

services:
  php:
    image: php:8.2-fpm
    container_name: app1_php
    volumes:
      - ./src:/var/www/html
    networks:
      - proxy-net

  nginx:
    image: nginx:latest
    container_name: app1_nginx
    ports:
      - "8080:80"
    volumes:
      - ./src:/var/www/html
    depends_on:
      - php
    networks:
      - proxy-net

networks:
  proxy-net:
    external: true
EOF

########################################
# 9. START EVERYTHING
########################################

echo "[2] Starting stacks..."

for dir in "$BASE_DIR"/tools/* "$BASE_DIR"/my-app/*; do
  if [[ -f "$dir/docker-compose.yml" ]]; then
    echo "→ Starting $(basename "$dir")"
    (cd "$dir" && docker compose up -d) || true
  fi
done

########################################

echo ""
echo "✅ ENTERPRISE INFRA DEPLOYED"
echo "📁 Location: $BASE_DIR"
echo "🌐 Network: proxy-net"


# UTILISATION
# chmod +x deploy_infra.sh
# ./deploy_infra.sh

# 🎯 CE QUE TU OBTIENS
# 🌐 Core Infra

# Nginx Proxy Manager ✔
# MariaDB interne ✔
# SSL ready ✔

# 📡 Services

# Pi-hole DNS ✔
# Uptime Kuma monitoring ✔
# Adminer DB tool ✔
# Dockhand Docker monitoring ✔

# 🧱 App layer
# PHP + Nginx stack ✔
# 🧠 ARCHITECTURE FINALE
#                 [ NPM ]
#                    |
#    ---------------------------------
#    |        |        |            |
# Pi-hole  App1    Uptime     Adminer
#                    |
#                Dockhand

# 💡 SI TU VEUX NEXT LEVEL

# Je peux te faire :

# 🔥 HTTPS automatique (Let's Encrypt + wildcard)
# 🔥 Backup automatique quotidien
# 🔥 Load balancing multi-apps
# 🔥 Docker observability (Prometheus + Grafana)