# 🏢 Arquitectura Multi-Tenant - Kadosh

## 📋 Resumen de la Arquitectura

El sistema Kadosh utiliza una **arquitectura multi-tenant con bases de datos separadas**. Esto significa:

1. **Base de Datos Maestra**: Contiene la tabla `tenants` que administra todos los subdominios
2. **Bases de Datos por Tenant**: Cada cliente tiene su propia base de datos independiente

---

## 🗄️ Estructura de Bases de Datos

### Base de Datos Maestra (Principal)

**Nombre**: La base de datos configurada en `.env` (ej: `odontoed`, `kadosh_master`)

**Contiene**:
- Tabla `tenants` - Administra todos los subdominios y sus configuraciones
- Otras tablas administrativas si las necesitas

**Propósito**: 
- Centralizar la administración de tenants
- Verificar acceso y estado de cada tenant
- Almacenar información de contacto y vencimientos

### Bases de Datos por Tenant

**Nombre**: Configurado en el campo `database_name` de la tabla `tenants`

**Ejemplos**:
- `kadosh_clinica1`
- `kadosh_clinica2`
- `tenant_abc`
- `clinica_xyz`

**Contiene**:
- Todas las tablas de la aplicación (pacientes, facturas, doctores, etc.)
- Cada tenant tiene sus propios datos completamente aislados

---

## 🔧 Configuración

### 1. Base de Datos Maestra

La base de datos maestra debe tener la tabla `tenants`:

```sql
-- Ejecutar en la base de datos maestra
CREATE TABLE `tenants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `subdominio` varchar(100) NOT NULL,
  `database_name` varchar(255) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `bloqueado` tinyint(1) NOT NULL DEFAULT '0',
  `notas` text DEFAULT NULL,
  `contacto_nombre` varchar(255) DEFAULT NULL,
  `contacto_email` varchar(255) DEFAULT NULL,
  `contacto_telefono` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenants_subdominio_unique` (`subdominio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Crear Base de Datos para Cada Tenant

Para cada tenant, necesitas:

1. **Crear la base de datos**:
```sql
CREATE DATABASE `kadosh_clinica1` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Aplicar las migraciones**:
```bash
# Cambiar temporalmente el .env
DB_DATABASE=kadosh_clinica1

# Aplicar migraciones
php artisan migrate --force
```

3. **Registrar el tenant en la tabla `tenants`**:
```sql
INSERT INTO `tenants` (
    `nombre`, 
    `subdominio`, 
    `database_name`, 
    `activo`, 
    `bloqueado`,
    `created_at`,
    `updated_at`
) VALUES (
    'Clínica ABC',
    'clinica1',
    'kadosh_clinica1',
    1,
    0,
    NOW(),
    NOW()
);
```

---

## 🌐 Funcionamiento del Sistema

### Flujo de Petición

1. **Usuario accede**: `clinica1.odontoed.com`
2. **Middleware detecta subdominio**: `clinica1`
3. **Consulta tabla `tenants`**: Busca el tenant con `subdominio = 'clinica1'`
4. **Verifica acceso**: Comprueba si está activo, no bloqueado y no vencido
5. **Configura conexión**: Usa el `database_name` del tenant (ej: `kadosh_clinica1`)
6. **Aplica la petición**: Todas las consultas van a la base de datos del tenant

### Ejemplo de Configuración

```php
// En la tabla tenants:
id: 1
nombre: "Clínica ABC"
subdominio: "clinica1"
database_name: "kadosh_clinica1"
activo: 1
bloqueado: 0
fecha_vencimiento: "2026-12-31"
```

**Resultado**:
- URL: `clinica1.odontoed.com` → Conecta a `kadosh_clinica1`
- URL: `clinica2.odontoed.com` → Conecta a `kadosh_clinica2`
- URL: `odontoed.com` (sin subdominio) → Usa base de datos por defecto

---

## 📝 Ventajas de esta Arquitectura

### ✅ Aislamiento Total
- Cada tenant tiene sus propios datos completamente separados
- No hay riesgo de que un tenant acceda a datos de otro
- Fácil de hacer backup/restore individual

### ✅ Escalabilidad
- Puedes mover bases de datos a diferentes servidores
- Fácil de distribuir la carga
- Cada tenant puede tener su propio servidor si crece mucho

### ✅ Seguridad
- Si una base de datos se compromete, las demás no se afectan
- Puedes dar permisos específicos por base de datos
- Fácil de auditar y cumplir con regulaciones

### ✅ Mantenimiento
- Puedes actualizar un tenant sin afectar a otros
- Fácil de hacer rollback individual
- Migraciones independientes por tenant

---

## ⚠️ Consideraciones

### Desventajas

1. **Más bases de datos que administrar**
   - Necesitas aplicar migraciones a cada una
   - Más backups que gestionar

2. **Recursos del servidor**
   - Cada base de datos consume memoria
   - Con muchos tenants, necesitas más recursos

3. **Complejidad de despliegue**
   - Las migraciones deben aplicarse a todas las bases de datos
   - Scripts automatizados son esenciales

### Alternativa: Base de Datos Compartida

Si prefieres una sola base de datos con separación por `tenant_id`:

**Ventajas**:
- Más fácil de administrar
- Menos recursos
- Migraciones más simples

**Desventajas**:
- Menos aislamiento
- Más complejo de escalar
- Backup/restore más complicado

---

## 🚀 Scripts de Administración

### Crear Nuevo Tenant

```bash
#!/bin/bash
# crear_tenant.sh

SUBDOMINIO=$1
NOMBRE=$2
DB_NAME="kadosh_${SUBDOMINIO}"

# Crear base de datos
mysql -u root -p -e "CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Aplicar migraciones
DB_DATABASE=$DB_NAME php artisan migrate --force

# Registrar en tabla tenants
mysql -u root -p odontoed -e "
INSERT INTO tenants (nombre, subdominio, database_name, activo, bloqueado, created_at, updated_at)
VALUES ('${NOMBRE}', '${SUBDOMINIO}', '${DB_NAME}', 1, 0, NOW(), NOW());
"
```

**Uso**:
```bash
./crear_tenant.sh clinica3 "Clínica XYZ"
```

### Aplicar Migraciones a Todos los Tenants

Ver el archivo `MULTI_TENANT_DEPLOY.md` para scripts completos.

---

## 🔍 Verificación

### Verificar que un Tenant Está Configurado Correctamente

```sql
-- En la base de datos maestra
SELECT 
    id,
    nombre,
    subdominio,
    database_name,
    activo,
    bloqueado,
    fecha_vencimiento,
    CASE 
        WHEN bloqueado = 1 THEN 'Bloqueado'
        WHEN activo = 0 THEN 'Inactivo'
        WHEN fecha_vencimiento < CURDATE() THEN 'Vencido'
        ELSE 'Activo'
    END as estado
FROM tenants;
```

### Verificar que la Base de Datos del Tenant Existe

```sql
SHOW DATABASES LIKE 'kadosh_%';
```

---

## 📞 Soporte

Si tienes dudas sobre la arquitectura:
- Revisa `MULTI_TENANT_DEPLOY.md` para scripts de despliegue
- Consulta `app/Http/Middleware/TenantMiddleware.php` para el código
- Contacto: edisondja@gmail.com

---

**¡Arquitectura Multi-Tenant lista! 🚀**
