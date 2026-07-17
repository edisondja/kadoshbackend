-- =============================================================================
-- Usuarios YA CREADOS sin permisos en BD (ej. edison con permisos NULL)
-- El código nuevo guarda permisos al crear; este script corrige los antiguos.
-- =============================================================================
-- Ejecutar en la BD del tenant:
--   USE `nombre_base_clinica`;
--   SOURCE actualizar_edison_permisos.sql;
-- =============================================================================

SET NAMES utf8mb4;

UPDATE `usuarios`
SET
  `roll` = 'Administrador',
  `id_rol` = NULL,
  `permisos` = JSON_OBJECT(
    'cargar_pacientes', true,
    'paciente', true,
    'invitar_paciente', true,
    'doctor', true,
    'asignar_ganancias_recibos', true,
    'procedimiento', true,
    'agregar_usuario', true,
    'especialidades', true,
    'notificaciones', true,
    'agregar_cita', true,
    'contabilidad', true,
    'nomina', true,
    'punto_venta', true,
    'salarios_doctores', true,
    'historial_pagos', true,
    'consulta_deudas', true,
    'reportes', true,
    'auditoria', true,
    'configuracion', true,
    'exportar_importar', true,
    'administrar_tenants', true,
    'manual_usuario', true
  ),
  `updated_at` = NOW()
WHERE `usuario` = 'edison'
   OR `id` = 1;

SELECT
  id,
  usuario,
  roll,
  nombre,
  apellido,
  id_rol,
  permisos,
  updated_at
FROM `usuarios`
WHERE `usuario` = 'edison' OR `id` = 1;
