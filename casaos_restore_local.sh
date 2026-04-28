#!/bin/bash
set -euo pipefail

BACKUP_DIR="${1:-}"

if [[ -z "$BACKUP_DIR" || ! -d "$BACKUP_DIR" ]]; then
    echo "Usage: $0 /path/to/backup_dir"
    exit 1
fi

echo "=== CASAOS LOCAL RESTORE START ==="

########################################

# 1. NETWORKS

########################################
echo "[1] Networks..."
while read -r net; do
    docker network create "$net" || true
done < "$BACKUP_DIR/networks.txt"

########################################

# 2. VOLUMES RESTORE

########################################
echo "[2] Volumes..."
for file in "$BACKUP_DIR"/*.tar.gz; do
    vol=$(basename "$file" .tar.gz)
    
    echo "→ Restore $vol"
    
    docker volume create "$vol" >/dev/null
    
    docker run --rm
    -v "$vol":/volume
    -v "$BACKUP_DIR":/backup
    alpine
    tar xzf "/backup/${vol}.tar.gz" -C /volume
done

########################################

# 3. IMAGES

########################################
echo "[3] Images..."
while read -r img; do
    docker pull "$img" || true
done < "$BACKUP_DIR/images.txt"

########################################

# 4. COMPOSE STACKS

########################################
echo "[4] Compose..."
if [ -d "$BACKUP_DIR/compose" ]; then
    for f in "$BACKUP_DIR/compose/"*; do
        dir=$(dirname "$f")
        (cd "$dir" && docker compose up -d) || true
    done
fi

########################################

# 5. DATABASE RESTORE

########################################
echo "[5] Databases..."

MYSQL=$(docker ps --filter ancestor=mysql --format '{{.Names}}' | head -n1)
POSTGRES=$(docker ps --filter ancestor=postgres --format '{{.Names}}' | head -n1)

if [[ -f "$BACKUP_DIR/mysql_all.sql" && -n "$MYSQL" ]]; then
    docker exec -i "$MYSQL" mysql -u root < "$BACKUP_DIR/mysql_all.sql" || true
fi

if [[ -f "$BACKUP_DIR/postgres_all.sql" && -n "$POSTGRES" ]]; then
    docker exec -i "$POSTGRES" psql -U postgres < "$BACKUP_DIR/postgres_all.sql" || true
fi

########################################

# FINAL CASAOS STEP

########################################
echo ""
echo "✅ RESTORE COMPLETED"
echo ""
echo "👉 CasaOS:"
echo "Réinstalle NPM / Dockage via l’interface"
echo "Les volumes restaurés rechargeront automatiquement la config"
