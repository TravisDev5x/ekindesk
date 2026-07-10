# syntax=docker/dockerfile:1

# ---- Stage 1: PHP deps ----
FROM composer:2 AS composer_build
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---- Stage 2: frontend build ----
FROM node:22-alpine AS frontend_build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- Stage 3: runtime ----
FROM php:8.4-fpm AS runtime

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libzip-dev libicu-dev libonig-dev \
        libxml2-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo pdo_pgsql pgsql mbstring bcmath xml dom zip intl gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=composer_build /app/vendor ./vendor
COPY . .
COPY --from=frontend_build /app/public/build ./public/build

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# php:8.4-fpm ships with output_buffering=Off. Some early byte gets flushed
# before Laravel sets its response headers on Inertia partial-reload requests,
# which locks in the default Content-Type/no X-Inertia header for the rest of
# the response. Buffering the whole response defers header commit until send.
RUN echo 'output_buffering = 4096' > /usr/local/etc/php/conf.d/zz-buffering.ini

# Cada deploy recrea el contenedor (opcache arranca vacío de nuevo), así que
# revalidar mtimes en cada request es I/O de más sin beneficio en producción.
RUN echo 'opcache.validate_timestamps=0' > /usr/local/etc/php/conf.d/zz-opcache.ini

# Pool de FPM dimensionado para la VM ARM (2 OCPU / 12GB) en vez del default
# genérico de la imagen (pm.max_children=5) — memory_limit=128M por worker
# deja margen de sobra para más concurrencia.
RUN sed -i \
        -e 's/^pm\.max_children = .*/pm.max_children = 10/' \
        -e 's/^pm\.start_servers = .*/pm.start_servers = 3/' \
        -e 's/^pm\.min_spare_servers = .*/pm.min_spare_servers = 2/' \
        -e 's/^pm\.max_spare_servers = .*/pm.max_spare_servers = 6/' \
        /usr/local/etc/php-fpm.d/www.conf

USER www-data

EXPOSE 9000
CMD ["php-fpm"]

# ---- Stage 4: nginx (assets estáticos horneados en la imagen, sin volumen del host) ----
# La config de nginx (docker/nginx/default.conf) se monta como volumen desde
# docker-compose.prod.yml, no se hornea aquí, para poder cambiarla sin rebuild.
FROM nginx:alpine AS nginx_runtime
COPY --from=frontend_build /app/public /var/www/html/public
