FROM php:7.4-fpm
RUN docker-php-ext-install pdo pdo_mysql
# content
ADD --chown=www-data:www-data public /var/www/html
