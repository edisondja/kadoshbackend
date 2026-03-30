-- Agrega permisos por módulo a usuarios
ALTER TABLE `usuarios`
  ADD COLUMN `permisos` TEXT NULL;

