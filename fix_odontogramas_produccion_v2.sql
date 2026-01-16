-- =====================================================
-- SCRIPT DE CORRECCIÓN PARA ODONTOGRAMAS EN PRODUCCIÓN V2
-- Fecha: 2026-01-16
-- Autor: Edison De Jesus Abreu
-- Email: edisondja@gmail.com
-- =====================================================
-- 
-- Este script corrige problemas comunes con la tabla odontogramas
-- y odontograma_detalles en producción
--
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

-- =====================================================
-- 1. PROCEDIMIENTOS PARA AGREGAR/MODIFICAR COLUMNAS
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

DROP PROCEDURE IF EXISTS modify_column_if_exists$$
CREATE PROCEDURE modify_column_if_exists(
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
    
    IF column_exists > 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', table_name, '` MODIFY COLUMN `', column_name, '` ', column_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- 2. VERIFICAR Y CREAR TABLA odontogramas
-- =====================================================

CREATE TABLE IF NOT EXISTS `odontogramas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `paciente_id` int(10) unsigned NOT NULL,
  `doctor_id` int(10) unsigned NOT NULL,
  `dibujo_odontograma` longtext NOT NULL,
  `estado` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `odontogramas_paciente_id_foreign` (`paciente_id`),
  KEY `odontogramas_doctor_id_foreign` (`doctor_id`),
  CONSTRAINT `odontogramas_paciente_id_foreign` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `odontogramas_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. VERIFICAR Y CREAR TABLA odontograma_detalles
-- =====================================================

CREATE TABLE IF NOT EXISTS `odontograma_detalles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `odontograma_id` int(10) unsigned NOT NULL,
  `diente` varchar(255) NOT NULL,
  `cara` varchar(255) DEFAULT NULL,
  `tipo` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `precio` decimal(8,2) NOT NULL DEFAULT '0.00',
  `color` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `odontograma_detalles_odontograma_id_foreign` (`odontograma_id`),
  CONSTRAINT `odontograma_detalles_odontograma_id_foreign` FOREIGN KEY (`odontograma_id`) REFERENCES `odontogramas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. CORREGIR TABLA odontogramas (si ya existe)
-- =====================================================

-- Agregar columna dibujo_odontograma si no existe
CALL add_column_if_not_exists('odontogramas', 'dibujo_odontograma', 'longtext NOT NULL');

-- Si la columna ya existe, modificarla a longtext
CALL modify_column_if_exists('odontogramas', 'dibujo_odontograma', 'longtext NOT NULL');

-- Asegurar que estado existe
CALL add_column_if_not_exists('odontogramas', 'estado', 'varchar(255) NOT NULL DEFAULT \'activo\'');

-- Asegurar que created_at y updated_at existen
CALL add_column_if_not_exists('odontogramas', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL add_column_if_not_exists('odontogramas', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- =====================================================
-- 5. CORREGIR TABLA odontograma_detalles (si ya existe)
-- =====================================================

-- Asegurar que todas las columnas necesarias existen
CALL add_column_if_not_exists('odontograma_detalles', 'odontograma_id', 'int(10) unsigned NOT NULL');
CALL add_column_if_not_exists('odontograma_detalles', 'diente', 'varchar(255) NOT NULL');
CALL add_column_if_not_exists('odontograma_detalles', 'cara', 'varchar(255) DEFAULT NULL');
CALL add_column_if_not_exists('odontograma_detalles', 'tipo', 'varchar(255) NOT NULL');
CALL add_column_if_not_exists('odontograma_detalles', 'descripcion', 'varchar(255) DEFAULT NULL');
CALL add_column_if_not_exists('odontograma_detalles', 'precio', 'decimal(8,2) NOT NULL DEFAULT \'0.00\'');
CALL add_column_if_not_exists('odontograma_detalles', 'color', 'varchar(255) DEFAULT NULL');
CALL add_column_if_not_exists('odontograma_detalles', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL add_column_if_not_exists('odontograma_detalles', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- =====================================================
-- 6. VERIFICAR Y CREAR ÍNDICES (si no existen)
-- =====================================================

-- Índices para odontogramas
SET @index_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'odontogramas' 
    AND INDEX_NAME = 'odontogramas_paciente_id_foreign');
    
IF @index_exists = 0 THEN
    ALTER TABLE `odontogramas` ADD KEY `odontogramas_paciente_id_foreign` (`paciente_id`);
END IF;

SET @index_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'odontogramas' 
    AND INDEX_NAME = 'odontogramas_doctor_id_foreign');
    
IF @index_exists = 0 THEN
    ALTER TABLE `odontogramas` ADD KEY `odontogramas_doctor_id_foreign` (`doctor_id`);
END IF;

-- Índices para odontograma_detalles
SET @index_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'odontograma_detalles' 
    AND INDEX_NAME = 'odontograma_detalles_odontograma_id_foreign');
    
IF @index_exists = 0 THEN
    ALTER TABLE `odontograma_detalles` ADD KEY `odontograma_detalles_odontograma_id_foreign` (`odontograma_id`);
END IF;

-- =====================================================
-- 7. VERIFICAR Y CREAR FOREIGN KEYS (si no existen)
-- =====================================================

-- Foreign key para odontogramas -> pacientes
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'odontogramas' 
    AND CONSTRAINT_NAME = 'odontogramas_paciente_id_foreign');
    
IF @fk_exists = 0 THEN
    SET @table_exists = (SELECT COUNT(*) FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pacientes');
    IF @table_exists > 0 THEN
        ALTER TABLE `odontogramas` 
        ADD CONSTRAINT `odontogramas_paciente_id_foreign` 
        FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE;
    END IF;
END IF;

-- Foreign key para odontogramas -> doctors
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'odontogramas' 
    AND CONSTRAINT_NAME = 'odontogramas_doctor_id_foreign');
    
IF @fk_exists = 0 THEN
    SET @table_exists = (SELECT COUNT(*) FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'doctors');
    IF @table_exists > 0 THEN
        ALTER TABLE `odontogramas` 
        ADD CONSTRAINT `odontogramas_doctor_id_foreign` 
        FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;
    END IF;
END IF;

-- Foreign key para odontograma_detalles -> odontogramas
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'odontograma_detalles' 
    AND CONSTRAINT_NAME = 'odontograma_detalles_odontograma_id_foreign');
    
IF @fk_exists = 0 THEN
    SET @table_exists = (SELECT COUNT(*) FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'odontogramas');
    IF @table_exists > 0 THEN
        ALTER TABLE `odontograma_detalles` 
        ADD CONSTRAINT `odontograma_detalles_odontograma_id_foreign` 
        FOREIGN KEY (`odontograma_id`) REFERENCES `odontogramas` (`id`) ON DELETE CASCADE;
    END IF;
END IF;

-- Limpiar procedimientos temporales
DROP PROCEDURE IF EXISTS add_column_if_not_exists;
DROP PROCEDURE IF EXISTS modify_column_if_exists;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
