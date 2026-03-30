-- Tabla de roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_nombre_unique` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permisos por módulo para cada rol
CREATE TABLE IF NOT EXISTS `role_modulos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_rol` int unsigned NOT NULL,
  `modulo` varchar(80) NOT NULL,
  `permitido` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_modulos_id_rol_modulo_unique` (`id_rol`, `modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vínculo usuario -> rol
ALTER TABLE `usuarios`
  ADD COLUMN `id_rol` int unsigned NULL;

