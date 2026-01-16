#!/usr/bin/env bash

# Script de Despliegue de Migraciones Multi-Tenant - Kadosh
# Uso: ./deploy_migrations_multi_tenant.sh
# Autor: Edison De Jesus Abreu
# Email: edisondja@gmail.com

set -e  # Detener si hay errores

echo "🚀 Iniciando despliegue de migraciones Multi-Tenant para Kadosh..."
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Variables
BACKUP_DIR="./backups"
PROJECT_DIR=$(pwd)
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
SUCCESS_COUNT=0
FAILED_COUNT=0
FAILED_DBS=""

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: No se encontró el archivo artisan${NC}"
    echo -e "${YELLOW}💡 Asegúrate de ejecutar este script desde el directorio kadoshbackend${NC}"
    exit 1
fi

# Crear directorio de backups si no existe
mkdir -p "$BACKUP_DIR"

echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  CONFIGURACIÓN MULTI-TENANT${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

# Solicitar credenciales de base de datos
printf "Ingresa el usuario de MySQL: "
read DB_USER
printf "Ingresa la contraseña de MySQL: "
stty -echo 2>/dev/null || true
read DB_PASS
stty echo 2>/dev/null || true
echo ""
echo ""

# Opción 1: Listar bases de datos automáticamente
echo -e "${YELLOW}🔍 Obteniendo lista de bases de datos...${NC}"
ALL_DATABASES=$(mysql -u "$DB_USER" -p"$DB_PASS" -e "SHOW DATABASES;" 2>/dev/null | grep -v -E "Database|information_schema|performance_schema|mysql|sys" || true)

# Opción 2: Permitir especificar patrón
echo ""
echo -e "${CYAN}Bases de datos encontradas:${NC}"
if [ -z "$ALL_DATABASES" ]; then
    echo -e "${RED}No se encontraron bases de datos${NC}"
    exit 1
fi
echo "$ALL_DATABASES" | nl
echo ""
printf "¿Usar todas las bases de datos? (s/n): "
read REPLY

if [ "$REPLY" != "s" ] && [ "$REPLY" != "S" ]; then
    echo ""
    printf "Ingresa el patrón para filtrar bases de datos (ej: kadosh_, tenant_): "
    read DB_PATTERN
    if [ ! -z "$DB_PATTERN" ]; then
        ALL_DATABASES=$(echo "$ALL_DATABASES" | grep "$DB_PATTERN" || true)
    fi
    
    if [ -z "$ALL_DATABASES" ]; then
        echo -e "${RED}No se encontraron bases de datos con ese patrón${NC}"
        exit 1
    fi
    
    echo ""
    echo -e "${CYAN}Bases de datos que se procesarán:${NC}"
    echo "$ALL_DATABASES" | nl
    echo ""
    printf "¿Continuar con estas bases de datos? (s/n): "
    read REPLY
    echo ""
    if [ "$REPLY" != "s" ] && [ "$REPLY" != "S" ]; then
        echo -e "${YELLOW}⚠️  Despliegue cancelado por el usuario${NC}"
        exit 0
    fi
fi

# Contar bases de datos
DB_COUNT=$(echo "$ALL_DATABASES" | wc -l | tr -d ' ')
echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  PROCESANDO $DB_COUNT BASES DE DATOS${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

# Función para procesar una base de datos
process_database() {
    local DB_NAME=$1
    local DB_NUM=$2
    local TOTAL=$3
    
    echo ""
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  [$DB_NUM/$TOTAL] Procesando: ${GREEN}$DB_NAME${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    
    # Crear backup
    echo -e "${YELLOW}📦 Creando backup de '$DB_NAME'...${NC}"
    BACKUP_FILE="$BACKUP_DIR/backup_${DB_NAME}_${TIMESTAMP}.sql"
    
    mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
        echo -e "${GREEN}✅ Backup creado: $BACKUP_SIZE${NC}"
    else
        echo -e "${RED}❌ Error al crear backup de '$DB_NAME'${NC}"
        FAILED_COUNT=$((FAILED_COUNT + 1))
        FAILED_DBS="$FAILED_DBS$DB_NAME (backup falló)\n"
        return 1
    fi
    
    # Actualizar .env temporalmente para esta base de datos
    if [ -f ".env" ]; then
        # Guardar .env original (solo la primera vez)
        if [ ! -f ".env.backup_$TIMESTAMP" ]; then
            cp .env ".env.backup_$TIMESTAMP"
        fi
        
        # Actualizar DB_DATABASE en .env
        if [[ "$OSTYPE" == "darwin"* ]] || [[ "$OSTYPE" == "freebsd"* ]]; then
            # macOS/FreeBSD
            sed -i '' "s/^DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
        else
            # Linux
            sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
        fi
    else
        echo -e "${RED}❌ No se encontró archivo .env${NC}"
        return 1
    fi
    
    # Verificar estado de migraciones
    echo -e "${YELLOW}🔍 Verificando estado de migraciones...${NC}"
    php artisan migrate:status > /dev/null 2>&1 || true
    
    # Aplicar migraciones
    echo -e "${YELLOW}🚀 Aplicando migraciones...${NC}"
    php artisan migrate --force > "/tmp/migrate_${DB_NAME}.log" 2>&1
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Migraciones aplicadas correctamente${NC}"
        
        # Verificar estado final
        PENDING=$(php artisan migrate:status 2>/dev/null | grep -c "Pending" || echo "0")
        if [ "$PENDING" -eq 0 ]; then
            echo -e "${GREEN}✅ Todas las migraciones están aplicadas${NC}"
            SUCCESS_COUNT=$((SUCCESS_COUNT + 1))
        else
            echo -e "${YELLOW}⚠️  Quedan $PENDING migraciones pendientes${NC}"
        fi
    else
        echo -e "${RED}❌ Error al aplicar migraciones${NC}"
        echo -e "${YELLOW}💡 Revisa el log: /tmp/migrate_${DB_NAME}.log${NC}"
        FAILED_COUNT=$((FAILED_COUNT + 1))
        FAILED_DBS="$FAILED_DBS$DB_NAME (migración falló)\n"
        return 1
    fi
    
    return 0
}

# Procesar cada base de datos
DB_NUM=0
echo "$ALL_DATABASES" | while IFS= read -r DB_NAME; do
    if [ ! -z "$DB_NAME" ]; then
        DB_NUM=$((DB_NUM + 1))
        process_database "$DB_NAME" $DB_NUM $DB_COUNT
        
        # Pequeña pausa entre bases de datos
        sleep 1
    fi
done

# Restaurar .env original al final
if [ -f ".env.backup_$TIMESTAMP" ]; then
    mv ".env.backup_$TIMESTAMP" .env
    echo -e "${GREEN}✅ Archivo .env restaurado${NC}"
fi

# Resumen final
echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  RESUMEN DEL DESPLIEGUE${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${GREEN}✅ Exitosas: $SUCCESS_COUNT${NC}"
echo -e "${RED}❌ Fallidas: $FAILED_COUNT${NC}"
echo ""

if [ $FAILED_COUNT -gt 0 ]; then
    echo -e "${RED}Bases de datos con errores:${NC}"
    if [ ! -z "$FAILED_DBS" ]; then
        echo -e "${RED}$FAILED_DBS${NC}" | sed 's/^/  • /'
    fi
    echo ""
    echo -e "${YELLOW}💡 Para restaurar un backup específico:${NC}"
    echo -e "${YELLOW}   mysql -u $DB_USER -p nombre_db < backups/backup_nombre_db_${TIMESTAMP}.sql${NC}"
fi

echo ""
echo -e "${GREEN}📦 Backups guardados en: $BACKUP_DIR${NC}"
echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
if [ $FAILED_COUNT -eq 0 ]; then
    echo -e "${GREEN}  ✅ DESPLIEGUE COMPLETADO EXITOSAMENTE${NC}"
else
    echo -e "${YELLOW}  ⚠️  DESPLIEGUE COMPLETADO CON ERRORES${NC}"
fi
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
