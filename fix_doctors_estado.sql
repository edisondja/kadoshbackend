-- =====================================================
-- SCRIPT PARA AGREGAR CAMPO estado A TABLA doctors
-- Fecha: 2026-01-16
-- =====================================================
-- Este script agrega el campo estado si no existe
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;

-- Verificar si existe el campo estado
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'doctors' 
    AND COLUMN_NAME = 'estado');

-- Agregar campo estado si no existe
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `doctors` ADD COLUMN `estado` tinyint(1) DEFAULT 1 AFTER `apellido`', 
    'SELECT "Columna estado ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- VERIFICACIÓN
-- =====================================================

SHOW COLUMNS FROM `doctors` LIKE 'estado';

-- Mostrar doctores activos e inactivos
SELECT 
    id,
    nombre,
    apellido,
    estado,
    CASE 
        WHEN estado = 1 OR estado = true THEN 'Activo'
        ELSE 'Inactivo'
    END as estado_texto
FROM doctors
ORDER BY estado DESC, id DESC;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
