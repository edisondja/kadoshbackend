-- INSERT tenant: SOS
-- Base de datos: tenant_service
-- Ejecutar: mysql -u usuario -p tenant_service < insert_tenant_sos.sql

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
  'SOS',
  'sos',
  'kadosh_sos',
  NULL,
  1,
  0,
  'Tenant SOS',
  NULL,
  NULL,
  NULL,
  NOW(),
  NOW()
);
