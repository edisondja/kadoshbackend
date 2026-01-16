-- =====================================================
-- SCRIPT FINAL PARA CORREGIR COLUMNA doctor_id EN odontogramas
-- Fecha: 2026-01-16
-- =====================================================
-- Este script corrige el problema de la columna doctor_id
-- Ejecuta este script en tu base de datos de producción
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;

-- =====================================================
-- PASO 1: Verificar si existe id_doctor y renombrarlo a doctor_id
-- =====================================================

-- Verificar si existe id_doctor
SELECT COUNT(*) INTO @tiene_id_doctor
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'odontogramas'
  AND COLUMN_NAME = 'id_doctor';

-- Verificar si existe doctor_id
SELECT COUNT(*) INTO @tiene_doctor_id
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'odontogramas'
  AND COLUMN_NAME = 'doctor_id';

-- Si existe id_doctor pero no doctor_id, renombrarlo
SET @sql_rename = IF(
    @tiene_id_doctor > 0 AND @tiene_doctor_id = 0,
    'ALTER TABLE `odontogramas` CHANGE COLUMN `id_doctor` `doctor_id` int(10) unsigned NOT NULL',
    'SELECT "No es necesario renombrar" as resultado'
);

PREPARE stmt FROM @sql_rename;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- PASO 2: Si no existe ninguna, agregar doctor_id
-- =====================================================

-- Verificar nuevamente después del posible rename
SELECT COUNT(*) INTO @tiene_doctor_id_ahora
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'odontogramas'
  AND COLUMN_NAME = 'doctor_id';

-- Si aún no existe, agregarlo
SET @sql_add = IF(
    @tiene_doctor_id_ahora = 0,
    'ALTER TABLE `odontogramas` ADD COLUMN `doctor_id` int(10) unsigned NOT NULL AFTER `paciente_id`',
    'SELECT "La columna doctor_id ya existe" as resultado'
);

PREPARE stmt2 FROM @sql_add;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- =====================================================
-- PASO 3: Agregar índice si no existe
-- =====================================================

SELECT COUNT(*) INTO @tiene_indice
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'odontogramas'
  AND INDEX_NAME = 'odontogramas_doctor_id_foreign';

SET @sql_add_index = IF(
    @tiene_indice = 0,
    'ALTER TABLE `odontogramas` ADD KEY `odontogramas_doctor_id_foreign` (`doctor_id`)',
    'SELECT "El índice ya existe" as resultado'
);

PREPARE stmt3 FROM @sql_add_index;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- =====================================================
-- PASO 4: Agregar Foreign Key si no existe
-- =====================================================

SELECT COUNT(*) INTO @tiene_fk
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'odontogramas'
  AND CONSTRAINT_NAME = 'odontogramas_doctor_id_foreign'
  AND CONSTRAINT_TYPE = 'FOREIGN KEY';

SET @sql_add_fk = IF(
    @tiene_fk = 0,
    'ALTER TABLE `odontogramas` ADD CONSTRAINT `odontogramas_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE',
    'SELECT "La foreign key ya existe" as resultado'
);

PREPARE stmt4 FROM @sql_add_fk;
EXECUTE stmt4;
DEALLOCATE PREPARE stmt4;

SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- VERIFICACIÓN FINAL
-- =====================================================

-- Mostrar las columnas relacionadas con doctor
SELECT 
    COLUMN_NAME as 'Columna',
    COLUMN_TYPE as 'Tipo',
    IS_NULLABLE as 'Puede ser NULL',
    COLUMN_KEY as 'Clave',
    COLUMN_DEFAULT as 'Valor por defecto'
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'odontogramas'
  AND (COLUMN_NAME LIKE '%doctor%' OR COLUMN_NAME LIKE '%paciente%')
ORDER BY ORDINAL_POSITION;

-- Mostrar las foreign keys
SELECT 
    CONSTRAINT_NAME as 'Constraint',
    TABLE_NAME as 'Tabla',
    COLUMN_NAME as 'Columna',
    REFERENCED_TABLE_NAME as 'Tabla Referenciada',
    REFERENCED_COLUMN_NAME as 'Columna Referenciada'
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'odontogramas'
  AND CONSTRAINT_NAME LIKE '%doctor%';

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
-- Después de ejecutar este script, la tabla odontogramas
-- debería tener la columna doctor_id correctamente configurada
-- =====================================================
