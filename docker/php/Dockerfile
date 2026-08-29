FROM php:8.3-fpm-alpine

RUN apk add --no-cache libpng libjpeg-turbo freetype git unzip \
    && apk add --no-cache --virtual .build-deps libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY entrypoint.sh /usr/local/bin/blog-entrypoint
RUN chmod +x /usr/local/bin/blog-entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["blog-entrypoint"]
CMD ["php-fpm"]
