FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
        libsqlite3-dev \
        unzip \
        libicu-dev \
    && docker-php-ext-install pdo_sqlite \
    && docker-php-ext-install intl

COPY . /var/www/html/

RUN a2enmod rewrite

RUN chown -R www-data:www-data /var/www/html/database \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]