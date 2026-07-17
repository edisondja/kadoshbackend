-- Tema de apariencia por clínica (configs.tema_apariencia)
-- Valores: classic | eda | violet | dark
-- classic = púrpura anterior (sin cambios radicales para clientes existentes)

SET @db := DATABASE();

SET @exist := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'configs'
    AND COLUMN_NAME = 'tema_apariencia'
);

SET @sql := IF(
  @exist = 0,
  'ALTER TABLE `configs`
     ADD COLUMN `tema_apariencia` VARCHAR(20) NOT NULL DEFAULT ''classic''
     AFTER `formato_hora_citas`',
  'SELECT ''Columna configs.tema_apariencia ya existe'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `configs`
SET `tema_apariencia` = 'classic'
WHERE `tema_apariencia` IS NULL
   OR TRIM(`tema_apariencia`) = ''
   OR `tema_apariencia` NOT IN ('classic', 'eda', 'violet', 'dark');
