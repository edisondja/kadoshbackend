-- INSERT de ejemplo para la tabla tenants
-- Base de datos: tenant_service (o la que uses para multi-tenant)
-- Ejecutar: mysql -u usuario -p tenant_service < insert_tenant_example.sql

USE tenant_service;

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
) VALUES (
  'Clínica Principal',           -- nombre
  'principal',                   -- subdominio (único)
  'kadosh_principal',            -- database_name (nombre de la BD del tenant)
  '2026-12-31',                  -- fecha_vencimiento (NULL = sin vencimiento)
  1,                             -- activo (1 = sí, 0 = no)
  0,                             -- bloqueado (0 = no bloqueado)
  'Tenant de ejemplo',           -- notas (opcional)
  'Admin Clínica',               -- contacto_nombre (opcional)
  'admin@clinica.com',           -- contacto_email (opcional)
  '809-555-0000',                -- contacto_telefono (opcional)
  NOW(),
  NOW()
);

-- Para más tenants, repite el INSERT con otros valores (subdominio debe ser único).
-- Ejemplo segundo tenant:
-- INSERT INTO `tenants` (nombre, subdominio, database_name, fecha_vencimiento, activo, bloqueado, created_at, updated_at)
-- VALUES ('Sucursal Norte', 'norte', 'kadosh_norte', NULL, 1, 0, NOW(), NOW());
