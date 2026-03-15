FROM nginx:alpine

COPY nginx/default.conf /etc/nginx/conf.d/default.conf

# Static assets for direct serving by nginx
COPY wp-content/themes/buggallery /usr/src/wordpress/wp-content/themes/buggallery
COPY wp-content/plugins/buggallery-core /usr/src/wordpress/wp-content/plugins/buggallery-core
