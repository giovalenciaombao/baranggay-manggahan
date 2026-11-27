FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev libpq-dev unzip \
    && docker-php-ext-install mbstring pdo_mysql pdo_pgsql opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PORT=10000

RUN a2enmod rewrite \
    && sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/; s|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel \
    && printf 'upload_max_filesize=6M\npost_max_size=8M\n' > "$PHP_INI_DIR/conf.d/uploads.ini"

COPY . .
RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private storage/app/public \
    && composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && chown -R www-data:www-data bootstrap/cache storage

EXPOSE 10000
CMD ["sh", "./start-render.sh"]
