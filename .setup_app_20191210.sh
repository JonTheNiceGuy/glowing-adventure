#! /bin/bash
# Configure Apache to serve from "localhost", to point to the right app path, and to log everything to stdout
# Based in-part on
# https://github.com/t3kit/ubuntu18.04-php7.2-apache/blob/54179f346aeb77d210d6397cc15fd4541ad074b5/Dockerfile
# and https://serverfault.com/a/711172 and
# https://github.com/docker-library/docs/blob/c869373/php/README.md#changing-documentroot-or-other-apache-configuration
apt-get update
apt-get install -y apache2 \
                   libapache2-mod-fcgid \
                   php-fpm \
                   curl \
                   ca-certificates \
                   nodejs \
                   npm \
                   php-gd \
                   php-mysql \
                   php-sqlite3 \
                   php-mbstring \
                   php-zip \
                   php-exif \
                   php-xml \
                   composer

echo "ServerName localhost" | tee /etc/apache2/conf-available/servername.conf
a2enconf servername

a2enmod rewrite
a2enmod proxy_fcgi setenvif
a2enconf php7.2-fpm
systemctl start php7.2-fpm

systemctl stop apache2
sed -ri -e "s!/var/www/html!/app/public!g" /etc/apache2/sites-available/*.conf
sed -ri -e "s!/var/www/!/app/public!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
systemctl start apache2

chown -R www-data:www-data /var/www
