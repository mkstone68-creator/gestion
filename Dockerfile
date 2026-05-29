FROM php:8.2-apache

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql gd zip

# -------------------------------------------------------
# FIX MPM : supprimer TOUS les modules MPM chargés
# puis forcer uniquement mpm_prefork
# -------------------------------------------------------
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load \
          /etc/apache2/mods-enabled/mpm_*.conf \
    && ln -s /etc/apache2/mods-available/mpm_prefork.load \
             /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -s /etc/apache2/mods-available/mpm_prefork.conf \
             /etc/apache2/mods-enabled/mpm_prefork.conf \
    && a2enmod rewrite

# Copier les fichiers du projet
COPY . /var/www/html/

# Permissions uploads
RUN mkdir -p /var/www/html/uploads/justifications \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# Script de démarrage : substitution PORT au runtime
RUN printf '#!/bin/bash\n\
APACHE_PORT=${PORT:-80}\n\
sed -i "s/Listen 80/Listen $APACHE_PORT/g" /etc/apache2/ports.conf\n\
sed -i "s/:80/:$APACHE_PORT/g" /etc/apache2/sites-available/000-default.conf\n\
exec apache2-foreground\n' > /start.sh \
    && chmod +x /start.sh

# PHP production
RUN echo "display_errors=Off" > /usr/local/etc/php/conf.d/production.ini \
    && echo "log_errors=On" >> /usr/local/etc/php/conf.d/production.ini

CMD ["/start.sh"]
