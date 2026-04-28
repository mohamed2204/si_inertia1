#!/bin/bash
set -euo pipefail

BACKUP_ROOT="${1:-./casaos_backups}"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="${BACKUP_ROOT}/backup_${DATE}"

mkdir -p "$BACKUP_DIR"

echo "=== CASAOS LOCAL BACKUP START ==="

########################################

# 1. CONTAINERS

########################################P
mapfile -t CONTAINERS < <(docker ps --format '{{.Names}}')

########################################

# 2. VOLUMES DETECTION

########################################
declare -A VOLUME_MAP

for c in "${CONTAINERS[@]}"; do
    while read -r v; do
        [[ -n "$v" ]] && VOLUME_MAP["$v"]=1
    done < <(docker inspect -f '{{range .Mounts}}{{if eq .Type "volume"}}{{.Name}}{{"\n"}}{{end}}{{end}}' "$c")
done

VOLUMES=("${!VOLUME_MAP[@]}")

echo "Volumes found: ${#VOLUMES[@]}"

########################################

# 3. BACKUP VOLUMES

########################################
for v in "${VOLUMES[@]}"; do
    echo "→ Backup volume: $v"
    docker run --rm
    -v "$v":/volume
    -v "$BACKUP_DIR":/backup
    alpine
    tar czf "/backup/${v}.tar.gz" -C /volume .
done

########################################

# 4. DATABASE DUMP

########################################
for c in "${CONTAINERS[@]}"; do
    IMAGE=$(docker inspect -f '{{.Config.Image}}' "$c")
    
    if [[ "$IMAGE" == *mysql* || "$IMAGE" == *mariadb* ]]; then
        echo "→ Dump MySQL: $c"
        docker exec "$c" sh -c 'mysqldump -u root --all-databases'
        > "$BACKUP_DIR/mysql_all.sql" || true
    fi
    
    if [[ "$IMAGE" == *postgres* ]]; then
        echo "→ Dump PostgreSQL: $c"
        docker exec "$c" sh -c 'pg_dumpall -U postgres'
        > "$BACKUP_DIR/postgres_all.sql" || true
    fi
done

########################################

# 5. NETWORKS

########################################
docker network ls --format '{{.Name}}'
| grep -vE '^(bridge|host|none)$' \

> "$BACKUP_DIR/networks.txt"

########################################

# 6. IMAGES

########################################
docker images --format '{{.Repository}}:{{.Tag}}'
| grep -v "<none>" \

> "$BACKUP_DIR/images.txt"

########################################

# 7. COMPOSE FILES

########################################
mkdir -p "$BACKUP_DIR/compose"
find . -name "docker-compose.yml" -o -name "compose.yml"
-exec cp {} "$BACKUP_DIR/compose/" ;

########################################

# DONE

########################################
echo ""
echo "✅ BACKUP COMPLETED"
echo "📁 Location: $BACKUP_DIR"
