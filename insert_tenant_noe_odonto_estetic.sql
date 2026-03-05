-- INSERT tenant: Noe Odonto Estetic
-- Base de datos: tenant_service
-- Ejecutar: mysql -u usuario -p tenant_service < insert_tenant_noe_odonto_estetic.sql

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
  'Noe Odonto Estetic',
  'noe',
  'kadosh_noe',
  NULL,
  1,
  0,
  'Clínica Noe Odonto Estetic',
  NULL,
  NULL,
  NULL,
  NOW(),
  NOW()
);
