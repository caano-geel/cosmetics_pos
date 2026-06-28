#!/bin/bash
set -e

export APACHE_PORT="${PORT:-80}"

# Render: activate .env.render as runtime .env (if .env not already present).
if [ -f /var/www/html/.env.render ] && [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.render /var/www/html/.env
    chown www-data:www-data /var/www/html/.env
    chmod 640 /var/www/html/.env
fi

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
