FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# Installer Apache + PHP 8.2 + extensions depuis ubuntu repos
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

# Ubuntu installe un seul MPM proprement — activer rewrite
RUN a2enmod rewrite php8.1

# Copier les fichiers
COPY . /var/www/html/
RUN rm -f /var/www/html/Dockerfile

# Permissions
RUN mkdir -p /var/www/html/uploads/justifications \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# Config Apache : AllowOverride All
RUN sed -i 's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf

# PHP production
RUN echo "display_errors=Off" >> /etc/php/8.1/apache2/php.ini \
    && echo "log_errors=On" >> /etc/php/8.1/apache2/php.ini

# Script runtime pour PORT Railway
RUN printf '#!/bin/bash\nset -e\nAPACHE_PORT=${PORT:-80}\nsed -i "s/Listen 80/Listen $APACHE_PORT/g" /etc/apache2/ports.conf\nsed -i "s/:80/:$APACHE_PORT/g" /etc/apache2/sites-available/000-default.conf\nexec apache2ctl -D FOREGROUND\n' > /start.sh \
    && chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
