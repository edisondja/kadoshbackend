-- =============================================================================
-- KADOSH / OdontoED — TODOS LOS CAMBIOS DE BASE DE DATOS (consolidado)
-- =============================================================================
-- Ejecutar en la base de datos de CADA clínica (tenant).
-- Motor: MySQL 5.7+ / MariaDB 10.2+
-- Charset: utf8mb4
--
-- INSTRUCCIONES:
--   1. Seleccione la BD del tenant:  USE `nombre_de_su_clinica`;
--   2. Ejecute este script completo.
--   3. Repita en cada base de datos de clínica (multi-tenant).
--
-- INCLUYE:
--   • usuarios.permisos, usuarios.id_rol, usuarios.foto_usuario
--   • Tablas roles, role_modulos
--   • doctor_invitaciones + doctors.correo_electronico, doctors.id_usuario
--   • doctors.porcentaje_ingresos
--   • configs.formato_hora_citas
--   • pacientes.cedula nullable
--   • paciente_invitaciones
--   • Permiso exportar_importar en role_modulos (datos)
-- =============================================================================

SET NAMES utf8mb4;
SET @db := DATABASE();

SELECT CONCAT('Ejecutando en base de datos: ', @db) AS info;

-- =============================================================================
-- 1) usuarios.permisos
-- =============================================================================
SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'permisos'
);
SET @sql := IF(@exist = 0,
  'ALTER TABLE `usuarios` ADD COLUMN `permisos` TEXT NULL',
  'SELECT ''Columna usuarios.permisos ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 2) Tabla roles
-- =============================================================================
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

-- =============================================================================
-- 3) Tabla role_modulos
-- =============================================================================
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

-- =============================================================================
-- 4) usuarios.id_rol
-- =============================================================================
SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'id_rol'
);
SET @sql := IF(@exist = 0,
  'ALTER TABLE `usuarios` ADD COLUMN `id_rol` int unsigned NULL',
  'SELECT ''Columna usuarios.id_rol ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 5) usuarios.foto_usuario
-- =============================================================================
SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'foto_usuario'
);
SET @sql := IF(@exist = 0,
  'ALTER TABLE `usuarios` ADD COLUMN `foto_usuario` VARCHAR(255) NULL DEFAULT NULL AFTER `apellido`',
  'SELECT ''Columna usuarios.foto_usuario ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 6) doctor_invitaciones
-- =============================================================================
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

-- =============================================================================
-- 7) doctors.correo_electronico
-- =============================================================================
SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'correo_electronico'
);
SET @sql := IF(@exist = 0,
  'ALTER TABLE `doctors` ADD COLUMN `correo_electronico` varchar(191) NULL',
  'SELECT ''Columna doctors.correo_electronico ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 8) doctors.id_usuario
-- =============================================================================
SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'id_usuario'
);
SET @sql := IF(@exist = 0,
  'ALTER TABLE `doctors` ADD COLUMN `id_usuario` int unsigned NULL',
  'SELECT ''Columna doctors.id_usuario ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 9) doctors.porcentaje_ingresos
-- =============================================================================
SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'porcentaje_ingresos'
);
SET @sql := IF(@exist = 0,
  'ALTER TABLE `doctors` ADD COLUMN `porcentaje_ingresos` DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER `estado`',
  'SELECT ''Columna doctors.porcentaje_ingresos ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 10) configs.formato_hora_citas  (12h | 24h)
-- =============================================================================
SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'configs' AND COLUMN_NAME = 'formato_hora_citas'
);
SET @has_recordatorio := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'configs' AND COLUMN_NAME = 'recordatorio_minutos'
);
SET @sql := IF(@exist = 0 AND @has_recordatorio > 0,
  'ALTER TABLE `configs` ADD COLUMN `formato_hora_citas` VARCHAR(10) NOT NULL DEFAULT ''12h'' AFTER `recordatorio_minutos`',
  IF(@exist = 0,
    'ALTER TABLE `configs` ADD COLUMN `formato_hora_citas` VARCHAR(10) NOT NULL DEFAULT ''12h''',
    'SELECT ''Columna configs.formato_hora_citas ya existe'' AS info'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `configs`
SET `formato_hora_citas` = '12h'
WHERE `formato_hora_citas` IS NULL
   OR TRIM(`formato_hora_citas`) = ''
   OR `formato_hora_citas` NOT IN ('12h', '24h');

-- =============================================================================
-- 11) pacientes.cedula — opcional
-- =============================================================================
SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pacientes' AND COLUMN_NAME = 'cedula'
);
SET @sql := IF(@exist > 0,
  'ALTER TABLE `pacientes` MODIFY COLUMN `cedula` VARCHAR(255) NULL DEFAULT NULL',
  'SELECT ''Tabla/columna pacientes.cedula no encontrada'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 12) paciente_invitaciones
-- =============================================================================
CREATE TABLE IF NOT EXISTS `paciente_invitaciones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(80) NOT NULL,
  `id_doctor` int unsigned NOT NULL,
  `telefono_destino` varchar(40) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paciente_invitaciones_token_unique` (`token`),
  KEY `paciente_invitaciones_id_doctor_index` (`id_doctor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 13) Permiso exportar_importar para todos los roles existentes
-- =============================================================================
INSERT INTO `role_modulos` (`id_rol`, `modulo`, `permitido`, `created_at`, `updated_at`)
SELECT r.id, 'exportar_importar', 1, NOW(), NOW()
FROM `roles` r
WHERE NOT EXISTS (
  SELECT 1 FROM `role_modulos` rm
  WHERE rm.id_rol = r.id AND rm.modulo = 'exportar_importar'
);

-- =============================================================================
-- VERIFICACIÓN FINAL
-- =============================================================================
SELECT 'usuarios.permisos' AS cambio,
       IF(COUNT(*) > 0, 'OK', 'FALTA') AS estado
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'permisos'

UNION ALL SELECT 'usuarios.id_rol',
       IF(COUNT(*) > 0, 'OK', 'FALTA')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'id_rol'

UNION ALL SELECT 'usuarios.foto_usuario',
       IF(COUNT(*) > 0, 'OK', 'FALTA')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'foto_usuario'

UNION ALL SELECT 'tabla roles',
       IF(COUNT(*) > 0, 'OK', 'FALTA')
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'roles'

UNION ALL SELECT 'tabla role_modulos',
       IF(COUNT(*) > 0, 'OK', 'FALTA')
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'role_modulos'

UNION ALL SELECT 'tabla doctor_invitaciones',
       IF(COUNT(*) > 0, 'OK', 'FALTA')
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctor_invitaciones'

UNION ALL SELECT 'doctors.correo_electronico',
       IF(COUNT(*) > 0, 'OK', 'FALTA')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'correo_electronico'

UNION ALL SELECT 'doctors.id_usuario',
       IF(COUNT(*) > 0, 'OK', 'FALTA')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'id_usuario'

UNION ALL SELECT 'doctors.porcentaje_ingresos',
       IF(COUNT(*) > 0, 'OK', 'FALTA')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'porcentaje_ingresos'

UNION ALL SELECT 'configs.formato_hora_citas',
       IF(COUNT(*) > 0, 'OK', 'FALTA')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'configs' AND COLUMN_NAME = 'formato_hora_citas'

UNION ALL SELECT 'pacientes.cedula nullable',
       IF(IS_NULLABLE = 'YES', 'OK', 'REVISAR')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pacientes' AND COLUMN_NAME = 'cedula'

UNION ALL SELECT 'tabla paciente_invitaciones',
       IF(COUNT(*) > 0, 'OK', 'FALTA')
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'paciente_invitaciones';

-- =============================================================================
-- REVERTIR (solo si necesita deshacer — ejecutar manualmente)
-- =============================================================================
-- ALTER TABLE `usuarios` DROP COLUMN `permisos`;
-- ALTER TABLE `usuarios` DROP COLUMN `id_rol`;
-- ALTER TABLE `usuarios` DROP COLUMN `foto_usuario`;
-- DROP TABLE IF EXISTS `role_modulos`;
-- DROP TABLE IF EXISTS `roles`;
-- DROP TABLE IF EXISTS `doctor_invitaciones`;
-- ALTER TABLE `doctors` DROP COLUMN `correo_electronico`;
-- ALTER TABLE `doctors` DROP COLUMN `id_usuario`;
-- ALTER TABLE `doctors` DROP COLUMN `porcentaje_ingresos`;
-- ALTER TABLE `configs` DROP COLUMN `formato_hora_citas`;
-- ALTER TABLE `pacientes` MODIFY COLUMN `cedula` VARCHAR(255) NOT NULL DEFAULT '';
-- DROP TABLE IF EXISTS `paciente_invitaciones`;

-- =============================================================================
-- Presupuestos de consulta (sin paciente registrado)
-- =============================================================================
SET @fk_presupuesto_paciente := (
  SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'presupuestos'
    AND COLUMN_NAME = 'paciente_id' AND REFERENCED_TABLE_NAME IS NOT NULL
  LIMIT 1
);
SET @sql_drop_fk_presupuesto := IF(
  @fk_presupuesto_paciente IS NOT NULL,
  CONCAT('ALTER TABLE `presupuestos` DROP FOREIGN KEY `', @fk_presupuesto_paciente, '`'),
  'SELECT 1'
);
PREPARE stmt_presupuesto_fk FROM @sql_drop_fk_presupuesto;
EXECUTE stmt_presupuesto_fk;
DEALLOCATE PREPARE stmt_presupuesto_fk;
ALTER TABLE `presupuestos` MODIFY COLUMN `paciente_id` INT UNSIGNED NULL;

-- =============================================================================
-- Odontograma: posición de tarjetas conectadas (coordenadas normalizadas 0–1)
-- =============================================================================
SET @col_card_x := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'odontograma_detalles' AND COLUMN_NAME = 'card_pos_x_pct'
);
SET @sql := IF(
  @col_card_x = 0,
  'ALTER TABLE `odontograma_detalles` ADD COLUMN `card_pos_x_pct` DECIMAL(8,6) NULL DEFAULT NULL AFTER `color`',
  'SELECT ''odontograma_detalles.card_pos_x_pct OK'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_card_y := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'odontograma_detalles' AND COLUMN_NAME = 'card_pos_y_pct'
);
SET @sql := IF(
  @col_card_y = 0,
  'ALTER TABLE `odontograma_detalles` ADD COLUMN `card_pos_y_pct` DECIMAL(8,6) NULL DEFAULT NULL AFTER `card_pos_x_pct`',
  'SELECT ''odontograma_detalles.card_pos_y_pct OK'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
