-- Cuota por defecto 10 GB para clínicas que aún tienen el default anterior (5)
-- o NULL. No toca cuotas personalizadas mayores a 10.
UPDATE tenants
SET storage_quota_gb = 10
WHERE storage_quota_gb IS NULL OR storage_quota_gb = 5;
