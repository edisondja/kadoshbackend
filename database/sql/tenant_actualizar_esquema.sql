-- =============================================================================
-- OdontoED / Kadosh — Actualizar tenant (tablas + columnas faltantes)
-- =============================================================================
-- Para bases que YA tienen las tablas principales (citas, configs, pacientes,
-- roles, role_modulos, doctor_invitaciones, paciente_invitaciones, recetas, etc.)
--
-- FALTA CREAR (si no existe):
--   • doctor_ganancias_recibos  — asignación de ganancias por recibo
--
-- MODIFICACIONES (solo si la columna no existe):
--   • usuarios: permisos, id_rol, foto_usuario
--   • doctors: correo_electronico, id_usuario, porcentaje_ingresos, especialidad
--   • configs: campos factura, Google Calendar, clave_secreta, mensaje_cumpleanos,
--              formato_hora_citas
--   • pacientes.cedula nullable
--   • citas.google_event_id
--   • facturas.tipo_factura
--   • procedimientos.comision
--   • productos: precio, codigo, categoria, stock_minimo, activo
--   • ficha_medicas.observaciones
--   • logs: descripcion, ip_address, user_agent
--   • odontogramas.dibujo_odontograma → LONGTEXT
--
-- USO:
--   USE `nombre_base_tenant`;
--   SOURCE tenant_actualizar_esquema.sql;
--   (o pegar y ejecutar en phpMyAdmin / MySQL Workbench)
-- =============================================================================

SET NAMES utf8mb4;
SET @db := DATABASE();

SELECT CONCAT('Actualizando tenant: ', @db) AS info;

-- =============================================================================
-- TABLA NUEVA: doctor_ganancias_recibos
-- =============================================================================
CREATE TABLE IF NOT EXISTS `doctor_ganancias_recibos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_recibo` int unsigned NOT NULL,
  `id_doctor` int unsigned NOT NULL,
  `ganancia_doctor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ganancia_clinica` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observaciones` text NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doctor_ganancias_recibos_recibo_doctor_unique` (`id_recibo`, `id_doctor`),
  KEY `doctor_ganancias_recibos_id_doctor_created_at_index` (`id_doctor`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FKs opcionales (si fallan por motor antiguo, la tabla igual sirve sin ellas)
SET @fk1 := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctor_ganancias_recibos'
    AND CONSTRAINT_NAME = 'doctor_ganancias_recibos_id_recibo_foreign'
);
SET @sql := IF(@fk1 = 0,
  'ALTER TABLE `doctor_ganancias_recibos`
     ADD CONSTRAINT `doctor_ganancias_recibos_id_recibo_foreign`
     FOREIGN KEY (`id_recibo`) REFERENCES `recibos` (`id`) ON DELETE CASCADE',
  'SELECT ''FK id_recibo ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk2 := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'doctor_ganancias_recibos'
    AND CONSTRAINT_NAME = 'doctor_ganancias_recibos_id_doctor_foreign'
);
SET @sql := IF(@fk2 = 0,
  'ALTER TABLE `doctor_ganancias_recibos`
     ADD CONSTRAINT `doctor_ganancias_recibos_id_doctor_foreign`
     FOREIGN KEY (`id_doctor`) REFERENCES `doctors` (`id`) ON DELETE CASCADE',
  'SELECT ''FK id_doctor ya existe'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- Tablas roles / role_modulos / invitaciones (por si faltan en copias viejas)
-- =============================================================================
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

CREATE TABLE IF NOT EXISTS `doctor_invitaciones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(80) NOT NULL,
  `id_doctor` int unsigned NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doctor_invitaciones_token_unique` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `paciente_invitaciones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(80) NOT NULL,
  `id_doctor` int unsigned NOT NULL,
  `telefono_destino` varchar(40) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paciente_invitaciones_token_unique` (`token`),
  KEY `paciente_invitaciones_id_doctor_index` (`id_doctor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned NOT NULL,
  `jti` varchar(64) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `user_agent` text,
  `dispositivo` varchar(120) DEFAULT NULL,
  `navegador` varchar(120) DEFAULT NULL,
  `last_active_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_sessions_jti_unique` (`jti`),
  KEY `user_sessions_usuario_idx` (`usuario_id`),
  KEY `user_sessions_activas_idx` (`revoked_at`,`expires_at`),
  CONSTRAINT `user_sessions_usuario_fk` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `asientos_contables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `origen_tipo` varchar(40) NOT NULL,
  `origen_id` int unsigned NOT NULL,
  `fecha` datetime NOT NULL,
  `tipo` varchar(30) NOT NULL,
  `tipo_label` varchar(80) NOT NULL,
  `referencia` varchar(120) DEFAULT NULL,
  `descripcion` text,
  `tercero` varchar(255) DEFAULT NULL,
  `forma_pago` varchar(120) DEFAULT NULL,
  `cuenta_debe` varchar(120) DEFAULT NULL,
  `cuenta_haber` varchar(120) DEFAULT NULL,
  `monto` decimal(14,2) NOT NULL DEFAULT 0.00,
  `naturaleza` varchar(20) NOT NULL,
  `id_factura` int unsigned DEFAULT NULL,
  `saldo_pendiente` decimal(14,2) DEFAULT NULL,
  `automatico` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asientos_origen_unique` (`origen_tipo`,`origen_id`),
  KEY `asientos_fecha_idx` (`fecha`),
  KEY `asientos_tipo_idx` (`tipo`),
  KEY `asientos_naturaleza_idx` (`naturaleza`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Macro: agregar columna si no existe (tabla, columna, definición ALTER)
-- =============================================================================
-- usuarios
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='usuarios' AND COLUMN_NAME='permisos');
SET @sql := IF(@exist=0,'ALTER TABLE `usuarios` ADD COLUMN `permisos` TEXT NULL','SELECT ''usuarios.permisos OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='usuarios' AND COLUMN_NAME='id_rol');
SET @sql := IF(@exist=0,'ALTER TABLE `usuarios` ADD COLUMN `id_rol` int unsigned NULL','SELECT ''usuarios.id_rol OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='usuarios' AND COLUMN_NAME='foto_usuario');
SET @sql := IF(@exist=0,'ALTER TABLE `usuarios` ADD COLUMN `foto_usuario` VARCHAR(255) NULL DEFAULT NULL','SELECT ''usuarios.foto_usuario OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='usuarios' AND COLUMN_NAME='bloqueado');
SET @sql := IF(@exist=0,'ALTER TABLE `usuarios` ADD COLUMN `bloqueado` tinyint(1) NOT NULL DEFAULT 0','SELECT ''usuarios.bloqueado OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='usuarios' AND COLUMN_NAME='bloqueado_at');
SET @sql := IF(@exist=0,'ALTER TABLE `usuarios` ADD COLUMN `bloqueado_at` timestamp NULL DEFAULT NULL','SELECT ''usuarios.bloqueado_at OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='usuarios' AND COLUMN_NAME='bloqueado_por');
SET @sql := IF(@exist=0,'ALTER TABLE `usuarios` ADD COLUMN `bloqueado_por` int unsigned NULL DEFAULT NULL','SELECT ''usuarios.bloqueado_por OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- doctors
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='doctors' AND COLUMN_NAME='correo_electronico');
SET @sql := IF(@exist=0,'ALTER TABLE `doctors` ADD COLUMN `correo_electronico` varchar(191) NULL','SELECT ''doctors.correo_electronico OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='doctors' AND COLUMN_NAME='id_usuario');
SET @sql := IF(@exist=0,'ALTER TABLE `doctors` ADD COLUMN `id_usuario` int unsigned NULL','SELECT ''doctors.id_usuario OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='doctors' AND COLUMN_NAME='porcentaje_ingresos');
SET @sql := IF(@exist=0,'ALTER TABLE `doctors` ADD COLUMN `porcentaje_ingresos` DECIMAL(5,2) NOT NULL DEFAULT 0','SELECT ''doctors.porcentaje_ingresos OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='doctors' AND COLUMN_NAME='especialidad');
SET @sql := IF(@exist=0,'ALTER TABLE `doctors` ADD COLUMN `especialidad` varchar(255) NULL','SELECT ''doctors.especialidad OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- configs (facturación, calendar, etc.)
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='configs' AND COLUMN_NAME='nombre_clinica');
SET @sql := IF(@exist=0,'ALTER TABLE `configs` ADD COLUMN `nombre_clinica` varchar(255) NULL','SELECT ''configs.nombre_clinica OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='configs' AND COLUMN_NAME='direccion_clinica');
SET @sql := IF(@exist=0,'ALTER TABLE `configs` ADD COLUMN `direccion_clinica` text NULL','SELECT ''configs.direccion_clinica OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='configs' AND COLUMN_NAME='telefono_clinica');
SET @sql := IF(@exist=0,'ALTER TABLE `configs` ADD COLUMN `telefono_clinica` varchar(255) NULL','SELECT ''configs.telefono_clinica OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='configs' AND COLUMN_NAME='rnc_clinica');
SET @sql := IF(@exist=0,'ALTER TABLE `configs` ADD COLUMN `rnc_clinica` varchar(255) NULL','SELECT ''configs.rnc_clinica OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='configs' AND COLUMN_NAME='email_clinica');
SET @sql := IF(@exist=0,'ALTER TABLE `configs` ADD COLUMN `email_clinica` varchar(255) NULL','SELECT ''configs.email_clinica OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='configs' AND COLUMN_NAME='tipo_numero_factura');
SET @sql := IF(@exist=0,'ALTER TABLE `configs` ADD COLUMN `tipo_numero_factura` varchar(20) NOT NULL DEFAULT ''comprobante''','SELECT ''configs.tipo_numero_factura OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='configs' AND COLUMN_NAME='prefijo_factura');
SET @sql := IF(@exist=0,'ALTER TABLE `configs` ADD COLUMN `prefijo_factura` varchar(255) NULL','SELECT ''configs.prefijo_factura OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='configs' AND COLUMN_NAME='usar_google_calendar');
SET @sql := IF(@exist=0,'ALTER TABLE `configs` ADD COLUMN `usar_google_calendar` tinyint(1) NOT NULL DEFAULT 0','SELECT ''configs.usar_google_calendar OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='configs' AND COLUMN_NAME='google_calendar_id');
SET @sql := IF(@exist=0,'ALTER TABLE `configs` ADD COLUMN `google_calendar_id` varchar(255) NULL','SELECT ''configs.google_calendar_id OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='configs' AND COLUMN_NAME='recordatorio_minutos');
SET @sql := IF(@exist=0,'ALTER TABLE `configs` ADD COLUMN `recordatorio_minutos` int NOT NULL DEFAULT 30','SELECT ''configs.recordatorio_minutos OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='configs' AND COLUMN_NAME='clave_secreta');
SET @sql := IF(@exist=0,'ALTER TABLE `configs` ADD COLUMN `clave_secreta` varchar(255) NULL','SELECT ''configs.clave_secreta OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='configs' AND COLUMN_NAME='mensaje_cumpleanos');
SET @sql := IF(@exist=0,'ALTER TABLE `configs` ADD COLUMN `mensaje_cumpleanos` text NULL','SELECT ''configs.mensaje_cumpleanos OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='configs' AND COLUMN_NAME='formato_hora_citas');
SET @sql := IF(@exist=0,'ALTER TABLE `configs` ADD COLUMN `formato_hora_citas` varchar(10) NOT NULL DEFAULT ''12h''','SELECT ''configs.formato_hora_citas OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='configs' AND COLUMN_NAME='tema_apariencia');
SET @sql := IF(@exist=0,'ALTER TABLE `configs` ADD COLUMN `tema_apariencia` varchar(20) NOT NULL DEFAULT ''classic''','SELECT ''configs.tema_apariencia OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `configs`
SET `formato_hora_citas` = '12h'
WHERE `formato_hora_citas` IS NULL
   OR TRIM(`formato_hora_citas`) = ''
   OR `formato_hora_citas` NOT IN ('12h', '24h');

UPDATE `configs`
SET `tema_apariencia` = 'classic'
WHERE `tema_apariencia` IS NULL
   OR TRIM(`tema_apariencia`) = ''
   OR `tema_apariencia` NOT IN ('classic', 'eda', 'violet', 'dark');

-- pacientes.cedula opcional
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pacientes' AND COLUMN_NAME='cedula');
SET @sql := IF(@exist>0,'ALTER TABLE `pacientes` MODIFY COLUMN `cedula` varchar(255) NULL DEFAULT NULL','SELECT ''pacientes.cedula N/A'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- citas
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='citas' AND COLUMN_NAME='google_event_id');
SET @sql := IF(@exist=0,'ALTER TABLE `citas` ADD COLUMN `google_event_id` varchar(255) NULL','SELECT ''citas.google_event_id OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- facturas
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='facturas' AND COLUMN_NAME='tipo_factura');
SET @sql := IF(@exist=0,'ALTER TABLE `facturas` ADD COLUMN `tipo_factura` varchar(50) NOT NULL DEFAULT ''servicio''','SELECT ''facturas.tipo_factura OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- procedimientos
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='procedimientos' AND COLUMN_NAME='comision');
SET @sql := IF(@exist=0,'ALTER TABLE `procedimientos` ADD COLUMN `comision` decimal(5,2) NOT NULL DEFAULT 0','SELECT ''procedimientos.comision OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- productos
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='productos' AND COLUMN_NAME='precio');
SET @sql := IF(@exist=0,'ALTER TABLE `productos` ADD COLUMN `precio` decimal(10,2) NOT NULL DEFAULT 0','SELECT ''productos.precio OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='productos' AND COLUMN_NAME='codigo');
SET @sql := IF(@exist=0,'ALTER TABLE `productos` ADD COLUMN `codigo` varchar(255) NULL','SELECT ''productos.codigo OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='productos' AND COLUMN_NAME='categoria');
SET @sql := IF(@exist=0,'ALTER TABLE `productos` ADD COLUMN `categoria` varchar(255) NULL','SELECT ''productos.categoria OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='productos' AND COLUMN_NAME='stock_minimo');
SET @sql := IF(@exist=0,'ALTER TABLE `productos` ADD COLUMN `stock_minimo` int NOT NULL DEFAULT 0','SELECT ''productos.stock_minimo OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='productos' AND COLUMN_NAME='activo');
SET @sql := IF(@exist=0,'ALTER TABLE `productos` ADD COLUMN `activo` tinyint(1) NOT NULL DEFAULT 1','SELECT ''productos.activo OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ficha_medicas
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ficha_medicas' AND COLUMN_NAME='observaciones');
SET @sql := IF(@exist=0,'ALTER TABLE `ficha_medicas` ADD COLUMN `observaciones` text NULL','SELECT ''ficha_medicas.observaciones OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- logs
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='logs' AND COLUMN_NAME='descripcion');
SET @sql := IF(@exist=0,'ALTER TABLE `logs` ADD COLUMN `descripcion` text NULL','SELECT ''logs.descripcion OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='logs' AND COLUMN_NAME='ip_address');
SET @sql := IF(@exist=0,'ALTER TABLE `logs` ADD COLUMN `ip_address` varchar(255) NULL','SELECT ''logs.ip_address OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='logs' AND COLUMN_NAME='user_agent');
SET @sql := IF(@exist=0,'ALTER TABLE `logs` ADD COLUMN `user_agent` text NULL','SELECT ''logs.user_agent OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- odontogramas: dibujo más grande
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='odontogramas' AND COLUMN_NAME='dibujo_odontograma');
SET @sql := IF(@exist>0,'ALTER TABLE `odontogramas` MODIFY COLUMN `dibujo_odontograma` longtext NULL','SELECT ''odontogramas.dibujo_odontograma N/A'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- Nómina empleados: salario base + deducciones opcionales
-- =============================================================================
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='activo');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `activo` tinyint(1) NOT NULL DEFAULT 1','SELECT ''empleados.activo OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='aplica_afp');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `aplica_afp` tinyint(1) NOT NULL DEFAULT 0','SELECT ''empleados.aplica_afp OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='aplica_sfs');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `aplica_sfs` tinyint(1) NOT NULL DEFAULT 0','SELECT ''empleados.aplica_sfs OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='aplica_isr');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `aplica_isr` tinyint(1) NOT NULL DEFAULT 0','SELECT ''empleados.aplica_isr OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='porcentaje_afp');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `porcentaje_afp` decimal(5,2) NOT NULL DEFAULT 2.87','SELECT ''empleados.porcentaje_afp OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='porcentaje_sfs');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `porcentaje_sfs` decimal(5,2) NOT NULL DEFAULT 3.04','SELECT ''empleados.porcentaje_sfs OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='porcentaje_isr');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `porcentaje_isr` decimal(5,2) NULL DEFAULT NULL','SELECT ''empleados.porcentaje_isr OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='otros_descuentos');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `otros_descuentos` decimal(10,2) NOT NULL DEFAULT 0','SELECT ''empleados.otros_descuentos OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='comentarios_nomina');
SET @sql := IF(@exist=0,'ALTER TABLE `empleados` ADD COLUMN `comentarios_nomina` text NULL','SELECT ''empleados.comentarios_nomina OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pagos_nomina' AND COLUMN_NAME='total_bruto');
SET @sql := IF(@exist=0,'ALTER TABLE `pagos_nomina` ADD COLUMN `total_bruto` decimal(10,2) NOT NULL DEFAULT 0','SELECT ''pagos_nomina.total_bruto OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pagos_nomina' AND COLUMN_NAME='total_deducciones');
SET @sql := IF(@exist=0,'ALTER TABLE `pagos_nomina` ADD COLUMN `total_deducciones` decimal(10,2) NOT NULL DEFAULT 0','SELECT ''pagos_nomina.total_deducciones OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pagos_nomina' AND COLUMN_NAME='neto_deposito');
SET @sql := IF(@exist=0,'ALTER TABLE `pagos_nomina` ADD COLUMN `neto_deposito` decimal(10,2) NOT NULL DEFAULT 0','SELECT ''pagos_nomina.neto_deposito OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pagos_nomina' AND COLUMN_NAME='deducciones_detalle');
SET @sql := IF(@exist=0,'ALTER TABLE `pagos_nomina` ADD COLUMN `deducciones_detalle` text NULL','SELECT ''pagos_nomina.deducciones_detalle OK'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- Usuarios existentes (edison, etc.): permisos NULL → JSON según roll
-- =============================================================================
UPDATE `usuarios`
SET
  `permisos` = JSON_OBJECT(
    'cargar_pacientes', true, 'paciente', true, 'invitar_paciente', true, 'doctor', true,
    'asignar_ganancias_recibos', true, 'procedimiento', true, 'agregar_usuario', true,
    'especialidades', true, 'notificaciones', true, 'agregar_cita', true, 'contabilidad', true,
    'nomina', true, 'punto_venta', true, 'salarios_doctores', true, 'historial_pagos', true,
    'consulta_deudas', true, 'reportes', true, 'auditoria', true, 'configuracion', true,
    'exportar_importar', true, 'administrar_tenants', true, 'manual_usuario', true
  ),
  `updated_at` = NOW()
WHERE `roll` = 'Administrador'
  AND (`permisos` IS NULL OR TRIM(`permisos`) = '' OR `permisos` = 'null');

-- Permiso exportar/importar para todos los roles
INSERT INTO `role_modulos` (`id_rol`, `modulo`, `permitido`, `created_at`, `updated_at`)
SELECT r.id, 'exportar_importar', 1, NOW(), NOW()
FROM `roles` r
WHERE NOT EXISTS (
  SELECT 1 FROM `role_modulos` rm
  WHERE rm.id_rol = r.id AND rm.modulo = 'exportar_importar'
);

-- Presupuestos de consulta: permitir paciente_id NULL (estimación sin paciente registrado)
SET @fk_pres := (
  SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='presupuestos' AND COLUMN_NAME='paciente_id'
    AND REFERENCED_TABLE_NAME IS NOT NULL
  LIMIT 1
);
SET @sql := IF(
  @fk_pres IS NOT NULL,
  CONCAT('ALTER TABLE `presupuestos` DROP FOREIGN KEY `', @fk_pres, '`'),
  'SELECT ''FK presupuestos.paciente_id OK'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @nullable_pres := (
  SELECT IS_NULLABLE FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='presupuestos' AND COLUMN_NAME='paciente_id'
  LIMIT 1
);
SET @sql := IF(
  @nullable_pres = 'NO',
  'ALTER TABLE `presupuestos` MODIFY COLUMN `paciente_id` INT UNSIGNED NULL',
  'SELECT ''presupuestos.paciente_id nullable OK'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Odontograma: posición de tarjetas conectadas (coordenadas normalizadas 0–1)
SET @col_card_x := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='odontograma_detalles' AND COLUMN_NAME='card_pos_x_pct'
);
SET @sql := IF(
  @col_card_x = 0,
  'ALTER TABLE `odontograma_detalles` ADD COLUMN `card_pos_x_pct` DECIMAL(8,6) NULL DEFAULT NULL AFTER `color`',
  'SELECT ''odontograma_detalles.card_pos_x_pct OK'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_card_y := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='odontograma_detalles' AND COLUMN_NAME='card_pos_y_pct'
);
SET @sql := IF(
  @col_card_y = 0,
  'ALTER TABLE `odontograma_detalles` ADD COLUMN `card_pos_y_pct` DECIMAL(8,6) NULL DEFAULT NULL AFTER `card_pos_x_pct`',
  'SELECT ''odontograma_detalles.card_pos_y_pct OK'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- VERIFICACIÓN
-- =============================================================================
SELECT 'TABLA doctor_ganancias_recibos' AS item,
       IF(COUNT(*)>0,'OK','FALTA') AS estado
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=@db AND TABLE_NAME='doctor_ganancias_recibos'

UNION ALL SELECT 'usuarios.foto_usuario', IF(COUNT(*)>0,'OK','FALTA')
FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='usuarios' AND COLUMN_NAME='foto_usuario'

UNION ALL SELECT 'doctors.porcentaje_ingresos', IF(COUNT(*)>0,'OK','FALTA')
FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='doctors' AND COLUMN_NAME='porcentaje_ingresos'

UNION ALL SELECT 'configs.formato_hora_citas', IF(COUNT(*)>0,'OK','FALTA')
FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='configs' AND COLUMN_NAME='formato_hora_citas'

UNION ALL SELECT 'configs.email_clinica', IF(COUNT(*)>0,'OK','FALTA')
FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='configs' AND COLUMN_NAME='email_clinica'

UNION ALL SELECT 'pacientes.cedula nullable', IF(IS_NULLABLE='YES','OK','REVISAR')
FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pacientes' AND COLUMN_NAME='cedula'

UNION ALL SELECT 'facturas.tipo_factura', IF(COUNT(*)>0,'OK','FALTA')
FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='facturas' AND COLUMN_NAME='tipo_factura'

UNION ALL SELECT 'procedimientos.comision', IF(COUNT(*)>0,'OK','FALTA')
FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='procedimientos' AND COLUMN_NAME='comision'

UNION ALL SELECT 'citas.google_event_id', IF(COUNT(*)>0,'OK','FALTA')
FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='citas' AND COLUMN_NAME='google_event_id'

UNION ALL SELECT 'empleados.aplica_afp', IF(COUNT(*)>0,'OK','FALTA')
FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='empleados' AND COLUMN_NAME='aplica_afp'

UNION ALL SELECT 'pagos_nomina.neto_deposito', IF(COUNT(*)>0,'OK','FALTA')
FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='pagos_nomina' AND COLUMN_NAME='neto_deposito';

-- Listado de tablas del tenant (debe incluir doctor_ganancias_recibos al final)
SELECT TABLE_NAME AS tablas_en_bd
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = @db AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME;
