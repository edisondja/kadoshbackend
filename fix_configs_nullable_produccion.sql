-- Ejecutar en producción para corregir el error: Column 'api_whatapps' cannot be null
-- Ejecutar como: mysql -u usuario -p nombre_bd < fix_configs_nullable_produccion.sql

ALTER TABLE configs MODIFY COLUMN api_whatapps VARCHAR(255) NULL DEFAULT '';
ALTER TABLE configs MODIFY COLUMN api_token_ws VARCHAR(255) NULL DEFAULT '';
ALTER TABLE configs MODIFY COLUMN api_gmail VARCHAR(255) NULL DEFAULT '';
ALTER TABLE configs MODIFY COLUMN api_token_google VARCHAR(255) NULL DEFAULT '';
ALTER TABLE configs MODIFY COLUMN api_instagram VARCHAR(255) NULL DEFAULT '';
ALTER TABLE configs MODIFY COLUMN token_instagram VARCHAR(255) NULL DEFAULT '';
ALTER TABLE configs MODIFY COLUMN prefijo_factura VARCHAR(255) NULL DEFAULT '';
ALTER TABLE configs MODIFY COLUMN google_calendar_id VARCHAR(255) NULL DEFAULT '';
