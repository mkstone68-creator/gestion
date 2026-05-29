FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# Installer Apache + PHP + extensions
RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    libapache2-mod-php8.1 \
    php8.1-mysql \
    php8.1-gd \
    php8.1-zip \
    php8.1-mbstring \
    php8.1-xml \
    && rm -rf /var/lib/apt/lists/*

# Activer rewrite
RUN a2enmod rewrite php8.1

# Supprimer la page par défaut d'Ubuntu
RUN rm -f /var/www/html/index.html

# Copier les fichiers du projet
COPY . /var/www/html/
RUN rm -f /var/www/html/Dockerfile

# Permissions uploads
RUN mkdir -p /var/www/html/uploads/justifications \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads

# Config Apache : AllowOverride All + index.php en priorité
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf && \
    sed -i 's/DirectoryIndex index.html/DirectoryIndex index.php index.html/g' \
        /etc/apache2/mods-enabled/dir.conf 2>/dev/null || \
    echo "DirectoryIndex index.php index.html index.cgi index.pl index.xhtml index.htm" \
        > /etc/apache2/mods-enabled/dir.conf

# PHP production
RUN echo "display_errors=Off" >> /etc/php/8.1/apache2/php.ini \
    && echo "log_errors=On" >> /etc/php/8.1/apache2/php.ini

# Script runtime pour PORT Railway
RUN printf '#!/bin/bash\nset -e\nAPACHE_PORT=${PORT:-80}\nsed -i "s/Listen 80/Listen $APACHE_PORT/g" /etc/apache2/ports.conf\nsed -i "s/:80/:$APACHE_PORT/g" /etc/apache2/sites-available/000-default.conf\nexec apache2ctl -D FOREGROUND\n' > /start.sh \
    && chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
