FROM php:{DOCKER_PHP_VERSION}-fpm
MAINTAINER Travis Tomka <ttomka@isovera.com>

# Install PHP extensions.
RUN apt-get update && apt-get install -y libpng12-dev libjpeg-dev libwebp-dev libpq-dev libmcrypt-dev libmagickwand-dev \
  && rm -rf /var/lib/apt/lists/* \
  && pecl install redis \
  && pecl install xdebug \
  && pecl install imagick-3.4.3 \
  && docker-php-ext-enable imagick xdebug redis \
  && docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr  --with-webp-dir=/usr \
  && docker-php-ext-install gd mbstring opcache pdo pdo_mysql pdo_pgsql zip mcrypt

# Install PHP composer.
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Run cleanup tasks on environment.
RUN apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

