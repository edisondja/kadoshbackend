-- Firma del doctor (imagen) + toggle opcional en configuración
-- Ejecutar en cada tenant / base local:
--   SOURCE database/sql/2026_07_25_firma_doctor_documentos.sql;
SET @db := DATABASE();

-- doctors.ruta_firma
SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'ruta_firma'
);
SET @sql := IF(
  @exist = 0,
  'ALTER TABLE `doctors` ADD COLUMN `ruta_firma` VARCHAR(255) NULL DEFAULT NULL AFTER `correo_electronico`',
  'SELECT ''doctors.ruta_firma OK'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- configs.mostrar_firma_documentos (0 = no mostrar, 1 = mostrar en recibos y recetas)
SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'configs' AND COLUMN_NAME = 'mostrar_firma_documentos'
);
SET @sql := IF(
  @exist = 0,
  'ALTER TABLE `configs` ADD COLUMN `mostrar_firma_documentos` TINYINT(1) NOT NULL DEFAULT 0 AFTER `tema_apariencia`',
  'SELECT ''configs.mostrar_firma_documentos OK'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
