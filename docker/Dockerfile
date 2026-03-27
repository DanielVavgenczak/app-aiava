FROM php:8.3-fpm

# Instalar dependências para Laravel 12 + PostgreSQL + Vídeos
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
    ffmpeg \
    && npm install -g yarn

# Instalar extensões PHP necessárias
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar PHP para uploads de vídeo
RUN echo "upload_max_filesize = 100M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

# 👇 REMOVER AS LINhas PROBLEMÁTICAS - vamos fazer isso depois que o código estiver copiado
# Configurar permissões para Laravel (vamos fazer depois via comando)

CMD ["php-fpm"]
