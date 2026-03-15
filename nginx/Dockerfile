FROM wordpress:6.8-php8.2-fpm AS wordpress-source

FROM nginx:alpine

COPY nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy WordPress core from the official image so nginx can
# serve static files and resolve try_files for index.php
COPY --from=wordpress-source /usr/src/wordpress /usr/src/wordpress

# Overlay custom theme and plugin
COPY wp-content/themes/buggallery /usr/src/wordpress/wp-content/themes/buggallery
COPY wp-content/plugins/buggallery-core /usr/src/wordpress/wp-content/plugins/buggallery-core
