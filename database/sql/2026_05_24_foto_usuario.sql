-- Foto de perfil para usuarios del sistema
USE `odontoed`;

ALTER TABLE `usuarios`
  ADD COLUMN `foto_usuario` VARCHAR(255) NULL DEFAULT NULL AFTER `apellido`;
