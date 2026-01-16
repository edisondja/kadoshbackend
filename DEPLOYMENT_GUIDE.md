# 🚀 Guía de Despliegue - Migraciones a Producción

Esta guía te ayudará a aplicar todas las nuevas migraciones a tu base de datos en producción de forma segura.

## 📋 Migraciones Nuevas Identificadas

Las siguientes migraciones han sido creadas y necesitan ser aplicadas:

### Migraciones de 2025-2026:
1. `2025_07_01_003622_create_configs_table.php` - Tabla de configuración
2. `2025_08_01_135747_create_logs_table.php` - Tabla de logs/auditoría
3. `2025_10_11_135117_create_odontogramas_table.php` - Tabla de odontogramas
4. `2025_10_13_224111_create_ficha_medicas_table.php` - Tabla de fichas médicas
5. `2026_01_13_005458_create_odontograma_detalles_table.php` - Detalles de odontogramas
6. `2026_01_15_012918_modify_odontogramas_dibujo_column.php` - Modificar columna dibujo
7. `2026_01_15_014626_create_pagos_mensuales_table.php` - Pagos mensuales
8. `2026_01_15_015032_add_fields_to_logs_table.php` - Campos adicionales en logs
9. `2026_01_15_020708_add_factura_fields_to_configs_table.php` - Campos de facturación
10. `2026_01_15_020917_add_google_event_id_to_citas_table.php` - Google Calendar
11. `2026_01_15_024552_add_comision_to_procedimientos_table.php` - Comisiones
12. `2026_01_15_024559_create_pagos_nomina_table.php` - Pagos de nómina
13. `2026_01_15_025557_create_salarios_doctores_table.php` - Salarios doctores
14. `2026_01_15_025602_add_tipo_to_facturas_table.php` - Tipo de factura
15. `2026_01_15_025617_add_precio_and_fields_to_productos_table.php` - Campos productos
16. `2026_01_15_030039_create_ventas_productos_table.php` - Ventas de productos
17. `2026_01_15_075020_create_recetas_table.php` - Recetas médicas
18. `2026_01_15_075625_add_especialidad_to_doctors_table.php` - Especialidades doctores
19. `2026_01_15_131225_create_especialidads_table.php` - Tabla de especialidades
20. `2026_01_16_003029_add_clave_secreta_to_configs_table.php` - Clave secreta

---

## ⚠️ PRE-DESPLIEGUE: Checklist de Seguridad

Antes de aplicar las migraciones, asegúrate de:

- [ ] **Backup completo de la base de datos**
- [ ] **Backup de archivos del proyecto**
- [ ] **Verificar que no hay usuarios activos** (o hacerlo en horario de mantenimiento)
- [ ] **Tener acceso SSH al servidor**
- [ ] **Tener credenciales de base de datos**
- [ ] **Probar primero en un ambiente de staging** (recomendado)

---

## 📦 Paso 1: Backup de Base de Datos

### Opción A: Backup Manual (MySQL)

```bash
# Conectarse al servidor
ssh usuario@tu-servidor.com

# Crear backup
mysqldump -u usuario_db -p nombre_base_datos > backup_$(date +%Y%m%d_%H%M%S).sql

# O con compresión
mysqldump -u usuario_db -p nombre_base_datos | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

### Opción B: Backup desde Laravel

```bash
cd /ruta/a/kadoshbackend
php artisan db:backup
```

### Opción C: Backup Remoto (si tienes acceso)

```bash
# Desde tu máquina local
mysqldump -h IP_SERVIDOR -u usuario -p nombre_db > backup_produccion.sql
```

**⚠️ IMPORTANTE**: Guarda el backup en un lugar seguro y verifica que se creó correctamente.

---

## 🔍 Paso 2: Verificar Estado Actual

### Ver qué migraciones ya están aplicadas:

```bash
cd /ruta/a/kadoshbackend
php artisan migrate:status
```

Esto te mostrará qué migraciones están pendientes.

### Verificar estructura actual de tablas:

```bash
# Verificar si existen las tablas nuevas
php artisan tinker
>>> Schema::hasTable('odontogramas')
>>> Schema::hasTable('pagos_mensuales')
>>> Schema::hasTable('recetas')
# etc...
```

---

## 🚀 Paso 3: Aplicar Migraciones

### Opción A: Aplicar Todas las Migraciones (Recomendado)

```bash
cd /ruta/a/kadoshbackend

# 1. Verificar estado
php artisan migrate:status

# 2. Aplicar migraciones (en modo producción)
php artisan migrate --force

# 3. Verificar que se aplicaron correctamente
php artisan migrate:status
```

### Opción B: Aplicar Migraciones Específicas (Si hay problemas)

Si alguna migración falla, puedes aplicarlas una por una:

```bash
# Aplicar migración específica
php artisan migrate --path=/database/migrations/2026_01_15_014626_create_pagos_mensuales_table.php --force
```

### Opción C: Usar el Script de Despliegue (Ver abajo)

---

## 🔄 Paso 4: Verificación Post-Migración

### Verificar que las tablas se crearon:

```bash
php artisan tinker
```

```php
// Verificar tablas nuevas
Schema::hasTable('odontogramas'); // Debe retornar true
Schema::hasTable('odontograma_detalles'); // Debe retornar true
Schema::hasTable('pagos_mensuales'); // Debe retornar true
Schema::hasTable('pagos_nomina'); // Debe retornar true
Schema::hasTable('salarios_doctores'); // Debe retornar true
Schema::hasTable('ventas_productos'); // Debe retornar true
Schema::hasTable('recetas'); // Debe retornar true
Schema::hasTable('especialidads'); // Debe retornar true

// Verificar columnas nuevas
Schema::hasColumn('facturas', 'tipo_factura'); // Debe retornar true
Schema::hasColumn('procedimientos', 'comision'); // Debe retornar true
Schema::hasColumn('doctors', 'especialidad'); // Debe retornar true
Schema::hasColumn('configs', 'clave_secreta'); // Debe retornar true
Schema::hasColumn('citas', 'google_event_id'); // Debe retornar true
```

### Verificar datos existentes:

```bash
# Verificar que los datos existentes no se afectaron
php artisan tinker
```

```php
// Contar registros en tablas existentes
DB::table('pacientes')->count();
DB::table('facturas')->count();
DB::table('doctors')->count();
// etc...
```

---

## 🔧 Paso 5: Actualizar Código del Frontend

Después de aplicar las migraciones, asegúrate de:

1. **Actualizar el código del frontend** en el servidor
2. **Compilar los assets**:

```bash
cd /ruta/a/kadosh
npm install
npm run build
```

3. **Verificar que la API responde correctamente**:

```bash
# Probar endpoint
curl http://tu-servidor.com/api/pacientes
```

---

## ⚡ Script de Despliegue Automatizado

He creado un script que automatiza el proceso. Guárdalo como `deploy_migrations.sh`:

```bash
#!/bin/bash

# Script de Despliegue de Migraciones - Kadosh
# Uso: ./deploy_migrations.sh

set -e  # Detener si hay errores

echo "🚀 Iniciando despliegue de migraciones..."

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Variables (ajusta según tu entorno)
DB_NAME="tu_base_datos"
DB_USER="tu_usuario"
BACKUP_DIR="./backups"
PROJECT_DIR="/ruta/a/kadoshbackend"

# Crear directorio de backups si no existe
mkdir -p $BACKUP_DIR

# Paso 1: Backup
echo -e "${YELLOW}📦 Creando backup de la base de datos...${NC}"
BACKUP_FILE="$BACKUP_DIR/backup_$(date +%Y%m%d_%H%M%S).sql"
mysqldump -u $DB_USER -p $DB_NAME > $BACKUP_FILE

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Backup creado: $BACKUP_FILE${NC}"
else
    echo -e "${RED}❌ Error al crear backup. Abortando...${NC}"
    exit 1
fi

# Paso 2: Verificar estado
echo -e "${YELLOW}🔍 Verificando estado de migraciones...${NC}"
cd $PROJECT_DIR
php artisan migrate:status

# Paso 3: Aplicar migraciones
echo -e "${YELLOW}🚀 Aplicando migraciones...${NC}"
php artisan migrate --force

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Migraciones aplicadas correctamente${NC}"
else
    echo -e "${RED}❌ Error al aplicar migraciones${NC}"
    echo -e "${YELLOW}💡 Para restaurar el backup: mysql -u $DB_USER -p $DB_NAME < $BACKUP_FILE${NC}"
    exit 1
fi

# Paso 4: Verificar
echo -e "${YELLOW}🔍 Verificando migraciones...${NC}"
php artisan migrate:status

echo -e "${GREEN}✅ Despliegue completado exitosamente${NC}"
echo -e "${YELLOW}💡 Backup guardado en: $BACKUP_FILE${NC}"
```

**Para usar el script:**

```bash
chmod +x deploy_migrations.sh
./deploy_migrations.sh
```

---

## 🔙 Rollback (Si algo sale mal)

### Opción A: Restaurar Backup

```bash
# Restaurar desde backup
mysql -u usuario_db -p nombre_base_datos < backup_YYYYMMDD_HHMMSS.sql
```

### Opción B: Revertir Migraciones Específicas

```bash
# Revertir última migración
php artisan migrate:rollback --step=1

# Revertir múltiples migraciones
php artisan migrate:rollback --step=5

# Revertir todas las migraciones (¡CUIDADO!)
php artisan migrate:reset
```

### Opción C: Revertir Migración Específica

Si necesitas revertir una migración específica, edita el método `down()` de la migración y ejecuta:

```bash
php artisan migrate:rollback --path=/database/migrations/nombre_migracion.php
```

---

## ⚠️ Problemas Comunes y Soluciones

### Error: "Table already exists"

```bash
# Verificar si la tabla existe
php artisan tinker
>>> Schema::hasTable('nombre_tabla')

# Si existe pero la migración no está registrada, puedes:
# 1. Marcar la migración como ejecutada (sin ejecutarla)
php artisan migrate --pretend

# 2. O eliminar la tabla manualmente (¡CUIDADO!)
# Solo si estás seguro de que está vacía o no la necesitas
```

### Error: "Foreign key constraint fails"

```bash
# Verificar datos huérfanos
php artisan tinker
>>> DB::table('tabla_hija')->whereNotIn('foreign_id', DB::table('tabla_padre')->pluck('id'))->get()

# Limpiar datos huérfanos antes de aplicar migración
```

### Error: "Column already exists"

```bash
# Verificar si la columna existe
php artisan tinker
>>> Schema::hasColumn('tabla', 'columna')

# Si existe, puedes modificar la migración para usar:
# $table->string('columna')->nullable()->change();
# en lugar de crear la columna
```

---

## 📝 Notas Importantes

1. **Horario de Mantenimiento**: Aplica las migraciones en horario de bajo tráfico
2. **Tiempo Estimado**: Las migraciones pueden tardar varios minutos dependiendo del tamaño de la BD
3. **Espacio en Disco**: Asegúrate de tener suficiente espacio para el backup
4. **Permisos**: Verifica que el usuario de la BD tenga permisos para crear tablas y modificar estructura
5. **Testing**: Siempre prueba primero en un ambiente de staging

---

## ✅ Checklist Final

Después del despliegue, verifica:

- [ ] Todas las migraciones se aplicaron correctamente
- [ ] Las tablas nuevas existen
- [ ] Las columnas nuevas existen
- [ ] Los datos existentes no se afectaron
- [ ] El frontend funciona correctamente
- [ ] La API responde correctamente
- [ ] Los nuevos módulos funcionan (odontogramas, recetas, etc.)
- [ ] El backup está guardado en lugar seguro

---

## 📞 Soporte

Si encuentras problemas durante el despliegue:

1. **Revisa los logs**: `storage/logs/laravel.log`
2. **Verifica permisos**: Asegúrate de que Laravel puede escribir en `storage/` y `bootstrap/cache/`
3. **Consulta la documentación**: Revisa los archivos de migración individuales
4. **Contacto**: edisondja@gmail.com

---

**¡Buena suerte con el despliegue! 🚀**
