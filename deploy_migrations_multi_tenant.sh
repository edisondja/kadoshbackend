#!/bin/bash

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
    printf "${RED}❌ Error: No se encontró el archivo artisan${NC}\n"
    printf "${YELLOW}💡 Asegúrate de ejecutar este script desde el directorio kadoshbackend${NC}\n"
    exit 1
fi

# Crear directorio de backups si no existe
mkdir -p "$BACKUP_DIR"

printf "${BLUE}═══════════════════════════════════════════════════════════${NC}\n"
printf "${BLUE}  CONFIGURACIÓN MULTI-TENANT${NC}\n"
printf "${BLUE}═══════════════════════════════════════════════════════════${NC}\n"
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
printf "${YELLOW}🔍 Obteniendo lista de bases de datos...${NC}\n"
MYSQL_OUTPUT=$(mysql -u "$DB_USER" -p"$DB_PASS" -e "SHOW DATABASES;" 2>&1)
MYSQL_EXIT_CODE=$?

if [ $MYSQL_EXIT_CODE -ne 0 ]; then
    printf "${RED}❌ Error al conectar con MySQL:${NC}\n"
    echo "$MYSQL_OUTPUT" | head -5
    exit 1
fi

ALL_DATABASES=$(echo "$MYSQL_OUTPUT" | grep -v -E "Database|information_schema|performance_schema|mysql|sys" || true)

# Opción 2: Permitir especificar patrón
echo ""
printf "${CYAN}Bases de datos encontradas:${NC}\n"
if [ -z "$ALL_DATABASES" ]; then
    printf "${RED}No se encontraron bases de datos${NC}\n"
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
        printf "${RED}No se encontraron bases de datos con ese patrón${NC}\n"
        exit 1
    fi
    
    echo ""
    printf "${CYAN}Bases de datos que se procesarán:${NC}\n"
    echo "$ALL_DATABASES" | nl
    echo ""
    printf "¿Continuar con estas bases de datos? (s/n): "
    read REPLY
    echo ""
    if [ "$REPLY" != "s" ] && [ "$REPLY" != "S" ]; then
        printf "${YELLOW}⚠️  Despliegue cancelado por el usuario${NC}\n"
        exit 0
    fi
fi

# Contar bases de datos
DB_COUNT=$(echo "$ALL_DATABASES" | wc -l | tr -d ' ')
echo ""
printf "${BLUE}═══════════════════════════════════════════════════════════${NC}\n"
printf "${BLUE}  PROCESANDO $DB_COUNT BASES DE DATOS${NC}\n"
printf "${BLUE}═══════════════════════════════════════════════════════════${NC}\n"
echo ""

# Función para procesar una base de datos
process_database() {
    local DB_NAME=$1
    local DB_NUM=$2
    local TOTAL=$3
    
    echo ""
    printf "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
    printf "${CYAN}  [$DB_NUM/$TOTAL] Procesando: ${GREEN}$DB_NAME${NC}\n"
    printf "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
    
    # Crear backup
    printf "${YELLOW}📦 Creando backup de '$DB_NAME'...${NC}\n"
    BACKUP_FILE="$BACKUP_DIR/backup_${DB_NAME}_${TIMESTAMP}.sql"
    
    mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
        printf "${GREEN}✅ Backup creado: $BACKUP_SIZE${NC}\n"
    else
        printf "${RED}❌ Error al crear backup de '$DB_NAME'${NC}\n"
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
        # Detectar sistema operativo
        if [ "$(uname)" = "Darwin" ]; then
            # macOS
            sed -i '' "s/^DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
        else
            # Linux
            sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
        fi
    else
        printf "${RED}❌ No se encontró archivo .env${NC}\n"
        return 1
    fi
    
    # Verificar estado de migraciones
    printf "${YELLOW}🔍 Verificando estado de migraciones...${NC}\n"
    php artisan migrate:status > /dev/null 2>&1 || true
    
    # Aplicar migraciones
    printf "${YELLOW}🚀 Aplicando migraciones...${NC}\n"
    php artisan migrate --force > "/tmp/migrate_${DB_NAME}.log" 2>&1
    
    if [ $? -eq 0 ]; then
        printf "${GREEN}✅ Migraciones aplicadas correctamente${NC}\n"
        
        # Verificar estado final
        PENDING=$(php artisan migrate:status 2>/dev/null | grep -c "Pending" || echo "0")
        if [ "$PENDING" -eq 0 ]; then
            printf "${GREEN}✅ Todas las migraciones están aplicadas${NC}\n"
            SUCCESS_COUNT=$((SUCCESS_COUNT + 1))
        else
            printf "${YELLOW}⚠️  Quedan $PENDING migraciones pendientes${NC}\n"
        fi
    else
        printf "${RED}❌ Error al aplicar migraciones${NC}\n"
        printf "${YELLOW}💡 Revisa el log: /tmp/migrate_${DB_NAME}.log${NC}\n"
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
    printf "${GREEN}✅ Archivo .env restaurado${NC}\n"
fi

# Resumen final
echo ""
printf "${BLUE}═══════════════════════════════════════════════════════════${NC}\n"
printf "${BLUE}  RESUMEN DEL DESPLIEGUE${NC}\n"
printf "${BLUE}═══════════════════════════════════════════════════════════${NC}\n"
echo ""
printf "${GREEN}✅ Exitosas: $SUCCESS_COUNT${NC}\n"
printf "${RED}❌ Fallidas: $FAILED_COUNT${NC}\n"
echo ""

if [ $FAILED_COUNT -gt 0 ]; then
    printf "${RED}Bases de datos con errores:${NC}\n"
    if [ ! -z "$FAILED_DBS" ]; then
        printf "${RED}$FAILED_DBS${NC}\n" | sed 's/^/  • /'
    fi
    echo ""
    printf "${YELLOW}💡 Para restaurar un backup específico:${NC}\n"
    printf "${YELLOW}   mysql -u $DB_USER -p nombre_db < backups/backup_nombre_db_${TIMESTAMP}.sql${NC}\n"
fi

echo ""
printf "${GREEN}📦 Backups guardados en: $BACKUP_DIR${NC}\n"
echo ""
printf "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"
if [ $FAILED_COUNT -eq 0 ]; then
    printf "${GREEN}  ✅ DESPLIEGUE COMPLETADO EXITOSAMENTE${NC}\n"
else
    printf "${YELLOW}  ⚠️  DESPLIEGUE COMPLETADO CON ERRORES${NC}\n"
fi
printf "${GREEN}═══════════════════════════════════════════════════════════${NC}\n"
echo ""
