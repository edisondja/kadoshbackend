-- Script para crear la tabla tenants
-- Ejecuta este script directamente en MySQL

SET FOREIGN_KEY_CHECKS=0;

-- Crear la tabla tenants si no existe
CREATE TABLE IF NOT EXISTS `tenants` (
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

SET FOREIGN_KEY_CHECKS=1;

-- Verificar que la tabla se creó correctamente
SHOW COLUMNS FROM `tenants`;
