-- =====================================================
-- Doctores + Especialidades
-- Actualiza esquema para módulo de doctores/especialidades
-- Seguro de re-ejecutar (idempotente)
-- =====================================================
--
-- Uso:
--   mysql -u root -p nombre_base < database/sql/2026_07_17_doctores_especialidades.sql
--
-- O en phpMyAdmin: seleccionar la BD del tenant y ejecutar este script.
-- =====================================================

SET @db := DATABASE();

-- -----------------------------------------------------
-- 1) Tabla especialidades
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `especialidades` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `especialidades_nombre_unique` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 2) Columnas en doctors necesarias para el módulo
-- -----------------------------------------------------

-- especialidad (nombre libre o de la tabla especialidades)
SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'especialidad'
);
SET @sql := IF(
  @exist = 0,
  'ALTER TABLE `doctors` ADD COLUMN `especialidad` varchar(255) NULL AFTER `numero_telefono`',
  'SELECT ''doctors.especialidad ya existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- sexo (M / F) — Dr. / Dra.
SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'sexo'
);
SET @sql := IF(
  @exist = 0,
  'ALTER TABLE `doctors` ADD COLUMN `sexo` ENUM(''M'',''F'') NULL DEFAULT NULL AFTER `especialidad`',
  'SELECT ''doctors.sexo ya existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- correo_electronico
SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'correo_electronico'
);
SET @sql := IF(
  @exist = 0,
  'ALTER TABLE `doctors` ADD COLUMN `correo_electronico` varchar(191) NULL AFTER `sexo`',
  'SELECT ''doctors.correo_electronico ya existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- id_usuario (vínculo con usuarios del sistema)
SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'id_usuario'
);
SET @sql := IF(
  @exist = 0,
  'ALTER TABLE `doctors` ADD COLUMN `id_usuario` int unsigned NULL AFTER `correo_electronico`',
  'SELECT ''doctors.id_usuario ya existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- porcentaje_ingresos (% del recibo para el doctor)
SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'porcentaje_ingresos'
);
SET @sql := IF(
  @exist = 0,
  'ALTER TABLE `doctors` ADD COLUMN `porcentaje_ingresos` DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER `id_usuario`',
  'SELECT ''doctors.porcentaje_ingresos ya existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- estado (activo / inactivo)
SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'estado'
);
SET @sql := IF(
  @exist = 0,
  'ALTER TABLE `doctors` ADD COLUMN `estado` tinyint(1) NOT NULL DEFAULT 1 AFTER `apellido`',
  'SELECT ''doctors.estado ya existe'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------
-- 3) Especialidades base (solo si la tabla está vacía)
-- -----------------------------------------------------
INSERT INTO `especialidades` (`nombre`, `descripcion`, `estado`, `created_at`, `updated_at`)
SELECT v.nombre, v.descripcion, 1, NOW(), NOW()
FROM (
  SELECT 'Odontología General' AS nombre, 'Atención odontológica general' AS descripcion
  UNION ALL SELECT 'Ortodoncia', 'Corrección de posición dental y oclusión'
  UNION ALL SELECT 'Endodoncia', 'Tratamiento de conductos'
  UNION ALL SELECT 'Periodoncia', 'Tratamiento de encías y tejidos de soporte'
  UNION ALL SELECT 'Cirugía Oral', 'Cirugía bucal y maxilofacial'
  UNION ALL SELECT 'Odontopediatría', 'Odontología infantil'
  UNION ALL SELECT 'Prostodoncia', 'Prótesis dentales'
  UNION ALL SELECT 'Implantología', 'Colocación y rehabilitación con implantes'
  UNION ALL SELECT 'Estética Dental', 'Blanqueamiento y estética dental'
) AS v
WHERE NOT EXISTS (
  SELECT 1 FROM `especialidades` e WHERE e.nombre = v.nombre
);

-- -----------------------------------------------------
-- 4) Verificación
-- -----------------------------------------------------
SELECT 'especialidades' AS tabla, COUNT(*) AS registros FROM `especialidades`
UNION ALL
SELECT 'doctors', COUNT(*) FROM `doctors`;

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db
  AND TABLE_NAME = 'doctors'
  AND COLUMN_NAME IN (
    'especialidad', 'sexo', 'correo_electronico',
    'id_usuario', 'porcentaje_ingresos', 'estado'
  )
ORDER BY ORDINAL_POSITION;

SELECT id, nombre, descripcion, estado FROM `especialidades` ORDER BY nombre;
