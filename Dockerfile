# ==============================================================
# Etapa 1: Instalación de dependencias de Composer para producción
# ==============================================================
FROM composer:2.8 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# ==============================================================
# Etapa 2: Imagen de producción basada en PHP 8.2 y Apache
# ==============================================================
FROM php:8.2-apache-bookworm

# Instalar extensiones mediante el instalador oficial de extensiones PHP para Docker (rápido y compatible sin BuildKit)
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/install-php-extensions

RUN chmod +x /usr/local/bin/install-php-extensions \
    && apt-get update && apt-get install -y --no-install-recommends unzip \
    && install-php-extensions pdo_pgsql pgsql bcmath zip opcache \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copiar configuraciones de servidor web y PHP
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

# Copiar el código del proyecto
COPY --chown=www-data:www-data . /var/www/html

# Copiar dependencias de Composer generadas en la primera etapa
COPY --from=vendor --chown=www-data:www-data /app/vendor /var/www/html/vendor

# Copiar y dar permisos al script de entrada (Entrypoint)
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Permisos de carpetas de almacenamiento y caché de Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Puerto asignado dinámicamente por Cloud Run
EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
