-- =====================================================
-- SCRIPT SIMPLE PARA CORREGIR COLUMNA doctor_id
-- Fecha: 2026-01-16
-- =====================================================
-- Este script renombra id_doctor a doctor_id o agrega doctor_id
-- Ejecuta este script en tu base de datos de producción
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;

-- Opción 1: Si existe id_doctor, renombrarlo a doctor_id
ALTER TABLE `odontogramas` 
CHANGE COLUMN `id_doctor` `doctor_id` int(10) unsigned NOT NULL;

-- Si la línea anterior da error (columna no existe), comenta esa línea
-- y ejecuta esta línea en su lugar:
-- ALTER TABLE `odontogramas` ADD COLUMN `doctor_id` int(10) unsigned NOT NULL AFTER `paciente_id`;

-- Agregar índice si no existe (ejecuta solo si es necesario)
ALTER TABLE `odontogramas` 
ADD KEY `odontogramas_doctor_id_foreign` (`doctor_id`);

-- Agregar foreign key constraint si no existe (ejecuta solo si es necesario)
ALTER TABLE `odontogramas` 
ADD CONSTRAINT `odontogramas_doctor_id_foreign` 
FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- Verificación: ejecuta esto para ver el estado actual
-- =====================================================
SHOW COLUMNS FROM `odontogramas` LIKE '%doctor%';
