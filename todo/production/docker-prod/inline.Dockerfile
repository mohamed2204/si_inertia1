FROM php:8.3-fpm

# Installer les dépendances système et les extensions PHP requises
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev

# Nettoyer le cache pour alléger l'image
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP indispensables
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Récupérer Composer depuis son image officielle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www