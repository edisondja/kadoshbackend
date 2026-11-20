#!/bin/bash
# Ejecutar tras git pull en el servidor de producción.
# Uso manual: ./scripts/deploy_after_pull.sh
# Automático: instalar hooks con ./scripts/install_git_hooks.sh

set -e

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [ ! -f artisan ]; then
  echo "No es el directorio kadoshbackend (falta artisan)."
  exit 0
fi

LOG_DIR="$ROOT/storage/logs"
mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/deploy_after_pull.log"

echo "" | tee -a "$LOG_FILE"
echo "======== $(date '+%Y-%m-%d %H:%M:%S') deploy_after_pull ========" | tee -a "$LOG_FILE"

# Dependencias PHP (opcional en producción si ya están instaladas)
if [ -f composer.json ] && command -v composer >/dev/null 2>&1; then
  echo "composer install..." | tee -a "$LOG_FILE"
  composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tee -a "$LOG_FILE" || true
fi

# Migraciones en todas las BD tenant (+ SQL idempotente en database/sql/)
echo "kadosh:migrate-tenants..." | tee -a "$LOG_FILE"
php artisan kadosh:migrate-tenants --sql 2>&1 | tee -a "$LOG_FILE"
EXIT_CODE=${PIPESTATUS[0]}

if [ "$EXIT_CODE" -eq 0 ]; then
  echo "Deploy BD: OK" | tee -a "$LOG_FILE"
else
  echo "Deploy BD: errores (código $EXIT_CODE). Revise $LOG_FILE" | tee -a "$LOG_FILE"
fi

php artisan config:clear 2>/dev/null | tee -a "$LOG_FILE" || true
php artisan cache:clear 2>/dev/null | tee -a "$LOG_FILE" || true

# Smoke tests de todos los módulos (lectura segura)
SMOKE_EXIT=0
if [ -f "$ROOT/scripts/run_smoke_tests.sh" ]; then
  echo "smoke modules..." | tee -a "$LOG_FILE"
  set +e
  SMOKE_BASE="${SMOKE_BASE:-http://127.0.0.1}" bash "$ROOT/scripts/run_smoke_tests.sh" 2>&1 | tee -a "$LOG_FILE"
  SMOKE_EXIT=${PIPESTATUS[0]}
  set -e
  if [ "$SMOKE_EXIT" -eq 0 ]; then
    echo "Smoke modules: OK" | tee -a "$LOG_FILE"
  else
    echo "Smoke modules: FALLÓ (código $SMOKE_EXIT). Revise $LOG_FILE y storage/logs/smoke_modules_*.json" | tee -a "$LOG_FILE"
  fi
fi

if [ "$EXIT_CODE" -ne 0 ]; then
  exit "$EXIT_CODE"
fi
exit "$SMOKE_EXIT"
