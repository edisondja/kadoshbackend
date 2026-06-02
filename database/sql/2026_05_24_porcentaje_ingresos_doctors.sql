-- Porcentaje de ingreso del doctor sobre pagos de facturas (recibos)
ALTER TABLE `doctors`
  ADD COLUMN `porcentaje_ingresos` DECIMAL(5,2) NOT NULL DEFAULT 0
  AFTER `estado`;
