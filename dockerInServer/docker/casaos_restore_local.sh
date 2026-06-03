#!/bin/bash

set -euo pipefail

BACKUP_DIR="${1:-}"

if [[ -z "$BACKUP_DIR" || ! -d "$BACKUP_DIR" ]]; then
  echo "Usage: $0 /path/to/casaos_backup"
  exit 1
fi

echo "=== CASAOS FULL RESTORE START ==="
echo "From: $BACKUP_DIR"

########################################
# 1. RESTORE DOCKER VOLUMES
########################################
echo "[1] Restoring Docker volumes..."

for file in "$BACKUP_DIR"/*.tar.gz; do
  [[ -f "$file" ]] || continue

  vol=$(basename "$file" .tar.gz)

  echo "→ Volume: $vol"

  docker volume create "$vol" >/dev/null || true

  docker run --rm \
    -v "$vol":/volume \
    -v "$BACKUP_DIR":/backup \
    alpine \
    sh -c "tar xzf /backup/${vol}.tar.gz -C /volume" || true
done

########################################
# 2. RESTORE CASAOS APPDATA
########################################
echo "[2] Restoring CasaOS AppData..."

if [[ -f "$BACKUP_DIR/DATA_AppData.tar.gz" ]]; then
  tar xzf "$BACKUP_DIR/DATA_AppData.tar.gz" -C / || true
fi

########################################
# 3. RESTORE STACKS (INFORMATION ONLY)
########################################
echo "[3] Docker stacks restore..."

if [[ -d "$BACKUP_DIR/compose" ]]; then
  echo "Stacks found in backup:"

  find "$BACKUP_DIR/compose" \
    \( -name "docker-compose.yml" -o -name "compose.yml" \) \
    -type f 2>/dev/null | while read -r f; do
      echo "→ $f"
  done

  echo ""
  echo "⚠️ Recreate stacks manually or run:"
  echo "docker compose up -d inside each folder"
fi

########################################
# 4. RESTORE MYSQL
########################################
echo "[4] Restoring MySQL/MariaDB..."

MYSQL_FILES=$(find "$BACKUP_DIR" -name "*mysql.sql" 2>/dev/null)

if [[ -n "$MYSQL_FILES" ]]; then
  MYSQL_CONTAINER=$(docker ps --format '{{.Names}}' | grep -Ei "mysql|mariadb" | head -n1 || true)

  if [[ -n "$MYSQL_CONTAINER" ]]; then
    for f in $MYSQL_FILES; do
      echo "→ Importing $f into $MYSQL_CONTAINER"

      docker exec -i "$MYSQL_CONTAINER" sh -c "mysql -u root" < "$f" || true
    done
  else
    echo "⚠️ No MySQL container running"
  fi
fi

########################################
# 5. RESTORE POSTGRES
########################################
echo "[5] Restoring PostgreSQL..."

PG_FILES=$(find "$BACKUP_DIR" -name "*postgres.sql" 2>/dev/null)

if [[ -n "$PG_FILES" ]]; then
  PG_CONTAINER=$(docker ps --format '{{.Names}}' | grep -i postgres | head -n1 || true)

  if [[ -n "$PG_CONTAINER" ]]; then
    for f in $PG_FILES; do
      echo "→ Importing $f into $PG_CONTAINER"

      docker exec -i "$PG_CONTAINER" sh -c "psql -U postgres" < "$f" || true
    done
  else
    echo "⚠️ No PostgreSQL container running"
  fi
fi

########################################
# 6. DOCKER SNAPSHOT INFO
########################################
echo "[6] Docker info restore (reference only)"

if [[ -f "$BACKUP_DIR/docker_ps.txt" ]]; then
  echo "Containers from backup:"
  cat "$BACKUP_DIR/docker_ps.txt"
fi

########################################

echo ""
echo "✅ RESTORE COMPLETED"
echo ""
echo "⚠️ IMPORTANT NEXT STEPS:"
echo "- Restart CasaOS UI"
echo "- Run docker compose up -d in stacks if needed"
echo "- Verify DB connections"

echo "[X] Restarting docker-compose stacks..."

find "$BACKUP_DIR/compose" \
  -name "docker-compose.yml" -o -name "compose.yml" \
  2>/dev/null | while read -r file; do

  dir=$(dirname "$file")

  echo "→ Starting stack in $dir"

  (cd "$dir" && docker compose up -d) || true
done