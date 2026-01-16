-- =====================================================
-- SCRIPT SQL FINAL - MIGRACIONES NUEVAS KADOSH
-- Fecha: 2026-01-16
-- Autor: Edison De Jesus Abreu
-- Email: edisondja@gmail.com
-- =====================================================
-- 
-- Esta versión verifica si las columnas/tablas existen antes de crearlas
-- INSTRUCCIONES:
-- 1. Haz backup de tu base de datos antes de ejecutar
-- 2. Ejecuta este script en cada base de datos tenant
-- 3. Los errores de "ya existe" se manejan automáticamente
--
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

-- =====================================================
-- CREAR TABLAS NUEVAS
-- =====================================================

-- 1. Tabla: configs
CREATE TABLE IF NOT EXISTS `configs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `ruta_logo` varchar(255) NOT NULL,
  `ruta_favicon` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `numero_empresa` varchar(255) NOT NULL,
  `dominio` varchar(255) NOT NULL,
  `api_whatapps` varchar(255) NOT NULL,
  `api_token_ws` varchar(255) NOT NULL,
  `api_gmail` varchar(255) NOT NULL,
  `api_token_google` varchar(255) NOT NULL,
  `api_instagram` varchar(255) NOT NULL,
  `token_instagram` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla: logs
CREATE TABLE IF NOT EXISTS `logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `accion` varchar(255) NOT NULL,
  `modulo` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `logs_usuario_id_foreign` (`usuario_id`),
  CONSTRAINT `logs_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla: especialidades
CREATE TABLE IF NOT EXISTS `especialidades` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `especialidades_nombre_unique` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla: ficha_medicas
CREATE TABLE IF NOT EXISTS `ficha_medicas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `paciente_id` int(10) unsigned NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `ocupacion` varchar(255) DEFAULT NULL,
  `tratamiento_actual` varchar(255) DEFAULT NULL,
  `tratamiento_detalle` varchar(255) DEFAULT NULL,
  `enfermedades` varchar(255) DEFAULT NULL,
  `medicamentos` varchar(255) DEFAULT NULL,
  `tabaquismo` varchar(255) DEFAULT NULL,
  `alcohol` varchar(255) DEFAULT NULL,
  `otros_habitos` varchar(255) DEFAULT NULL,
  `antecedentes_familiares` varchar(255) DEFAULT NULL,
  `alergias` varchar(255) DEFAULT NULL,
  `alergias_detalle` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ficha_medicas_paciente_id_foreign` (`paciente_id`),
  CONSTRAINT `ficha_medicas_paciente_id_foreign` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla: odontogramas
CREATE TABLE IF NOT EXISTS `odontogramas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `paciente_id` int(10) unsigned NOT NULL,
  `doctor_id` int(10) unsigned NOT NULL,
  `dibujo_odontograma` longtext NOT NULL,
  `estado` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `odontogramas_paciente_id_foreign` (`paciente_id`),
  KEY `odontogramas_doctor_id_foreign` (`doctor_id`),
  CONSTRAINT `odontogramas_paciente_id_foreign` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `odontogramas_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabla: odontograma_detalles
CREATE TABLE IF NOT EXISTS `odontograma_detalles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `odontograma_id` int(10) unsigned NOT NULL,
  `diente` varchar(255) NOT NULL,
  `cara` varchar(255) DEFAULT NULL,
  `tipo` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `precio` decimal(8,2) NOT NULL DEFAULT '0.00',
  `color` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `odontograma_detalles_odontograma_id_foreign` (`odontograma_id`),
  CONSTRAINT `odontograma_detalles_odontograma_id_foreign` FOREIGN KEY (`odontograma_id`) REFERENCES `odontogramas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabla: pagos_mensuales
CREATE TABLE IF NOT EXISTS `pagos_mensuales` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `fecha_pago` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `estado` varchar(255) NOT NULL DEFAULT 'pendiente',
  `comentarios` text,
  `dias_gracia` int(11) NOT NULL DEFAULT '3',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pagos_mensuales_usuario_id_foreign` (`usuario_id`),
  KEY `pagos_mensuales_fecha_vencimiento_index` (`fecha_vencimiento`),
  KEY `pagos_mensuales_estado_index` (`estado`),
  CONSTRAINT `pagos_mensuales_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabla: pagos_nomina
CREATE TABLE IF NOT EXISTS `pagos_nomina` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` int(10) unsigned DEFAULT NULL,
  `empleado_id` int(10) unsigned DEFAULT NULL,
  `fecha_pago` date NOT NULL,
  `periodo_inicio` date NOT NULL,
  `periodo_fin` date NOT NULL,
  `monto_comisiones` decimal(10,2) NOT NULL DEFAULT '0.00',
  `salario_base` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_pago` decimal(10,2) NOT NULL,
  `estado` varchar(255) NOT NULL DEFAULT 'pendiente',
  `comentarios` text,
  `tipo` varchar(255) NOT NULL DEFAULT 'comision',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pagos_nomina_doctor_id_foreign` (`doctor_id`),
  KEY `pagos_nomina_empleado_id_foreign` (`empleado_id`),
  KEY `pagos_nomina_doctor_id_periodo_inicio_periodo_fin_index` (`doctor_id`,`periodo_inicio`,`periodo_fin`),
  KEY `pagos_nomina_empleado_id_periodo_inicio_periodo_fin_index` (`empleado_id`,`periodo_inicio`,`periodo_fin`),
  CONSTRAINT `pagos_nomina_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pagos_nomina_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Tabla: salarios_doctores
CREATE TABLE IF NOT EXISTS `salarios_doctores` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` int(10) unsigned NOT NULL,
  `salario` decimal(10,2) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `comentarios` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salarios_doctores_doctor_id_foreign` (`doctor_id`),
  CONSTRAINT `salarios_doctores_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Tabla: ventas_productos
CREATE TABLE IF NOT EXISTS `ventas_productos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_factura` int(10) unsigned NOT NULL,
  `id_producto` int(10) unsigned NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ventas_productos_id_factura_foreign` (`id_factura`),
  KEY `ventas_productos_id_producto_foreign` (`id_producto`),
  CONSTRAINT `ventas_productos_id_factura_foreign` FOREIGN KEY (`id_factura`) REFERENCES `facturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ventas_productos_id_producto_foreign` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Tabla: recetas
CREATE TABLE IF NOT EXISTS `recetas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_paciente` int(10) unsigned NOT NULL,
  `id_doctor` int(10) unsigned NOT NULL,
  `medicamentos` text NOT NULL,
  `indicaciones` text,
  `diagnostico` text,
  `fecha` date NOT NULL,
  `codigo_receta` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recetas_codigo_receta_unique` (`codigo_receta`),
  KEY `recetas_id_paciente_foreign` (`id_paciente`),
  KEY `recetas_id_doctor_foreign` (`id_doctor`),
  CONSTRAINT `recetas_id_paciente_foreign` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recetas_id_doctor_foreign` FOREIGN KEY (`id_doctor`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MODIFICAR TABLAS EXISTENTES (con verificación)
-- =====================================================

-- Función auxiliar para agregar columna solo si no existe
DELIMITER $$

DROP PROCEDURE IF EXISTS add_column_if_not_exists$$
CREATE PROCEDURE add_column_if_not_exists(
    IN table_name VARCHAR(255),
    IN column_name VARCHAR(255),
    IN column_definition TEXT
)
BEGIN
    DECLARE column_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO column_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = table_name
      AND COLUMN_NAME = column_name;
    
    IF column_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD COLUMN `', column_name, '` ', column_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DROP PROCEDURE IF EXISTS modify_column_if_exists$$
CREATE PROCEDURE modify_column_if_exists(
    IN table_name VARCHAR(255),
    IN column_name VARCHAR(255),
    IN column_definition TEXT
)
BEGIN
    DECLARE column_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO column_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = table_name
      AND COLUMN_NAME = column_name;
    
    IF column_exists > 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', table_name, '` MODIFY COLUMN `', column_name, '` ', column_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- Modificar columna dibujo_odontograma (solo si existe)
CALL modify_column_if_exists('odontogramas', 'dibujo_odontograma', 'longtext NOT NULL');

-- Agregar campos a configs
CALL add_column_if_not_exists('configs', 'nombre_clinica', 'varchar(255) DEFAULT NULL AFTER `nombre`');
CALL add_column_if_not_exists('configs', 'direccion_clinica', 'text DEFAULT NULL AFTER `nombre_clinica`');
CALL add_column_if_not_exists('configs', 'telefono_clinica', 'varchar(255) DEFAULT NULL AFTER `direccion_clinica`');
CALL add_column_if_not_exists('configs', 'rnc_clinica', 'varchar(255) DEFAULT NULL AFTER `telefono_clinica`');
CALL add_column_if_not_exists('configs', 'email_clinica', 'varchar(255) DEFAULT NULL AFTER `rnc_clinica`');
CALL add_column_if_not_exists('configs', 'tipo_numero_factura', 'enum(\'comprobante\',\'factura\') DEFAULT \'comprobante\' AFTER `email_clinica`');
CALL add_column_if_not_exists('configs', 'prefijo_factura', 'varchar(255) DEFAULT NULL AFTER `tipo_numero_factura`');
CALL add_column_if_not_exists('configs', 'usar_google_calendar', 'tinyint(1) DEFAULT \'0\' AFTER `api_token_google`');
CALL add_column_if_not_exists('configs', 'google_calendar_id', 'varchar(255) DEFAULT NULL AFTER `usar_google_calendar`');
CALL add_column_if_not_exists('configs', 'recordatorio_minutos', 'int(11) DEFAULT \'30\' AFTER `google_calendar_id`');
CALL add_column_if_not_exists('configs', 'clave_secreta', 'varchar(255) DEFAULT NULL AFTER `recordatorio_minutos`');

-- Agregar campos a logs
CALL add_column_if_not_exists('logs', 'descripcion', 'text DEFAULT NULL AFTER `modulo`');
CALL add_column_if_not_exists('logs', 'ip_address', 'varchar(255) DEFAULT NULL AFTER `descripcion`');
CALL add_column_if_not_exists('logs', 'user_agent', 'text DEFAULT NULL AFTER `ip_address`');

-- Agregar campo a citas
CALL add_column_if_not_exists('citas', 'google_event_id', 'varchar(255) DEFAULT NULL AFTER `fin`');

-- Agregar campo a procedimientos
CALL add_column_if_not_exists('procedimientos', 'comision', 'decimal(5,2) DEFAULT \'0.00\' AFTER `precio`');

-- Agregar campo a facturas
CALL add_column_if_not_exists('facturas', 'tipo_factura', 'varchar(255) DEFAULT \'servicio\' AFTER `tipo_de_pago`');

-- Agregar campos a productos
CALL add_column_if_not_exists('productos', 'codigo', 'varchar(255) DEFAULT NULL AFTER `nombre`');
CALL add_column_if_not_exists('productos', 'precio', 'decimal(10,2) DEFAULT \'0.00\' AFTER `descripcion`');
CALL add_column_if_not_exists('productos', 'categoria', 'varchar(255) DEFAULT NULL AFTER `precio`');
CALL add_column_if_not_exists('productos', 'stock_minimo', 'int(11) DEFAULT \'0\' AFTER `cantidad`');
CALL add_column_if_not_exists('productos', 'activo', 'tinyint(1) DEFAULT \'1\' AFTER `stock_minimo`');

-- Agregar campo a doctors
CALL add_column_if_not_exists('doctors', 'especialidad', 'varchar(255) DEFAULT NULL AFTER `numero_telefono`');

-- Limpiar procedimientos temporales
DROP PROCEDURE IF EXISTS add_column_if_not_exists;
DROP PROCEDURE IF EXISTS modify_column_if_exists;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
