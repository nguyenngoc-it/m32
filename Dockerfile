FROM gobizdotvn/php-fpm:7.4.1
COPY --chown=www-data:www-data . /var/www/html