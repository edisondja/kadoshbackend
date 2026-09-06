#!/usr/bin/env bash
# =============================================================================
# Actualiza esquema en TODAS las bases (clinica + cada tenant)
#
# 1) Hace backup automático antes de tocar nada
# 2) Aplica SQL idempotente en cada base
#
# Uso:
#   chmod +x scripts/actualizar-todas-las-bases.sh
#   DB_USER=root DB_PASS=secret ./scripts/actualizar-todas-las-bases.sh
#
# Solo tenants (sin clinica):
#   SOLO_TENANTS=1 DB_USER=root DB_PASS=secret ./scripts/actualizar-todas-las-bases.sh
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
LIST_FILE="${LIST_FILE:-$SCRIPT_DIR/databases.production.list}"
SQL_TENANT="${SQL_TENANT:-$ROOT_DIR/database/sql/tenant_actualizar_esquema.sql}"
SQL_MASTER="${SQL_MASTER:-$ROOT_DIR/create_tenants_table.sql}"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
HACER_BACKUP="${HACER_BACKUP:-1}"
SOLO_TENANTS="${SOLO_TENANTS:-0}"

mysql_cmd() {
  if [[ -n "$DB_PASS" ]]; then
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$@"
  else
    mysql -h "$DB_HOST" -u "$DB_USER" "$@"
  fi
}

load_databases() {
  while IFS= read -r line || [[ -n "$line" ]]; do
    line="${line%%#*}"
    line="$(echo "$line" | xargs)"
    [[ -n "$line" ]] && echo "$line"
  done < "$LIST_FILE" | awk '!seen[$0]++'
}

is_master_db() {
  [[ "$1" == "clinica" ]]
}

echo "=============================================="
echo " Actualizar esquema — todas las bases"
echo "=============================================="

if [[ ! -f "$SQL_TENANT" ]]; then
  echo "ERROR: No existe $SQL_TENANT"
  exit 1
fi

if [[ "$HACER_BACKUP" == "1" ]]; then
  echo ""
  echo ">> Paso 1: Backup antes de actualizar..."
  HACER_BACKUP=0 "$SCRIPT_DIR/backup-all-databases.sh"
fi

echo ""
echo ">> Paso 2: Aplicar SQL en cada base..."
OK=0
FAIL=0

while IFS= read -r DB; do
  [[ -z "$DB" ]] && continue

  if [[ "$SOLO_TENANTS" == "1" ]] && is_master_db "$DB"; then
    echo "— Omitiendo master: $DB"
    continue
  fi

  echo ""
  echo "→ Actualizando: $DB"

  if ! mysql_cmd -e "USE \`$DB\`;" 2>/dev/null; then
    echo "  ✗ No se pudo conectar o no existe: $DB"
    FAIL=$((FAIL + 1))
    continue
  fi

  if is_master_db "$DB"; then
    if [[ -f "$SQL_MASTER" ]]; then
      if mysql_cmd "$DB" < "$SQL_MASTER" 2>/dev/null; then
        echo "  ✓ Master: tenants table OK"
      else
        echo "  ⚠ Master: revisar create_tenants_table.sql"
      fi
    fi
    # La master no tiene pacientes/configs de clínica; solo tenants
    OK=$((OK + 1))
    continue
  fi

  if mysql_cmd "$DB" < "$SQL_TENANT" 2>/dev/null; then
    echo "  ✓ tenant_actualizar_esquema.sql aplicado"
    OK=$((OK + 1))
  else
    echo "  ✗ Error aplicando SQL en $DB"
    FAIL=$((FAIL + 1))
  fi

  # SQL adicionales idempotentes (tema, firma, etc.)
  for EXTRA in \
    "$ROOT_DIR/database/sql/2026_07_17_tema_apariencia_configs.sql" \
    "$ROOT_DIR/database/sql/2026_07_25_firma_doctor_documentos.sql" \
    "$ROOT_DIR/database/sql/2026_08_28_user_sessions.sql" \
    "$ROOT_DIR/database/sql/2026_08_29_asientos_contables.sql" \
    "$ROOT_DIR/database/sql/2026_08_29_usuarios_bloqueado.sql"
  do
    if [[ -f "$EXTRA" ]]; then
      mysql_cmd "$DB" < "$EXTRA" 2>/dev/null && echo "  ✓ $(basename "$EXTRA")" || true
    fi
  done

done < <(load_databases)

echo ""
echo "=============================================="
echo " Completado: ${OK} OK, ${FAIL} con error"
echo "=============================================="

exit 0
