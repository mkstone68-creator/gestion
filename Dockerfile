FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

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

RUN a2enmod rewrite php8.1

# Supprimer la page par défaut Ubuntu
RUN rm -f /var/www/html/index.html

# Copier les fichiers du projet
COPY . /var/www/html/
RUN rm -f /var/www/html/Dockerfile

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/uploads/justifications \
    && chmod -R 775 /var/www/html/uploads

# Config Apache
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# index.php en priorité
RUN echo '<IfModule mod_dir.c>\n    DirectoryIndex index.php index.html\n</IfModule>' \
    > /etc/apache2/mods-enabled/dir.conf

# Supprimer le ServerName warning + activer les erreurs PHP pour debug
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN echo "display_errors=On" > /etc/php/8.1/apache2/conf.d/99-debug.ini \
    && echo "error_reporting=E_ALL" >> /etc/php/8.1/apache2/conf.d/99-debug.ini \
    && echo "log_errors=On" >> /etc/php/8.1/apache2/conf.d/99-debug.ini \
    && echo "error_log=/var/log/apache2/php_errors.log" >> /etc/php/8.1/apache2/conf.d/99-debug.ini

# Rediriger les logs Apache vers stdout/stderr pour Railway
RUN ln -sf /proc/1/fd/1 /var/log/apache2/access.log \
    && ln -sf /proc/1/fd/2 /var/log/apache2/error.log \
    && ln -sf /proc/1/fd/2 /var/log/apache2/php_errors.log

# Script runtime PORT Railway
RUN printf '#!/bin/bash\nset -e\nAPACHE_PORT=${PORT:-80}\nsed -i "s/Listen 80/Listen $APACHE_PORT/g" /etc/apache2/ports.conf\nsed -i "s/:80/:$APACHE_PORT/g" /etc/apache2/sites-available/000-default.conf\nexec apache2ctl -D FOREGROUND\n' > /start.sh \
    && chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
