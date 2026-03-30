-- =============================================================================
-- Invitación por correo para registro de odontólogo + vínculo usuario/doctor
-- Equivalente a: database/migrations/2026_03_29_000001_doctor_invitaciones_and_doctor_columns.php
-- Ejecutar en la base de datos de cada clínica (tenant) que use el módulo.
-- Motor: MySQL / MariaDB (utf8mb4)
-- =============================================================================

-- Tabla de tokens de un solo uso para completar usuario/contraseña
CREATE TABLE IF NOT EXISTS `doctor_invitaciones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(80) NOT NULL,
  `id_doctor` int unsigned NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doctor_invitaciones_token_unique` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columnas en doctors (si ya existen, MySQL devolverá error 1060; omita esas líneas)
ALTER TABLE `doctors`
  ADD COLUMN `correo_electronico` varchar(191) NULL;

ALTER TABLE `doctors`
  ADD COLUMN `id_usuario` int unsigned NULL;

-- Opcional: índice para buscar doctor por usuario vinculado
-- CREATE INDEX `doctors_id_usuario_index` ON `doctors` (`id_usuario`);

-- =============================================================================
-- REVERTIR (solo si necesita deshacer los cambios)
-- =============================================================================
-- DROP TABLE IF EXISTS `doctor_invitaciones`;
-- ALTER TABLE `doctors` DROP COLUMN `id_usuario`;
-- ALTER TABLE `doctors` DROP COLUMN `correo_electronico`;
