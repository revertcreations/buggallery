FROM wordpress:6.8-php8.2-fpm

# PHP upload/memory limits
COPY nginx/php.ini-uploads.conf /usr/local/etc/php/conf.d/uploads.ini

# Use /usr/src/wordpress as the document root (static image approach).
# This avoids the named volume on /var/www/html hiding image updates.
WORKDIR /usr/src/wordpress
RUN cp -s wp-config-docker.php wp-config.php

# Install WooCommerce and Stripe Gateway from wordpress.org
# (matches .wp-env.json plugin sources used in local dev)
RUN curl -fSL https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip -o /tmp/woocommerce.zip \
    && unzip -q /tmp/woocommerce.zip -d ./wp-content/plugins/ \
    && rm /tmp/woocommerce.zip \
    && curl -fSL https://downloads.wordpress.org/plugin/woocommerce-gateway-stripe.latest-stable.zip -o /tmp/stripe.zip \
    && unzip -q /tmp/stripe.zip -d ./wp-content/plugins/ \
    && rm /tmp/stripe.zip \
    && chown -R www-data:www-data ./wp-content/plugins/woocommerce ./wp-content/plugins/woocommerce-gateway-stripe

# Custom theme and plugin — baked into the image
COPY --chown=www-data:www-data wp-content/themes/buggallery ./wp-content/themes/buggallery
COPY --chown=www-data:www-data wp-content/plugins/buggallery-core ./wp-content/plugins/buggallery-core

# Must-use plugin for WooCommerce auto-configuration
COPY --chown=www-data:www-data wordpress/mu-plugins/ ./wp-content/mu-plugins/
