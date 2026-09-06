-- =============================================================================
-- MÓDULO SESIONES ACTIVAS — UNA SOLA TABLA
-- Ejecutar en la misma base donde están pacientes, usuarios, facturas, etc.
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned NOT NULL,
  `jti` varchar(64) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `user_agent` text,
  `dispositivo` varchar(120) DEFAULT NULL,
  `navegador` varchar(120) DEFAULT NULL,
  `last_active_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_sessions_jti_unique` (`jti`),
  KEY `user_sessions_usuario_idx` (`usuario_id`),
  KEY `user_sessions_activas_idx` (`revoked_at`, `expires_at`),
  CONSTRAINT `user_sessions_usuario_fk` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
