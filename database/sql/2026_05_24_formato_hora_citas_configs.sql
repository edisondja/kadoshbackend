ALTER TABLE `configs`
  ADD COLUMN `formato_hora_citas` VARCHAR(10) NOT NULL DEFAULT '12h'
  AFTER `recordatorio_minutos`;
