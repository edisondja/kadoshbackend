-- Posición de tarjetas de procedimiento en el odontograma
SET @db := DATABASE();

SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'odontograma_detalles' AND COLUMN_NAME = 'card_pos_x_pct'
);
SET @sql := IF(
  @exist = 0,
  'ALTER TABLE `odontograma_detalles` ADD COLUMN `card_pos_x_pct` DECIMAL(8,6) NULL DEFAULT NULL AFTER `color`',
  'SELECT ''odontograma_detalles.card_pos_x_pct OK'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'odontograma_detalles' AND COLUMN_NAME = 'card_pos_y_pct'
);
SET @sql := IF(
  @exist = 0,
  'ALTER TABLE `odontograma_detalles` ADD COLUMN `card_pos_y_pct` DECIMAL(8,6) NULL DEFAULT NULL AFTER `card_pos_x_pct`',
  'SELECT ''odontograma_detalles.card_pos_y_pct OK'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
