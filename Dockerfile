FROM php:8.1-apache

# Install mysqli, pdo_mysql, and bcmath extensions
RUN docker-php-ext-install mysqli pdo_mysql bcmath

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install unzip and git for Composer
RUN apt-get update && apt-get install -y unzip git && rm -rf /var/lib/apt/lists/*

# Copy project files
COPY . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Install PHP dependencies (if composer.json exists)
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi

# Enable error reporting
RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/php.ini && \
    echo "display_errors = On" >> /usr/local/etc/php/php.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/php.ini

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]