FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        gettext-base \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" mysqli pdo pdo_mysql gd \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY docker/apache/ports.conf /etc/apache2/ports.conf.template
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf.template

COPY . /var/www/html/

RUN sed -i 's/\r$//' /var/www/html/docker/entrypoint.sh \
    && mkdir -p uploads/avatars uploads/brands uploads/backups uploads/system certs \
    && chown -R www-data:www-data uploads \
    && chmod -R 775 uploads \
    && chmod +x /var/www/html/docker/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
