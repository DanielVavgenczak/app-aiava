FROM php:8.5-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    libpq-dev libicu-dev libzip-dev libssl-dev \
    nodejs npm ffmpeg \
    && rm -rf /var/lib/apt/lists/* \
    && npm install -g yarn

RUN docker-php-ext-install -j$(nproc) pdo pdo_pgsql mbstring exif pcntl bcmath gd zip intl
RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN echo "upload_max_filesize = 100M\npost_max_size = 100M\nmemory_limit = 512M\nmax_execution_time = 300" \
    > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html
COPY . /var/www/html

# Evita 500 por permissão no bind mount (local apenas)
RUN chmod -R 777 storage bootstrap/cache

CMD ["php-fpm"]
