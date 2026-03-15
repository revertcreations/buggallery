#!/bin/bash
set -e

# Force-copy our custom theme and plugin into the WordPress volume
# on every container start. This ensures updates from the Docker
# image always take effect, even when wp_core volume persists.
echo "Installing BugGallery theme and plugin..."
cp -rf /usr/src/buggallery/themes/buggallery /var/www/html/wp-content/themes/buggallery
cp -rf /usr/src/buggallery/plugins/buggallery-core /var/www/html/wp-content/plugins/buggallery-core
chown -R www-data:www-data /var/www/html/wp-content/themes/buggallery
chown -R www-data:www-data /var/www/html/wp-content/plugins/buggallery-core
echo "BugGallery theme and plugin installed."

# Hand off to the official WordPress entrypoint
exec docker-entrypoint.sh "$@"
