-- Bloqueo de usuarios del sistema (por tenant)
SET NAMES utf8mb4;
SET @db := DATABASE();

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='usuarios' AND COLUMN_NAME='bloqueado');
SET @sql := IF(@exist=0,'ALTER TABLE `usuarios` ADD COLUMN `bloqueado` tinyint(1) NOT NULL DEFAULT 0','SELECT ''usuarios.bloqueado OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='usuarios' AND COLUMN_NAME='bloqueado_at');
SET @sql := IF(@exist=0,'ALTER TABLE `usuarios` ADD COLUMN `bloqueado_at` timestamp NULL DEFAULT NULL','SELECT ''usuarios.bloqueado_at OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='usuarios' AND COLUMN_NAME='bloqueado_por');
SET @sql := IF(@exist=0,'ALTER TABLE `usuarios` ADD COLUMN `bloqueado_por` int unsigned NULL DEFAULT NULL','SELECT ''usuarios.bloqueado_por OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
