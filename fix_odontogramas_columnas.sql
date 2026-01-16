-- =====================================================
-- SCRIPT DE CORRECCIÓN DE COLUMNAS ODONTOGRAMAS
-- Fecha: 2026-01-16
-- Autor: Edison De Jesus Abreu
-- Email: edisondja@gmail.com
-- =====================================================
-- 
-- Este script corrige los nombres de columnas en odontogramas
-- para que coincidan con el código (doctor_id en lugar de id_doctor)
--
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

DELIMITER $$

DROP PROCEDURE IF EXISTS rename_column_if_exists$$
CREATE PROCEDURE rename_column_if_exists(
    IN table_name VARCHAR(255),
    IN old_column_name VARCHAR(255),
    IN new_column_name VARCHAR(255),
    IN column_definition TEXT
)
BEGIN
    DECLARE old_exists INT DEFAULT 0;
    DECLARE new_exists INT DEFAULT 0;
    
    -- Verificar si la columna antigua existe
    SELECT COUNT(*) INTO old_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = table_name
      AND COLUMN_NAME = old_column_name;
    
    -- Verificar si la columna nueva ya existe
    SELECT COUNT(*) INTO new_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = table_name
      AND COLUMN_NAME = new_column_name;
    
    -- Si la columna antigua existe y la nueva no, renombrar
    IF old_exists > 0 AND new_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', table_name, '` CHANGE COLUMN `', old_column_name, '` `', new_column_name, '` ', column_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

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
-- CORREGIR TABLA odontogramas
-- =====================================================

-- Renombrar id_doctor a doctor_id si existe
CALL rename_column_if_exists('odontogramas', 'id_doctor', 'doctor_id', 'int(10) unsigned NOT NULL');

-- Si no existe ninguna de las dos, agregar doctor_id
CALL add_column_if_not_exists('odontogramas', 'doctor_id', 'int(10) unsigned NOT NULL');

-- Asegurar que paciente_id existe (puede ser id_paciente)
CALL rename_column_if_exists('odontogramas', 'id_paciente', 'paciente_id', 'int(10) unsigned NOT NULL');
CALL add_column_if_not_exists('odontogramas', 'paciente_id', 'int(10) unsigned NOT NULL');

-- Asegurar que dibujo_odontograma existe y es longtext
CALL add_column_if_not_exists('odontogramas', 'dibujo_odontograma', 'longtext NOT NULL');

-- Asegurar que estado existe
CALL add_column_if_not_exists('odontogramas', 'estado', 'varchar(255) NOT NULL DEFAULT \'activo\'');

-- Asegurar que created_at y updated_at existen
CALL add_column_if_not_exists('odontogramas', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL add_column_if_not_exists('odontogramas', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- =====================================================
-- VERIFICAR Y CREAR ÍNDICES
-- =====================================================

-- Índice para paciente_id
SET @index_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'odontogramas' 
    AND INDEX_NAME = 'odontogramas_paciente_id_foreign');
    
IF @index_exists = 0 THEN
    ALTER TABLE `odontogramas` ADD KEY `odontogramas_paciente_id_foreign` (`paciente_id`);
END IF;

-- Índice para doctor_id
SET @index_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'odontogramas' 
    AND INDEX_NAME = 'odontogramas_doctor_id_foreign');
    
IF @index_exists = 0 THEN
    ALTER TABLE `odontogramas` ADD KEY `odontogramas_doctor_id_foreign` (`doctor_id`);
END IF;

-- =====================================================
-- VERIFICAR Y CREAR FOREIGN KEYS
-- =====================================================

-- Foreign key para paciente_id
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

-- Foreign key para doctor_id
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

-- Limpiar procedimientos temporales
DROP PROCEDURE IF EXISTS rename_column_if_exists;
DROP PROCEDURE IF EXISTS add_column_if_not_exists;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
