-- INSERT tenant: DentalAdvence
-- Base de datos: tenant_service
-- Ejecutar: mysql -u usuario -p tenant_service < insert_tenant_dentaladvence.sql

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
  'DentalAdvence',
  'dentaladvence',
  'kadosh_dentaladvence',
  NULL,
  1,
  0,
  'Tenant DentalAdvence',
  NULL,
  NULL,
  NULL,
  NOW(),
  NOW()
);
