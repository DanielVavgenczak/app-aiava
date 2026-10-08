FROM php:8.5-fpm

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    git curl libpq-dev libpng-dev libonig-dev libxml2-dev libicu-dev nodejs npm ffmpeg \
    supervisor \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd intl \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN printf '%s\n%s\n' 'upload_max_filesize = 100M' 'post_max_size = 100M' > /usr/local/etc/php/conf.d/uploads.ini

COPY docker/supervisor.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

COPY composer.json composer.lock package.json package-lock.json .npmrc ./
RUN composer install --no-dev --no-interaction --no-scripts --optimize-autoloader \
    && npm ci

COPY . /var/www/html

RUN composer dump-autoload --no-dev --optimize --no-scripts \
    && php artisan package:discover --ansi \
    && npm run build \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
