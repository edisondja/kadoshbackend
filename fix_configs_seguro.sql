-- =====================================================
-- SCRIPT SEGURO PARA CREAR/ACTUALIZAR TABLA configs
-- Fecha: 2026-01-16
-- =====================================================
-- Este script verifica antes de agregar cada columna
-- para evitar errores si ya existen
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;

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
-- 2. AGREGAR CAMPOS ADICIONALES CON VERIFICACIÓN
-- =====================================================

-- Campo telefono
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'configs' 
    AND COLUMN_NAME = 'telefono');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `configs` ADD COLUMN `telefono` varchar(255) DEFAULT NULL AFTER `email`', 
    'SELECT "Columna telefono ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- nombre_clinica
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'configs' 
    AND COLUMN_NAME = 'nombre_clinica');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `configs` ADD COLUMN `nombre_clinica` varchar(255) DEFAULT NULL AFTER `nombre`', 
    'SELECT "Columna nombre_clinica ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- direccion_clinica
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'configs' 
    AND COLUMN_NAME = 'direccion_clinica');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `configs` ADD COLUMN `direccion_clinica` text DEFAULT NULL AFTER `nombre_clinica`', 
    'SELECT "Columna direccion_clinica ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- telefono_clinica
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'configs' 
    AND COLUMN_NAME = 'telefono_clinica');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `configs` ADD COLUMN `telefono_clinica` varchar(255) DEFAULT NULL AFTER `direccion_clinica`', 
    'SELECT "Columna telefono_clinica ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- rnc_clinica
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'configs' 
    AND COLUMN_NAME = 'rnc_clinica');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `configs` ADD COLUMN `rnc_clinica` varchar(255) DEFAULT NULL AFTER `telefono_clinica`', 
    'SELECT "Columna rnc_clinica ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- email_clinica
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'configs' 
    AND COLUMN_NAME = 'email_clinica');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `configs` ADD COLUMN `email_clinica` varchar(255) DEFAULT NULL AFTER `rnc_clinica`', 
    'SELECT "Columna email_clinica ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- tipo_numero_factura
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'configs' 
    AND COLUMN_NAME = 'tipo_numero_factura');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `configs` ADD COLUMN `tipo_numero_factura` enum(\'comprobante\',\'factura\') DEFAULT \'comprobante\' AFTER `email_clinica`', 
    'SELECT "Columna tipo_numero_factura ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- prefijo_factura
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'configs' 
    AND COLUMN_NAME = 'prefijo_factura');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `configs` ADD COLUMN `prefijo_factura` varchar(255) DEFAULT NULL AFTER `tipo_numero_factura`', 
    'SELECT "Columna prefijo_factura ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- usar_google_calendar
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'configs' 
    AND COLUMN_NAME = 'usar_google_calendar');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `configs` ADD COLUMN `usar_google_calendar` tinyint(1) DEFAULT \'0\' AFTER `api_token_google`', 
    'SELECT "Columna usar_google_calendar ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- google_calendar_id
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'configs' 
    AND COLUMN_NAME = 'google_calendar_id');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `configs` ADD COLUMN `google_calendar_id` varchar(255) DEFAULT NULL AFTER `usar_google_calendar`', 
    'SELECT "Columna google_calendar_id ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- recordatorio_minutos
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'configs' 
    AND COLUMN_NAME = 'recordatorio_minutos');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `configs` ADD COLUMN `recordatorio_minutos` int(11) DEFAULT \'30\' AFTER `google_calendar_id`', 
    'SELECT "Columna recordatorio_minutos ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- clave_secreta
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'configs' 
    AND COLUMN_NAME = 'clave_secreta');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `configs` ADD COLUMN `clave_secreta` varchar(255) DEFAULT NULL AFTER `recordatorio_minutos`', 
    'SELECT "Columna clave_secreta ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- VERIFICACIÓN FINAL
-- =====================================================

SELECT 
    COLUMN_NAME as 'Columna',
    COLUMN_TYPE as 'Tipo',
    IS_NULLABLE as 'Puede ser NULL',
    COLUMN_DEFAULT as 'Valor por defecto'
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'configs'
ORDER BY ORDINAL_POSITION;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
