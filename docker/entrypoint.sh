#!/bin/sh
set -e

# Pastikan folder database dan file sqlite ada jika menggunakan SQLite
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    mkdir -p /var/www/html/database
    touch /var/www/html/database/database.sqlite
    chown -R www-data:www-data /var/www/html/database
fi

# Buat symbolic link storage
php artisan storage:link || true

# Jalankan cache konfigurasi
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Jalankan migrasi database otomatis saat container start
php artisan migrate --force || true

# Jalankan seeder jika flag RUN_SEEDER diset ke true
if [ "$RUN_SEEDER" = "true" ]; then
    echo "Running database seeder..."
    php artisan db:seed --force || true
fi

# Jalankan supervisord (Nginx + PHP-FPM)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
