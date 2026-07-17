-- =============================================================================
-- Insertar roles del sistema + permisos (role_modulos) + asignar a usuarios
-- =============================================================================
-- Ejecutar en la BD del tenant:
--   USE `nombre_base_clinica`;
--   SOURCE insert_roles_y_asignar_usuarios.sql;
--
-- Roles: Administrador, Secretaria, Contable, Odontologo
-- Edison (y opcionalmente otros admins) quedan con id_rol = Administrador
-- =============================================================================

SET NAMES utf8mb4;
SET @db := DATABASE();
SET @now := NOW();

SELECT CONCAT('Roles en: ', @db) AS info;

-- -----------------------------------------------------------------------------
-- 1) Tablas (por si no existen)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_nombre_unique` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_modulos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_rol` int unsigned NOT NULL,
  `modulo` varchar(80) NOT NULL,
  `permitido` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_modulos_id_rol_modulo_unique` (`id_rol`, `modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2) Insertar roles (si no existen por nombre)
-- -----------------------------------------------------------------------------
INSERT INTO `roles` (`nombre`, `descripcion`, `activo`, `created_at`, `updated_at`)
SELECT 'Administrador', 'Acceso total al sistema', 1, @now, @now
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `nombre` = 'Administrador');

INSERT INTO `roles` (`nombre`, `descripcion`, `activo`, `created_at`, `updated_at`)
SELECT 'Secretaria', 'Recepción, pacientes y citas', 1, @now, @now
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `nombre` = 'Secretaria');

INSERT INTO `roles` (`nombre`, `descripcion`, `activo`, `created_at`, `updated_at`)
SELECT 'Contable', 'Contabilidad, nómina y finanzas', 1, @now, @now
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `nombre` = 'Contable');

INSERT INTO `roles` (`nombre`, `descripcion`, `activo`, `created_at`, `updated_at`)
SELECT 'Odontologo', 'Consulta clínica y pacientes', 1, @now, @now
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `nombre` = 'Odontologo');

SET @rol_admin      := (SELECT `id` FROM `roles` WHERE `nombre` = 'Administrador' LIMIT 1);
SET @rol_secretaria := (SELECT `id` FROM `roles` WHERE `nombre` = 'Secretaria' LIMIT 1);
SET @rol_contable   := (SELECT `id` FROM `roles` WHERE `nombre` = 'Contable' LIMIT 1);
SET @rol_odontologo := (SELECT `id` FROM `roles` WHERE `nombre` = 'Odontologo' LIMIT 1);

-- -----------------------------------------------------------------------------
-- 3) Permisos por rol (solo módulos permitido = 1)
-- -----------------------------------------------------------------------------

-- Administrador: todos los módulos
INSERT INTO `role_modulos` (`id_rol`, `modulo`, `permitido`, `created_at`, `updated_at`)
SELECT @rol_admin, m.modulo, 1, @now, @now
FROM (
  SELECT 'cargar_pacientes' AS modulo UNION ALL SELECT 'paciente' UNION ALL SELECT 'invitar_paciente'
  UNION ALL SELECT 'doctor' UNION ALL SELECT 'asignar_ganancias_recibos' UNION ALL SELECT 'procedimiento'
  UNION ALL SELECT 'agregar_usuario' UNION ALL SELECT 'especialidades' UNION ALL SELECT 'notificaciones'
  UNION ALL SELECT 'agregar_cita' UNION ALL SELECT 'contabilidad' UNION ALL SELECT 'nomina'
  UNION ALL SELECT 'punto_venta' UNION ALL SELECT 'salarios_doctores' UNION ALL SELECT 'historial_pagos'
  UNION ALL SELECT 'consulta_deudas' UNION ALL SELECT 'reportes' UNION ALL SELECT 'auditoria'
  UNION ALL SELECT 'configuracion' UNION ALL SELECT 'exportar_importar' UNION ALL SELECT 'administrar_tenants'
  UNION ALL SELECT 'manual_usuario'
) AS m
WHERE @rol_admin IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `role_modulos` rm
    WHERE rm.`id_rol` = @rol_admin AND rm.`modulo` = m.modulo
  );

-- Secretaria
INSERT INTO `role_modulos` (`id_rol`, `modulo`, `permitido`, `created_at`, `updated_at`)
SELECT @rol_secretaria, m.modulo, 1, @now, @now
FROM (
  SELECT 'cargar_pacientes' AS modulo UNION ALL SELECT 'paciente' UNION ALL SELECT 'invitar_paciente'
  UNION ALL SELECT 'notificaciones' UNION ALL SELECT 'agregar_cita' UNION ALL SELECT 'manual_usuario'
) AS m
WHERE @rol_secretaria IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `role_modulos` rm
    WHERE rm.`id_rol` = @rol_secretaria AND rm.`modulo` = m.modulo
  );

-- Contable
INSERT INTO `role_modulos` (`id_rol`, `modulo`, `permitido`, `created_at`, `updated_at`)
SELECT @rol_contable, m.modulo, 1, @now, @now
FROM (
  SELECT 'cargar_pacientes' AS modulo UNION ALL SELECT 'contabilidad' UNION ALL SELECT 'nomina'
  UNION ALL SELECT 'punto_venta' UNION ALL SELECT 'salarios_doctores' UNION ALL SELECT 'historial_pagos'
  UNION ALL SELECT 'consulta_deudas' UNION ALL SELECT 'manual_usuario'
) AS m
WHERE @rol_contable IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `role_modulos` rm
    WHERE rm.`id_rol` = @rol_contable AND rm.`modulo` = m.modulo
  );

-- Odontologo (mismos módulos que Secretaria en el backend)
INSERT INTO `role_modulos` (`id_rol`, `modulo`, `permitido`, `created_at`, `updated_at`)
SELECT @rol_odontologo, m.modulo, 1, @now, @now
FROM (
  SELECT 'cargar_pacientes' AS modulo UNION ALL SELECT 'paciente' UNION ALL SELECT 'invitar_paciente'
  UNION ALL SELECT 'notificaciones' UNION ALL SELECT 'agregar_cita' UNION ALL SELECT 'manual_usuario'
) AS m
WHERE @rol_odontologo IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `role_modulos` rm
    WHERE rm.`id_rol` = @rol_odontologo AND rm.`modulo` = m.modulo
  );

-- -----------------------------------------------------------------------------
-- 4) Asignar rol Administrador a Edison (id_rol + permisos en JSON)
--    roll sigue 'Administrador' para el menú; id_rol enlaza la tabla roles
-- -----------------------------------------------------------------------------
UPDATE `usuarios`
SET
  `roll` = 'Administrador',
  `id_rol` = @rol_admin,
  `permisos` = JSON_OBJECT(
    'cargar_pacientes', true, 'paciente', true, 'invitar_paciente', true, 'doctor', true,
    'asignar_ganancias_recibos', true, 'procedimiento', true, 'agregar_usuario', true,
    'especialidades', true, 'notificaciones', true, 'agregar_cita', true, 'contabilidad', true,
    'nomina', true, 'punto_venta', true, 'salarios_doctores', true, 'historial_pagos', true,
    'consulta_deudas', true, 'reportes', true, 'auditoria', true, 'configuracion', true,
    'exportar_importar', true, 'administrar_tenants', true, 'manual_usuario', true
  ),
  `updated_at` = @now
WHERE `usuario` = 'edison' OR `id` = 1;

-- Opcional: asignar id_rol Administrador a todos los admins
-- UPDATE `usuarios`
-- SET `id_rol` = @rol_admin, `updated_at` = @now
-- WHERE `roll` = 'Administrador';

-- -----------------------------------------------------------------------------
-- 5) Verificación
-- -----------------------------------------------------------------------------
SELECT `id`, `nombre`, `descripcion`, `activo` FROM `roles` ORDER BY `id`;

SELECT rm.`id_rol`, r.`nombre` AS rol, COUNT(*) AS modulos_activos
FROM `role_modulos` rm
JOIN `roles` r ON r.`id` = rm.`id_rol`
WHERE rm.`permitido` = 1
GROUP BY rm.`id_rol`, r.`nombre`
ORDER BY rm.`id_rol`;

SELECT `id`, `usuario`, `roll`, `id_rol`, LEFT(`permisos`, 80) AS permisos_preview
FROM `usuarios`
WHERE `usuario` = 'edison' OR `id` = 1;
