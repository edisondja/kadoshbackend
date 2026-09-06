#!/usr/bin/env bash
# =============================================================================
# Provisiona (o actualiza) un VirtualHost Apache2 para un tenant.
#
# Uso (como root o con sudoers restringido):
#   ./provision-apache-vhost.sh <dominio> <document_root>
#
# Ejemplo:
#   sudo ./provision-apache-vhost.sh isalex.odontoed.com /var/www/isalex.odontoed.com/public_html
#
# En Ubuntu:
#   /etc/apache2/sites-available/<dominio>.conf
#   a2ensite <dominio>.conf
#   apache2ctl configtest && systemctl reload apache2
# =============================================================================

set -euo pipefail

DOMINIO="${1:-}"
DOCROOT="${2:-}"
SITES_AVAILABLE="${APACHE_SITES_AVAILABLE:-/etc/apache2/sites-available}"
SITES_ENABLED="${APACHE_SITES_ENABLED:-/etc/apache2/sites-enabled}"

if [[ -z "$DOMINIO" || -z "$DOCROOT" ]]; then
  echo "Uso: $0 <dominio> <document_root>"
  exit 1
fi

# Seguridad básica: solo bajo /var/www
case "$DOCROOT" in
  /var/www/*) ;;
  *)
    echo "ERROR: document_root debe estar bajo /var/www/ (recibido: $DOCROOT)"
    exit 1
    ;;
esac

# Validar dominio simple
if ! [[ "$DOMINIO" =~ ^[a-zA-Z0-9]([a-zA-Z0-9.-]*[a-zA-Z0-9])?$ ]]; then
  echo "ERROR: dominio inválido: $DOMINIO"
  exit 1
fi

mkdir -p "$DOCROOT"

# .htaccess SPA por defecto si no existe
if [[ ! -f "$DOCROOT/.htaccess" ]]; then
  cat > "$DOCROOT/.htaccess" <<'HTACCESS'
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^static/ - [L]
  RewriteRule ^manifest\.json$ - [L]
  RewriteRule ^favicon\.ico$ - [L]
  RewriteCond %{REQUEST_URI} \.(js|css|map|json|ico|png|jpe?g|gif|svg|webp|woff2?|ttf|eot)$ [NC]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule ^ - [R=404,L]
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} -f [OR]
  RewriteCond %{REQUEST_FILENAME} -d
  RewriteRule ^ - [L]
  RewriteRule ^ /index.html [L]
</IfModule>
HTACCESS
fi

CONF_NAME="${DOMINIO}.conf"
CONF_PATH="${SITES_AVAILABLE}/${CONF_NAME}"

cat > "$CONF_PATH" <<EOF
# Generado por Kadosh / OdontoED — no editar a mano salvo necesidad
<VirtualHost *:80>
    ServerName ${DOMINIO}
    ServerAlias www.${DOMINIO}
    DocumentRoot ${DOCROOT}

    <Directory ${DOCROOT}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/${DOMINIO}-error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMINIO}-access.log combined
</VirtualHost>
EOF

echo "✓ Escrito: $CONF_PATH"

if command -v a2ensite >/dev/null 2>&1; then
  a2ensite "$CONF_NAME" >/dev/null 2>&1 || true
  echo "✓ a2ensite $CONF_NAME"
fi

# Enlace manual por si a2ensite no está
if [[ -d "$SITES_ENABLED" && ! -e "${SITES_ENABLED}/${CONF_NAME}" ]]; then
  ln -sf "${CONF_PATH}" "${SITES_ENABLED}/${CONF_NAME}"
  echo "✓ Symlink en sites-enabled"
fi

if command -v apache2ctl >/dev/null 2>&1; then
  apache2ctl configtest
  if command -v systemctl >/dev/null 2>&1; then
    systemctl reload apache2
  else
    apache2ctl graceful || service apache2 reload || true
  fi
  echo "✓ Apache recargado"
fi

echo "OK dominio=${DOMINIO} docroot=${DOCROOT}"
exit 0
