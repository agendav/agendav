FROM php:8.5-cli AS builder

RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        gnupg \
        libicu-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" intl zip \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && curl -sS https://getcomposer.org/installer | php -- \
        --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . /app

RUN cd web && composer install --no-dev --prefer-dist --no-interaction --no-progress \
 && cd /app && npm install --legacy-peer-deps --no-audit --no-fund \
 && npm run build:templates \
 && npm run build:css \
 && npm run build:js \
 && rm -rf node_modules \
 && rm -rf web/var/log/* web/var/cache/twig/* web/var/cache/profiler/*


FROM php:8.5-apache AS runtime

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libicu-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/app/web/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf

COPY docker/agendav/agendav.conf /etc/apache2/conf-available/agendav.conf
RUN a2enconf agendav

WORKDIR /app/web

COPY --from=builder --chown=www-data:www-data /app /app

# Strip world-read on directories holding secrets (settings.php with the
# csrf secret + DB password) and runtime state (var/session.key, sessions
# DB credentials in cache, log files). Apache runs the workers as
# www-data, which retains rwx via the group.
RUN chmod -R 750 /app/web/var /app/web/config

# Production image: refuse to leak stack traces by accident. docker-compose
# overrides this to 'dev' for the local development stack.
ENV AGENDAV_ENVIRONMENT=prod

EXPOSE 80
