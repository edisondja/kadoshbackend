-- Tabla de administradores del sistema (BD maestra).
-- Ejecutar en la misma base de datos donde está la tabla tenants (ej: odontoed).

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `apellido` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_users_usuario_unique` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar primer administrador (usuario: admin, contraseña: Meteoro2412)
INSERT INTO `admin_users` (`usuario`, `password`, `nombre`, `apellido`, `activo`, `created_at`, `updated_at`)
VALUES ('admin', '$2y$10$PfIwjVHxVQMfQTWYuPayxeIrPoVRhqW0XRVt1OlRWeoqEI6CFQztO', 'Administrador', 'Sistema', 1, NOW(), NOW());
