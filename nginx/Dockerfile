FROM nginx:alpine

# Bake the nginx config into the image to avoid
# file-level bind mount issues on Coolify/Docker
COPY nginx/default.conf /etc/nginx/conf.d/default.conf

# Bake custom theme and plugin for static asset serving
COPY wp-content/themes/buggallery /var/www/html/wp-content/themes/buggallery
COPY wp-content/plugins/buggallery-core /var/www/html/wp-content/plugins/buggallery-core
