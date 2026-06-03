#!/bin/bash

set -euo pipefail

BACKUP_DIR="./casaos_backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

exec > >(tee -i "$BACKUP_DIR/backup.log")
exec 2>&1

echo "=== FULL CASAOS BACKUP START ==="

########################################
# 1. STACKS DOCKER COMPOSE
########################################
echo "[1] Detecting docker-compose projects (deduplicated)..."

mkdir -p "$BACKUP_DIR/compose"

declare -A seen

docker ps -q | while read -r container; do
  compose_file=$(docker inspect -f \
    '{{ index .Config.Labels "com.docker.compose.project.config_files" }}' \
    "$container" 2>/dev/null || true)

  if [[ -n "$compose_file" ]]; then

    if [[ -z "${seen[$compose_file]+x}" ]]; then
      echo "→ Found: $compose_file"
      cp --parents "$compose_file" "$BACKUP_DIR/compose/" 2>/dev/null || true
      seen["$compose_file"]=1
    fi

  fi
done

########################################
# 2. CASAOS DATA
########################################
echo "[2] CasaOS AppData..."

if [ -d /DATA/AppData ]; then
  tar czf "$BACKUP_DIR/DATA_AppData.tar.gz" /DATA/AppData 2>/dev/null || true
fi

########################################
# 3. DOCKER VOLUMES (ALL)
########################################
echo "[3] Docker volumes..."

VOLUMES=$(docker volume ls -q)

for v in $VOLUMES; do
  docker run --rm \
    -v "$v":/volume \
    -v "$BACKUP_DIR":/backup \
    alpine \
    tar czf "/backup/${v}.tar.gz" -C /volume . 2>/dev/null || true
done

########################################
# 4. MYSQL / MARIADB DUMP (CRITICAL FIX)
########################################
echo "[4] MySQL/MariaDB dump..."

#DB_CONTAINERS=$(docker ps --format '{{.Names}}' | grep -Ei "mysql|mariadb" || true)

mapfile -t CONTAINERS < <(docker ps --format '{{.Names}}')

for c in "${CONTAINERS[@]}"; do
  IMAGE=$(docker inspect -f '{{.Config.Image}}' "$c")

  if [[ "$IMAGE" == *mysql* || "$IMAGE" == *mariadb* ]]; then
    echo "→ Dump MySQL: $c"

    DB_PASS=$(docker inspect -f '{{range .Config.Env}}{{println .}}{{end}}' "$c" \
      | grep MYSQL_ROOT_PASSWORD | cut -d= -f2)

    if [[ -n "$DB_PASS" ]]; then
      docker exec "$c" sh -c "mysqldump -u root -p\"$DB_PASS\" --all-databases" \
        > "$BACKUP_DIR/${c}_mysql_all.sql" || true
    else
      echo "⚠️ No MYSQL_ROOT_PASSWORD found, skipping dump"
    fi
  fi
done

########################################
# 5. POSTGRES (si existant)
########################################
echo "[5] PostgreSQL dump..."

PG_CONTAINERS=$(docker ps --format '{{.Names}}' | grep -i postgres || true)

for c in $PG_CONTAINERS; do
  docker exec "$c" sh -c "pg_dumpall -U postgres" \
    > "$BACKUP_DIR/${c}_postgres.sql" || true
done

########################################
# 6. DOCKER SNAPSHOT
########################################
echo "[6] Docker snapshot..."

docker ps -a > "$BACKUP_DIR/docker_ps.txt"

if [ "$(docker ps -aq)" ]; then
  docker inspect $(docker ps -aq) \
    > "$BACKUP_DIR/docker_inspect.json" 2>/dev/null || true
fi

########################################

echo ""
echo "✅ BACKUP COMPLETED"
echo "📁 Location: $BACKUP_DIR"