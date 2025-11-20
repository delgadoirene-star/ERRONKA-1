# Dockerfile (en raíz del proyecto)
FROM php:8.1-apache
RUN docker-php-ext-install mysqli pdo_mysql
RUN a2enmod rewrite
COPY . /var/www/html
WORKDIR /var/www/html
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
EXPOSE 80
CMD ["apache2-foreground"]