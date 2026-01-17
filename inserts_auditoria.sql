-- =====================================================
-- INSERTS PARA EL MÓDULO DE AUDITORÍA
-- =====================================================
-- Este script contiene ejemplos de registros de auditoría
-- para diferentes módulos del sistema.
-- 
-- IMPORTANTE: Ajusta los valores de usuario_id según 
-- los IDs reales de usuarios en tu base de datos.
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;

-- Verificar que la tabla logs existe y tiene las columnas necesarias
-- Si no existen las columnas adicionales, se agregan
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'logs' 
    AND COLUMN_NAME = 'descripcion');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `logs` ADD COLUMN `descripcion` text DEFAULT NULL AFTER `modulo`', 
    'SELECT "Columna descripcion ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'logs' 
    AND COLUMN_NAME = 'ip_address');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `logs` ADD COLUMN `ip_address` varchar(255) DEFAULT NULL AFTER `descripcion`', 
    'SELECT "Columna ip_address ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'logs' 
    AND COLUMN_NAME = 'user_agent');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `logs` ADD COLUMN `user_agent` text DEFAULT NULL AFTER `ip_address`', 
    'SELECT "Columna user_agent ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- INSERTS DE EJEMPLO
-- =====================================================
-- NOTA: Reemplaza los valores de usuario_id (1, 2, 3, etc.)
-- con los IDs reales de usuarios en tu base de datos.
-- Puedes obtenerlos con: SELECT id, nombre, apellido FROM usuarios;

-- =====================================================
-- MÓDULO: FACTURAS
-- =====================================================
INSERT INTO `logs` (`usuario_id`, `modulo`, `accion`, `descripcion`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 'Facturas', 'Crear Factura', 'Se creó una nueva factura con ID: 1001 para el paciente Juan Pérez', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY),
(1, 'Facturas', 'Crear Factura', 'Se creó una nueva factura con ID: 1002 para el paciente María González', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY),
(2, 'Facturas', 'Crear Factura', 'Se creó una nueva factura con ID: 1003 para el paciente Carlos Rodríguez', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),
(1, 'Facturas', 'Eliminar Factura', 'Se eliminó la factura con ID: 1001', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY),
(2, 'Facturas', 'Crear Factura', 'Se creó una nueva factura con ID: 1004 para el paciente Ana Martínez', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY);

-- =====================================================
-- MÓDULO: RECIBOS
-- =====================================================
INSERT INTO `logs` (`usuario_id`, `modulo`, `accion`, `descripcion`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 'Recibos', 'Crear Recibo', 'Se creó un nuevo recibo con ID: 2001 para la factura 1002', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY),
(2, 'Recibos', 'Crear Recibo', 'Se creó un nuevo recibo con ID: 2002 para la factura 1003', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),
(1, 'Recibos', 'Eliminar Recibo', 'Se eliminó el recibo con ID: 2001', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY),
(2, 'Recibos', 'Crear Recibo', 'Se creó un nuevo recibo con ID: 2003 para la factura 1004', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY);

-- =====================================================
-- MÓDULO: ODONTOGRAMAS
-- =====================================================
INSERT INTO `logs` (`usuario_id`, `modulo`, `accion`, `descripcion`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 'Odontogramas', 'Crear Odontograma', 'Se creó un nuevo odontograma para el paciente Juan Pérez (ID: 50)', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY),
(2, 'Odontogramas', 'Crear Odontograma', 'Se creó un nuevo odontograma para el paciente María González (ID: 51)', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY),
(1, 'Odontogramas', 'Eliminar Odontograma', 'Se eliminó el odontograma con ID: 50', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY),
(2, 'Odontogramas', 'Crear Odontograma', 'Se creó un nuevo odontograma para el paciente Carlos Rodríguez (ID: 52)', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY);

-- =====================================================
-- MÓDULO: PACIENTES
-- =====================================================
INSERT INTO `logs` (`usuario_id`, `modulo`, `accion`, `descripcion`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 'Pacientes', 'Crear Paciente', 'Se creó un nuevo paciente: Juan Pérez (Cédula: 001-1234567-8)', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY),
(1, 'Pacientes', 'Actualizar Paciente', 'Se actualizó la información del paciente Juan Pérez (ID: 1)', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 8 DAY, NOW() - INTERVAL 8 DAY),
(2, 'Pacientes', 'Crear Paciente', 'Se creó un nuevo paciente: María González (Cédula: 001-2345678-9)', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 9 DAY, NOW() - INTERVAL 9 DAY),
(1, 'Pacientes', 'Eliminar Paciente', 'Se eliminó el paciente con ID: 3', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY),
(2, 'Pacientes', 'Actualizar Paciente', 'Se actualizó la información del paciente María González (ID: 2)', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY);

-- =====================================================
-- MÓDULO: DOCTORES
-- =====================================================
INSERT INTO `logs` (`usuario_id`, `modulo`, `accion`, `descripcion`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 'Doctores', 'Crear Doctor', 'Se creó un nuevo doctor: Dr. Pedro Sánchez (Cédula: 001-3456789-0)', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 7 DAY, NOW() - INTERVAL 7 DAY),
(1, 'Doctores', 'Actualizar Doctor', 'Se actualizó la información del doctor Dr. Pedro Sánchez (ID: 1)', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY),
(2, 'Doctores', 'Crear Doctor', 'Se creó un nuevo doctor: Dra. Laura Fernández (Cédula: 001-4567890-1)', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY),
(1, 'Doctores', 'Desactivar Doctor', 'Se desactivó el doctor con ID: 2', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY),
(1, 'Doctores', 'Activar Doctor', 'Se activó el doctor con ID: 2', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY);

-- =====================================================
-- MÓDULO: CITAS
-- =====================================================
INSERT INTO `logs` (`usuario_id`, `modulo`, `accion`, `descripcion`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 'Citas', 'Crear Cita', 'Se creó una nueva cita para el paciente Juan Pérez con el Dr. Pedro Sánchez', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY),
(2, 'Citas', 'Crear Cita', 'Se creó una nueva cita para el paciente María González con la Dra. Laura Fernández', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY),
(1, 'Citas', 'Actualizar Cita', 'Se actualizó la cita con ID: 1', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),
(2, 'Citas', 'Eliminar Cita', 'Se eliminó la cita con ID: 2', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY),
(1, 'Citas', 'Crear Cita', 'Se creó una nueva cita para el paciente Carlos Rodríguez con el Dr. Pedro Sánchez', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY);

-- =====================================================
-- MÓDULO: PRESUPUESTOS
-- =====================================================
INSERT INTO `logs` (`usuario_id`, `modulo`, `accion`, `descripcion`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 'Presupuestos', 'Crear Presupuesto', 'Se creó un nuevo presupuesto para el paciente Juan Pérez (ID: 3001)', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY),
(2, 'Presupuestos', 'Crear Presupuesto', 'Se creó un nuevo presupuesto para el paciente María González (ID: 3002)', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY),
(1, 'Presupuestos', 'Actualizar Presupuesto', 'Se actualizó el presupuesto con ID: 3001', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY),
(2, 'Presupuestos', 'Eliminar Presupuesto', 'Se eliminó el presupuesto con ID: 3002', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY);

-- =====================================================
-- MÓDULO: RECETAS
-- =====================================================
INSERT INTO `logs` (`usuario_id`, `modulo`, `accion`, `descripcion`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 'Recetas', 'Crear Receta', 'Se creó una nueva receta para el paciente Juan Pérez (ID: 4001)', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY),
(2, 'Recetas', 'Crear Receta', 'Se creó una nueva receta para el paciente María González (ID: 4002)', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY),
(1, 'Recetas', 'Actualizar Receta', 'Se actualizó la receta con ID: 4001', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),
(2, 'Recetas', 'Eliminar Receta', 'Se eliminó la receta con ID: 4002', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY);

-- =====================================================
-- MÓDULO: USUARIOS
-- =====================================================
INSERT INTO `logs` (`usuario_id`, `modulo`, `accion`, `descripcion`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 'Usuarios', 'Crear Usuario', 'Se creó un nuevo usuario: admin2', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 8 DAY, NOW() - INTERVAL 8 DAY),
(1, 'Usuarios', 'Actualizar Usuario', 'Se actualizó la información del usuario con ID: 2', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY),
(1, 'Usuarios', 'Cambiar Contraseña', 'Se cambió la contraseña del usuario con ID: 2', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY),
(1, 'Usuarios', 'Eliminar Usuario', 'Se eliminó el usuario con ID: 3', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY);

-- =====================================================
-- MÓDULO: CONFIGURACIÓN
-- =====================================================
INSERT INTO `logs` (`usuario_id`, `modulo`, `accion`, `descripcion`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 'Configuración', 'Actualizar Configuración', 'Se actualizó la configuración del sistema (nombre de clínica)', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 7 DAY, NOW() - INTERVAL 7 DAY),
(1, 'Configuración', 'Actualizar Configuración', 'Se actualizó la configuración del sistema (tipo de factura)', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY),
(2, 'Configuración', 'Actualizar Configuración', 'Se actualizó la configuración del sistema (prefijo de factura)', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY);

-- =====================================================
-- MÓDULO: ESPECIALIDADES
-- =====================================================
INSERT INTO `logs` (`usuario_id`, `modulo`, `accion`, `descripcion`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 'Especialidades', 'Crear Especialidad', 'Se creó una nueva especialidad: Ortodoncia', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 9 DAY, NOW() - INTERVAL 9 DAY),
(1, 'Especialidades', 'Actualizar Especialidad', 'Se actualizó la especialidad con ID: 1', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 7 DAY, NOW() - INTERVAL 7 DAY),
(2, 'Especialidades', 'Crear Especialidad', 'Se creó una nueva especialidad: Endodoncia', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 8 DAY, NOW() - INTERVAL 8 DAY),
(1, 'Especialidades', 'Eliminar Especialidad', 'Se eliminó la especialidad con ID: 2', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY);

-- =====================================================
-- MÓDULO: REPORTES
-- =====================================================
INSERT INTO `logs` (`usuario_id`, `modulo`, `accion`, `descripcion`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 'Reportes', 'Generar Reporte', 'Se generó un reporte de facturas del mes actual', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),
(2, 'Reportes', 'Generar Reporte', 'Se generó un reporte de pacientes activos', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY),
(1, 'Reportes', 'Exportar Reporte', 'Se exportó un reporte de facturas en formato PDF', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY);

-- =====================================================
-- MÓDULO: SISTEMA (Login, Logout, etc.)
-- =====================================================
INSERT INTO `logs` (`usuario_id`, `modulo`, `accion`, `descripcion`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 'Sistema', 'Iniciar Sesión', 'El usuario inició sesión en el sistema', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 1 HOUR, NOW() - INTERVAL 1 HOUR),
(2, 'Sistema', 'Iniciar Sesión', 'El usuario inició sesión en el sistema', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36', NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 2 HOUR),
(1, 'Sistema', 'Cerrar Sesión', 'El usuario cerró sesión en el sistema', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW() - INTERVAL 30 MINUTE, NOW() - INTERVAL 30 MINUTE),
(1, 'Sistema', 'Iniciar Sesión', 'El usuario inició sesión en el sistema', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', NOW(), NOW());

-- =====================================================
-- VERIFICACIÓN
-- =====================================================
-- Verificar que los registros se insertaron correctamente
SELECT 
    COUNT(*) as total_registros,
    COUNT(DISTINCT modulo) as total_modulos,
    COUNT(DISTINCT usuario_id) as total_usuarios
FROM logs;

-- Mostrar un resumen por módulo
SELECT 
    modulo,
    COUNT(*) as total_acciones,
    MIN(created_at) as primera_accion,
    MAX(created_at) as ultima_accion
FROM logs
GROUP BY modulo
ORDER BY total_acciones DESC;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
