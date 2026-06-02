-- =============================================================================
-- KADOSH / OdontoED — Cambios de base de datos (24-may-2026)
-- =============================================================================
-- Ejecutar en la base de datos de cada clínica (tenant).
-- Motor: MySQL 5.7+ / MariaDB 10.2+
-- Charset recomendado: utf8mb4
--
-- Incluye:
--   1. doctors.porcentaje_ingresos      → % de ingresos del doctor en recibos
--   2. configs.formato_hora_citas       → formato 12h / 24h en calendario de citas
--   3. pacientes.cedula nullable        → cédula opcional al registrar paciente
--   4. paciente_invitaciones (tabla)    → enlaces de un solo uso para registro público
--
-- Nota: la alerta de inicio de sesión por correo NO requiere columnas nuevas;
--       usa configs.email_clinica o configs.email (ya existentes).
-- =============================================================================

SET NAMES utf8mb4;
SET @db := DATABASE();

-- -----------------------------------------------------------------------------
-- 1) doctors.porcentaje_ingresos
--    Porcentaje que gana el doctor automáticamente al pagar un recibo.
-- -----------------------------------------------------------------------------
SET @exist := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'doctors'
    AND COLUMN_NAME = 'porcentaje_ingresos'
);

SET @sql := IF(
  @exist = 0,
  'ALTER TABLE `doctors`
     ADD COLUMN `porcentaje_ingresos` DECIMAL(5,2) NOT NULL DEFAULT 0
     AFTER `estado`',
  'SELECT ''Columna doctors.porcentaje_ingresos ya existe'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 2) configs.formato_hora_citas
--    Valores: ''12h'' (predeterminado) o ''24h''.
-- -----------------------------------------------------------------------------
SET @exist := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'configs'
    AND COLUMN_NAME = 'formato_hora_citas'
);

SET @has_recordatorio := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'configs'
    AND COLUMN_NAME = 'recordatorio_minutos'
);

SET @sql := IF(
  @exist = 0 AND @has_recordatorio > 0,
  'ALTER TABLE `configs`
     ADD COLUMN `formato_hora_citas` VARCHAR(10) NOT NULL DEFAULT ''12h''
     AFTER `recordatorio_minutos`',
  IF(
    @exist = 0,
    'ALTER TABLE `configs`
       ADD COLUMN `formato_hora_citas` VARCHAR(10) NOT NULL DEFAULT ''12h''',
    'SELECT ''Columna configs.formato_hora_citas ya existe'' AS info'
  )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Normalizar valores vacíos o inválidos
UPDATE `configs`
SET `formato_hora_citas` = '12h'
WHERE `formato_hora_citas` IS NULL
   OR TRIM(`formato_hora_citas`) = ''
   OR `formato_hora_citas` NOT IN ('12h', '24h');

-- -----------------------------------------------------------------------------
-- 3) pacientes.cedula — opcional al agregar paciente
-- -----------------------------------------------------------------------------
SET @exist := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'pacientes'
    AND COLUMN_NAME = 'cedula'
);

SET @sql := IF(
  @exist > 0,
  'ALTER TABLE `pacientes`
     MODIFY COLUMN `cedula` VARCHAR(255) NULL DEFAULT NULL',
  'SELECT ''Tabla/columna pacientes.cedula no encontrada'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 4) paciente_invitaciones — registro de paciente por enlace (un solo uso)
-- -----------------------------------------------------------------------------
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
-- VERIFICACIÓN (opcional — revisar resultado)
-- =============================================================================
SELECT 'doctors.porcentaje_ingresos' AS cambio,
       IF(COUNT(*) > 0, 'OK', 'FALTA') AS estado
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'porcentaje_ingresos'

UNION ALL

SELECT 'configs.formato_hora_citas',
       IF(COUNT(*) > 0, 'OK', 'FALTA')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'configs' AND COLUMN_NAME = 'formato_hora_citas'

UNION ALL

SELECT 'pacientes.cedula nullable',
       IF(IS_NULLABLE = 'YES', 'OK', 'REVISAR')
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pacientes' AND COLUMN_NAME = 'cedula'

UNION ALL

SELECT 'tabla paciente_invitaciones',
       IF(COUNT(*) > 0, 'OK', 'FALTA')
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'paciente_invitaciones';

-- =============================================================================
-- REVERTIR (solo si necesita deshacer — ejecutar manualmente)
-- =============================================================================
-- ALTER TABLE `doctors` DROP COLUMN `porcentaje_ingresos`;
-- ALTER TABLE `configs` DROP COLUMN `formato_hora_citas`;
-- ALTER TABLE `pacientes` MODIFY COLUMN `cedula` VARCHAR(255) NOT NULL DEFAULT '';
-- DROP TABLE IF EXISTS `paciente_invitaciones`;
