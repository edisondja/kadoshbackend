-- =============================================================================
-- Registrar tenant ISALEX DENTAL CLINIC EIRL en la BD PRINCIPAL (tabla tenants)
-- =============================================================================
-- La tabla `tenants` vive en la BD maestra (no en el tenant).
--
-- Ejecutar en la BD maestra:
--   mysql -u usuario -p tenant_service < database/sql/insert_tenant_isalex.sql
--
-- Acceso típico:   https://isalex.odontoed.com/
-- BD del tenant:   tenant_isalex
--   1) CREATE DATABASE tenant_isalex CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   2) Importar esquema / ejecutar tenant_actualizar_esquema.sql en tenant_isalex
--   3) Cargar configs (RNC, logo, etc.) dentro de esa BD
--
-- Datos clínica:
--   RNC:       133-05706-9
--   Teléfono:  829-942-3798
--   Dirección: Av. Las palmas esq. Respaldo 4, plaza Juan Carlos, local #01,
--              Las palmas de Herrera Santo Domingo, Dominican Republic
-- =============================================================================

USE `tenant_service`;

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
  'ISALEX DENTAL CLINIC EIRL',
  'isalex',
  'tenant_isalex',
  NULL,
  1,
  0,
  'RNC 133-05706-9 | Av. Las palmas esq. Respaldo 4, plaza Juan Carlos, local #01, Las palmas de Herrera Santo Domingo, Dominican Republic',
  'ISALEX DENTAL CLINIC EIRL',
  NULL,
  '829-942-3798',
  NOW(),
  NOW()
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `tenants` WHERE `subdominio` = 'isalex' OR `database_name` = 'tenant_isalex'
);

UPDATE `tenants`
SET
  `nombre` = 'ISALEX DENTAL CLINIC EIRL',
  `database_name` = 'tenant_isalex',
  `activo` = 1,
  `bloqueado` = 0,
  `notas` = 'RNC 133-05706-9 | Av. Las palmas esq. Respaldo 4, plaza Juan Carlos, local #01, Las palmas de Herrera Santo Domingo, Dominican Republic',
  `contacto_nombre` = 'ISALEX DENTAL CLINIC EIRL',
  `contacto_telefono` = '829-942-3798',
  `updated_at` = NOW()
WHERE `subdominio` = 'isalex' OR `database_name` = 'tenant_isalex';

SELECT `id`, `nombre`, `subdominio`, `database_name`, `activo`, `bloqueado`, `contacto_telefono`, `fecha_vencimiento`
FROM `tenants`
WHERE `subdominio` = 'isalex' OR `database_name` = 'tenant_isalex';
