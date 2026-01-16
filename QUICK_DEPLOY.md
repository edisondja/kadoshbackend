# ⚡ Despliegue Rápido - Migraciones a Producción

## 🚀 Método Rápido (Recomendado)

### 1. Backup Rápido

```bash
# Desde el servidor de producción
cd /ruta/a/kadoshbackend
mysqldump -u usuario -p nombre_db > backup_$(date +%Y%m%d).sql
```

### 2. Aplicar Migraciones

```bash
php artisan migrate --force
```

### 3. Verificar

```bash
php artisan migrate:status
```

---

## 📋 Lista de Migraciones a Aplicar

```
✅ 2025_07_01_003622_create_configs_table.php
✅ 2025_08_01_135747_create_logs_table.php
✅ 2025_10_11_135117_create_odontogramas_table.php
✅ 2025_10_13_224111_create_ficha_medicas_table.php
✅ 2026_01_13_005458_create_odontograma_detalles_table.php
✅ 2026_01_15_012918_modify_odontogramas_dibujo_column.php
✅ 2026_01_15_014626_create_pagos_mensuales_table.php
✅ 2026_01_15_015032_add_fields_to_logs_table.php
✅ 2026_01_15_020708_add_factura_fields_to_configs_table.php
✅ 2026_01_15_020917_add_google_event_id_to_citas_table.php
✅ 2026_01_15_024552_add_comision_to_procedimientos_table.php
✅ 2026_01_15_024559_create_pagos_nomina_table.php
✅ 2026_01_15_025557_create_salarios_doctores_table.php
✅ 2026_01_15_025602_add_tipo_to_facturas_table.php
✅ 2026_01_15_025617_add_precio_and_fields_to_productos_table.php
✅ 2026_01_15_030039_create_ventas_productos_table.php
✅ 2026_01_15_075020_create_recetas_table.php
✅ 2026_01_15_075625_add_especialidad_to_doctors_table.php
✅ 2026_01_15_131225_create_especialidads_table.php
✅ 2026_01_16_003029_add_clave_secreta_to_configs_table.php
```

**Total: 20 migraciones nuevas**

---

## ⚠️ Importante

1. **Siempre haz backup primero**
2. **Aplica en horario de bajo tráfico**
3. **Verifica después de aplicar**
4. **Ten el backup a mano por si necesitas rollback**

---

## 🔙 Rollback Rápido (Si algo falla)

```bash
# Restaurar backup
mysql -u usuario -p nombre_db < backup_YYYYMMDD.sql
```

---

**Para más detalles, consulta `DEPLOYMENT_GUIDE.md`**
