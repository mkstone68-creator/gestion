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

# Installer les extensions PHP requises (mysqli est la clé ici)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql gd zip

# Activer mod_rewrite (utile pour les URL propres)
RUN a2enmod rewrite

# Copier les fichiers du projet
COPY . /var/www/html/

# Donner les bonnes permissions au dossier uploads
RUN mkdir -p /var/www/html/uploads/justifications \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# Railway fournit un PORT dynamique via variable d'environnement.
# Apache doit écouter sur ce port (pas 80).
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf \
    && sed -i 's/:80/:${PORT}/g' /etc/apache2/sites-available/000-default.conf

# Désactiver l'affichage des erreurs en production (sécurité)
RUN echo "display_errors=Off" > /usr/local/etc/php/conf.d/production.ini \
    && echo "log_errors=On" >> /usr/local/etc/php/conf.d/production.ini

# Railway lira PORT depuis l'environnement
CMD ["apache2-foreground"]
