FROM wordpress:6.8-php8.2-fpm

# PHP upload/memory limits
COPY nginx/php.ini-uploads.conf /usr/local/etc/php/conf.d/uploads.ini

# Use /usr/src/wordpress as the document root (static image approach).
# This avoids the named volume on /var/www/html hiding image updates.
WORKDIR /usr/src/wordpress
RUN cp -s wp-config-docker.php wp-config.php

# Custom theme and plugin — baked into the image
COPY --chown=www-data:www-data wp-content/themes/buggallery ./wp-content/themes/buggallery
COPY --chown=www-data:www-data wp-content/plugins/buggallery-core ./wp-content/plugins/buggallery-core
