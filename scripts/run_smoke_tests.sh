#!/usr/bin/env bash
# Smoke tests post-deploy. Exit 1 si algún módulo crítico falla.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

export SMOKE_BASE="${SMOKE_BASE:-http://127.0.0.1}"
export SMOKE_TIMEOUT="${SMOKE_TIMEOUT:-20}"

if [ -z "${SMOKE_HOST:-}" ] && [ -z "${SMOKE_TENANT_HOST:-}" ]; then
  # Default producción: primer tenant con dominio real si existe
  if [ -f .env ]; then
    # shellcheck disable=SC1091
    set +e
    # Preferencias conocidas
    for candidate in felix.noeodontoestetic.com alexanderserver.odontoed.com service.clinickadosh.com; do
      if curl -sS -m 3 -o /dev/null -w "%{http_code}" -H "Host: $candidate" "$SMOKE_BASE/api/mobile/ping" | grep -q '^200$'; then
        export SMOKE_HOST="$candidate"
        break
      fi
    done
    set -e
  fi
fi

if [ -z "${SMOKE_HOST:-}${SMOKE_TENANT_HOST:-}" ]; then
  echo "SMOKE_HOST no definido y no se detectó tenant. Ejemplo:"
  echo "  SMOKE_HOST=felix.noeodontoestetic.com ./scripts/run_smoke_tests.sh"
  exit 1
fi

echo ">>> Smoke modules ($SMOKE_BASE Host=${SMOKE_HOST:-$SMOKE_TENANT_HOST})"
php "$ROOT/scripts/smoke_modules.php"
