#!/bin/bash

# Script para aplicar corrección de odontogramas en producción
# Uso: ./aplicar_fix_odontogramas.sh

echo "🔧 Aplicando corrección de odontogramas en producción..."
echo ""

# Solicitar credenciales
printf "Usuario MySQL: "
read DB_USER
printf "Contraseña MySQL: "
stty -echo 2>/dev/null || true
read DB_PASS
stty echo 2>/dev/null || true
echo ""

# Obtener lista de bases de datos
printf "Obteniendo lista de bases de datos...\n"
ALL_DATABASES=$(mysql -u "$DB_USER" -p"$DB_PASS" -e "SHOW DATABASES;" 2>/dev/null | grep -v -E "Database|information_schema|performance_schema|mysql|sys" || true)

if [ -z "$ALL_DATABASES" ]; then
    echo "❌ No se encontraron bases de datos"
    exit 1
fi

echo ""
echo "Bases de datos encontradas:"
echo "$ALL_DATABASES" | nl
echo ""
printf "¿Aplicar corrección a todas? (s/n): "
read REPLY

if [ "$REPLY" != "s" ] && [ "$REPLY" != "S" ]; then
    printf "Patrón para filtrar (ej: kadosh_): "
    read PATTERN
    if [ ! -z "$PATTERN" ]; then
        ALL_DATABASES=$(echo "$ALL_DATABASES" | grep "$PATTERN" || true)
    fi
fi

# Aplicar corrección a cada base de datos
for DB_NAME in $ALL_DATABASES; do
    if [ ! -z "$DB_NAME" ]; then
        echo ""
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo "🔧 Corrigiendo: $DB_NAME"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        
        mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < fix_odontogramas_produccion.sql 2>&1 | grep -v "Using a password"
        
        if [ ${PIPESTATUS[0]} -eq 0 ]; then
            echo "✅ Corrección aplicada correctamente a $DB_NAME"
        else
            echo "❌ Error al aplicar corrección a $DB_NAME"
        fi
    fi
done

echo ""
echo "✅ Proceso completado"
