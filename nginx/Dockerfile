FROM wordpress:6.8-php8.2-fpm AS wordpress-source

FROM nginx:alpine

COPY nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy full WordPress core (including WP-bundled plugins/themes)
# from the official image so nginx can serve static files
COPY --from=wordpress-source /usr/src/wordpress /usr/src/wordpress

# WooCommerce and Stripe Gateway static assets
# (downloaded at build time to match the wordpress Dockerfile)
RUN apk add --no-cache curl unzip \
    && curl -fSL https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip -o /tmp/woocommerce.zip \
    && unzip -q /tmp/woocommerce.zip -d /usr/src/wordpress/wp-content/plugins/ \
    && rm /tmp/woocommerce.zip \
    && curl -fSL https://downloads.wordpress.org/plugin/woocommerce-gateway-stripe.latest-stable.zip -o /tmp/stripe.zip \
    && unzip -q /tmp/stripe.zip -d /usr/src/wordpress/wp-content/plugins/ \
    && rm /tmp/stripe.zip \
    && apk del curl unzip

# Overlay custom theme and plugin
COPY wp-content/themes/buggallery /usr/src/wordpress/wp-content/themes/buggallery
COPY wp-content/plugins/buggallery-core /usr/src/wordpress/wp-content/plugins/buggallery-core
