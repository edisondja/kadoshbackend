-- =====================================================
-- SCRIPT PARA CREAR/ACTUALIZAR TABLA configs
-- Fecha: 2026-01-16
-- =====================================================
-- Este script:
-- 1. Crea la tabla configs si no existe
-- 2. Agrega solo los campos faltantes si la tabla ya existe
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

-- =====================================================
-- 1. CREAR TABLA configs SI NO EXISTE
-- =====================================================

CREATE TABLE IF NOT EXISTS `configs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL DEFAULT '',
  `descripcion` varchar(255) NOT NULL DEFAULT '',
  `ruta_logo` varchar(255) NOT NULL DEFAULT '',
  `ruta_favicon` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `numero_empresa` varchar(255) NOT NULL DEFAULT '',
  `dominio` varchar(255) NOT NULL DEFAULT '',
  `api_whatapps` varchar(255) NOT NULL DEFAULT '',
  `api_token_ws` varchar(255) NOT NULL DEFAULT '',
  `api_gmail` varchar(255) NOT NULL DEFAULT '',
  `api_token_google` varchar(255) NOT NULL DEFAULT '',
  `api_instagram` varchar(255) NOT NULL DEFAULT '',
  `token_instagram` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. FUNCIÓN PARA AGREGAR COLUMNAS SOLO SI NO EXISTEN
-- =====================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS add_column_if_not_exists$$

CREATE PROCEDURE add_column_if_not_exists(
    IN table_name VARCHAR(255),
    IN column_name VARCHAR(255),
    IN column_definition TEXT
)
BEGIN
    DECLARE column_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO column_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = table_name
      AND COLUMN_NAME = column_name;
    
    IF column_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD COLUMN `', column_name, '` ', column_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- 3. AGREGAR CAMPOS ADICIONALES (SI NO EXISTEN)
-- =====================================================

-- Campo telefono (si existe numero_empresa pero no telefono)
CALL add_column_if_not_exists('configs', 'telefono', 'varchar(255) DEFAULT NULL AFTER `email`');

-- Campos de clínica
CALL add_column_if_not_exists('configs', 'nombre_clinica', 'varchar(255) DEFAULT NULL AFTER `nombre`');
CALL add_column_if_not_exists('configs', 'direccion_clinica', 'text DEFAULT NULL AFTER `nombre_clinica`');
CALL add_column_if_not_exists('configs', 'telefono_clinica', 'varchar(255) DEFAULT NULL AFTER `direccion_clinica`');
CALL add_column_if_not_exists('configs', 'rnc_clinica', 'varchar(255) DEFAULT NULL AFTER `telefono_clinica`');
CALL add_column_if_not_exists('configs', 'email_clinica', 'varchar(255) DEFAULT NULL AFTER `rnc_clinica`');

-- Campos de facturación
CALL add_column_if_not_exists('configs', 'tipo_numero_factura', 'enum(\'comprobante\',\'factura\') DEFAULT \'comprobante\' AFTER `email_clinica`');
CALL add_column_if_not_exists('configs', 'prefijo_factura', 'varchar(255) DEFAULT NULL AFTER `tipo_numero_factura`');

-- Campos de Google Calendar
CALL add_column_if_not_exists('configs', 'usar_google_calendar', 'tinyint(1) DEFAULT \'0\' AFTER `api_token_google`');
CALL add_column_if_not_exists('configs', 'google_calendar_id', 'varchar(255) DEFAULT NULL AFTER `usar_google_calendar`');
CALL add_column_if_not_exists('configs', 'recordatorio_minutos', 'int(11) DEFAULT \'30\' AFTER `google_calendar_id`');

-- Campo clave secreta
CALL add_column_if_not_exists('configs', 'clave_secreta', 'varchar(255) DEFAULT NULL AFTER `recordatorio_minutos`');

-- =====================================================
-- 4. LIMPIAR PROCEDURE (OPCIONAL)
-- =====================================================

DROP PROCEDURE IF EXISTS add_column_if_not_exists;

-- =====================================================
-- 5. VERIFICACIÓN FINAL
-- =====================================================

-- Mostrar todas las columnas de la tabla configs
SELECT 
    COLUMN_NAME as 'Columna',
    COLUMN_TYPE as 'Tipo',
    IS_NULLABLE as 'Puede ser NULL',
    COLUMN_DEFAULT as 'Valor por defecto',
    ORDINAL_POSITION as 'Posición'
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'configs'
ORDER BY ORDINAL_POSITION;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
-- Este script crea la tabla configs completa con todos
-- los campos necesarios, o agrega solo los campos faltantes
-- si la tabla ya existe en producción
-- =====================================================
