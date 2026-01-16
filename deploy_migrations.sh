#!/bin/bash

# Script de Despliegue de Migraciones - Kadosh
# Uso: ./deploy_migrations.sh
# Autor: Edison De Jesus Abreu
# Email: edisondja@gmail.com

set -e  # Detener si hay errores

echo "🚀 Iniciando despliegue de migraciones de Kadosh..."
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables (ajusta según tu entorno)
BACKUP_DIR="./backups"
PROJECT_DIR=$(pwd)
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: No se encontró el archivo artisan${NC}"
    echo -e "${YELLOW}💡 Asegúrate de ejecutar este script desde el directorio kadoshbackend${NC}"
    exit 1
fi

# Crear directorio de backups si no existe
mkdir -p $BACKUP_DIR

echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  PASO 1: BACKUP DE BASE DE DATOS${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

# Solicitar credenciales de base de datos
read -p "Ingresa el nombre de la base de datos: " DB_NAME
read -p "Ingresa el usuario de la base de datos: " DB_USER
read -sp "Ingresa la contraseña de la base de datos: " DB_PASS
echo ""

BACKUP_FILE="$BACKUP_DIR/backup_${DB_NAME}_${TIMESTAMP}.sql"

echo -e "${YELLOW}📦 Creando backup de la base de datos '$DB_NAME'...${NC}"
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_FILE 2>/dev/null

if [ $? -eq 0 ]; then
    BACKUP_SIZE=$(du -h $BACKUP_FILE | cut -f1)
    echo -e "${GREEN}✅ Backup creado exitosamente${NC}"
    echo -e "${GREEN}   Archivo: $BACKUP_FILE${NC}"
    echo -e "${GREEN}   Tamaño: $BACKUP_SIZE${NC}"
else
    echo -e "${RED}❌ Error al crear backup. Verifica las credenciales.${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  PASO 2: VERIFICAR ESTADO ACTUAL${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${YELLOW}🔍 Verificando estado de migraciones...${NC}"
php artisan migrate:status

echo ""
read -p "¿Continuar con la aplicación de migraciones? (s/n): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo -e "${YELLOW}⚠️  Despliegue cancelado por el usuario${NC}"
    exit 0
fi

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  PASO 3: APLICAR MIGRACIONES${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${YELLOW}🚀 Aplicando migraciones...${NC}"
php artisan migrate --force

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Migraciones aplicadas correctamente${NC}"
else
    echo -e "${RED}❌ Error al aplicar migraciones${NC}"
    echo ""
    echo -e "${YELLOW}💡 Para restaurar el backup ejecuta:${NC}"
    echo -e "${YELLOW}   mysql -u $DB_USER -p $DB_NAME < $BACKUP_FILE${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  PASO 4: VERIFICACIÓN POST-MIGRACIÓN${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${YELLOW}🔍 Verificando estado final de migraciones...${NC}"
php artisan migrate:status

echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ DESPLIEGUE COMPLETADO EXITOSAMENTE${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}📋 Resumen:${NC}"
echo -e "   • Backup guardado en: ${GREEN}$BACKUP_FILE${NC}"
echo -e "   • Tamaño del backup: ${GREEN}$BACKUP_SIZE${NC}"
echo -e "   • Todas las migraciones aplicadas correctamente"
echo ""
echo -e "${YELLOW}💡 Próximos pasos:${NC}"
echo -e "   1. Verificar que el frontend funciona correctamente"
echo -e "   2. Probar los nuevos módulos (odontogramas, recetas, etc.)"
echo -e "   3. Verificar que los datos existentes no se afectaron"
echo ""
echo -e "${GREEN}¡Despliegue completado! 🎉${NC}"
