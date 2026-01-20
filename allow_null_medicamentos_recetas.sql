-- Script para permitir NULL en la columna medicamentos de la tabla recetas
-- Esto permite usar texto libre sin necesidad de medicamentos individuales

SET FOREIGN_KEY_CHECKS=0;

-- Verificar si la columna existe y modificarla para permitir NULL
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'recetas' 
    AND COLUMN_NAME = 'medicamentos');

SET @sqlstmt := IF(@exist > 0, 
    'ALTER TABLE `recetas` MODIFY COLUMN `medicamentos` text NULL DEFAULT NULL', 
    'SELECT "Columna medicamentos no existe"');

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS=1;

-- Verificar que el cambio se aplicó correctamente
SHOW COLUMNS FROM `recetas` LIKE 'medicamentos';

-- Verificar que ahora permite NULL
SELECT 
    COLUMN_NAME, 
    IS_NULLABLE, 
    COLUMN_TYPE, 
    COLUMN_DEFAULT
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'recetas' 
AND COLUMN_NAME = 'medicamentos';
