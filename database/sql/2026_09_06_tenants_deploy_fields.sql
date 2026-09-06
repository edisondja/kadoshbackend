-- Campos de deploy frontend / Apache en tabla tenants (BD maestra clinica)
SET NAMES utf8mb4;
SET @db := DATABASE();

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tenants' AND COLUMN_NAME='dominio');
SET @sql := IF(@exist=0,'ALTER TABLE `tenants` ADD COLUMN `dominio` varchar(255) NULL DEFAULT NULL AFTER `subdominio`','SELECT ''tenants.dominio OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tenants' AND COLUMN_NAME='document_root');
SET @sql := IF(@exist=0,'ALTER TABLE `tenants` ADD COLUMN `document_root` varchar(500) NULL DEFAULT NULL AFTER `database_name`','SELECT ''tenants.document_root OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tenants' AND COLUMN_NAME='api_url');
SET @sql := IF(@exist=0,'ALTER TABLE `tenants` ADD COLUMN `api_url` varchar(500) NULL DEFAULT NULL AFTER `document_root`','SELECT ''tenants.api_url OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tenants' AND COLUMN_NAME='vhost_enabled');
SET @sql := IF(@exist=0,'ALTER TABLE `tenants` ADD COLUMN `vhost_enabled` tinyint(1) NOT NULL DEFAULT 0 AFTER `api_url`','SELECT ''tenants.vhost_enabled OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tenants' AND COLUMN_NAME='ultimo_deploy_at');
SET @sql := IF(@exist=0,'ALTER TABLE `tenants` ADD COLUMN `ultimo_deploy_at` timestamp NULL DEFAULT NULL AFTER `vhost_enabled`','SELECT ''tenants.ultimo_deploy_at OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Rellenar dominio/document_root desde subdominio si están vacíos
UPDATE `tenants`
SET `dominio` = CONCAT(`subdominio`, '.odontoed.com')
WHERE (`dominio` IS NULL OR TRIM(`dominio`) = '')
  AND `subdominio` IS NOT NULL AND TRIM(`subdominio`) <> '';

UPDATE `tenants`
SET `document_root` = CONCAT('/var/www/', `dominio`, '/public_html')
WHERE (`document_root` IS NULL OR TRIM(`document_root`) = '')
  AND `dominio` IS NOT NULL AND TRIM(`dominio`) <> '';

UPDATE `tenants`
SET `api_url` = CONCAT('https://', `dominio`)
WHERE (`api_url` IS NULL OR TRIM(`api_url`) = '')
  AND `dominio` IS NOT NULL AND TRIM(`dominio`) <> '';

SELECT id, nombre, subdominio, dominio, database_name, document_root, api_url, vhost_enabled
FROM `tenants`
ORDER BY id;
