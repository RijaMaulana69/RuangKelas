# ==========================================
# Tahap 1: Build Assets Frontend (Vite)
# ==========================================
FROM node:20-alpine AS frontend
WORKDIR /app

COPY package*.json ./
RUN npm install

COPY vite.config.js ./
COPY resources/ ./resources/
COPY public/ ./public/

RUN npm run build

# ==========================================
# Tahap 2: Runtime PHP 8.3 + Nginx
# ==========================================
FROM php:8.3-fpm-alpine

# Install sistem dependensi dan library pendukung
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    sqlite-dev

# Install ekstensi PHP yang dibutuhkan Laravel
RUN docker-php-ext-install pdo pdo_sqlite pdo_mysql mbstring exif pcntl bcmath gd zip

# Ambil Composer resmi
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set direktori kerja
WORKDIR /var/www/html

# Salin seluruh kode aplikasi
COPY . /var/www/html

# Salin hasil kompilasi Vite dari tahap 1
COPY --from=frontend /app/public/build /var/www/html/public/build

# Install dependensi PHP via Composer (optimasi untuk production)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Salin konfigurasi Nginx, Supervisord, dan Entrypoint
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Normalisasi line-ending untuk entrypoint script (mencegah error Windows CRLF) dan beri izin eksekusi
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

# Berikan izin ke www-data untuk folder storage dan cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Port yang dibuka
EXPOSE 80

# Jalankan entrypoint saat container mulai
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
