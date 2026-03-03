FROM php:8.3-fpm

# Dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    nodejs \
    npm \
    ffmpeg

# Extensões PHP
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copia código (usado apenas em produção)
COPY . .

# Instala dependências PHP
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Permissões padrão
RUN chown -R www-data:www-data storage bootstrap/cache || true

CMD ["php-fpm"]
