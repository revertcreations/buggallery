FROM nginx:alpine

# Bake the nginx config into the image to avoid
# file-level bind mount issues on Coolify/Docker
COPY nginx/default.conf /etc/nginx/conf.d/default.conf
