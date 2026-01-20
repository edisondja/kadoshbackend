-- Script simple para permitir NULL en la columna medicamentos
-- Ejecuta este script directamente en MySQL

ALTER TABLE `recetas` MODIFY COLUMN `medicamentos` text NULL DEFAULT NULL;

-- Verificar el cambio
SHOW COLUMNS FROM `recetas` WHERE Field = 'medicamentos';
