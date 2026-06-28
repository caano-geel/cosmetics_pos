#!/bin/bash
set -e

export APACHE_PORT="${PORT:-80}"

# Render and other PaaS hosts inject PORT; Apache must listen on it.
envsubst '${APACHE_PORT}' < /etc/apache2/ports.conf.template > /etc/apache2/ports.conf
envsubst '${APACHE_PORT}' < /etc/apache2/sites-available/000-default.conf.template > /etc/apache2/sites-available/000-default.conf

UPLOAD_DIRS=(
    /var/www/html/uploads
    /var/www/html/uploads/avatars
    /var/www/html/uploads/brands
    /var/www/html/uploads/backups
    /var/www/html/uploads/system
)

for dir in "${UPLOAD_DIRS[@]}"; do
    mkdir -p "$dir"
    chown -R www-data:www-data "$dir"
    chmod -R 775 "$dir"
done

exec apache2-foreground
