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

exit "$EXIT_CODE"
