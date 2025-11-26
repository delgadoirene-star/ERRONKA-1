FROM php:8.1-apache

# Instalar dependencias de sistema necesarias para extensiones y para composer
RUN apt-get update && apt-get install -y \
        libzip-dev \
        zip \
        curl \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-install mysqli pdo_mysql bcmath

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Silence ServerName warning
RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar solo archivos necesarios para composer primero (optimiza caché)
COPY composer.json composer.lock* ./
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi

# Copiar el resto del código (excluyendo vendor por .dockerignore)
COPY . /var/www/html

# Forzar regeneración del autoload si hay problemas
RUN if [ -f composer.json ]; then composer dump-autoload --no-dev; fi

# Configuración de errores PHP
RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/php.ini && \
    echo "display_errors = On" >> /usr/local/etc/php/php.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/php.ini

# Permisos
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]