FROM wordpress:6.8-php8.2-fpm

# Bake PHP upload/memory limits into the image
COPY nginx/php.ini-uploads.conf /usr/local/etc/php/conf.d/uploads.ini

# Stage custom theme and plugin in a scratch location.
# The custom entrypoint copies them into the volume on every start.
COPY wp-content/themes/buggallery /usr/src/buggallery/themes/buggallery
COPY wp-content/plugins/buggallery-core /usr/src/buggallery/plugins/buggallery-core

# Custom entrypoint that installs theme/plugin then calls WP entrypoint
COPY wordpress/entrypoint-custom.sh /usr/local/bin/entrypoint-custom.sh
ENTRYPOINT ["entrypoint-custom.sh"]
CMD ["php-fpm"]
