#!/bin/bash
set -e

# Configurar el puerto dinámico asignado por Google Cloud Run ($PORT)
PORT="${PORT:-8080}"
sed -i "s/8080/${PORT}/g" /etc/apache2/sites-available/000-default.conf
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/Listen 8080/Listen ${PORT}/g" /etc/apache2/ports.conf

# Asegurar permisos en carpetas de almacenamiento y caché
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Si se indica RUN_MIGRATIONS=true en variables de entorno, ejecutar migraciones
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "[Entrypoint] Ejecutando migraciones de base de datos..."
    php artisan migrate --force --no-interaction || echo "[Entrypoint] Advertencia: Las migraciones no pudieron ejecutarse (verificar conexión a BD)."
fi

# Optimizar Laravel para producción
if [ "${APP_ENV}" = "production" ]; then
    echo "[Entrypoint] Modo producción detectado: cacheando rutas y vistas..."
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "[Entrypoint] Iniciando servidor web en el puerto ${PORT}..."
exec apache2-foreground
