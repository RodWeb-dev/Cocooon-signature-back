FROM php:8.3-apache

# Extensions PHP nécessaires
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli

# Activer le module Apache rewrite (pour les routes REST)
RUN a2enmod rewrite

# Copier la config Apache personnalisée
COPY ./config/apache.conf /etc/apache2/sites-available/000-default.conf
