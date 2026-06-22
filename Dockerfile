FROM php:8.4-apache

RUN apt-get update && apt-get install -y libicu-dev libgd-dev libzip-dev zip unzip \
    # Extensions PHP nécessaires
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    intl \
    zip \
    gd \
    # Activer le module Apache rewrite (pour les routes REST)
    && a2enmod rewrite

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier les dépendances et installer
COPY ./src/composer.json ./src/composer.lock /var/www/html/
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist

# Copier le dossier src
COPY ./src /var/www/html

# Copier la config Apache personnalisée
COPY ./config/apache.conf /etc/apache2/sites-available/000-default.conf
