FROM php:8.5-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/app/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

COPY agendav.conf /etc/apache2/conf-available/agendav.conf
RUN a2enconf agendav

RUN mkdir -p /app/var && chown www-data:www-data /app/var

WORKDIR /app
