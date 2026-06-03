-- =============================================================================
-- KADOSH / OdontoED — Cambios de base de datos (24-may-2026)
-- =============================================================================
-- Ejecutar en la base de datos de cada clínica (tenant).
-- Motor: MySQL 5.7+ / MariaDB 10.2+
-- Charset recomendado: utf8mb4
--
-- CAMBIOS DE ESQUEMA (este script):
--   1. doctors.porcentaje_ingresos      → % ingreso del doctor al pagar recibos
--   2. configs.formato_hora_citas       → formato 12h / 24h en calendario de citas
--   3. pacientes.cedula nullable          → cédula opcional al registrar paciente
--   4. paciente_invitaciones (tabla)      → enlaces de un solo uso para invitar pacientes
--
-- SIN CAMBIOS DE ESQUEMA (solo código PHP/React):
--   • Alerta de inicio de sesión por correo → usa configs.email_clinica / configs.email
--   • Exportar pacientes a CSV              → usa permiso existente exportar_importar
--     (tablas roles / role_modulos / usuarios.permisos — ver sección 5 opcional)
-- =============================================================================

SET NAMES utf8mb4;
SET @db := DATABASE();

-- -----------------------------------------------------------------------------
-- 1) doctors.porcentaje_ingresos
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
-- 2) configs.formato_hora_citas  (valores: 12h | 24h)
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
-- 4) paciente_invitaciones — registro público por enlace (un solo uso)
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

-- -----------------------------------------------------------------------------
-- 5) OPCIONAL — Permiso exportar pacientes CSV (sin columnas nuevas)
--    El módulo se llama: exportar_importar
--    Reemplace @id_rol por el ID del rol que debe exportar (ej. 2).
--    El usuario debe tener roll = 'Personalizado' e id_rol apuntando a ese rol,
--    o ser Administrador (tiene todos los permisos por defecto).
-- -----------------------------------------------------------------------------
-- SET @id_rol := 2;
--
-- INSERT INTO `role_modulos` (`id_rol`, `modulo`, `permitido`, `created_at`, `updated_at`)
-- VALUES (@id_rol, 'exportar_importar', 1, NOW(), NOW())
-- ON DUPLICATE KEY UPDATE `permitido` = 1, `updated_at` = NOW();

-- =============================================================================
-- VERIFICACIÓN
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
-- REVERTIR (ejecutar manualmente solo si necesita deshacer)
-- =============================================================================
-- ALTER TABLE `doctors` DROP COLUMN `porcentaje_ingresos`;
-- ALTER TABLE `configs` DROP COLUMN `formato_hora_citas`;
-- ALTER TABLE `pacientes` MODIFY COLUMN `cedula` VARCHAR(255) NOT NULL DEFAULT '';
-- DROP TABLE IF EXISTS `paciente_invitaciones`;
