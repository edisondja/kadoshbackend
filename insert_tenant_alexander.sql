-- =============================================================================
-- Registrar tenant CLINICA DR. ALEXANDER en la BD MAESTRA (tabla tenants)
-- =============================================================================
-- Endpoint / subdominio: alexanderserver.odontoed.com
-- BD del tenant:         tenant_alexander
-- Frontend config:       config_site_alexander.json
--
-- Ejecutar en la BD maestra (producción: clinica):
--   mysql -u usuario -p clinica < insert_tenant_alexander.sql
--
-- Luego:
--   CREATE DATABASE tenant_alexander CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   mysql -u usuario -p tenant_alexander < database/sql/tenant_actualizar_esquema.sql
-- =============================================================================

USE `clinica`;

INSERT INTO `tenants` (
  `nombre`,
  `subdominio`,
  `database_name`,
  `fecha_vencimiento`,
  `activo`,
  `bloqueado`,
  `notas`,
  `contacto_nombre`,
  `contacto_email`,
  `contacto_telefono`,
  `created_at`,
  `updated_at`
)
SELECT
  'Clinica Dr. Alexander',
  'alexanderserver',
  'tenant_alexander',
  NULL,
  1,
  0,
  'Tenant Dr. Alexander | https://alexanderserver.odontoed.com | config_site_alexander.json',
  'Dr. Alexander',
  NULL,
  NULL,
  NOW(),
  NOW()
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `tenants`
  WHERE `subdominio` = 'alexanderserver'
     OR `database_name` = 'tenant_alexander'
);

UPDATE `tenants`
SET
  `nombre` = 'Clinica Dr. Alexander',
  `subdominio` = 'alexanderserver',
  `database_name` = 'tenant_alexander',
  `activo` = 1,
  `bloqueado` = 0,
  `notas` = 'Tenant Dr. Alexander | https://alexanderserver.odontoed.com | config_site_alexander.json',
  `contacto_nombre` = 'Dr. Alexander',
  `updated_at` = NOW()
WHERE `subdominio` = 'alexanderserver'
   OR `database_name` = 'tenant_alexander';

-- Campos deploy (si ya corrió 2026_09_06_tenants_deploy_fields.sql)
SET @db := DATABASE();
SET @has_dominio := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tenants' AND COLUMN_NAME='dominio');
SET @sql := IF(@has_dominio>0,
  'UPDATE `tenants` SET `dominio`=''alexanderserver.odontoed.com'', `document_root`=''/var/www/alexanderserver.odontoed.com/public_html'', `api_url`=''https://alexanderserver.odontoed.com'' WHERE `subdominio`=''alexanderserver'' OR `database_name`=''tenant_alexander''',
  'SELECT ''OK sin columnas deploy (opcional)'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT `id`, `nombre`, `subdominio`, `database_name`, `activo`, `bloqueado`, `notas`
FROM `tenants`
WHERE `subdominio` = 'alexanderserver' OR `database_name` = 'tenant_alexander';
