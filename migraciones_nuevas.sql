-- =====================================================
-- SCRIPT SQL - MIGRACIONES NUEVAS KADOSH
-- Fecha: 2026-01-16
-- Autor: Edison De Jesus Abreu
-- Email: edisondja@gmail.com
-- =====================================================
-- 
-- INSTRUCCIONES:
-- 1. Haz backup de tu base de datos antes de ejecutar
-- 2. Ejecuta este script en cada base de datos tenant
-- 3. Verifica que todas las tablas se crearon correctamente
--
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;

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
  `descripcion` text,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text,
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

-- Modificar columna dibujo_odontograma (si la tabla ya existe)
ALTER TABLE `odontogramas` 
MODIFY COLUMN `dibujo_odontograma` longtext NOT NULL;

-- Agregar campos a tabla configs (si ya existe)
-- Nota: Si la columna ya existe, estos comandos fallarán silenciosamente
-- Puedes ignorar los errores "Duplicate column name"

ALTER TABLE `configs` 
ADD COLUMN `nombre_clinica` varchar(255) DEFAULT NULL AFTER `nombre`;

ALTER TABLE `configs` 
ADD COLUMN `direccion_clinica` text DEFAULT NULL AFTER `nombre_clinica`;

ALTER TABLE `configs` 
ADD COLUMN `telefono_clinica` varchar(255) DEFAULT NULL AFTER `direccion_clinica`;

ALTER TABLE `configs` 
ADD COLUMN `rnc_clinica` varchar(255) DEFAULT NULL AFTER `telefono_clinica`;

ALTER TABLE `configs` 
ADD COLUMN `email_clinica` varchar(255) DEFAULT NULL AFTER `rnc_clinica`;

ALTER TABLE `configs` 
ADD COLUMN `tipo_numero_factura` enum('comprobante','factura') DEFAULT 'comprobante' AFTER `email_clinica`;

ALTER TABLE `configs` 
ADD COLUMN `prefijo_factura` varchar(255) DEFAULT NULL AFTER `tipo_numero_factura`;

ALTER TABLE `configs` 
ADD COLUMN `usar_google_calendar` tinyint(1) DEFAULT '0' AFTER `api_token_google`;

ALTER TABLE `configs` 
ADD COLUMN `google_calendar_id` varchar(255) DEFAULT NULL AFTER `usar_google_calendar`;

ALTER TABLE `configs` 
ADD COLUMN `recordatorio_minutos` int(11) DEFAULT '30' AFTER `google_calendar_id`;

ALTER TABLE `configs` 
ADD COLUMN `clave_secreta` varchar(255) DEFAULT NULL AFTER `recordatorio_minutos`;

-- Agregar campos a tabla logs (si ya existe)
ALTER TABLE `logs` 
ADD COLUMN `descripcion` text DEFAULT NULL AFTER `modulo`;

ALTER TABLE `logs` 
ADD COLUMN `ip_address` varchar(255) DEFAULT NULL AFTER `descripcion`;

ALTER TABLE `logs` 
ADD COLUMN `user_agent` text DEFAULT NULL AFTER `ip_address`;

-- Agregar campo a tabla citas (si ya existe)
ALTER TABLE `citas` 
ADD COLUMN `google_event_id` varchar(255) DEFAULT NULL AFTER `fin`;

-- Agregar campo a tabla procedimientos (si ya existe)
ALTER TABLE `procedimientos` 
ADD COLUMN `comision` decimal(5,2) DEFAULT '0.00' AFTER `precio`;

-- Agregar campo a tabla facturas (si ya existe)
ALTER TABLE `facturas` 
ADD COLUMN `tipo_factura` varchar(255) DEFAULT 'servicio' AFTER `tipo_de_pago`;

-- Agregar campos a tabla productos (si ya existe)
ALTER TABLE `productos` 
ADD COLUMN `codigo` varchar(255) DEFAULT NULL AFTER `nombre`;

ALTER TABLE `productos` 
ADD COLUMN `precio` decimal(10,2) DEFAULT '0.00' AFTER `descripcion`;

ALTER TABLE `productos` 
ADD COLUMN `categoria` varchar(255) DEFAULT NULL AFTER `precio`;

ALTER TABLE `productos` 
ADD COLUMN `stock_minimo` int(11) DEFAULT '0' AFTER `cantidad`;

ALTER TABLE `productos` 
ADD COLUMN `activo` tinyint(1) DEFAULT '1' AFTER `stock_minimo`;

-- Agregar campo a tabla doctors (si ya existe)
ALTER TABLE `doctors` 
ADD COLUMN `especialidad` varchar(255) DEFAULT NULL AFTER `numero_telefono`;

SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
-- 
-- Verificación rápida:
-- SELECT TABLE_NAME FROM information_schema.TABLES 
-- WHERE TABLE_SCHEMA = DATABASE() 
-- AND TABLE_NAME IN ('configs', 'logs', 'especialidades', 'ficha_medicas', 
--                    'odontogramas', 'odontograma_detalles', 'pagos_mensuales', 
--                    'pagos_nomina', 'salarios_doctores', 'ventas_productos', 'recetas');
-- =====================================================
