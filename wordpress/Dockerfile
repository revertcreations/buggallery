FROM wordpress:6.8-php8.2-fpm

# Bake PHP upload/memory limits into the image
COPY nginx/php.ini-uploads.conf /usr/local/etc/php/conf.d/uploads.ini

# Bake custom theme and plugin into the image
COPY wp-content/themes/buggallery /var/www/html/wp-content/themes/buggallery
COPY wp-content/plugins/buggallery-core /var/www/html/wp-content/plugins/buggallery-core
