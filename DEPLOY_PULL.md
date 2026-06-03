# Despliegue automático de base de datos tras `git pull`

## Instalación (una vez en el servidor)

```bash
cd /ruta/a/kadoshbackend
chmod +x scripts/*.sh .githooks/post-merge
./scripts/install_git_hooks.sh
```

Eso configura Git para que, **después de cada `git pull`**, se ejecute:

1. `composer install --no-dev` (si hay composer)
2. `php artisan kadosh:migrate-tenants --sql` en **cada tenant activo**
3. Limpieza de caché de Laravel

Log: `storage/logs/deploy_after_pull.log`

---

## Cómo decide qué bases de datos migrar

1. Si existe la tabla `tenants` en la BD maestra (`.env` → `DB_DATABASE`): migra cada `database_name` con `activo = 1`.
2. Si no hay tenants: migra solo `DB_DATABASE` del `.env`.

---

## Uso manual (sin pull)

```bash
# Todas las BD de tenants activos + SQL nuevos en database/sql/
php artisan kadosh:migrate-tenants --sql

# Solo la BD del .env (clínica única)
php artisan kadosh:migrate-tenants --skip-tenants-table --sql

# Una BD concreta
php artisan kadosh:migrate-tenants --database=nombre_clinica_db --sql

# Con backup antes de migrar
php artisan kadosh:migrate-tenants --backup --sql
```

O el script completo:

```bash
./scripts/deploy_after_pull.sh
```

---

## Archivos SQL en `database/sql/`

- Deben ser **idempotentes** (`IF NOT EXISTS`, etc.).
- No usen `USE nombre_db;` (el comando ya apunta a cada tenant).
- Cada archivo se aplica **una sola vez** por base de datos (tabla `schema_sql_applied`).
- Los cambios nuevos deben preferir **migraciones Laravel** en `database/migrations/`.

---

## Nuevos cambios de esquema

1. Crear migración: `php artisan make:migration descripcion_cambio`
2. Commit y push.
3. En producción: `git pull` → se aplican solas (si instalaste los hooks).

Para SQL sueltos opcionales, añadir archivo en `database/sql/` con nombre ordenable por fecha, ej. `2026_06_02_mi_cambio.sql`.
