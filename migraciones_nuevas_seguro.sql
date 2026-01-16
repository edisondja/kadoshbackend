-- =====================================================
-- SCRIPT SQL - MIGRACIONES NUEVAS KADOSH (VERSIÓN SEGURA)
-- Fecha: 2026-01-16
-- Autor: Edison De Jesus Abreu
-- Email: edisondja@gmail.com
-- =====================================================
-- 
-- Esta versión verifica si las tablas/columnas existen antes de crearlas
-- INSTRUCCIONES:
-- 1. Haz backup de tu base de datos antes de ejecutar
-- 2. Ejecuta este script en cada base de datos tenant
-- 3. Los errores de "ya existe" son normales y se pueden ignorar
--
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;
SET sql_mode = '';

-- =====================================================
-- 1. TABLA: configs
-- =====================================================
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

-- =====================================================
-- 2. TABLA: logs (auditoría)
-- =====================================================
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

-- =====================================================
-- 3. TABLA: especialidades
-- =====================================================
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

-- =====================================================
-- 4. TABLA: ficha_medicas
-- =====================================================
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

-- =====================================================
-- 5. TABLA: odontogramas
-- =====================================================
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

-- =====================================================
-- 6. TABLA: odontograma_detalles
-- =====================================================
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

-- =====================================================
-- 7. TABLA: pagos_mensuales
-- =====================================================
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

-- =====================================================
-- 8. TABLA: pagos_nomina
-- =====================================================
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

-- =====================================================
-- 9. TABLA: salarios_doctores
-- =====================================================
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

-- =====================================================
-- 10. TABLA: ventas_productos
-- =====================================================
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

-- =====================================================
-- 11. TABLA: recetas
-- =====================================================
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
-- MODIFICACIONES A TABLAS EXISTENTES
-- =====================================================
-- Nota: Si alguna columna ya existe, se mostrará un error que puedes ignorar

-- Modificar columna dibujo_odontograma (si la tabla ya existe)
ALTER TABLE `odontogramas` 
MODIFY COLUMN `dibujo_odontograma` longtext NOT NULL;

-- Agregar campos a tabla configs
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configs' AND COLUMN_NAME = 'nombre_clinica');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `configs` ADD COLUMN `nombre_clinica` varchar(255) DEFAULT NULL AFTER `nombre`', 'SELECT "Columna nombre_clinica ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configs' AND COLUMN_NAME = 'direccion_clinica');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `configs` ADD COLUMN `direccion_clinica` text DEFAULT NULL AFTER `nombre_clinica`', 'SELECT "Columna direccion_clinica ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configs' AND COLUMN_NAME = 'telefono_clinica');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `configs` ADD COLUMN `telefono_clinica` varchar(255) DEFAULT NULL AFTER `direccion_clinica`', 'SELECT "Columna telefono_clinica ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configs' AND COLUMN_NAME = 'rnc_clinica');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `configs` ADD COLUMN `rnc_clinica` varchar(255) DEFAULT NULL AFTER `telefono_clinica`', 'SELECT "Columna rnc_clinica ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configs' AND COLUMN_NAME = 'email_clinica');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `configs` ADD COLUMN `email_clinica` varchar(255) DEFAULT NULL AFTER `rnc_clinica`', 'SELECT "Columna email_clinica ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configs' AND COLUMN_NAME = 'tipo_numero_factura');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `configs` ADD COLUMN `tipo_numero_factura` enum(\'comprobante\',\'factura\') DEFAULT \'comprobante\' AFTER `email_clinica`', 'SELECT "Columna tipo_numero_factura ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configs' AND COLUMN_NAME = 'prefijo_factura');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `configs` ADD COLUMN `prefijo_factura` varchar(255) DEFAULT NULL AFTER `tipo_numero_factura`', 'SELECT "Columna prefijo_factura ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configs' AND COLUMN_NAME = 'usar_google_calendar');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `configs` ADD COLUMN `usar_google_calendar` tinyint(1) DEFAULT \'0\' AFTER `api_token_google`', 'SELECT "Columna usar_google_calendar ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configs' AND COLUMN_NAME = 'google_calendar_id');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `configs` ADD COLUMN `google_calendar_id` varchar(255) DEFAULT NULL AFTER `usar_google_calendar`', 'SELECT "Columna google_calendar_id ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configs' AND COLUMN_NAME = 'recordatorio_minutos');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `configs` ADD COLUMN `recordatorio_minutos` int(11) DEFAULT \'30\' AFTER `google_calendar_id`', 'SELECT "Columna recordatorio_minutos ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configs' AND COLUMN_NAME = 'clave_secreta');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `configs` ADD COLUMN `clave_secreta` varchar(255) DEFAULT NULL AFTER `recordatorio_minutos`', 'SELECT "Columna clave_secreta ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar campos a tabla logs
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'logs' AND COLUMN_NAME = 'descripcion');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `logs` ADD COLUMN `descripcion` text DEFAULT NULL AFTER `modulo`', 'SELECT "Columna descripcion ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'logs' AND COLUMN_NAME = 'ip_address');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `logs` ADD COLUMN `ip_address` varchar(255) DEFAULT NULL AFTER `descripcion`', 'SELECT "Columna ip_address ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'logs' AND COLUMN_NAME = 'user_agent');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `logs` ADD COLUMN `user_agent` text DEFAULT NULL AFTER `ip_address`', 'SELECT "Columna user_agent ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar campo a tabla citas
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'citas' AND COLUMN_NAME = 'google_event_id');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `citas` ADD COLUMN `google_event_id` varchar(255) DEFAULT NULL AFTER `fin`', 'SELECT "Columna google_event_id ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar campo a tabla procedimientos
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'procedimientos' AND COLUMN_NAME = 'comision');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `procedimientos` ADD COLUMN `comision` decimal(5,2) DEFAULT \'0.00\' AFTER `precio`', 'SELECT "Columna comision ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar campo a tabla facturas
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'facturas' AND COLUMN_NAME = 'tipo_factura');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `facturas` ADD COLUMN `tipo_factura` varchar(255) DEFAULT \'servicio\' AFTER `tipo_de_pago`', 'SELECT "Columna tipo_factura ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar campos a tabla productos
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'codigo');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `productos` ADD COLUMN `codigo` varchar(255) DEFAULT NULL AFTER `nombre`', 'SELECT "Columna codigo ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'precio');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `productos` ADD COLUMN `precio` decimal(10,2) DEFAULT \'0.00\' AFTER `descripcion`', 'SELECT "Columna precio ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'categoria');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `productos` ADD COLUMN `categoria` varchar(255) DEFAULT NULL AFTER `precio`', 'SELECT "Columna categoria ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'stock_minimo');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `productos` ADD COLUMN `stock_minimo` int(11) DEFAULT \'0\' AFTER `cantidad`', 'SELECT "Columna stock_minimo ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'activo');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `productos` ADD COLUMN `activo` tinyint(1) DEFAULT \'1\' AFTER `stock_minimo`', 'SELECT "Columna activo ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar campo a tabla doctors
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'especialidad');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE `doctors` ADD COLUMN `especialidad` varchar(255) DEFAULT NULL AFTER `numero_telefono`', 'SELECT "Columna especialidad ya existe"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
