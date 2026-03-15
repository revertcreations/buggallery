#!/usr/bin/env bash
set -euo pipefail

UPLOAD_DIR="${WP_UPLOADS_DIR:-/usr/src/wordpress/wp-content/uploads}"

# Ensure uploads directory exists before adjusting permissions
mkdir -p "${UPLOAD_DIR}"

if [ "$(id -u)" -eq 0 ]; then
  chown -R www-data:www-data "${UPLOAD_DIR}"
  # Keep the directory setgid so new folders inherit the same group
  chmod g+rwXs "${UPLOAD_DIR}"
else
  echo "Warning: uploads dir ownership remains unchanged (not running as root)" >&2
fi

exec /usr/local/bin/docker-entrypoint.sh "$@"
