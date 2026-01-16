# 🏢 Guía de Despliegue Multi-Tenant - Kadosh

Esta guía está diseñada específicamente para entornos **multi-tenant** donde tienes múltiples bases de datos (una por cada tenant/cliente).

---

## 🎯 Escenario Multi-Tenant

En un sistema multi-tenant, cada cliente tiene su propia base de datos. Esto significa que necesitas:

1. ✅ Identificar todas las bases de datos tenant
2. ✅ Aplicar las migraciones a cada una
3. ✅ Hacer backup de cada base de datos
4. ✅ Verificar el estado de cada una
5. ✅ Manejar errores de forma individual

---

## 🚀 Método 1: Script Automatizado (Recomendado)

### Usar el script multi-tenant:

```bash
cd kadoshbackend
chmod +x deploy_migrations_multi_tenant.sh
./deploy_migrations_multi_tenant.sh
```

### El script:

1. **Lista todas las bases de datos** disponibles
2. **Te permite filtrar** por patrón (ej: `kadosh_`, `tenant_`)
3. **Crea backup** de cada base de datos
4. **Aplica migraciones** a cada una
5. **Verifica** el estado de cada una
6. **Genera un reporte** final con éxitos y fallos

---

## 🔧 Método 2: Script Personalizado con Lista de Tenants

Si tienes una lista específica de bases de datos tenant, crea un script personalizado:

```bash
#!/bin/bash

# Lista de bases de datos tenant
TENANT_DATABASES=(
    "kadosh_tenant1"
    "kadosh_tenant2"
    "kadosh_tenant3"
    "kadosh_clinica_abc"
    "kadosh_clinica_xyz"
)

DB_USER="tu_usuario"
DB_PASS="tu_contraseña"
BACKUP_DIR="./backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

for DB_NAME in "${TENANT_DATABASES[@]}"; do
    echo "Procesando: $DB_NAME"
    
    # Backup
    mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > "$BACKUP_DIR/backup_${DB_NAME}_${TIMESTAMP}.sql"
    
    # Actualizar .env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
    
    # Aplicar migraciones
    php artisan migrate --force
    
    echo "✅ Completado: $DB_NAME"
    echo ""
done
```

---

## 🔍 Método 3: Identificar Tenants desde Base de Datos Maestra

Si tienes una base de datos maestra que lista todos los tenants:

```bash
#!/bin/bash

# Base de datos maestra
MASTER_DB="kadosh_master"
DB_USER="tu_usuario"
DB_PASS="tu_contraseña"

# Obtener lista de tenants desde la BD maestra
TENANT_DATABASES=$(mysql -u $DB_USER -p$DB_PASS $MASTER_DB -e \
    "SELECT database_name FROM tenants WHERE active = 1;" \
    -N 2>/dev/null)

# Procesar cada tenant
for DB_NAME in $TENANT_DATABASES; do
    echo "Procesando tenant: $DB_NAME"
    # ... resto del código
done
```

---

## 📋 Método 4: Manual con Laravel Tinker

Si prefieres hacerlo manualmente con más control:

```bash
php artisan tinker
```

```php
// Listar todas las bases de datos
$databases = DB::select("SHOW DATABASES");
$tenantDbs = array_filter($databases, function($db) {
    return strpos($db->Database, 'kadosh_') === 0 || 
           strpos($db->Database, 'tenant_') === 0;
});

// Para cada tenant
foreach($tenantDbs as $db) {
    $dbName = $db->Database;
    echo "Procesando: $dbName\n";
    
    // Cambiar conexión
    Config::set('database.connections.mysql.database', $dbName);
    DB::purge('mysql');
    
    // Aplicar migraciones
    Artisan::call('migrate', ['--force' => true]);
    
    echo "✅ Completado: $dbName\n";
}
```

---

## 🛠️ Método 5: Usando Laravel Multi-Tenancy Package

Si estás usando un paquete como `tenancy/tenancy` o `stancl/tenancy`:

```bash
# El paquete maneja automáticamente las migraciones
php artisan tenants:migrate

# O con opciones específicas
php artisan tenants:migrate --tenants=tenant1,tenant2
php artisan tenants:migrate --path=database/migrations/2026
```

---

## ⚙️ Configuración Recomendada

### Opción A: Archivo de Configuración de Tenants

Crea un archivo `config/tenants.php`:

```php
<?php

return [
    'databases' => [
        'kadosh_tenant1',
        'kadosh_tenant2',
        'kadosh_clinica_abc',
        // ... más tenants
    ],
    
    'pattern' => 'kadosh_%', // Patrón para identificar tenants
    
    'backup_path' => storage_path('backups'),
];
```

### Opción B: Tabla de Tenants en BD Maestra

```sql
CREATE TABLE tenants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    database_name VARCHAR(255),
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 🔄 Proceso Paso a Paso

### 1. Identificar Todas las Bases de Datos Tenant

```bash
# Listar todas las bases de datos
mysql -u usuario -p -e "SHOW DATABASES;"

# Filtrar por patrón
mysql -u usuario -p -e "SHOW DATABASES;" | grep "kadosh_"
```

### 2. Backup de Todas las Bases de Datos

```bash
# Script para backup masivo
for db in $(mysql -u usuario -p -e "SHOW DATABASES;" | grep "kadosh_"); do
    mysqldump -u usuario -p $db > backup_${db}_$(date +%Y%m%d).sql
done
```

### 3. Aplicar Migraciones a Cada Tenant

Usa el script `deploy_migrations_multi_tenant.sh` o el método que prefieras.

### 4. Verificar Estado de Cada Tenant

```bash
# Para cada tenant, verificar
php artisan migrate:status
```

---

## ⚠️ Consideraciones Importantes

### 1. **Tiempo de Ejecución**
- Con muchos tenants, el proceso puede tardar mucho tiempo
- Considera ejecutar en horario de mantenimiento
- Puedes paralelizar si tienes recursos

### 2. **Espacio en Disco**
- Cada backup ocupa espacio
- Con 20 tenants, necesitarás ~20x el tamaño de una BD
- Considera comprimir los backups

### 3. **Rollback Individual**
- Si un tenant falla, puedes restaurar solo ese tenant
- Los demás tenants no se afectan

### 4. **Monitoreo**
- Monitorea el progreso del script
- Revisa los logs de cada tenant
- Verifica que todos los tenants funcionen después

---

## 🔙 Rollback Multi-Tenant

### Rollback de un Tenant Específico

```bash
# Restaurar un tenant específico
mysql -u usuario -p kadosh_tenant1 < backups/backup_kadosh_tenant1_20260116.sql
```

### Rollback de Todos los Tenants

```bash
# Restaurar todos los tenants desde backups
for backup in backups/backup_kadosh_*_20260116.sql; do
    DB_NAME=$(echo $backup | sed 's/.*backup_\(.*\)_20260116\.sql/\1/')
    mysql -u usuario -p $DB_NAME < $backup
done
```

---

## 📊 Reporte Post-Despliegue

Después del despliegue, verifica:

```bash
# Script de verificación
for db in $(mysql -u usuario -p -e "SHOW DATABASES;" | grep "kadosh_"); do
    echo "Verificando: $db"
    # Actualizar .env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=$db/" .env
    # Verificar migraciones
    php artisan migrate:status
    echo ""
done
```

---

## 🎯 Mejores Prácticas

1. ✅ **Siempre haz backup primero**
2. ✅ **Prueba en un tenant de desarrollo primero**
3. ✅ **Aplica en horario de bajo tráfico**
4. ✅ **Monitorea el progreso**
5. ✅ **Verifica cada tenant después del despliegue**
6. ✅ **Ten un plan de rollback listo**
7. ✅ **Documenta qué tenants se procesaron**

---

## 📞 Soporte

Si encuentras problemas:

1. Revisa los logs: `storage/logs/laravel.log`
2. Verifica permisos de base de datos
3. Consulta los backups creados
4. Contacto: edisondja@gmail.com

---

**¡Despliegue Multi-Tenant exitoso! 🚀**
