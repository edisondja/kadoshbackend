-- =====================================================
-- SCRIPT SIMPLE PARA CREAR/ACTUALIZAR TABLA configs
-- Fecha: 2026-01-16
-- =====================================================
-- Ejecuta este script en tu base de datos de producción
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;

-- =====================================================
-- 1. CREAR TABLA configs SI NO EXISTE
-- =====================================================

CREATE TABLE IF NOT EXISTS `configs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL DEFAULT '',
  `descripcion` varchar(255) NOT NULL DEFAULT '',
  `ruta_logo` varchar(255) NOT NULL DEFAULT '',
  `ruta_favicon` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `numero_empresa` varchar(255) NOT NULL DEFAULT '',
  `dominio` varchar(255) NOT NULL DEFAULT '',
  `api_whatapps` varchar(255) NOT NULL DEFAULT '',
  `api_token_ws` varchar(255) NOT NULL DEFAULT '',
  `api_gmail` varchar(255) NOT NULL DEFAULT '',
  `api_token_google` varchar(255) NOT NULL DEFAULT '',
  `api_instagram` varchar(255) NOT NULL DEFAULT '',
  `token_instagram` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. AGREGAR CAMPOS ADICIONALES (si no existen)
-- =====================================================
-- Si alguno da error porque ya existe, puedes ignorarlo

-- Campo telefono
ALTER TABLE `configs` ADD COLUMN `telefono` varchar(255) DEFAULT NULL AFTER `email`;

-- Campos de clínica
ALTER TABLE `configs` ADD COLUMN `nombre_clinica` varchar(255) DEFAULT NULL AFTER `nombre`;
ALTER TABLE `configs` ADD COLUMN `direccion_clinica` text DEFAULT NULL AFTER `nombre_clinica`;
ALTER TABLE `configs` ADD COLUMN `telefono_clinica` varchar(255) DEFAULT NULL AFTER `direccion_clinica`;
ALTER TABLE `configs` ADD COLUMN `rnc_clinica` varchar(255) DEFAULT NULL AFTER `telefono_clinica`;
ALTER TABLE `configs` ADD COLUMN `email_clinica` varchar(255) DEFAULT NULL AFTER `rnc_clinica`;

-- Campos de facturación
ALTER TABLE `configs` ADD COLUMN `tipo_numero_factura` enum('comprobante','factura') DEFAULT 'comprobante' AFTER `email_clinica`;
ALTER TABLE `configs` ADD COLUMN `prefijo_factura` varchar(255) DEFAULT NULL AFTER `tipo_numero_factura`;

-- Campos de Google Calendar
ALTER TABLE `configs` ADD COLUMN `usar_google_calendar` tinyint(1) DEFAULT '0' AFTER `api_token_google`;
ALTER TABLE `configs` ADD COLUMN `google_calendar_id` varchar(255) DEFAULT NULL AFTER `usar_google_calendar`;
ALTER TABLE `configs` ADD COLUMN `recordatorio_minutos` int(11) DEFAULT '30' AFTER `google_calendar_id`;

-- Campo clave secreta
ALTER TABLE `configs` ADD COLUMN `clave_secreta` varchar(255) DEFAULT NULL AFTER `recordatorio_minutos`;

SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- VERIFICACIÓN
-- =====================================================

SHOW COLUMNS FROM `configs`;

-- =====================================================
-- NOTA: Si algún ALTER TABLE da error de "Duplicate column name",
-- es porque esa columna ya existe. Puedes ignorar ese error y
-- continuar con el siguiente.
-- =====================================================
