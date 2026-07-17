-- Nómina empleados: salario base y deducciones opcionales (AFP, SFS, ISR)
-- Ejecutar en cada BD tenant

SET @db := DATABASE();

-- empleados: campos opcionales de contacto
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='activo');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `activo` tinyint(1) NOT NULL DEFAULT 1','SELECT ''empleados.activo OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='aplica_afp');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `aplica_afp` tinyint(1) NOT NULL DEFAULT 0','SELECT ''empleados.aplica_afp OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='aplica_sfs');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `aplica_sfs` tinyint(1) NOT NULL DEFAULT 0','SELECT ''empleados.aplica_sfs OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='aplica_isr');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `aplica_isr` tinyint(1) NOT NULL DEFAULT 0','SELECT ''empleados.aplica_isr OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='porcentaje_afp');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `porcentaje_afp` decimal(5,2) NOT NULL DEFAULT 2.87','SELECT ''empleados.porcentaje_afp OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='porcentaje_sfs');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `porcentaje_sfs` decimal(5,2) NOT NULL DEFAULT 3.04','SELECT ''empleados.porcentaje_sfs OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='porcentaje_isr');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `porcentaje_isr` decimal(5,2) NULL DEFAULT NULL COMMENT ''Si NULL usa tabla DGII simplificada''','SELECT ''empleados.porcentaje_isr OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='otros_descuentos');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `otros_descuentos` decimal(10,2) NOT NULL DEFAULT 0','SELECT ''empleados.otros_descuentos OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='comentarios_nomina');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `comentarios_nomina` text NULL','SELECT ''empleados.comentarios_nomina OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- pagos_nomina: desglose depósito
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pagos_nomina' AND COLUMN_NAME='total_bruto');
SET @sql := IF(@exist=0,'ALTER TABLE `pagos_nomina` ADD COLUMN `total_bruto` decimal(10,2) NOT NULL DEFAULT 0','SELECT ''pagos_nomina.total_bruto OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pagos_nomina' AND COLUMN_NAME='total_deducciones');
SET @sql := IF(@exist=0,'ALTER TABLE `pagos_nomina` ADD COLUMN `total_deducciones` decimal(10,2) NOT NULL DEFAULT 0','SELECT ''pagos_nomina.total_deducciones OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pagos_nomina' AND COLUMN_NAME='neto_deposito');
SET @sql := IF(@exist=0,'ALTER TABLE `pagos_nomina` ADD COLUMN `neto_deposito` decimal(10,2) NOT NULL DEFAULT 0','SELECT ''pagos_nomina.neto_deposito OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pagos_nomina' AND COLUMN_NAME='deducciones_detalle');
SET @sql := IF(@exist=0,'ALTER TABLE `pagos_nomina` ADD COLUMN `deducciones_detalle` text NULL','SELECT ''pagos_nomina.deducciones_detalle OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
