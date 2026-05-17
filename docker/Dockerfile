FROM php:8.3-fpm

# Instalar dependências para Laravel + PostgreSQL + Vídeos
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    nodejs \
    npm \
    ffmpeg \
    && npm install -g yarn

# Instalar extensões PHP necessárias
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd intl zip

# Instalar extensão Redis
RUN pecl install redis && docker-php-ext-enable redis

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar PHP para uploads de vídeo
RUN echo "upload_max_filesize = 100M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

# Copia o código para dentro da imagem
COPY . /var/www/html

# Cria pastas necessárias e ajusta permissões
RUN mkdir -p /var/www/html/bootstrap/cache \
    /var/www/html/storage/logs \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    && chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
