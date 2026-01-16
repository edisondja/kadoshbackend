-- =====================================================
-- SCRIPT DE CORRECCIÓN PARA COLUMNA doctor_id EN odontogramas
-- Fecha: 2026-01-16
-- Descripción: Agrega o renombra la columna doctor_id si no existe
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

-- =====================================================
-- 1. VERIFICAR Y RENOMBRAR/AGREGAR COLUMNA doctor_id
-- =====================================================

-- Verificar si existe la columna id_doctor (nombre antiguo)
SET @col_id_doctor_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'odontogramas' 
    AND COLUMN_NAME = 'id_doctor');

-- Verificar si existe la columna doctor_id (nombre nuevo)
SET @col_doctor_id_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'odontogramas' 
    AND COLUMN_NAME = 'doctor_id');

-- Si existe id_doctor pero no doctor_id, renombrarlo
SET @sql_rename = IF(
    @col_id_doctor_exists > 0 AND @col_doctor_id_exists = 0,
    'ALTER TABLE `odontogramas` CHANGE COLUMN `id_doctor` `doctor_id` int(10) unsigned NOT NULL',
    'SELECT "No es necesario renombrar: la columna doctor_id ya existe o id_doctor no existe" as mensaje'
);

PREPARE stmt_rename FROM @sql_rename;
EXECUTE stmt_rename;
DEALLOCATE PREPARE stmt_rename;

-- Si no existe ninguna de las dos, agregar doctor_id
SET @sql_add = IF(
    @col_id_doctor_exists = 0 AND @col_doctor_id_exists = 0,
    'ALTER TABLE `odontogramas` ADD COLUMN `doctor_id` int(10) unsigned NOT NULL AFTER `paciente_id`',
    'SELECT "No es necesario agregar: la columna doctor_id ya existe o id_doctor existe" as mensaje'
);

PREPARE stmt_add FROM @sql_add;
EXECUTE stmt_add;
DEALLOCATE PREPARE stmt_add;

-- =====================================================
-- 2. VERIFICAR Y AGREGAR FOREIGN KEY SI NO EXISTE
-- =====================================================

-- Verificar si existe el índice de la foreign key
SET @fk_index_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'odontogramas' 
    AND INDEX_NAME = 'odontogramas_doctor_id_foreign');

-- Agregar índice si no existe
SET @sql_add_index = IF(
    @col_doctor_id_exists > 0 AND @fk_index_exists = 0,
    'ALTER TABLE `odontogramas` ADD KEY `odontogramas_doctor_id_foreign` (`doctor_id`)',
    'SELECT "No es necesario agregar índice: ya existe o la columna no existe" as mensaje'
);

PREPARE stmt_add_index FROM @sql_add_index;
EXECUTE stmt_add_index;
DEALLOCATE PREPARE stmt_add_index;

-- Verificar si existe la foreign key constraint
SET @fk_constraint_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'odontogramas' 
    AND CONSTRAINT_NAME = 'odontogramas_doctor_id_foreign'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY');

-- Agregar foreign key constraint si no existe
SET @sql_add_fk = IF(
    @col_doctor_id_exists > 0 AND @fk_index_exists > 0 AND @fk_constraint_exists = 0,
    'ALTER TABLE `odontogramas` ADD CONSTRAINT `odontogramas_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE',
    'SELECT "No es necesario agregar foreign key: ya existe o los requisitos no se cumplen" as mensaje'
);

PREPARE stmt_add_fk FROM @sql_add_fk;
EXECUTE stmt_add_fk;
DEALLOCATE PREPARE stmt_add_fk;

-- =====================================================
-- 3. MIGRAR DATOS SI SE RENOMBRÓ LA COLUMNA
-- =====================================================

-- Si se renombró la columna, los datos ya están migrados automáticamente
-- Pero si por alguna razón ambas columnas existen, copiar datos de id_doctor a doctor_id
SET @sql_migrate_data = IF(
    @col_id_doctor_exists > 0 AND @col_doctor_id_exists > 0,
    'UPDATE `odontogramas` SET `doctor_id` = `id_doctor` WHERE `doctor_id` IS NULL OR `doctor_id` = 0',
    'SELECT "No es necesario migrar datos: las columnas no coexisten" as mensaje'
);

PREPARE stmt_migrate FROM @sql_migrate_data;
EXECUTE stmt_migrate;
DEALLOCATE PREPARE stmt_migrate;

-- =====================================================
-- 4. VERIFICACIÓN FINAL
-- =====================================================

-- Mostrar el estado final de la tabla
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY,
    COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'odontogramas'
    AND COLUMN_NAME IN ('id_doctor', 'doctor_id')
ORDER BY COLUMN_NAME;

SELECT 
    CONSTRAINT_NAME,
    CONSTRAINT_TYPE,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'odontogramas'
    AND CONSTRAINT_NAME LIKE '%doctor_id%';

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
-- Este script:
-- 1. Renombra id_doctor a doctor_id si id_doctor existe y doctor_id no existe
-- 2. Agrega doctor_id si ninguna de las dos columnas existe
-- 3. Agrega el índice y foreign key constraint si no existen
-- 4. Migra datos si ambas columnas coexisten temporalmente
-- 5. Muestra el estado final para verificación
-- =====================================================
