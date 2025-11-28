FROM php:8.1-apache

ENV DEBIAN_FRONTEND=noninteractive

# Install system packages and PHP extensions
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
        libzip-dev \
        zip \
        unzip \
        curl \
        git \
        default-mysql-client \
    && docker-php-ext-install mysqli pdo_mysql bcmath zip \
    && a2enmod rewrite headers \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy Apache security configuration
COPY apache-security.conf /etc/apache2/conf-available/zabala-security.conf
RUN a2enconf zabala-security

WORKDIR /var/www/html

# Copy composer files and install dependencies
COPY composer.json composer.lock* ./
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist; fi

# Copy application code
COPY . /var/www/html

# Ensure wait script is executable
RUN if [ -f /var/www/html/tools/wait-for-db.sh ]; then \
        cp /var/www/html/tools/wait-for-db.sh /usr/local/bin/wait-for-db.sh && chmod +x /usr/local/bin/wait-for-db.sh; \
    fi

# Regenerate autoload
RUN if [ -f composer.json ]; then composer dump-autoload --no-dev --optimize; fi

# PHP config
RUN printf "%s\n" "error_reporting = E_ALL" "display_displays = On" "log_errors = On" > /usr/local/etc/php/conf.d/99-custom.ini

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]