FROM php:8.2-apache

# Installer les dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP requises
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql gd zip

# Désactiver les MPM en conflit, puis activer uniquement mpm_prefork
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# Copier les fichiers du projet
COPY . /var/www/html/

# Donner les bonnes permissions au dossier uploads
RUN mkdir -p /var/www/html/uploads/justifications \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# Script de démarrage pour gérer le PORT dynamique de Railway
RUN echo '#!/bin/bash\n\
export APACHE_PORT=${PORT:-80}\n\
sed -i "s/Listen 80/Listen $APACHE_PORT/g" /etc/apache2/ports.conf\n\
sed -i "s/:80/:$APACHE_PORT/g" /etc/apache2/sites-available/000-default.conf\n\
exec apache2-foreground' > /start.sh \
    && chmod +x /start.sh

# Désactiver l'affichage des erreurs en production
RUN echo "display_errors=Off" > /usr/local/etc/php/conf.d/production.ini \
    && echo "log_errors=On" >> /usr/local/etc/php/conf.d/production.ini

CMD ["/start.sh"]
