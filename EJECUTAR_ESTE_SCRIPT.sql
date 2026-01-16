-- =====================================================
-- ⚠️ EJECUTA ESTE SCRIPT EN PRODUCCIÓN ⚠️
-- =====================================================
-- Este script corrige el problema de la columna doctor_id
-- en la tabla odontogramas
-- =====================================================
-- 
-- INSTRUCCIONES:
-- 1. Haz un backup de tu base de datos antes de ejecutar
-- 2. Ejecuta este script completo en tu base de datos
-- 3. Verifica que no haya errores
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;

-- Si la tabla tiene id_doctor, renómbralo a doctor_id
ALTER TABLE `odontogramas` 
CHANGE COLUMN `id_doctor` `doctor_id` int(10) unsigned NOT NULL;

-- Si la línea anterior da error porque id_doctor no existe,
-- entonces la columna ya se llama doctor_id o no existe.
-- En ese caso, ejecuta esto en su lugar:
-- ALTER TABLE `odontogramas` 
-- ADD COLUMN `doctor_id` int(10) unsigned NOT NULL AFTER `paciente_id`;

SET FOREIGN_KEY_CHECKS=1;

-- Verificar que funcionó:
SHOW COLUMNS FROM `odontogramas` LIKE '%doctor%';

-- =====================================================
-- DESPUÉS DE EJECUTAR ESTE SCRIPT:
-- 1. Recarga la página donde está el error
-- 2. Intenta crear un odontograma nuevamente
-- 3. El error debería desaparecer
-- =====================================================
