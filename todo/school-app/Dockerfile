FROM php:8.2-fpm-alpine

# Installation des dépendances système nécessaires
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    icu-dev \
    oniguruma-dev \
    libpng-dev

# Installation des extensions PHP pour Laravel et PostgreSQL
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pdo_mysql \
    zip \
    intl \
    bcmath \
    gd \
    mbstring

# Installation de Composer depuis l'image officielle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# On ne fait plus de COPY . . ni de chown ici pour éviter les conflits de droits.
# Les permissions seront gérées dynamiquement par ton utilisateur hôte (ID 1000).

EXPOSE 9000
CMD ["php-fpm"]

# Définition du répertoire de travail dans le conteneur
#WORKDIR /var/www/html
#
## Copie des fichiers de l'application
#COPY . .
#
## Ajustement des permissions pour le serveur web (www-data)
#RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
#
## Passage à l'utilisateur www-data pour plus de sécurité
#USER www-data
#
#EXPOSE 9000
#
#CMD ["php-fpm"]