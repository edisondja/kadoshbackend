-- Local: permiso exportar_importar para todos los roles existentes
-- Base: odontoed (ajuste USE si aplica)

USE `odontoed`;

INSERT INTO `role_modulos` (`id_rol`, `modulo`, `permitido`, `created_at`, `updated_at`)
SELECT r.id, 'exportar_importar', 1, NOW(), NOW()
FROM `roles` r
WHERE NOT EXISTS (
  SELECT 1 FROM `role_modulos` rm
  WHERE rm.id_rol = r.id AND rm.modulo = 'exportar_importar'
);

-- Verificación
SELECT id_rol, modulo, permitido
FROM `role_modulos`
WHERE modulo = 'exportar_importar';
