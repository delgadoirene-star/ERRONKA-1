FROM php:8.1-apache

ENV DEBIAN_FRONTEND=noninteractive

# Install system packages, PHP extensions and enable Apache modules in one layer
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
        libzip-dev \
        zip \
        curl \
        git \
        default-mysql-client \
    && docker-php-ext-install mysqli pdo_mysql bcmath \
    && a2enmod rewrite headers ssl socache_shmcb \
    && a2dismod remoteip || true \
    && rm -f /etc/apache2/conf-enabled/remoteip.conf /etc/apache2/conf-available/remoteip.conf || true \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && rm -rf /var/lib/apt/lists/*

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Copy composer files and install dependencies (cache friendly)
COPY composer.json composer.lock* ./
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist; fi

# Copy application code
COPY . /var/www/html

# Ensure wait script is present and executable
RUN if [ -f /var/www/html/tools/wait-for-db.sh ]; then \
        cp /var/www/html/tools/wait-for-db.sh /usr/local/bin/wait-for-db.sh && chmod +x /usr/local/bin/wait-for-db.sh; \
    fi

# Regenerate autoload if composer.json exists
RUN if [ -f composer.json ]; then composer dump-autoload --no-dev --optimize; fi

# PHP configuration tweaks
RUN printf "%s\n" "error_reporting = E_ALL" "display_errors = On" "log_errors = On" > /usr/local/etc/php/conf.d/99-custom.ini

# Ensure correct permissions for web server user
RUN chown -R www-data:www-data /var/www/html

# Add SSL vhost (certs will be mounted at runtime)
COPY ./apache/igai-ssl.conf /etc/apache2/sites-available/igai-ssl.conf
RUN a2ensite igai-ssl

EXPOSE 80 443

CMD ["apache2-foreground"]