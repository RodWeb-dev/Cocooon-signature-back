FROM php:8.4-apache

# Extensions PHP nécessaires
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli

# Activer le module Apache rewrite (pour les routes REST)
RUN a2enmod rewrite

RUN apt-get update && apt-get install -y zip unzip libzip-dev \
    && docker-php-ext-install zip

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier les dépendances et installer
COPY ./src/composer.json ./src/composer.lock /var/www/html/
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist

# Copier la config Apache personnalisée
COPY ./config/apache.conf /etc/apache2/sites-available/000-default.conf
