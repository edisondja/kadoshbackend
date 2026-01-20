-- Script para agregar la columna sexo a la tabla doctors
-- Ejecutar este script en la base de datos

SET FOREIGN_KEY_CHECKS=0;

-- Verificar si la columna existe antes de agregarla
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'doctors' 
    AND COLUMN_NAME = 'sexo');

SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `doctors` ADD COLUMN `sexo` ENUM("M", "F") NULL DEFAULT NULL AFTER `especialidad`', 
    'SELECT "Columna sexo ya existe"');

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS=1;

-- Verificar que la columna fue agregada
SHOW COLUMNS FROM `doctors` LIKE 'sexo';

-- Mostrar algunos registros para verificar
SELECT id, nombre, apellido, sexo, 
       CASE 
           WHEN sexo = 'F' THEN 'Dra.'
           WHEN sexo = 'M' THEN 'Dr.'
           ELSE 'Dr.'
       END as titulo
FROM doctors 
LIMIT 10;
