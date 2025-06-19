FROM php:8.4-fpm-alpine3.20

WORKDIR "/app"

# Install essential build tools
RUN apk add --no-cache \
    git \
    yarn \
    autoconf \
    g++ \
    make \
    openssl-dev \
    oniguruma-dev \
    libxml2-dev \
    libxslt-dev \
    libzip-dev \
    pcre-dev \
    libstdc++ \
    zlib-dev \
    linux-headers \
    freetype \
    freetype-dev \
    icu-dev

# Optional, force UTC as server time
RUN echo "UTC" > /etc/timezone

# Install composer
ENV COMPOSER_HOME=/composer
RUN curl -s https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN set -eux; \
	composer clear-cache
ENV PATH="${PATH}:/composer/vendor/bin/:/root/.composer/vendor/bin"

# install php extensions
RUN docker-php-ext-install \
    bcmath \
    mbstring \
    xml \
    xsl \
    zip \
    gd \
    intl \
    pdo \
    mysqli \
    pdo_mysql \
    soap

RUN pecl install opcache apcu \
    && docker-php-ext-enable opcache apcu

RUN docker-php-ext-configure pcntl --enable-pcntl \
  && docker-php-ext-install pcntl;

RUN pecl install redis && docker-php-ext-enable redis

RUN mkdir -p /composer \
    && chown -R 1000:1000 /composer

## install xdebug
RUN pecl install xdebug-3.4.2 \
 && docker-php-ext-enable xdebug

# clean up
RUN rm -rf /tmp/* /var/cache/apk/*

USER 1000
VOLUME /app
