#!/bin/bash
# Configura Git para usar .githooks/ (post-merge → migraciones automáticas tras pull)

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

chmod +x .githooks/post-merge 2>/dev/null || true
chmod +x scripts/deploy_after_pull.sh 2>/dev/null || true

git config core.hooksPath .githooks

echo "✓ Hooks instalados: core.hooksPath = .githooks"
echo "  Tras cada 'git pull', se ejecutará: scripts/deploy_after_pull.sh"
echo ""
echo "Probar manualmente:"
echo "  ./scripts/deploy_after_pull.sh"
echo ""
echo "Solo migraciones Laravel (sin SQL sueltos):"
echo "  php artisan kadosh:migrate-tenants"
echo ""
echo "Con backup antes de migrar:"
echo "  php artisan kadosh:migrate-tenants --backup --sql"
