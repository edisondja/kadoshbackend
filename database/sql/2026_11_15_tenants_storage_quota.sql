-- Cuotas de almacenamiento por clínica (BD maestra donde vive `tenants`)
-- Si la columna ya existe, ignore el error de duplicado.

ALTER TABLE `tenants`
  ADD COLUMN `storage_quota_gb` DECIMAL(8,2) NOT NULL DEFAULT 5.00 AFTER `notas`;

ALTER TABLE `tenants`
  ADD COLUMN `storage_used_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `storage_quota_gb`;
