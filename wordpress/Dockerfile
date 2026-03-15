FROM wordpress:6.8-php8.2-fpm

# Bake PHP upload/memory limits into the image
COPY nginx/php.ini-uploads.conf /usr/local/etc/php/conf.d/uploads.ini
