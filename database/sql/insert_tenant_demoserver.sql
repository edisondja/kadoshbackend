-- =============================================================================
-- Registrar tenant demo en la BASE DE DATOS PRINCIPAL (tabla tenants)
-- =============================================================================
-- La tabla `tenants` vive en la BD maestra (no en el tenant).
-- Ajuste el nombre de la BD maestra si no es tenant_service:
--   USE `tenant_service`;   -- o `odontoed`, según su .env DB_DATABASE
--
-- Ejecutar:
--   mysql -u usuario -p tenant_service < insert_tenant_demoserver.sql
--
-- Acceso clínica:  https://demo.odontoed.com/
-- API (reservado): https://demoserver.odontoed.com/  → usa .env, no este subdominio
-- BD del tenant:   tenant_demoserver  (crear la BD y ejecutar tenant_actualizar_esquema.sql ahí)
-- =============================================================================

-- Cambie el nombre si su BD maestra es otra:
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
  'Odontoed Demo',
  'demo',
  'tenant_demoserver',
  '2027-12-31',
  1,
  0,
  'Tenant de demostración / demoserver',
  'Edison De Jesus Abreu',
  'serive@odontoed.com',
  '829-439-8878',
  NOW(),
  NOW()
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `tenants` WHERE `subdominio` = 'demo'
);

-- Si ya existía demo pero con otro database_name, actualizar:
UPDATE `tenants`
SET
  `nombre` = 'Odontoed Demo',
  `database_name` = 'tenant_demoserver',
  `fecha_vencimiento` = COALESCE(`fecha_vencimiento`, '2027-12-31'),
  `activo` = 1,
  `bloqueado` = 0,
  `notas` = 'Tenant de demostración / demoserver',
  `contacto_nombre` = 'Edison De Jesus Abreu',
  `contacto_email` = 'serive@odontoed.com',
  `contacto_telefono` = '829-439-8878',
  `updated_at` = NOW()
WHERE `subdominio` = 'demo';

SELECT `id`, `nombre`, `subdominio`, `database_name`, `activo`, `bloqueado`, `fecha_vencimiento`
FROM `tenants`
WHERE `subdominio` = 'demo' OR `database_name` = 'tenant_demoserver';
