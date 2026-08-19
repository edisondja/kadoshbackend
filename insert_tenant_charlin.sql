-- INSERT tenant: Clinica Charlin
-- Base de datos: tenant_service
-- Ejecutar: mysql -u usuario -p tenant_service < insert_tenant_charlin.sql

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
  'Clinica Charlin',
  'dentalcharlinserver',
  'tenant_dentalcharlinserver',
  NULL,
  1,
  0,
  'Clinica Dental Dra. Charline Hernández',
  NULL,
  NULL,
  NULL,
  NOW(),
  NOW()
);
