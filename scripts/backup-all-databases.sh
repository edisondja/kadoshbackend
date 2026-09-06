#!/usr/bin/env bash
# =============================================================================
# Backup de TODAS las bases Kadosh (clinica + tenants)
#
# Uso:
#   chmod +x scripts/backup-all-databases.sh
#   DB_USER=root DB_PASS=secret ./scripts/backup-all-databases.sh
#
# Auto-descubrir tenant_* + clinica (ignora lista):
#   AUTO_DISCOVER=1 DB_USER=root DB_PASS=secret ./scripts/backup-all-databases.sh
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
LIST_FILE="${LIST_FILE:-$SCRIPT_DIR/databases.production.list}"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
BACKUP_DIR="${BACKUP_DIR:-$ROOT_DIR/storage/backups}"
FECHA="$(date +%Y%m%d_%H%M%S)"
DESTINO="${BACKUP_DIR}/${FECHA}"
AUTO_DISCOVER="${AUTO_DISCOVER:-0}"

mkdir -p "$DESTINO"

mysql_cmd() {
  if [[ -n "$DB_PASS" ]]; then
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$@"
  else
    mysql -h "$DB_HOST" -u "$DB_USER" "$@"
  fi
}

mysqldump_cmd() {
  if [[ -n "$DB_PASS" ]]; then
    mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$@"
  else
    mysqldump -h "$DB_HOST" -u "$DB_USER" "$@"
  fi
}

load_databases() {
  local dbs=()
  if [[ "$AUTO_DISCOVER" == "1" ]]; then
    while IFS= read -r db; do
      [[ -n "$db" ]] && dbs+=("$db")
    done < <(mysql_cmd -N -e "SHOW DATABASES LIKE 'tenant_%';")
    if mysql_cmd -N -e "SHOW DATABASES LIKE 'clinica';" | grep -q '^clinica$'; then
      dbs=("clinica" "${dbs[@]}")
    fi
  else
    while IFS= read -r line || [[ -n "$line" ]]; do
      line="${line%%#*}"
      line="$(echo "$line" | xargs)"
      [[ -n "$line" ]] && dbs+=("$line")
    done < "$LIST_FILE"
  fi
  printf '%s\n' "${dbs[@]}" | awk '!seen[$0]++'
}

echo "=============================================="
echo " Backup Kadosh — ${FECHA}"
echo " Destino: ${DESTINO}"
echo "=============================================="

if ! command -v mysqldump &> /dev/null; then
  echo "ERROR: mysqldump no está instalado."
  exit 1
fi

mapfile -t DATABASES < <(load_databases)

if [[ ${#DATABASES[@]} -eq 0 ]]; then
  echo "ERROR: No hay bases en la lista. Revise $LIST_FILE"
  exit 1
fi

OK=0
FAIL=0

for DB in "${DATABASES[@]}"; do
  ARCHIVO="${DESTINO}/${DB}.sql"
  echo ""
  echo "→ Respaldando: ${DB}"

  if mysqldump_cmd \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    "$DB" > "$ARCHIVO" 2>/dev/null; then
    gzip -f "$ARCHIVO"
    echo "  ✓ ${ARCHIVO}.gz"
    OK=$((OK + 1))
  else
    echo "  ✗ Error al respaldar ${DB}"
    rm -f "$ARCHIVO"
    FAIL=$((FAIL + 1))
  fi
done

echo ""
echo "=============================================="
echo " Completado: ${OK} OK, ${FAIL} con error"
echo " Carpeta: ${DESTINO}"
echo "=============================================="

TAR="${BACKUP_DIR}/kadosh_backup_${FECHA}.tar.gz"
if tar -czf "$TAR" -C "$BACKUP_DIR" "$FECHA" 2>/dev/null; then
  echo "Paquete completo: ${TAR}"
fi

exit 0
